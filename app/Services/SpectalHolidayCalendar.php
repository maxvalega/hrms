<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\HolidaySetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Spectal 2026 public + optional holiday calendar from company policy.
 */
class SpectalHolidayCalendar
{
    /**
     * Mandatory public holidays (office closed).
     *
     * @return array<int, array{occasion:string,start_date:string,end_date:string,day_type:string,is_optional:bool}>
     */
    public static function publicHolidays2026(): array
    {
        return [
            ['occasion' => 'New Year', 'start_date' => '2026-01-01', 'end_date' => '2026-01-01', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Republic Day', 'start_date' => '2026-01-26', 'end_date' => '2026-01-26', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Holi', 'start_date' => '2026-03-04', 'end_date' => '2026-03-04', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Independence Day', 'start_date' => '2026-08-15', 'end_date' => '2026-08-15', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Raksha Bandhan', 'start_date' => '2026-08-28', 'end_date' => '2026-08-28', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Gandhi Jayanti', 'start_date' => '2026-10-02', 'end_date' => '2026-10-02', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Dussehra', 'start_date' => '2026-10-20', 'end_date' => '2026-10-20', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Haryana Day', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Diwali (Second Half)', 'start_date' => '2026-11-06', 'end_date' => '2026-11-06', 'day_type' => 'second_half', 'is_optional' => false],
            ['occasion' => 'Diwali', 'start_date' => '2026-11-07', 'end_date' => '2026-11-10', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => 'Christmas', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'day_type' => 'full_day', 'is_optional' => false],
            ['occasion' => "New Year's Eve", 'start_date' => '2026-12-31', 'end_date' => '2026-12-31', 'day_type' => 'full_day', 'is_optional' => false],
        ];
    }

    /**
     * Optional public holidays: employee may take any 2, with 2 weeks' notice to RM.
     *
     * @return array<int, array{occasion:string,start_date:string,end_date:string,day_type:string,is_optional:bool}>
     */
    public static function optionalHolidays2026(): array
    {
        return [
            ['occasion' => 'Eid-ul-Fitr', 'start_date' => '2026-03-21', 'end_date' => '2026-03-21', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Ram Navami', 'start_date' => '2026-03-26', 'end_date' => '2026-03-26', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Good Friday', 'start_date' => '2026-04-03', 'end_date' => '2026-04-03', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Dr. Ambedkar Jayanti', 'start_date' => '2026-04-14', 'end_date' => '2026-04-14', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Eid-al-Adha (Bakrid)', 'start_date' => '2026-05-27', 'end_date' => '2026-05-27', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Muharram', 'start_date' => '2026-06-26', 'end_date' => '2026-06-26', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Onam & Eid-e-Milad', 'start_date' => '2026-08-26', 'end_date' => '2026-08-26', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Janmashtami', 'start_date' => '2026-09-04', 'end_date' => '2026-09-04', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Ganesh Chaturthi', 'start_date' => '2026-09-14', 'end_date' => '2026-09-14', 'day_type' => 'full_day', 'is_optional' => true],
            ['occasion' => 'Guru Nanak Jayanti', 'start_date' => '2026-11-24', 'end_date' => '2026-11-24', 'day_type' => 'full_day', 'is_optional' => true],
        ];
    }

    public static function all2026(): array
    {
        return array_merge(self::publicHolidays2026(), self::optionalHolidays2026());
    }

    public static function syncForCreator(int $createdBy, int $year = 2026): int
    {
        if (!Schema::hasTable('holidays') || !Schema::hasColumn('holidays', 'occasion')) {
            return 0;
        }

        $canonical = self::all2026();
        $keepKeys = [];
        $count = 0;
        foreach ($canonical as $row) {
            $keepKeys[] = $row['start_date'] . '|' . $row['occasion'];
            $payload = [
                'end_date' => $row['end_date'],
                'created_by' => $createdBy,
            ];
            if (Schema::hasColumn('holidays', 'is_optional')) {
                $payload['is_optional'] = $row['is_optional'] ? 1 : 0;
            }
            if (Schema::hasColumn('holidays', 'day_type')) {
                $payload['day_type'] = $row['day_type'];
            }

            Holiday::updateOrCreate(
                [
                    'created_by' => $createdBy,
                    'start_date' => $row['start_date'],
                    'occasion' => $row['occasion'],
                ],
                $payload
            );
            $count++;
        }

        $existing = Holiday::where('created_by', $createdBy)->whereYear('start_date', $year)->get();
        foreach ($existing as $holiday) {
            $key = $holiday->start_date . '|' . $holiday->occasion;
            if (!in_array($key, $keepKeys, true)) {
                $holiday->delete();
            }
        }

        if (Schema::hasTable('holiday_settings')) {
            try {
                $settings = HolidaySetting::query()->first();
                $data = [
                    'enable_optional_holidays' => true,
                    'max_optional_holidays_per_year' => 2,
                    'require_optional_holiday_approval' => true,
                ];
                if ($settings) {
                    $settings->fill($data)->save();
                }
            } catch (\Throwable $e) {
                // settings table may not be fully migrated
            }
        }

        return $count;
    }

    /**
     * @return array<int, int>
     */
    public static function likelySpectalCreatorIds(): array
    {
        $ids = User::query()
            ->where(function ($q) {
                $q->where('email', 'like', '%spectal%')
                    ->orWhere('name', 'like', '%Spectal%')
                    ->orWhere('name', 'like', '%Gandhy%');
            })
            ->pluck('id')
            ->all();

        $companyIds = User::where('type', 'company')->pluck('id')->all();
        if (empty($ids) && !empty($companyIds)) {
            return array_map('intval', $companyIds);
        }

        $creatorIds = User::whereIn('id', $ids)->pluck('created_by')->all();
        $all = array_merge($ids, $creatorIds, $companyIds);

        return array_values(array_unique(array_filter(array_map('intval', $all))));
    }
}
