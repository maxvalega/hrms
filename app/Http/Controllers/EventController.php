<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Event as LocalEvent;
use App\Models\EventEmployee;
use App\Models\Projects;
use App\Models\Tasks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Utility;
use App\Imports\EventImport;
use App\Exports\EventExport;
use App\Models\AttendanceEmployee;
use App\Models\AttendanceModificationRequest;
use App\Models\Holiday;
use App\Models\Webhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\GoogleCalendar\Event as GoogleEvent;

use function App\Models\WebhookCall;

class EventController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('Manage Event')) {

            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get();

            $events    = LocalEvent::where('created_by', '=', \Auth::user()->creatorId())->get();

            $today_date = date('m');
            // $current_month_event = Event::select('id','start_date','end_date', 'title', 'created_at','color')->whereRaw('MONTH(start_date)=' . $today_date,'MONTH(end_date)=' . $today_date)->get();
            $current_month_event = LocalEvent::where('created_by', \Auth::user()->creatorId())->select('id', 'start_date', 'end_date', 'title', 'created_at', 'color')->whereNotNull(['start_date', 'end_date'])->whereMonth('start_date', $today_date)->whereMonth('end_date', $today_date)->get();
            $arrEvents = [];
            foreach ($events as $event) {

                $arr['id']    = $event['id'];
                $arr['title'] = $event['title'];
                $arr['start'] = $event['start_date'];
                $arr['end']   = $event['end_date'];
                // $arr['allDay']    = !0;
                // $arr['className'] = 'bg-danger';
                $arr['className'] = $event['color'];
                // $arr['borderColor']     = "#fff";
                // $arr['textColor']       = "white";
                $arr['url']             = route('event.edit', $event['id']);

                $arrEvents[] = $arr;
            }
            // $arrEvents = str_replace('"[', '[', str_replace(']"', ']', json_encode($arrEvents)));
            $arrEvents =  json_encode($arrEvents);

            return view('event.index', compact('arrEvents', 'employees', 'current_month_event', 'events'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('Create Event')) {
            $branch      = Branch::where('created_by', '=', \Auth::user()->creatorId())->get();
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get();
            $employees   = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('event.create', compact('employees', 'branch', 'departments'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('Create Event')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'branch_id' => 'required',
                    'department_id' => 'required',
                    'employee_id' => 'required',
                    'title' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'color' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $event                = new LocalEvent();
            $event->branch_id     = $request->branch_id;
            $event->department_id = json_encode($request->department_id);
            $event->employee_id   = json_encode($request->employee_id);
            $event->title         = $request->title;
            $event->start_date    = $request->start_date;
            $event->end_date      = $request->end_date;
            $event->color         = $request->color;
            $event->description   = $request->description;
            $event->created_by    = \Auth::user()->creatorId();
            $event->save();

            //  slack
            $setting = Utility::settings(\Auth::user()->creatorId());
            $branch = Branch::find($request->branch_id);
            if (isset($setting['event_notification']) && $setting['event_notification'] == 1) {
                // $msg = $request->title . ' ' . __("for branch") . ' ' . $branch->name . ' ' . ("from") . ' ' . $request->start_date . ' ' . __("to") . ' ' . $request->end_date . '.';

                $uArr = [
                    'event_name' => $request->title,
                    'branch_name' => $branch->name,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ];

                Utility::send_slack_msg('new_event', $uArr);
            }

            //telegram
            $setting = Utility::settings(\Auth::user()->creatorId());
            $branch = Branch::find($request->branch_id);
            if (isset($setting['telegram_event_notification']) && $setting['telegram_event_notification'] == 1) {
                // $msg = $request->title . ' ' . __("for branch") . ' ' . $branch->name . ' ' . ("from") . ' ' . $request->start_date . ' ' . __("to") . ' ' . $request->end_date . '.';

                $uArr = [
                    'event_name' => $request->title,
                    'branch_name' => $branch->name,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ];

                Utility::send_telegram_msg('new_event', $uArr);
            }


            //twilio
            $setting = Utility::settings(\Auth::user()->creatorId());
            $branch = Branch::find($request->branch_id);
            $departments = Department::where('branch_id', $request->branch_id)->first();
            $employee = Employee::where('branch_id', $request->branch_id)->first();

            if (isset($setting['twilio_event_notification']) && $setting['twilio_event_notification'] == 1) {
                // $employeess = Employee::whereIn('branch_id', $request->employee_id)->get();
                // foreach ($employeess as $key => $employee) {
                    // $msg = $request->title . ' ' . __("for branch") . ' ' . $branch->name . ' ' . ("from") . ' ' . $request->start_date . ' ' . __("to") . ' ' . $request->end_date . '.';

                    $uArr = [
                        'event_name' => $request->title,
                        'branch_name' => $branch->name,
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                    ];

                    Utility::send_twilio_msg($employee->phone, 'new_event', $uArr);
                // }
            }



            if (in_array('0', $request->employee_id)) {
                $departmentEmployee = Employee::whereIn('department_id', $request->department_id)->get()->pluck('id');
                $departmentEmployee = $departmentEmployee;
            } else {
                $departmentEmployee = $request->employee_id;
            }
            foreach ($departmentEmployee as $employee) {
                $eventEmployee              = new EventEmployee();
                $eventEmployee->event_id    = $event->id;
                $eventEmployee->employee_id = $employee;
                $eventEmployee->created_by  = \Auth::user()->creatorId();
                $eventEmployee->save();
            }

            // google calendar
            if ($request->get('synchronize_type')  == 'google_calender') {

                $type = 'event';
                $request1 = new GoogleEvent();
                $request1->title = $request->title;
                $request1->start_date = $request->start_date;
                $request1->end_date = $request->end_date;

                Utility::addCalendarData($request1, $type);
            }

            //webhook
            $module = 'New Event';
            $webhook =  Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($event);
                // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    return redirect()->back()->with('success', __('Event successfully created.'));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }

            return redirect()->route('event.index')->with('success', __('Event successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Event $event)
    {
        // $events    = Event::where('created_by', '=', \Auth::user()->creatorId())->get();

        return redirect()->route('event.index');
    }

    public function edit($event)
    {
        $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $event = LocalEvent::find($event);
        return view('event.edit', compact('event', 'employees'));
    }

    public function update(Request $request, LocalEvent $event)
    {
        if (\Auth::user()->can('Edit Event')) {
            if ($event->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'title' => 'required',
                        'start_date' => 'required',
                        'end_date' => 'required',
                        'color' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $event->title       = $request->title;
                $event->start_date  = $request->start_date;
                $event->end_date    = $request->end_date;
                $event->color       = $request->color;
                $event->description = $request->description;
                $event->save();

                // return redirect()->route('event.index')->with('success', __('Event successfully updated.'));
                return redirect()->back()->with('success', __('Event successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(LocalEvent $event)
    {
        if (\Auth::user()->can('Delete Event')) {
            if ($event->created_by == \Auth::user()->creatorId()) {
                $event->delete();

                return redirect()->route('event.index')->with('success', __('Event successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function export()
    {
        $name = 'event' . date('Y-m-d i:h:s');
        $data = Excel::download(new EventExport(), $name . '.xlsx');


        return $data;
    }

    public function importFile()
    {
        return view('event.import');
    }

    public function import(Request $request)
    {
        $rules = [
            'file' => 'required|mimes:csv,txt',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $events = (new EventImport())->toArray(request()->file('file'))[0];

        $totalEvents = count($events) - 1;
        $errorArray    = [];

        for ($i = 1; $i <= count($events) - 1; $i++) {

            $event = $events[$i];
            $eventsByTitle = LocalEvent::where('title', $event[2])->first();

            if (!empty($eventsByTitle)) {
                $eventData = $eventsByTitle;
            } else {
                $eventData = new LocalEvent();
            }

            $eventData->branch_id           = $event[0];
            $eventData->department_id       = $event[1];
            $eventData->employee_id         = '["0"]';
            $eventData->title               = $event[2];
            $eventData->start_date          = $event[3];
            $eventData->end_date            = $event[4];
            $eventData->color               = $event[5];
            $eventData->description         = $event[6];
            $eventData->created_by          = $event[7];

            if (empty($eventData)) {
                $errorArray[] = $eventData;
            } else {
                $eventData->save();
            }
        }

        $errorRecord = [];
        if (empty($errorArray)) {
            $data['status'] = 'success';
            $data['msg']    = __('Record successfully imported');
        } else {
            $data['status'] = 'error';
            $data['msg']    = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalEvents . ' ' . 'record');


            foreach ($errorArray as $errorData) {

                $errorRecord[] = implode(',', $errorData);
            }

            \Session::put('errorArray', $errorRecord);
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }

    public function getdepartment(Request $request)
    {

        if ($request->branch_id == 0) {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
        }

        return response()->json($departments);
    }

    public function getemployee(Request $request)
    {
        if ($request->department_id == 0) {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        } else {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->whereIn('department_id',$request->department_id)->get()->pluck('name', 'id')->toArray();
        }

        return response()->json($employees);
    }

    public function showData($id)
    {
        $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $event = LocalEvent::find($id);

        return view('event.edit', compact('event', 'employees'));
    }

    public function get_event_data(Request $request)
    {
        $arrayJson = [];
        if ($request->get('calender_type') == 'google_calender') {
            $type = 'event';
            $arrayJson = Utility::getCalendarData($type);
            if (! is_array($arrayJson)) {
                $arrayJson = [];
            }
        } else {
            $data = LocalEvent::where('created_by', \Auth::user()->creatorId())->get();

            foreach ($data as $val) {
                $end_date = date_create($val->end_date);
                date_add($end_date, date_interval_create_from_date_string('1 days'));
                $arrayJson[] = [
                    'id' => $val->id,
                    'title' => $val->title,
                    'start' => $val->start_date,
                    'end' => date_format($end_date, 'Y-m-d H:i:s'),
                    'className' => $val->color,
                    'allDay' => true,
                    'url' => route('event.edit', $val['id']),
                ];
            }
        }

        $onlyEmployeeId = null;
        if (\Auth::user()->type === 'employee') {
            $emp = Employee::where('user_id', \Auth::id())->first();
            $onlyEmployeeId = $emp ? (int) $emp->id : null;
        }

        $attendanceEvents = $this->buildAttendanceCalendarEvents($onlyEmployeeId);
        $arrayJson = array_merge($arrayJson, $attendanceEvents);

        return response()->json($arrayJson);
    }

    /**
     * One calendar entry per employee per day: Present / Absent / Leave / Half Day + Late in / Early out.
     */
    protected function buildAttendanceCalendarEvents(?int $onlyEmployeeId): array
    {
        $creatorId = \Auth::user()->creatorId();
        $dateFrom = Carbon::now()->subMonths(4)->startOfMonth()->format('Y-m-d');
        $dateTo = Carbon::now()->addMonth()->endOfMonth()->format('Y-m-d');

        $query = AttendanceEmployee::query()
            ->where('created_by', $creatorId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with(['employee:id,name'])
            ->orderBy('date')
            ->orderBy('id');

        if ($onlyEmployeeId !== null) {
            $query->where('employee_id', $onlyEmployeeId);
        }

        $records = $query->get();
        if ($records->isEmpty()) {
            return [];
        }

        $swipeAttendanceIds = [];
        if (Schema::hasTable('attendance_modification_requests')) {
            $swipeAttendanceIds = AttendanceModificationRequest::whereIn(
                'attendance_employee_id',
                $records->pluck('id')->unique()->filter()->all()
            )->pluck('attendance_employee_id')->map(fn ($id) => (int) $id)->unique()->all();
            $swipeAttendanceIds = array_flip($swipeAttendanceIds);
        }

        $grouped = $records->groupBy(fn ($a) => $a->employee_id . '|' . $a->date);
        $out = [];

        foreach ($grouped as $rows) {
            /** @var \Illuminate\Support\Collection $rows */
            $sample = $rows->sortBy('id')->first();
            if (! $sample) {
                continue;
            }

            $empName = optional($sample->employee)->name ?: __('Employee');

            $priority = static function ($status) {
                $s = strtolower(trim((string) $status));

                return match ($s) {
                    'absent', 'leave' => 40,
                    'half day' => 30,
                    default => 10,
                };
            };

            $mainRow = $rows->sortByDesc(fn ($r) => $priority($r->status))->first();
            $mainStatus = strtolower(trim((string) ($mainRow->status ?? 'present')));

            $hasSwipeRequest = $rows->contains(fn ($r) => isset($swipeAttendanceIds[(int) $r->id]));

            $hasLate = $rows->contains(function ($r) {
                if ((int) ($r->late_mark ?? 0) > 0) {
                    return true;
                }
                $late = trim((string) ($r->late ?? '00:00:00'));

                return $late !== '' && $late !== '00:00:00';
            });

            $hasEarly = $rows->contains(function ($r) {
                if ((int) ($r->early_mark ?? 0) > 0) {
                    return true;
                }
                $el = trim((string) ($r->early_leaving ?? '00:00:00'));

                return $el !== '' && $el !== '00:00:00';
            });

            // Leave days tied to swipe requests (or Leave status from swipe flow)
            // should show as Attendance Swipe Request on the calendar.
            if ($hasSwipeRequest || $mainStatus === 'leave') {
                $statusLabel = __('Attendance Swipe Request');
            } else {
                $statusLabel = match ($mainStatus) {
                    'absent' => __('Absent'),
                    'half day' => __('Half Day'),
                    default => __('Present'),
                };
            }

            $parts = [$statusLabel];
            if (! $hasSwipeRequest && $mainStatus !== 'leave') {
                if ($hasLate) {
                    $parts[] = __('Late in');
                }
                if ($hasEarly) {
                    $parts[] = __('Early out');
                }
            }

            $title = $onlyEmployeeId !== null
                ? implode(' · ', $parts)
                : $empName . ': ' . implode(' · ', $parts);

            $className = match (true) {
                $hasSwipeRequest || $mainStatus === 'leave' => 'attn-cal-leave',
                $mainStatus === 'absent' => 'attn-cal-absent',
                $mainStatus === 'half day' => 'attn-cal-halfday',
                default => $hasLate ? 'attn-cal-late' : 'attn-cal-present',
            };

            $end = Carbon::parse($sample->date)->addDay()->format('Y-m-d H:i:s');

            $out[] = [
                'id' => 'attn-' . $sample->employee_id . '-' . str_replace('-', '', (string) $sample->date),
                'title' => $title,
                'start' => $sample->date,
                'end' => $end,
                'allDay' => true,
                'className' => $className,
                'classNames' => [$className],
                'url' => route('attendanceemployee.index', ['type' => 'daily', 'date' => $sample->date]),
            ];
        }

        return $out;
    }
}
