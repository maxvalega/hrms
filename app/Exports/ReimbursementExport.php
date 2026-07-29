<?php

namespace App\Exports;

use App\Models\ReimbursementClaim;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReimbursementExport implements FromCollection, WithHeadings
{
    protected $month;
    protected $branch;
    protected $department;

    public function __construct($data)
    {
        $this->month = $data['month'];
        $this->branch = $data['branch'];
        $this->department = $data['department'];
    }

    public function collection()
    {
        $query = ReimbursementClaim::query()
            ->select(
                'reimbursement_claims.*',
                'employees.name',
                'employees.employee_id as emp_code',
                'employees.account_holder_name',
                'employees.account_number',
                'employees.bank_name',
                'employees.bank_identifier_code'
            )
            ->leftJoin('employees', 'reimbursement_claims.employee_id', '=', 'employees.id')
            ->where('reimbursement_claims.created_by', \Auth::user()->creatorId())
            ->where('reimbursement_claims.status', 'approved');

        if (!empty($this->month) && $this->month !== '0') {
            $query->where('reimbursement_claims.claim_month', $this->month);
        }

        if (!empty($this->branch) && (int) $this->branch !== 0) {
            $query->where('employees.branch_id', $this->branch);
        }

        if (!empty($this->department) && (int) $this->department !== 0) {
            $query->where('employees.department_id', $this->department);
        }

        $rows = $query->orderBy('reimbursement_claims.claim_month')->orderBy('employees.name')->get();

        $export = collect();
        foreach ($rows as $claim) {
            $export->push([
                'employee_id' => !empty($claim->emp_code) ? \Auth::user()->employeeIdFormat($claim->emp_code) : '',
                'employee_name' => $claim->name ?? '',
                'component' => $claim->component_name ?: ('#' . $claim->component_id),
                'claim_month' => $claim->claim_month,
                'amount' => number_format((float) $claim->amount, 2, '.', ''),
                'approved_at' => $claim->approved_at ? $claim->approved_at->format('Y-m-d') : '',
                'account_holder' => $claim->account_holder_name ?? '',
                'account_number' => $claim->account_number ?? '',
                'bank_name' => $claim->bank_name ?? '',
                'ifsc' => $claim->bank_identifier_code ?? '',
                'remarks' => $claim->remarks ?? '',
            ]);
        }

        return $export;
    }

    public function headings(): array
    {
        return [
            'Employee Id',
            'Employee Name',
            'Component',
            'Claim Month',
            'Amount',
            'Approved Date',
            'Account Holder',
            'Account Number',
            'Bank Name',
            'IFSC',
            'Remarks',
        ];
    }
}
