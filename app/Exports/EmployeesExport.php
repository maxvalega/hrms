<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Employee::where('created_by', \Auth::user()->creatorId())
            ->with([
                'branch:id,name',
                'department:id,name',
                'designation:id,name',
                'employeeType:id,name',
                'reportingManager:id,name',
                'hod:id,name',
                'management:id,name',
                'shift:id,name',
            ])
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        // Matches Spectal Employee Master.xlsx column layout
        return [
            'Employee ID',
            'Name',
            'Date of Birth',
            'Gender',
            'Email ID',
            'Date of Join',
            'Phone Number',
            'Branch',
            'Department',
            'Designation',
            'Employee Type',
            'Account Holder Name',
            'Account Number',
            'Bank Name',
            'Bank Identifier Code',
            'Branch Location',
            'Present Address',
            'Permanent Address',
            'Reporting Manager',
            'HOD',
            'Management',
            'Annual CTC',
            'Shift',
        ];
    }

    /**
     * @param  \App\Models\Employee  $employee
     */
    public function map($employee): array
    {
        $user = \Auth::user();
        $employeeId = $employee->employee_id;
        if ($employeeId !== null && $employeeId !== '' && method_exists($user, 'employeeIdFormat')) {
            $employeeId = $user->employeeIdFormat($employeeId);
        }

        $monthlySalary = (float) ($employee->salary ?? 0);
        $annualCtc = $monthlySalary > 0 ? round($monthlySalary * 12, 2) : '';

        $presentAddress = trim((string) ($employee->present_address ?: $employee->address ?: ''));
        $permanentAddress = trim((string) ($employee->permanent_address ?: ''));

        $shiftName = optional($employee->shift)->name;
        if (! $shiftName && ! empty($employee->shift_type)) {
            $shiftName = $employee->shift_type;
        }

        return [
            $employeeId ?: '',
            $employee->name ?? '',
            $this->formatDate($employee->dob),
            $employee->gender ?? '',
            $employee->email ?? '',
            $this->formatDate($employee->company_doj),
            $employee->phone ?? '',
            optional($employee->branch)->name ?? '',
            optional($employee->department)->name ?? '',
            optional($employee->designation)->name ?? '',
            optional($employee->employeeType)->name ?? '',
            $employee->account_holder_name ?? '',
            $employee->account_number ?? '',
            $employee->bank_name ?? '',
            $employee->bank_identifier_code ?? '',
            $employee->branch_location ?? '',
            $presentAddress,
            $permanentAddress,
            optional($employee->reportingManager)->name ?? '',
            optional($employee->hod)->name ?? '',
            optional($employee->management)->name ?? '',
            $annualCtc,
            $shiftName ?? '',
        ];
    }

    protected function formatDate($value): string
    {
        if (empty($value) || $value === '0000-00-00') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
