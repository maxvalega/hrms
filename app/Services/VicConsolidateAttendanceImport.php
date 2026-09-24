<?php

namespace App\Services;

use App\Models\AttendanceEmployee;
use App\Models\Employee;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class VicConsolidateAttendanceImport
{
    /**
     * @return array{
     *   month:string,
     *   created:int,
     *   updated:int,
     *   skipped:int,
     *   errors:array<int,string>,
     *   employees:int
     * }
     */
    public function import(UploadedFile $file, int $creatorId): array
    {
        $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array)
            {
                return $array;
            }
        }, $file);

        $rows = $sheets[0] ?? [];
        if ($rows === []) {
            throw new \RuntimeException(__('Uploaded file is empty.'));
        }

        [$headerRowIndex, $header] = $this->findHeaderRow($rows);
        if ($headerRowIndex === null) {
            throw new \RuntimeException(__('This is not a Consolidate Attendance Register. Use the 6-row Status / Shift / IN / OUT / Hours file.'));
        }

        $dayColumns = $this->parseDayColumns($header);
        if (count($dayColumns) < 5) {
            throw new \RuntimeException(__('Could not find day columns like 01-08-2026 in the file.'));
        }

        $codeCol = $this->findHeaderColumn($header, ['employee_code', 'emp_code', 'emp_id', 'employee_id', 'code']);
        $nameCol = $this->findHeaderColumn($header, ['employee_name', 'name', 'emp_name']);
        $labelCol = $this->findLabelColumn($rows, $headerRowIndex, $dayColumns);

        if ($codeCol === null && $nameCol === null) {
            throw new \RuntimeException(__('Could not find Employee Code or Employee Name column.'));
        }

        $month = $this->monthFromDayColumns($dayColumns);
        $cols = $this->identityColumns($header);
        $defaultIn = Utility::getValByName('company_start_time') ?: '09:00:00';
        $defaultOut = Utility::getValByName('company_end_time') ?: '18:00:00';

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $registerRows = [];
        $current = null;

        for ($r = $headerRowIndex + 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            if (!$this->rowHasValue($row)) {
                continue;
            }

            $label = $labelCol !== null ? $this->normalizeLabel($row[$labelCol] ?? '') : '';
            $code = $codeCol !== null ? trim((string) ($row[$codeCol] ?? '')) : '';
            $name = $nameCol !== null ? trim((string) ($row[$nameCol] ?? '')) : '';

            if ($label === 'status' || ($current === null && ($code !== '' || $name !== ''))) {
                if ($current !== null) {
                    $registerRows[] = $this->blockToRegisterRow($current, $cols, $dayColumns);
                    [$c, $u, $s, $err] = $this->persistEmployeeBlock($current, $creatorId, $dayColumns, $defaultIn, $defaultOut);
                    $created += $c;
                    $updated += $u;
                    $skipped += $s;
                    $errors = array_merge($errors, $err);
                }

                $current = [
                    'row' => $r + 1,
                    'code' => $code,
                    'name' => $name,
                    'status' => $row,
                    'shift' => [],
                    'in' => [],
                    'out' => [],
                    'hours' => [],
                    'overtime' => [],
                ];
                continue;
            }

            if ($current === null) {
                continue;
            }

            if ($label === 'shift') {
                $current['shift'] = $row;
            } elseif ($label === 'in') {
                $current['in'] = $row;
            } elseif ($label === 'out') {
                $current['out'] = $row;
            } elseif ($label === 'working_hours') {
                $current['hours'] = $row;
            } elseif (in_array($label, ['overtime', 'overtime_hours', 'ot'], true)) {
                $current['overtime'] = $row;
            }
        }

        if ($current !== null) {
            $registerRows[] = $this->blockToRegisterRow($current, $cols, $dayColumns);
            [$c, $u, $s, $err] = $this->persistEmployeeBlock($current, $creatorId, $dayColumns, $defaultIn, $defaultOut);
            $created += $c;
            $updated += $u;
            $skipped += $s;
            $errors = array_merge($errors, $err);
        }

        $snapshot = [
            'month' => $month,
            'day_headers' => array_values(array_map(fn ($ymd) => date('d-m-Y', strtotime($ymd)), $dayColumns)),
            'rows' => $registerRows,
            'totals' => $this->totalsFromRegisterRows($registerRows),
        ];
        $this->saveSnapshot($creatorId, $month, $snapshot);

        return [
            'month' => $month,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'employees' => count($registerRows),
        ];
    }

    /**
     * @return array{rows:array<int,array<string,mixed>>,day_headers:array<int,string>,totals:array<string,float|int>}|null
     */
    public static function loadSnapshot(int $creatorId, string $month): ?array
    {
        $path = self::snapshotPath($creatorId, $month);
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $decoded = json_decode(Storage::disk('local')->get($path), true);

        return is_array($decoded) && !empty($decoded['rows']) ? $decoded : null;
    }

    /**
     * @param  array{month:string,day_headers:array,rows:array,totals:array}  $snapshot
     */
    protected function saveSnapshot(int $creatorId, string $month, array $snapshot): void
    {
        Storage::disk('local')->put(self::snapshotPath($creatorId, $month), json_encode($snapshot));
    }

    protected static function snapshotPath(int $creatorId, string $month): string
    {
        return 'vic_registers/' . $creatorId . '/' . $month . '.json';
    }

    /**
     * @param  array<int,mixed>  $header
     * @return array{0:?int,1:array<int,mixed>}
     */
    protected function findHeaderRow(array $rows): array
    {
        foreach ($rows as $idx => $row) {
            if ($idx > 20) {
                break;
            }
            $keys = [];
            foreach ($row as $cell) {
                $keys[] = $this->headerKey($cell);
            }
            $hasName = in_array('employee_name', $keys, true) || in_array('name', $keys, true);
            $hasCode = in_array('employee_code', $keys, true) || in_array('emp_code', $keys, true);
            $days = $this->parseDayColumns($row);
            if (($hasName || $hasCode) && count($days) >= 5) {
                return [$idx, $row];
            }
        }

        return [null, []];
    }

    /**
     * @param  array<int,mixed>  $header
     * @return array<int,string> col => Y-m-d
     */
    protected function parseDayColumns(array $header): array
    {
        $skip = [
            'present', 'absent', 'half_day', 'miss_punch', 'week_off', 'holiday',
            'approved_leave', 'pending_leave', 'approved_outduty', 'pending_outduty',
            'sr_no', 'sr', 'employee_code', 'employee_name', 'employee_number',
            'joining_date', 'branch', 'department', 'designation', 'division',
            'working_area', 'project', 'name', 'status',
        ];
        $days = [];
        foreach ($header as $col => $raw) {
            $key = $this->headerKey($raw);
            if ($key === '' || in_array($key, $skip, true)) {
                continue;
            }
            $date = $this->parseDateCell($raw, null);
            if ($date) {
                $days[(int) $col] = $date;
            }
        }

        return $days;
    }

    /**
     * @param  array<int,array<int,mixed>>  $rows
     * @param  array<int,string>  $dayColumns
     */
    protected function findLabelColumn(array $rows, int $headerRowIndex, array $dayColumns): ?int
    {
        $wanted = ['status', 'shift', 'in', 'out', 'working_hours', 'overtime_hours', 'overtime'];
        $scores = [];
        $scanUntil = min(count($rows), $headerRowIndex + 40);
        for ($r = $headerRowIndex + 1; $r < $scanUntil; $r++) {
            foreach ($rows[$r] as $col => $cell) {
                if (isset($dayColumns[(int) $col])) {
                    continue;
                }
                $label = $this->normalizeLabel($cell);
                if (in_array($label, $wanted, true)) {
                    $scores[(int) $col] = ($scores[(int) $col] ?? 0) + 1;
                }
            }
        }
        if ($scores === []) {
            return null;
        }
        arsort($scores);

        return (int) array_key_first($scores);
    }

    /**
     * @param  array<int,mixed>  $header
     * @param  array<int,string>  $aliases
     */
    protected function findHeaderColumn(array $header, array $aliases): ?int
    {
        foreach ($header as $col => $raw) {
            if (in_array($this->headerKey($raw), $aliases, true)) {
                return (int) $col;
            }
        }

        return null;
    }

    /**
     * @param  array<int,mixed>  $header
     * @return array<string,?int>
     */
    protected function identityColumns(array $header): array
    {
        return [
            'sr' => $this->findHeaderColumn($header, ['sr_no', 'sr', 'sno']),
            'code' => $this->findHeaderColumn($header, ['employee_code', 'emp_code', 'emp_id', 'employee_id', 'code']),
            'name' => $this->findHeaderColumn($header, ['employee_name', 'name', 'emp_name']),
            'number' => $this->findHeaderColumn($header, ['employee_number', 'mobile_number', 'mobile', 'phone']),
            'doj' => $this->findHeaderColumn($header, ['joining_date', 'doj', 'date_of_joining']),
            'branch' => $this->findHeaderColumn($header, ['branch']),
            'department' => $this->findHeaderColumn($header, ['department']),
            'designation' => $this->findHeaderColumn($header, ['designation']),
            'division' => $this->findHeaderColumn($header, ['division']),
            'working_area' => $this->findHeaderColumn($header, ['working_area']),
            'project' => $this->findHeaderColumn($header, ['project']),
            'present' => $this->findHeaderColumn($header, ['present']),
            'absent' => $this->findHeaderColumn($header, ['absent']),
            'half_day' => $this->findHeaderColumn($header, ['half_day']),
            'miss_punch' => $this->findHeaderColumn($header, ['miss_punch']),
            'week_off' => $this->findHeaderColumn($header, ['week_off']),
            'holiday' => $this->findHeaderColumn($header, ['holiday']),
            'approved_leave' => $this->findHeaderColumn($header, ['approved_leave']),
            'pending_leave' => $this->findHeaderColumn($header, ['pending_leave']),
            'approved_outduty' => $this->findHeaderColumn($header, ['approved_outduty']),
            'pending_outduty' => $this->findHeaderColumn($header, ['pending_outduty']),
        ];
    }

    /**
     * @param  array{row:int,code:string,name:string,status:array,shift:array,in:array,out:array,hours:array,overtime:array}  $block
     * @param  array<string,?int>  $cols
     * @param  array<int,string>  $dayColumns
     * @return array<string,mixed>
     */
    protected function blockToRegisterRow(array $block, array $cols, array $dayColumns): array
    {
        $statusRow = $block['status'] ?? [];
        $days = [];
        foreach ($dayColumns as $col => $dateYmd) {
            $days[] = [
                'code' => $this->displayCell($block['status'][$col] ?? '', true),
                'shift' => $this->displayCell($block['shift'][$col] ?? ''),
                'in' => $this->displayDateTimeCell($block['in'][$col] ?? '', $dateYmd),
                'out' => $this->displayDateTimeCell($block['out'][$col] ?? '', $dateYmd),
                'hours' => $this->displayCell($block['hours'][$col] ?? ''),
                'ot' => $this->displayCell($block['overtime'][$col] ?? '', false, true),
            ];
        }

        return [
            'sr' => $this->displayCell($this->colValue($statusRow, $cols['sr'])),
            'code' => $this->displayCell($this->colValue($statusRow, $cols['code']) ?: $block['code']),
            'name' => $this->displayCell($this->colValue($statusRow, $cols['name']) ?: $block['name']),
            'number' => $this->displayCell($this->colValue($statusRow, $cols['number'])),
            'doj' => $this->displayDateCell($this->colValue($statusRow, $cols['doj'])),
            'branch' => $this->displayCell($this->colValue($statusRow, $cols['branch'])),
            'department' => $this->displayCell($this->colValue($statusRow, $cols['department'])),
            'designation' => $this->displayCell($this->colValue($statusRow, $cols['designation'])),
            'division' => $this->displayCell($this->colValue($statusRow, $cols['division'])),
            'working_area' => $this->displayCell($this->colValue($statusRow, $cols['working_area'])),
            'project' => $this->displayCell($this->colValue($statusRow, $cols['project'])),
            'counts' => [
                'present' => $this->displayCell($this->colValue($statusRow, $cols['present'])),
                'absent' => $this->displayCell($this->colValue($statusRow, $cols['absent'])),
                'half_day' => $this->displayCell($this->colValue($statusRow, $cols['half_day'])),
                'miss_punch' => $this->displayCell($this->colValue($statusRow, $cols['miss_punch'])),
                'week_off' => $this->displayCell($this->colValue($statusRow, $cols['week_off'])),
                'holiday' => $this->displayCell($this->colValue($statusRow, $cols['holiday'])),
                'approved_leave' => $this->displayCell($this->colValue($statusRow, $cols['approved_leave'])),
                'pending_leave' => $this->displayCell($this->colValue($statusRow, $cols['pending_leave'])),
                'approved_outduty' => $this->displayCell($this->colValue($statusRow, $cols['approved_outduty'])),
                'pending_outduty' => $this->displayCell($this->colValue($statusRow, $cols['pending_outduty'])),
            ],
            'days' => $days,
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<string,float|int>
     */
    protected function totalsFromRegisterRows(array $rows): array
    {
        $totals = [
            'present' => 0,
            'leave' => 0,
            'overtime_hours' => 0,
            'early_hours' => 0,
            'late_hours' => 0,
        ];
        foreach ($rows as $row) {
            $totals['present'] += (float) ($row['counts']['present'] ?? 0);
            $totals['leave'] += (float) ($row['counts']['approved_leave'] ?? 0);
        }

        return $totals;
    }

    protected function colValue(array $row, ?int $col)
    {
        return $col === null ? '' : ($row[$col] ?? '');
    }

    protected function displayCell($value, bool $status = false, bool $ot = false): string
    {
        if ($value === null || $value === '') {
            return $ot ? '0' : '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $status ? $value->format('d-m-Y') : $value->format('d-m-Y H:i:s');
        }
        $text = trim((string) $value);
        if ($status && strcasecmp($text, 'status') === 0) {
            return '';
        }

        return $text;
    }

    protected function displayDateCell($value): string
    {
        $date = $this->parseDateCell($value, null);
        if ($date) {
            return date('d-m-Y', strtotime($date));
        }

        return $this->displayCell($value);
    }

    protected function displayDateTimeCell($value, string $dateYmd): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d-m-Y H:i:s');
        }
        $time = $this->parseTimeCell($value);
        $text = trim((string) $value);
        if (preg_match('/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/', $text) && preg_match('/\d{1,2}:\d{2}/', $text)) {
            return $text;
        }
        if ($time) {
            return date('d-m-Y', strtotime($dateYmd)) . ' ' . $time;
        }

        return $text;
    }

    /**
     * @param  array{row:int,code:string,name:string,status:array,in:array,out:array,overtime:array}  $block
     * @param  array<int,string>  $dayColumns
     * @return array{0:int,1:int,2:int,3:array<int,string>}
     */
    protected function persistEmployeeBlock(array $block, int $creatorId, array $dayColumns, string $defaultIn, string $defaultOut): array
    {
        $employee = $this->resolveEmployee($block['code'], $block['name'], $creatorId);
        if (!$employee) {
            $label = trim($block['code'] . ' ' . $block['name']) ?: ('row ' . $block['row']);

            return [0, 0, 1, ['Row ' . $block['row'] . ': employee not found (' . $label . ')']];
        }

        $created = 0;
        $updated = 0;
        foreach ($dayColumns as $col => $dateYmd) {
            $code = $this->normalizeStatusCode((string) ($block['status'][$col] ?? ''));
            if ($code === '' || $code === '-') {
                continue;
            }

            $clockIn = $this->parseTimeCell($block['in'][$col] ?? null) ?: '00:00:00';
            $clockOut = $this->parseTimeCell($block['out'][$col] ?? null) ?: '00:00:00';
            $overtime = $this->parseHourCell($block['overtime'][$col] ?? null);

            [$status, $lateMark] = $this->mapStatus($code);
            if (in_array($status, ['Absent', 'Leave', 'Week Off'], true)) {
                $clockIn = '00:00:00';
                $clockOut = '00:00:00';
                $overtime = '00:00:00';
            } elseif ($status === 'Present' || $status === 'Half Day') {
                if ($clockIn === '00:00:00') {
                    $clockIn = $defaultIn;
                }
                if ($clockOut === '00:00:00' && $status === 'Present') {
                    $clockOut = $defaultOut;
                }
            }

            $existing = AttendanceEmployee::where('employee_id', $employee->id)->where('date', $dateYmd)->first();
            $isUpdate = (bool) $existing;
            $att = $existing ?: new AttendanceEmployee();
            $att->employee_id = $employee->id;
            $att->created_by = $creatorId;
            $att->date = $dateYmd;
            $att->status = $status;
            $att->clock_in = $clockIn;
            $att->clock_out = $clockOut;
            $att->total_rest = $att->total_rest ?: '00:00:00';
            $att->late = $att->late ?: '00:00:00';
            $att->early_leaving = $att->early_leaving ?: '00:00:00';
            $att->overtime = $overtime;
            $att->late_mark = $lateMark;
            if ($status === 'Absent') {
                $att->deduction_units = 1;
            } elseif ($status === 'Half Day') {
                $att->deduction_units = 0.5;
            } else {
                $att->deduction_units = $att->deduction_units ?: 0;
            }
            $att->save();
            $isUpdate ? $updated++ : $created++;
        }

        return [$created, $updated, 0, []];
    }

    protected function resolveEmployee(string $code, string $name, int $creatorId): ?Employee
    {
        $query = Employee::where('created_by', $creatorId);

        $empNum = (int) preg_replace('/[^0-9]/', '', $code);
        if ($empNum > 0) {
            $employee = (clone $query)->where('employee_id', $empNum)->first()
                ?: (clone $query)->where('id', $empNum)->first();
            if ($employee) {
                return $employee;
            }
        }

        $name = $this->normalizePersonName($name);
        if ($name === '') {
            return null;
        }

        $matches = (clone $query)->get()->filter(function (Employee $employee) use ($name) {
            return $this->normalizePersonName((string) $employee->name) === $name;
        });

        return $matches->count() === 1 ? $matches->first() : ($matches->first() ?: null);
    }

    protected function normalizePersonName(string $name): string
    {
        $name = strtoupper(trim($name));
        $name = preg_replace('/\b(MR|MRS|MS|MISS)\.?\b/', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }

    /**
     * @return array{0:string,1:int} status, late_mark
     */
    protected function mapStatus(string $code): array
    {
        $code = strtoupper(trim($code));
        $code = str_replace([' ', '.'], '', $code);

        if (in_array($code, ['P+LC', 'PLC', 'P+L'], true) || str_contains($code, '+LC')) {
            return ['Present', 1];
        }
        if (in_array($code, ['HFD', 'HD', 'HALFDAY'], true)) {
            return ['Half Day', 0];
        }
        if (in_array($code, ['WO', 'WEEKOFF', 'WEEKLYOFF', 'OFF'], true)) {
            return ['Week Off', 0];
        }
        if (in_array($code, ['MP', 'MISSPUNCH'], true)) {
            return ['Miss Punch', 0];
        }
        if (in_array($code, ['PL', 'CL', 'SL', 'EL', 'LEAVE'], true)) {
            return ['Leave', 0];
        }
        if (str_contains($code, 'LWP')) {
            return str_starts_with($code, 'P') ? ['Present', 0] : ['LWP', 0];
        }
        if (in_array($code, ['A', 'ABSENT'], true)) {
            return ['Absent', 0];
        }
        if (in_array($code, ['P', 'PRESENT'], true)) {
            return ['Present', 0];
        }
        if ($code === 'H' || $code === 'HOLIDAY') {
            return ['Week Off', 0];
        }

        return ['Present', 0];
    }

    protected function normalizeStatusCode(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || strcasecmp($raw, 'status') === 0) {
            return '';
        }

        return $raw;
    }

    protected function parseTimeCell($value): ?string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }
        if (is_numeric($value)) {
            $num = (float) $value;
            if ($num > 0 && $num < 1.5) {
                $seconds = (int) round($num * 86400);

                return gmdate('H:i:s', $seconds);
            }
            if ($num > 20000 && $num < 80000) {
                try {
                    return ExcelDate::excelToDateTimeObject($num)->format('H:i:s');
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        $raw = trim((string) $value);
        if (preg_match('/(\d{1,2}:\d{2}:\d{2})/', $raw, $m)) {
            return date('H:i:s', strtotime($m[1]));
        }
        if (preg_match('/(\d{1,2}:\d{2})/', $raw, $m)) {
            return date('H:i:s', strtotime($m[1]));
        }

        return null;
    }

    protected function parseHourCell($value): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '00:00:00';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }
        $raw = trim((string) $value);
        if (preg_match('/^(\d{1,3}):(\d{2})(?::(\d{2}))?$/', $raw, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) ($m[3] ?? 0));
        }
        if (is_numeric($raw)) {
            $hours = (int) $raw;

            return sprintf('%02d:00:00', $hours);
        }

        return '00:00:00';
    }

    protected function parseDateCell($value, ?string $monthHint): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            $num = (float) $value;
            if ($num > 20000 && $num < 80000) {
                try {
                    return ExcelDate::excelToDateTimeObject($num)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        $raw = trim((string) $value);
        $raw = preg_replace('/^(mon|tue|wed|thu|fri|sat|sun)[a-z]*[\s,]*/i', '', $raw) ?? $raw;
        $raw = trim($raw);
        if (preg_match('/(\d{1,2}[-\/.]\d{1,2}[-\/.]\d{4})/', $raw, $m)) {
            $raw = $m[1];
        } elseif (preg_match('/(\d{4}[-\/.]\d{1,2}[-\/.]\d{1,2})/', $raw, $m)) {
            $raw = $m[1];
        }

        foreach (['d-m-Y', 'd/m/Y', 'd.m.Y', 'Y-m-d', 'Y/m/d'] as $fmt) {
            $dt = \DateTime::createFromFormat('!' . $fmt, $raw);
            if ($dt instanceof \DateTime) {
                $year = (int) $dt->format('Y');
                if ($year >= 1990 && $year <= 2100) {
                    return $dt->format('Y-m-d');
                }
            }
        }

        try {
            $parsed = Carbon::parse($raw);
            $year = (int) $parsed->format('Y');
            if ($year >= 1990 && $year <= 2100) {
                return $parsed->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * @param  array<int,string>  $dayColumns
     */
    protected function monthFromDayColumns(array $dayColumns): string
    {
        $first = reset($dayColumns);

        return $first ? substr($first, 0, 7) : date('Y-m');
    }

    protected function headerKey($value): string
    {
        $raw = trim((string) $value);
        $raw = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $raw) ?? '');

        return trim($raw, '_');
    }

    protected function normalizeLabel($value): string
    {
        $key = $this->headerKey($value);
        if ($key === 'working_hours' || $key === 'work_hours') {
            return 'working_hours';
        }
        if (in_array($key, ['overtime_hours', 'overtime', 'ot'], true)) {
            return 'overtime';
        }

        return $key;
    }

    protected function rowHasValue(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return true;
            }
        }

        return false;
    }
}
