<?php

namespace App\Http\Controllers;

use App\Mail\EmployeeLetterMail;
use App\Models\Employee;
use App\Models\EmployeeLetter;
use App\Models\LetterFormat;
use App\Models\Utility;
use App\Support\TenantHost;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeLetterController extends Controller
{
    protected function assertPortal(): void
    {
        if (!TenantHost::isJeminiMainPortal()) {
            abort(404);
        }
    }

    protected function canManage(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->can('Manage Employee')
            || in_array($user->type, ['company', 'hr', 'super admin'], true);
    }

    protected function ensureManage(): void
    {
        $this->assertPortal();
        if (!$this->canManage()) {
            abort(403, __('Permission denied.'));
        }
    }

    public function index(Request $request)
    {
        $this->ensureManage();
        $creatorId = Auth::user()->creatorId();
        $type = $request->input('type', 'all');
        $q = trim((string) $request->input('q', ''));

        $query = EmployeeLetter::where('created_by', $creatorId)
            ->with(['employee', 'issuer'])
            ->orderByDesc('id');

        if ($type !== 'all' && isset(LetterFormat::TYPES[$type])) {
            $query->where('type', $type);
        }
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('recipient_name', 'like', '%' . $q . '%')
                    ->orWhere('recipient_email', 'like', '%' . $q . '%')
                    ->orWhere('subject', 'like', '%' . $q . '%');
            });
        }

        $letters = $query->paginate(20)->withQueryString();
        $totals = [
            'all' => EmployeeLetter::where('created_by', $creatorId)->count(),
            'emailed' => EmployeeLetter::where('created_by', $creatorId)->whereNotNull('emailed_at')->count(),
        ];

        return view('employee_letters.index', compact('letters', 'type', 'q', 'totals'));
    }

    public function formats()
    {
        $this->ensureManage();
        LetterFormat::ensureDefaults(Auth::user()->creatorId());
        $order = array_keys(LetterFormat::TYPES);
        $formats = LetterFormat::where('created_by', Auth::user()->creatorId())
            ->get()
            ->sortBy(fn ($f) => array_search($f->type, $order, true))
            ->keyBy('type');

        return view('employee_letters.formats', compact('formats'));
    }

    public function editFormat(string $type)
    {
        $this->ensureManage();
        if (!isset(LetterFormat::TYPES[$type])) {
            abort(404);
        }
        $format = LetterFormat::forCompany(Auth::user()->creatorId(), $type);

        return view('employee_letters.format_edit', compact('format'));
    }

    public function updateFormat(Request $request, string $type)
    {
        $this->ensureManage();
        if (!isset(LetterFormat::TYPES[$type])) {
            abort(404);
        }

        $data = $request->validate([
            'content' => 'required|string',
            'format_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $format = LetterFormat::forCompany(Auth::user()->creatorId(), $type);
        $format->content = $data['content'];

        if ($request->hasFile('format_file')) {
            if ($format->file_path && Storage::disk('public')->exists($format->file_path)) {
                Storage::disk('public')->delete($format->file_path);
            }
            $file = $request->file('format_file');
            $name = $type . '-' . time() . '.' . $file->getClientOriginalExtension();
            $format->file_path = $file->storeAs('letter-formats/' . Auth::user()->creatorId(), $name, 'public');
            $format->file_name = $file->getClientOriginalName();
        }

        $format->save();

        return redirect()->route('employee-letters.formats')
            ->with('success', __('Letter format saved. You can now issue this letter from Issue Letters.'));
    }

    public function create(Request $request)
    {
        $this->ensureManage();
        LetterFormat::ensureDefaults(Auth::user()->creatorId());
        $type = $request->input('type', LetterFormat::TYPE_OFFER);
        if (!isset(LetterFormat::TYPES[$type])) {
            $type = LetterFormat::TYPE_OFFER;
        }
        $employees = Employee::where('created_by', Auth::user()->creatorId())->orderBy('name')->get();
        $format = LetterFormat::forCompany(Auth::user()->creatorId(), $type);

        return view('employee_letters.create', compact('type', 'employees', 'format'));
    }

    public function store(Request $request)
    {
        $this->ensureManage();
        $data = $request->validate([
            'type' => 'required|in:offer,appointment,confirmation,increment',
            'employee_id' => 'nullable|integer',
            'recipient_name' => 'nullable|string|max:190',
            'recipient_email' => 'nullable|email|max:190',
            'salary' => 'nullable|string|max:80',
            'new_salary' => 'nullable|string|max:80',
            'increment_amount' => 'nullable|string|max:80',
            'increment_percent' => 'nullable|string|max:40',
            'effective_date' => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'joining_date' => 'nullable|date',
            'job_title' => 'nullable|string|max:120',
            'offer_expiry' => 'nullable|date',
            'send_email' => 'nullable|boolean',
        ]);

        $creatorId = Auth::user()->creatorId();
        $employee = null;
        if (!empty($data['employee_id'])) {
            $employee = Employee::where('created_by', $creatorId)->find($data['employee_id']);
        }

        $name = $employee?->name ?: ($data['recipient_name'] ?? '');
        $email = $employee?->email ?: ($data['recipient_email'] ?? '');
        if ($name === '') {
            return back()->withInput()->with('error', __('Select an employee or enter the recipient name.'));
        }
        if ($request->boolean('send_email') && $email === '') {
            return back()->withInput()->with('error', __('Recipient email is required to send the letter.'));
        }

        $format = LetterFormat::forCompany($creatorId, $data['type']);
        $vars = $this->placeholderValues($employee, $data);
        $body = EmployeeLetter::replacePlaceholders($format->content ?: LetterFormat::defaultContent($data['type']), $vars);
        $subject = LetterFormat::TYPES[$data['type']] . ' — ' . $name;

        $letter = EmployeeLetter::create([
            'letter_format_id' => $format->id,
            'type' => $data['type'],
            'employee_id' => $employee?->id,
            'recipient_name' => $name,
            'recipient_email' => $email ?: null,
            'subject' => $subject,
            'body_html' => $body,
            'extra_fields' => $vars,
            'status' => 'issued',
            'issued_at' => now(),
            'issued_by' => Auth::id(),
            'created_by' => $creatorId,
        ]);

        $this->writePdf($letter);

        $emailed = false;
        if ($request->boolean('send_email') && $email) {
            $emailed = $this->sendLetterEmail($letter);
        }

        $msg = __('Letter issued and published on the portal.');
        if ($emailed) {
            $msg = __('Letter issued, saved on the portal, and emailed to :email.', ['email' => $email]);
        } elseif ($request->boolean('send_email')) {
            $msg .= ' ' . __('Email could not be sent. You can retry from the letter page.');
        }

        return redirect()->route('employee-letters.show', $letter->id)->with('success', $msg);
    }

    public function show(int $id)
    {
        $this->assertPortal();
        $letter = $this->findVisibleLetter($id);

        return view('employee_letters.show', compact('letter'));
    }

    public function pdf(int $id)
    {
        $this->assertPortal();
        $letter = $this->findVisibleLetter($id);
        if (!$letter->pdf_path || !Storage::disk('public')->exists($letter->pdf_path)) {
            $this->writePdf($letter);
        }

        return Storage::disk('public')->download(
            $letter->pdf_path,
            Str::slug($letter->typeLabel() . '-' . $letter->recipient_name) . '.pdf'
        );
    }

    public function email(int $id)
    {
        $this->ensureManage();
        $letter = EmployeeLetter::where('created_by', Auth::user()->creatorId())->findOrFail($id);
        if (!$letter->recipient_email) {
            return back()->with('error', __('This letter has no recipient email.'));
        }
        if ($this->sendLetterEmail($letter)) {
            return back()->with('success', __('Letter emailed to :email.', ['email' => $letter->recipient_email]));
        }

        return back()->with('error', __('Email could not be sent. Check SMTP settings.'));
    }

    public function mine()
    {
        $this->assertPortal();
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        $letters = collect();
        if ($employee) {
            $letters = EmployeeLetter::where('created_by', $user->creatorId())
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->paginate(20);
        }

        return view('employee_letters.mine', compact('letters'));
    }

    protected function findVisibleLetter(int $id): EmployeeLetter
    {
        $user = Auth::user();
        $letter = EmployeeLetter::where('created_by', $user->creatorId())->findOrFail($id);
        if ($this->canManage()) {
            return $letter;
        }
        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee && (int) $letter->employee_id === (int) $employee->id) {
            return $letter;
        }
        abort(403);
    }

    protected function placeholderValues(?Employee $employee, array $data): array
    {
        $settings = Utility::settings();
        $company = $settings['company_name'] ?? (Auth::user()->name ?? config('app.name'));
        $emp = $employee;

        return [
            'employee_name' => $emp?->name ?: ($data['recipient_name'] ?? ''),
            'employee_code' => $emp?->employee_id ?: '—',
            'designation' => $emp?->designation?->name ?: ($data['job_title'] ?? '—'),
            'department' => $emp?->department?->name ?: '—',
            'branch' => $emp?->branch?->name ?: '—',
            'company_name' => $company,
            'date' => now()->format('d M Y'),
            'joining_date' => !empty($data['joining_date'])
                ? date('d M Y', strtotime($data['joining_date']))
                : ($emp?->company_doj ? date('d M Y', strtotime($emp->company_doj)) : '—'),
            'address' => $emp?->address ?: ($emp?->present_address ?: '—'),
            'email' => $emp?->email ?: ($data['recipient_email'] ?? ''),
            'salary' => $data['salary'] ?? ($emp?->salary ?: '—'),
            'new_salary' => $data['new_salary'] ?? '—',
            'increment_amount' => $data['increment_amount'] ?? '—',
            'increment_percent' => $data['increment_percent'] ?? '—',
            'effective_date' => !empty($data['effective_date']) ? date('d M Y', strtotime($data['effective_date'])) : '—',
            'confirmation_date' => !empty($data['confirmation_date']) ? date('d M Y', strtotime($data['confirmation_date'])) : '—',
            'job_title' => $data['job_title'] ?? ($emp?->designation?->name ?: '—'),
            'offer_expiry' => !empty($data['offer_expiry']) ? date('d M Y', strtotime($data['offer_expiry'])) : '—',
        ];
    }

    protected function writePdf(EmployeeLetter $letter): void
    {
        $settings = Utility::settings();
        $companyName = $settings['company_name'] ?? config('app.name');
        $pdf = Pdf::loadView('employee_letters.pdf', [
            'letter' => $letter,
            'companyName' => $companyName,
        ]);
        $path = 'employee-letters/' . $letter->id . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $letter->pdf_path = $path;
        $letter->save();
    }

    protected function sendLetterEmail(EmployeeLetter $letter): bool
    {
        if (!$letter->recipient_email) {
            return false;
        }
        if (!$letter->pdf_path || !Storage::disk('public')->exists($letter->pdf_path)) {
            $this->writePdf($letter);
        }

        $companyId = (int) Auth::user()->creatorId();
        $settings = Utility::settingsByUser($companyId);
        Utility::applySmtpConfig($settings);

        try {
            Mail::to($letter->recipient_email)->send(new EmployeeLetterMail(
                $letter,
                $settings,
                Storage::disk('public')->path($letter->pdf_path),
                Str::slug($letter->typeLabel()) . '.pdf'
            ));
            $letter->emailed_at = now();
            $letter->status = 'emailed';
            $letter->save();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
