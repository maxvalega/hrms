<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Department;
use App\Models\ReimbursementClaim;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReimbursementExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    protected string $type;
    protected ?string $month;
    protected ?string $year;
    protected $branch;
    protected $department;
    protected int $rowCount = 0;
    protected float $totalAmount = 0;

    public function __construct(array $data)
    {
        $this->type = $data['type'] ?? 'monthly';
        $this->month = $data['month'] ?? null;
        $this->year = $data['year'] ?? null;
        $this->branch = $data['branch'] ?? 0;
        $this->department = $data['department'] ?? 0;
    }

    public function title(): string
    {
        return 'Reimbursement Report';
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Employee ID',
            'Employee Name',
            'Component',
            'Claim Month',
            'Amount',
            'Status',
            'Approved Date',
            'Account Holder',
            'Account Number',
            'Bank Name',
            'IFSC',
            'Remarks',
            'Branch',
            'Department',
        ];
    }

    public function array(): array
    {
        $query = ReimbursementClaim::query()
            ->select(
                'reimbursement_claims.*',
                'employees.name',
                'employees.employee_id as emp_code',
                'employees.account_holder_name',
                'employees.account_number',
                'employees.bank_name',
                'employees.bank_identifier_code',
                'employees.branch_id',
                'employees.department_id'
            )
            ->leftJoin('employees', 'reimbursement_claims.employee_id', '=', 'employees.id')
            ->where('reimbursement_claims.created_by', \Auth::user()->creatorId())
            ->where('reimbursement_claims.status', 'approved');

        if ($this->type === 'yearly' && !empty($this->year)) {
            $query->where('reimbursement_claims.claim_month', '>=', $this->year . '-01')
                ->where('reimbursement_claims.claim_month', '<=', $this->year . '-12');
        } elseif (!empty($this->month) && $this->month !== '0') {
            $query->where('reimbursement_claims.claim_month', $this->month);
        }

        if (!empty($this->branch) && (int) $this->branch !== 0) {
            $query->where('employees.branch_id', $this->branch);
        }

        if (!empty($this->department) && (int) $this->department !== 0) {
            $query->where('employees.department_id', $this->department);
        }

        $rows = $query->orderBy('reimbursement_claims.claim_month')->orderBy('employees.name')->get();

        $branchNames = Branch::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        $departmentNames = Department::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');

        $export = [];
        $serial = 1;
        foreach ($rows as $claim) {
            $amount = (float) $claim->amount;
            $this->totalAmount += $amount;

            $export[] = [
                $serial++,
                !empty($claim->emp_code) ? \Auth::user()->employeeIdFormat($claim->emp_code) : '',
                $claim->name ?? '',
                $claim->component_name ?: ('#' . $claim->component_id),
                $claim->claim_month,
                $amount,
                'Approved',
                $claim->approved_at ? $claim->approved_at->format('d-m-Y') : '',
                $claim->account_holder_name ?? '',
                $claim->account_number ?? '',
                $claim->bank_name ?? '',
                $claim->bank_identifier_code ?? '',
                $claim->remarks ?? '',
                $branchNames[$claim->branch_id] ?? '',
                $departmentNames[$claim->department_id] ?? '',
            ];
        }

        $this->rowCount = count($export);

        if ($this->rowCount > 0) {
            $export[] = [
                '',
                '',
                '',
                '',
                'TOTAL',
                $this->totalAmount,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        return $export;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F4E79'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = $this->rowCount + 1;
                $totalRow = $this->rowCount > 0 ? $lastDataRow + 1 : 1;
                $range = 'A1:O' . $totalRow;

                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A1:O1')->getAlignment()->setWrapText(true);
                $sheet->getRowDimension(1)->setRowHeight(22);

                // Amount column as number
                if ($this->rowCount > 0) {
                    $sheet->getStyle('F2:F' . $totalRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle('F2:F' . $totalRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Total row styling
                    $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('D9E2F3');
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter($this->rowCount > 0 ? 'A1:O' . $lastDataRow : 'A1:O1');
            },
        ];
    }
}
