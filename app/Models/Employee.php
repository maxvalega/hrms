<?php

namespace App\Models;

use App\Support\TenantHost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use SebastianBergmann\CodeCoverage\Percentage;

class Employee extends Model
{
    protected $table = 'employees';
    protected $fillable = [
        'user_id',
        'name',
        'dob',
        'gender',
        'phone',
        'address',
        'present_address',
        'permanent_address',
        'present_country',
        'present_state',
        'present_city',
        'present_pincode',
        'permanent_country',
        'permanent_state',
        'permanent_city',
        'permanent_pincode',
        'family_details',
        'emergency_contact_name',
        'emergency_contact_phone',
        'blood_group',
        'insurance_id',
        'insurer_name',
        'insurance_contact_person',
        'hobbies',
        'food_type',
        'education',
        'email',
        'password',
        'employee_id',
        'biometric_emp_id',
        'branch_id',
        'department_id',
        'designation_id',
        'department_hierarchy',
        'reporting_manager_id',
        'hod_id',
        'management_id',
        'company_doj',
        'shift_type',
        'shift_id',
        'documents',
        'account_holder_name',
        'account_number',
        'bank_name',
        'bank_identifier_code',
        'branch_location',
        'tax_payer_id',
        'aadhar_number',
        'pan_number',
        'uan_number',
        'esic_number',
        'mentor_buddy_id',
        'salary_type',
        'employee_type_id',
        'monthly_stipend',
        'account_type',
        'salary',
        'created_by',
    ];

    public static function ensureForClockPunch(?User $user = null): ?self
    {
        $user = $user ?: \Auth::user();
        if (!$user) {
            return null;
        }

        $emp = static::where('user_id', $user->id)->first();
        if ($emp) {
            return $emp;
        }

        $creatorId = method_exists($user, 'creatorId') ? $user->creatorId() : (int) $user->created_by;

        if (!empty($user->email)) {
            $emp = static::where('created_by', $creatorId)->where('email', $user->email)->first();
            if ($emp) {
                if ((int) $emp->user_id !== (int) $user->id) {
                    $emp->user_id = $user->id;
                    $emp->save();
                }

                return $emp;
            }
        }

        if (!TenantHost::isJeminiMainPortal() || !in_array($user->type, ['company', 'hr'], true)) {
            return null;
        }

        try {
            $columns = Schema::getColumnListing('employees');
            $latest = static::where('created_by', $creatorId)->latest('id')->first();
            $nextNum = $latest ? max(1, ((int) $latest->employee_id) + 1) : 1;

            $row = [
                'user_id' => $user->id,
                'name' => $user->name ?: 'Admin',
                'email' => $user->email,
                'password' => $user->password,
                'phone' => $user->phone ?? '',
                'employee_id' => (string) $nextNum,
                'branch_id' => Schema::hasTable('branches') ? (int) (Branch::where('created_by', $creatorId)->value('id') ?: 0) : 0,
                'department_id' => Schema::hasTable('departments') ? (int) (Department::where('created_by', $creatorId)->value('id') ?: 0) : 0,
                'designation_id' => Schema::hasTable('designations') ? (int) (Designation::where('created_by', $creatorId)->value('id') ?: 0) : 0,
                'company_doj' => date('Y-m-d'),
                'created_by' => $creatorId,
                'gender' => '',
                'address' => '',
            ];

            $payload = [];
            foreach ($row as $key => $value) {
                if (in_array($key, $columns, true)) {
                    $payload[$key] = $value;
                }
            }

            return static::create($payload);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function documents()
    {
        return $this->hasMany('App\Models\EmployeeDocument', 'employee_id', 'employee_id')->get();
    }

    public function salary_type()
    {
        return $this->hasOne('App\Models\PayslipType', 'id', 'salary_type')->pluck('name')->first();
    }

    public function account_type()
    {
        return $this->hasOne('App\Models\AccountList', 'id', 'account_type')->pluck('account_name')->first();
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
 
    public function get_net_salary()
    {
        $allowances      = Allowance::where('employee_id', '=', $this->id)->get();
        $total_allowance = 0;
        foreach ($allowances as $allowance) {
            if ($allowance->type == 'percentage') {
                $employee          = Employee::find($allowance->employee_id);
                $total_allowance  = $allowance->amount * $employee->salary / 100  + $total_allowance;
            } else {
                $total_allowance = $allowance->amount + $total_allowance;
            }
        }

        //commission
        $commissions      = Commission::where('employee_id', '=', $this->id)->get();

        $total_commission = 0;
        foreach ($commissions as $commission) {
            if ($commission->type == 'percentage') {
                $employee          = Employee::find($commission->employee_id);
                $total_commission  = $commission->amount * $employee->salary / 100 + $total_commission;
            } else {
                $total_commission = $commission->amount + $total_commission;
            }
        }



        //Loan
        $loans      = Loan::where('employee_id', '=', $this->id)->get();
        $total_loan = 0;
        foreach ($loans as $loan) {
            if ($loan->type == 'percentage') {
                $employee = Employee::find($loan->employee_id);
                $total_loan  = $loan->amount * $employee->salary / 100   + $total_loan;
            } else {
                $total_loan = $loan->amount + $total_loan;
            }
           
        }

        //Saturation Deduction
        $saturation_deductions      = SaturationDeduction::where('employee_id', '=', $this->id)->get();
        $total_saturation_deduction = 0;
        foreach ($saturation_deductions as $saturation_deduction) {
            if ($saturation_deduction->type == 'percentage') {
                $employee          = Employee::find($saturation_deduction->employee_id);
                $total_saturation_deduction  = $saturation_deduction->amount * $employee->salary / 100 + $total_saturation_deduction;
            } else {
                $total_saturation_deduction = $saturation_deduction->amount + $total_saturation_deduction;
            }
        }

        //OtherPayment
        $other_payments      = OtherPayment::where('employee_id', '=', $this->id)->get();
        $total_other_payment = 0;
        foreach ($other_payments as $other_payment) {
            if ($other_payment->type == 'percentage') {
                $employee          = Employee::find($other_payment->employee_id);
                $total_other_payment  = $other_payment->amount * $employee->salary / 100  + $total_other_payment;
            } else {
                $total_other_payment = $other_payment->amount + $total_other_payment;
            }
        }

        //Overtime
        $over_times      = Overtime::where('employee_id', '=', $this->id)->get();
        $total_over_time = 0;
        foreach ($over_times as $over_time) {
            $total_work      = $over_time->number_of_days * $over_time->hours;
            $amount          = $total_work * $over_time->rate;
            $total_over_time = $amount + $total_over_time;
        }


        //Net Salary Calculate
        $advance_salary = $total_allowance + $total_commission - $total_loan - $total_saturation_deduction + $total_other_payment + $total_over_time;

        $employee       = Employee::where('id', '=', $this->id)->first();

        $net_salary     = (!empty($employee->salary) ? $employee->salary : 0) + $advance_salary;

        return $net_salary;
    }

    public static function allowance($id)
    {
        //allowance
        $allowances      = Allowance::where('employee_id', '=', $id)->get();
        $total_allowance = 0;
        foreach ($allowances as $allowance) {
            $total_allowance = $allowance->amount + $total_allowance;
        }

        $allowance_json = json_encode($allowances);

        return $allowance_json;
    }

    public static function commission($id)
    {
        //commission
        $commissions      = Commission::where('employee_id', '=', $id)->get();
        $total_commission = 0;

        foreach ($commissions as $commission) {
            $total_commission = $commission->amount + $total_commission;
        }
        $commission_json = json_encode($commissions);

        return $commission_json;
    }

    public static function loan($id)
    {
        //Loan
        $loans      = Loan::where('employee_id', '=', $id)->get();
        $total_loan = 0;
        foreach ($loans as $loan) {
            $total_loan = $loan->amount + $total_loan;
        }
        $loan_json = json_encode($loans);

        return $loan_json;
    }

    public static function saturation_deduction($id)
    {
        //Saturation Deduction
        $saturation_deductions      = SaturationDeduction::where('employee_id', '=', $id)->get();
        $total_saturation_deduction = 0;
        foreach ($saturation_deductions as $saturation_deduction) {
            $total_saturation_deduction = $saturation_deduction->amount + $total_saturation_deduction;
        }
        $saturation_deduction_json = json_encode($saturation_deductions);

        return $saturation_deduction_json;
    }

    public static function other_payment($id)
    {
        //OtherPayment
        $other_payments      = OtherPayment::where('employee_id', '=', $id)->get();
        $total_other_payment = 0;
        foreach ($other_payments as $other_payment) {
            $total_other_payment = $other_payment->amount + $total_other_payment;
        }
        $other_payment_json = json_encode($other_payments);

        return $other_payment_json;
    }

    public static function overtime($id)
    {
        //Overtime
        $over_times      = Overtime::where('employee_id', '=', $id)->get();
        $total_over_time = 0;
        foreach ($over_times as $over_time) {
            $total_work      = $over_time->number_of_days * $over_time->hours;
            $amount          = $total_work * $over_time->rate;
            $total_over_time = $amount + $total_over_time;
        }
        $over_time_json = json_encode($over_times);

        return $over_time_json;
    }

    public static function employee_id()
    {
        $employee = Employee::latest()->first();

        return !empty($employee) ? $employee->id + 1 : 1;
    }

    public function branch()
    {
        return $this->hasOne('App\Models\Branch', 'id', 'branch_id');
    }

    public function phone()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'phone');
    }

    public function department()
    {
        return $this->hasOne('App\Models\Department', 'id', 'department_id');
    }

    public function designation()
    {
        return $this->hasOne('App\Models\Designation', 'id', 'designation_id');
    }

    public function reportingManager()
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id', 'id');
    }

    public function hod()
    {
        return $this->belongsTo(Employee::class, 'hod_id', 'id');
    }

    public function management()
    {
        return $this->belongsTo(Employee::class, 'management_id', 'id');
    }

    public function salaryType()
    {
        return $this->hasOne('App\Models\PayslipType', 'id', 'salary_type');
    }

    public function employeeType()
    {
        return $this->belongsTo(EmployeeType::class, 'employee_type_id');
    }

    /* ──────────────────────────────────────────────────────────────
     * Hierarchy / Manager helpers
     * ──────────────────────────────────────────────────────────── */

    /**
     * Is this employee a "manager" — i.e. does any other employee report to
     * them as reporting manager, HOD, or management? Used by survey module
     * to gate "Team Pulse" access (a team must exist to see team data).
     */
    public function isManagerLevel(): bool
    {
        $id = $this->id;
        return self::where(function ($q) use ($id) {
            $q->where('reporting_manager_id', $id)
              ->orWhere('hod_id', $id)
              ->orWhere('management_id', $id);
        })->exists();
    }

    /**
     * Distinguishes Management-level (whose `management_id` is set on others)
     * from regular reporting managers. Survey analytics dashboards may grant
     * Management broader access than line managers.
     */
    public function isManagementLevel(): bool
    {
        return self::where('management_id', $this->id)->exists();
    }

    /**
     * IDs of every employee who reports up to this employee (any of the 3
     * hierarchy fields). Used to filter analytics to "my team only".
     *
     * @return \Illuminate\Support\Collection<int>
     */
    public function teamMemberIds(): \Illuminate\Support\Collection
    {
        $id = $this->id;
        return self::where('created_by', $this->created_by)
            ->where(function ($q) use ($id) {
                $q->where('reporting_manager_id', $id)
                  ->orWhere('hod_id', $id)
                  ->orWhere('management_id', $id);
            })
            ->pluck('id');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function paySlip()
    {
        return $this->hasOne('App\Models\PaySlip', 'id', 'employee_id');
    }


    public function present_status($employee_id, $data)
    {
        return AttendanceEmployee::where('employee_id', $employee_id)->where('date', $data)->first();
    }
    public static function employee_name($name)
    {

        $employee = Employee::where('id', $name)->first();
        if (!empty($employee)) {
            return $employee->name;
        }
    }


    public static function login_user($name)
    {
        $user = User::where('id', $name)->first();
        return $user->name;
    }

    public static function employee_salary($salary)
    {

        $employee = Employee::where("salary", $salary)->first();
        if ($employee->salary == '0' || $employee->salary == '0.0') {
            return "-";
        } else {
            return $employee->salary;
        }
    }
}
