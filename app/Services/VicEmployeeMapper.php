<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VicEmployeeMapper
{
    public const DEFAULT_PASSWORD = 'Vic@2026';

    /**
     * @return array{created:int,updated:int,skipped:int,errors:array<int,string>}
     */
    public function map(int $creatorId): array
    {
        $path = base_path('database/data/vic_employee_data_final.csv');
        if (!is_file($path)) {
            throw new \RuntimeException('Vimal employee file is missing.');
        }

        $rows = $this->readCsv($path);
        if ($rows === []) {
            throw new \RuntimeException('Vimal employee file is empty.');
        }

        $this->freeCompanyPlaceholderCode($creatorId);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $mapped = [];

        foreach ($rows as $index => $row) {
            $name = $this->fullName($row);
            if ($name === '') {
                $skipped++;
                continue;
            }

            try {
                $wasNew = $this->upsertEmployee($creatorId, $row, $name);
                $wasNew ? $created++ : $updated++;
                $mapped[] = $name;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = 'Row ' . ($index + 2) . ' (' . $name . '): ' . $e->getMessage();
            }
        }

        $this->linkReportingManagers($creatorId);

        return compact('created', 'updated', 'skipped', 'errors');
    }

    public static function needsMapping(int $creatorId): bool
    {
        return !Employee::where('created_by', $creatorId)->where('employee_id', '17')->exists();
    }

    /**
     * @return array<int,array<string,string>>
     */
    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);

            return [];
        }
        $header = array_map(fn ($h) => trim((string) $h), $header);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = trim((string) ($data[$i] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    protected function upsertEmployee(int $creatorId, array $row, string $name): bool
    {
        $code = preg_replace('/[^0-9]/', '', (string) ($row['Employee_Code'] ?? ''));
        $email = $this->emailFor($row, $code, $name);
        $phone = preg_replace('/[^0-9]/', '', (string) ($row['Mobileno'] ?? '')) ?: '';
        $branch = $this->firstOrCreateBranch($creatorId, $row['Branch'] ?? 'MASJID');
        $department = $this->firstOrCreateDepartment($creatorId, $branch->id, $row['Department'] ?? 'GENERAL');
        $designation = $this->firstOrCreateDesignation($creatorId, $branch->id, $department->id, $row['Designation'] ?? 'STAFF');

        $employee = $this->findExisting($creatorId, $code, $email, $phone, $name);
        $isNew = !$employee;

        $user = $employee?->user_id ? User::find($employee->user_id) : null;
        if (!$user) {
            $user = User::where('email', $email)->first();
        }
        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'type' => strcasecmp((string) ($row['Role'] ?? ''), 'Admin') === 0 ? 'employee' : 'employee',
                'lang' => 'en',
                'created_by' => $creatorId,
                'email_verified_at' => now(),
            ]);
            try {
                $user->assignRole('Employee');
            } catch (\Throwable $e) {
                // Role may already be missing on some tenants; employee record still works.
            }
        } else {
            $user->name = $name;
            if ((int) $user->created_by === 0 || $user->created_by === null) {
                $user->created_by = $creatorId;
            }
            $user->save();
        }

        $payload = [
            'user_id' => $user->id,
            'name' => $name,
            'dob' => $this->parseDate($row['BirthDate'] ?? '') ?: null,
            'gender' => $this->gender($row['Gender'] ?? ''),
            'phone' => $phone,
            'address' => $row['Local_Address'] ?: ($row['Permanent_Address'] ?? ''),
            'present_address' => $row['Local_Address'] ?? '',
            'permanent_address' => $row['Permanent_Address'] ?? '',
            'email' => $email,
            'password' => $user->password,
            'employee_id' => $code !== '' ? $code : ($employee->employee_id ?? $this->nextOpenCode($creatorId)),
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'company_doj' => $this->parseDate($row['JoinDate'] ?? '') ?: null,
            'account_holder_name' => $row['NameAsBank'] ?? $name,
            'account_number' => $row['AccountNo'] ?? '',
            'bank_name' => $row['Bank'] ?? '',
            'bank_identifier_code' => $row['Bank Ifsc Code'] ?? '',
            'created_by' => $creatorId,
        ];

        if (Schema::hasColumn('employees', 'aadhar_number')) {
            $payload['aadhar_number'] = $row['Aadhar Card No'] ?? '';
        }
        if (Schema::hasColumn('employees', 'pan_number')) {
            $payload['pan_number'] = $row['PANNo'] ?? '';
        }
        if (Schema::hasColumn('employees', 'uan_number')) {
            $payload['uan_number'] = $row['UANNo'] ?? '';
        }
        if (Schema::hasColumn('employees', 'esic_number')) {
            $payload['esic_number'] = $row['ESIC Number'] ?? '';
        }

        $columns = Schema::getColumnListing('employees');
        $payload = array_filter(
            $payload,
            fn ($key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_KEY
        );

        if ($isNew) {
            Employee::create($payload);
        } else {
            $employee->fill($payload);
            $employee->save();
        }

        return $isNew;
    }

    protected function findExisting(int $creatorId, string $code, string $email, string $phone, string $name): ?Employee
    {
        $query = Employee::where('created_by', $creatorId);
        if ($code !== '') {
            $found = (clone $query)->where('employee_id', $code)->first();
            if ($found) {
                return $found;
            }
        }
        $found = (clone $query)->where('email', $email)->first();
        if ($found) {
            return $found;
        }
        if ($phone !== '') {
            $found = (clone $query)->where('phone', $phone)->first();
            if ($found) {
                return $found;
            }
        }

        $want = $this->normalizeName($name);

        return (clone $query)->get()->first(function (Employee $employee) use ($want) {
            return $this->normalizeName((string) $employee->name) === $want;
        });
    }

    protected function freeCompanyPlaceholderCode(int $creatorId): void
    {
        $placeholder = Employee::where('created_by', $creatorId)
            ->where(function ($q) {
                $q->where('name', 'like', 'Vimal Industrial%')
                    ->orWhere('name', 'like', 'Vimal%Company%');
            })
            ->get();

        foreach ($placeholder as $employee) {
            if ((string) $employee->employee_id === '1') {
                $employee->employee_id = (string) $this->nextOpenCode($creatorId, 90);
                $employee->save();
            }
        }
    }

    protected function nextOpenCode(int $creatorId, int $start = 25): int
    {
        $used = Employee::where('created_by', $creatorId)->pluck('employee_id')->map(fn ($v) => (int) $v)->all();
        $code = $start;
        while (in_array($code, $used, true)) {
            $code++;
        }

        return $code;
    }

    protected function firstOrCreateBranch(int $creatorId, string $name): Branch
    {
        $name = trim($name) ?: 'MASJID';
        $branch = Branch::where('created_by', $creatorId)->where('name', $name)->first();
        if ($branch) {
            return $branch;
        }

        return Branch::create([
            'name' => $name,
            'created_by' => $creatorId,
        ]);
    }

    protected function firstOrCreateDepartment(int $creatorId, int $branchId, string $name): Department
    {
        $name = trim($name) ?: 'GENERAL';
        $department = Department::where('created_by', $creatorId)->where('name', $name)->first();
        if ($department) {
            if (!$department->branch_id) {
                $department->branch_id = $branchId;
                $department->save();
            }

            return $department;
        }

        return Department::create([
            'name' => $name,
            'branch_id' => $branchId,
            'created_by' => $creatorId,
        ]);
    }

    protected function firstOrCreateDesignation(int $creatorId, int $branchId, int $departmentId, string $name): Designation
    {
        $name = trim($name) ?: 'STAFF';
        $designation = Designation::where('created_by', $creatorId)->where('name', $name)->first();
        if ($designation) {
            return $designation;
        }

        return Designation::create([
            'name' => $name,
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'created_by' => $creatorId,
        ]);
    }

    protected function linkReportingManagers(int $creatorId): void
    {
        $nitish = Employee::where('created_by', $creatorId)->get()->first(function (Employee $employee) {
            return $this->normalizeName((string) $employee->name) === 'NITISH DIORA';
        });
        if (!$nitish) {
            return;
        }

        Employee::where('created_by', $creatorId)
            ->where('id', '!=', $nitish->id)
            ->where(function ($q) {
                $q->whereNull('reporting_manager_id')->orWhere('reporting_manager_id', 0);
            })
            ->update(['reporting_manager_id' => $nitish->id]);
    }

    protected function fullName(array $row): string
    {
        $parts = array_filter([
            $row['First_Name'] ?? '',
            $row['Middle_Name'] ?? '',
            $row['Last_Name'] ?? '',
        ], fn ($p) => trim((string) $p) !== '');

        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?? '');
    }

    protected function emailFor(array $row, string $code, string $name): string
    {
        foreach (['Email_Id', 'Offical_MailId'] as $key) {
            $email = strtolower(trim((string) ($row[$key] ?? '')));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        $slug = Str::slug($name, '.');
        $suffix = $code !== '' ? $code : substr(md5($name), 0, 6);

        return 'vic.' . ($slug !== '' ? $slug : 'emp') . '.' . $suffix . '@jemini.local';
    }

    protected function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return null;
        }
        foreach (['d-m-Y', 'j-n-Y', 'Y-m-d'] as $fmt) {
            $dt = \DateTime::createFromFormat('!' . $fmt, $value);
            if ($dt instanceof \DateTime) {
                $year = (int) $dt->format('Y');
                if ($year >= 1950 && $year <= 2100) {
                    return $dt->format('Y-m-d');
                }
            }
        }

        return null;
    }

    protected function gender(string $value): string
    {
        $value = strtolower(trim($value));
        if (in_array($value, ['male', 'female'], true)) {
            return ucfirst($value);
        }

        return '';
    }

    protected function normalizeName(string $name): string
    {
        $name = strtoupper(trim($name));
        $name = preg_replace('/\b(MR|MRS|MS|MISS)\.?\b/', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }
}
