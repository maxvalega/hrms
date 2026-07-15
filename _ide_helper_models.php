<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Coingate{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coingate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coingate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coingate query()
 */
	class Coingate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $account_name
 * @property float $initial_balance
 * @property string $account_number
 * @property string $branch_code
 * @property string $bank_branch
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereBankBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereBranchCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereInitialBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountList whereUpdatedAt($value)
 */
	class AccountList extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $active_seconds
 * @property int $idle_seconds
 * @property int $keystrokes
 * @property int $mouse_events
 * @property string|null $active_window
 * @property string|null $active_app
 * @property string|null $active_url
 * @property int|null $productivity_score
 * @property string|null $hostname
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $captured_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereActiveApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereActiveSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereActiveUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereActiveWindow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereCapturedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereHostname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereIdleSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereKeystrokes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereMouseEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereProductivityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgentActivity whereUserId($value)
 */
	class AgentActivity extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $allowance_option
 * @property string $title
 * @property float $amount
 * @property string|null $type
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereAllowanceOption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Allowance whereUpdatedAt($value)
 */
	class Allowance extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AllowanceOption whereUpdatedAt($value)
 */
	class AllowanceOption extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string $start_date
 * @property string $end_date
 * @property int $branch_id
 * @property string $department_id
 * @property string $employee_id
 * @property string $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereUpdatedAt($value)
 */
	class Announcement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $announcement_id
 * @property int $employee_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee whereAnnouncementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementEmployee whereUpdatedAt($value)
 */
	class AnnouncementEmployee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch
 * @property int $employee
 * @property string|null $rating
 * @property string $appraisal_date
 * @property int $customer_experience
 * @property int $marketing
 * @property int $administration
 * @property int $professionalism
 * @property int $integrity
 * @property int $attendance
 * @property string|null $remark
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branches
 * @property-read \App\Models\Employee|null $employees
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereAdministration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereAppraisalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereAttendance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereCustomerExperience($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereEmployee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereIntegrity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereMarketing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereProfessionalism($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereUpdatedAt($value)
 */
	class Appraisal extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $employee_id
 * @property string $name
 * @property string $purchase_date
 * @property string $supported_date
 * @property float $amount
 * @property string|null $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereSupportedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereUpdatedAt($value)
 */
	class Asset extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $device_id
 * @property string|null $active_app
 * @property string|null $active_window_title
 * @property int $idle_seconds
 * @property int $keyboard_count
 * @property int $mouse_count
 * @property \Illuminate\Support\Carbon $captured_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\AtDevice|null $device
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereActiveApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereActiveWindowTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereCapturedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereIdleSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereKeyboardCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereMouseCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtActivityLog whereUserId($value)
 */
	class AtActivityLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $device_id
 * @property string $app_name
 * @property string|null $window_title
 * @property int $duration_seconds
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\AtDevice|null $device
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereAppName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtAppUsageLog whereWindowTitle($value)
 */
	class AtAppUsageLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $device_id
 * @property \Illuminate\Support\Carbon $work_date
 * @property int $active_seconds
 * @property int $idle_seconds
 * @property int $total_screenshots
 * @property string|null $most_used_app
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AtDevice|null $device
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereActiveSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereIdleSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereMostUsedApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereTotalScreenshots($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDailySummary whereWorkDate($value)
 */
	class AtDailySummary extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $created_by
 * @property string $device_uuid
 * @property string $device_name
 * @property string|null $os
 * @property string|null $ip_address
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AtActivityLog> $activity
 * @property-read int|null $activity_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AtScreenshot> $screenshots
 * @property-read int|null $screenshots_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereDeviceUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtDevice whereUserId($value)
 */
	class AtDevice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $device_id
 * @property string $image_path
 * @property string|null $active_app
 * @property string|null $active_window_title
 * @property int $size_bytes
 * @property \Illuminate\Support\Carbon $captured_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\AtDevice|null $device
 * @property-read string $url
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereActiveApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereActiveWindowTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereCapturedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtScreenshot whereUserId($value)
 */
	class AtScreenshot extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $device_id
 * @property string $status
 * @property string|null $reason
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AtDevice|null $device
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtStopRequest whereUserId($value)
 */
	class AtStopRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $date
 * @property string $status
 * @property string $clock_in
 * @property string $clock_out
 * @property string $late
 * @property int $late_mark
 * @property string $early_leaving
 * @property int $early_mark
 * @property string $overtime
 * @property int $less_hours_mark
 * @property string $total_rest
 * @property numeric $deduction_units
 * @property int $created_by
 * @property string|null $device_type
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $address
 * @property string|null $photo
 * @property int|null $photo_verified
 * @property string|null $device_type_out
 * @property string|null $latitude_out
 * @property string|null $longitude_out
 * @property string|null $address_out
 * @property string|null $photo_out
 * @property int|null $photo_out_verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $facial_verification_photo
 * @property string $facial_verification_status
 * @property numeric $facial_verification_confidence
 * @property int $professional_days_at_attendance Days employed at time of attendance
 * @property int $professional_months_at_attendance Months employed at time of attendance
 * @property int $professional_years_at_attendance Years employed at time of attendance
 * @property int $in_probation_at_attendance Was employee in probation at time of attendance
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employee|null $employees
 * @property-read mixed $photo_out_url
 * @property-read mixed $photo_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereAddressOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereDeductionUnits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereDeviceTypeOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereEarlyLeaving($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereEarlyMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereFacialVerificationConfidence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereFacialVerificationPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereFacialVerificationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereInProbationAtAttendance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereLate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereLateMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereLatitudeOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereLessHoursMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereLongitudeOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereOvertime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee wherePhotoOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee wherePhotoOutVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee wherePhotoVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereProfessionalDaysAtAttendance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereProfessionalMonthsAtAttendance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereProfessionalYearsAtAttendance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereTotalRest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceEmployee whereUpdatedAt($value)
 */
	class AttendanceEmployee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $attendance_employee_id
 * @property int $employee_id
 * @property int $manager_employee_id
 * @property string|null $requested_status
 * @property string|null $requested_clock_in
 * @property string|null $requested_clock_out
 * @property string|null $reason
 * @property string $status
 * @property string|null $manager_comment
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AttendanceEmployee|null $attendance
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employee|null $manager
 * @property-read \App\Models\Employee|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereAttendanceEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereManagerComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereManagerEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereRequestedClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereRequestedClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereRequestedStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequest whereUpdatedAt($value)
 */
	class AttendanceModificationRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $attendance_modification_request_id
 * @property int $attendance_employee_id
 * @property int|null $employee_id
 * @property int|null $manager_employee_id
 * @property string $action
 * @property array<array-key, mixed>|null $old_snapshot
 * @property array<array-key, mixed>|null $new_snapshot
 * @property string|null $remarks
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AttendanceModificationRequest|null $swipeRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereAttendanceEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereAttendanceModificationRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereManagerEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereNewSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereOldSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceModificationRequestLog whereUpdatedAt($value)
 */
	class AttendanceModificationRequestLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceSetting query()
 */
	class AttendanceSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $award_type
 * @property string $date
 * @property string $gift
 * @property string $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AwardType|null $awardType
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereAwardType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereGift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereUpdatedAt($value)
 */
	class Award extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AwardType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AwardType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AwardType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AwardType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AwardType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AwardType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AwardType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AwardType whereUpdatedAt($value)
 */
	class AwardType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $screenshot_path
 * @property string|null $page_url
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $captured_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $screenshot_url
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot whereCapturedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot wherePageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot whereScreenshotPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackgroundScreenshot whereUserId($value)
 */
	class BackgroundScreenshot extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $candidate_id
 * @property string $check_type
 * @property string $item_label
 * @property string $status
 * @property string|null $notes
 * @property string|null $document_path
 * @property \Illuminate\Support\Carbon|null $initiated_on
 * @property \Illuminate\Support\Carbon|null $completed_on
 * @property int|null $verified_by_user_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\JobApplication|null $candidate
 * @property-read \App\Models\User|null $verifier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereCheckType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereCompletedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereDocumentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereInitiatedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereItemLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BgvCheck whereVerifiedByUserId($value)
 */
	class BgvCheck extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereUpdatedAt($value)
 */
	class Branch extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $favorite_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChFavorite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChFavorite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChFavorite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChFavorite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChFavorite whereFavoriteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChFavorite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChFavorite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChFavorite whereUserId($value)
 */
	class ChFavorite extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $type
 * @property int $from_id
 * @property int $to_id
 * @property string|null $body
 * @property string|null $attachment
 * @property int $seen
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereSeen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChMessage whereUpdatedAt($value)
 */
	class ChMessage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property int $owner_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatGroupMember> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatGroupMessage> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User $owner
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroup whereUpdatedAt($value)
 */
	class ChatGroup extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $chat_group_id
 * @property int $user_id
 * @property int|null $added_by
 * @property string|null $last_read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChatGroup $group
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember whereChatGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember whereLastReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMember whereUserId($value)
 */
	class ChatGroupMember extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $chat_group_id
 * @property int $user_id
 * @property string $message
 * @property string $message_type
 * @property string|null $voice_path
 * @property string|null $file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChatGroup $group
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereChatGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereMessageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatGroupMessage whereVoicePath($value)
 */
	class ChatGroupMessage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $title
 * @property float $amount
 * @property string|null $type
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereUpdatedAt($value)
 */
	class Commission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch
 * @property string $title
 * @property string $description
 * @property string|null $attachment
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branches
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy whereAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyPolicy whereUpdatedAt($value)
 */
	class CompanyPolicy extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property float $days
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon $earned_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereEarnedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompensatoryLeave whereUpdatedAt($value)
 */
	class CompensatoryLeave extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Competencies whereUpdatedAt($value)
 */
	class Competencies extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $complaint_from
 * @property int $complaint_against
 * @property string $title
 * @property string $complaint_date
 * @property string $description
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereComplaintAgainst($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereComplaintDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereComplaintFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Complaint whereUpdatedAt($value)
 */
	class Complaint extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $subject
 * @property int $employee_name
 * @property float|null $value
 * @property int $type
 * @property string $start_date
 * @property string $end_date
 * @property string|null $notes
 * @property string $status
 * @property string|null $description
 * @property string|null $contract_description
 * @property string|null $employee_signature
 * @property string|null $company_signature
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ContractAttechment|null $ContractAttechment
 * @property-read \App\Models\ContractComment|null $ContractComment
 * @property-read \App\Models\ContractNote|null $ContractNote
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContractComment> $comment
 * @property-read int|null $comment_count
 * @property-read \App\Models\ContractType|null $contract_type
 * @property-read \App\Models\User|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContractAttechment> $files
 * @property-read int|null $files_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContractNote> $note
 * @property-read int|null $note_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereCompanySignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereContractDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereEmployeeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereEmployeeSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereValue($value)
 */
	class Contract extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $contract_id
 * @property string $user_id
 * @property string $files
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment whereContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment whereFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractAttechment whereUserId($value)
 */
	class ContractAttechment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $contract_id
 * @property string $user_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment whereContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractComment whereUserId($value)
 */
	class ContractComment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $contract_id
 * @property int $user_id
 * @property string $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote whereContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractNote whereUserId($value)
 */
	class ContractNote extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContractType whereUpdatedAt($value)
 */
	class ContractType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property float $discount
 * @property int $limit
 * @property string|null $description
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUpdatedAt($value)
 */
	class Coupon extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $question
 * @property string|null $is_required
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomQuestion whereUpdatedAt($value)
 */
	class CustomQuestion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $candidate_id
 * @property int $user_id
 * @property string $note
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\JobApplication|null $candidate
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNote whereUserId($value)
 */
	class DecisionNote extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeductionOption whereUpdatedAt($value)
 */
	class DeductionOption extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 */
	class Department extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $account_id
 * @property float $amount
 * @property string $date
 * @property int $income_category_id
 * @property int $payer_id
 * @property int $payment_type_id
 * @property string|null $referal_id
 * @property string|null $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AccountList|null $accounts
 * @property-read \App\Models\IncomeType|null $income_categorys
 * @property-read \App\Models\Payer|null $payers
 * @property-read \App\Models\PaymentType|null $payment_types
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereIncomeCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit wherePayerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit wherePaymentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereReferalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deposit whereUpdatedAt($value)
 */
	class Deposit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $branch_id
 * @property int $department_id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\Department|null $department
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Designation whereUpdatedAt($value)
 */
	class Designation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $is_required
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 */
	class Document extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $role
 * @property string $document
 * @property string|null $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload whereDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DucumentUpload whereUpdatedAt($value)
 */
	class DucumentUpload extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $from
 * @property string|null $slug
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereUpdatedAt($value)
 */
	class EmailTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $parent_id
 * @property string $lang
 * @property string $subject
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Languages|null $language
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplateLang whereUpdatedAt($value)
 */
	class EmailTemplateLang extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $dob
 * @property string $gender
 * @property Employee|null $phone
 * @property string $address
 * @property string|null $family_details
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $emergency_contact_relation
 * @property string|null $blood_group
 * @property string|null $insurance_id
 * @property string|null $insurer_name
 * @property string|null $insurance_contact_person
 * @property string|null $hobbies
 * @property string|null $food_type
 * @property string|null $education
 * @property string|null $present_address
 * @property string|null $permanent_address
 * @property string|null $present_country
 * @property string|null $present_state
 * @property string|null $present_city
 * @property string|null $present_pincode
 * @property string|null $permanent_country
 * @property string|null $permanent_state
 * @property string|null $permanent_city
 * @property string|null $permanent_pincode
 * @property string|null $department_hierarchy
 * @property int|null $reporting_manager_id
 * @property int|null $hod_id
 * @property int|null $management_id
 * @property int|null $mentor_buddy_id
 * @property string $email
 * @property string $password
 * @property string $employee_id
 * @property int $branch_id
 * @property int $department_id
 * @property int $designation_id
 * @property string|null $company_doj
 * @property string|null $documents
 * @property string|null $account_holder_name
 * @property string|null $account_number
 * @property string|null $bank_name
 * @property string|null $bank_identifier_code
 * @property string|null $branch_location
 * @property string|null $tax_payer_id
 * @property string|null $aadhar_number
 * @property string|null $pan_number
 * @property string|null $uan_number
 * @property string|null $esic_number
 * @property int|null $salary_type
 * @property int|null $employee_type_id
 * @property numeric|null $monthly_stipend
 * @property int|null $account_type
 * @property float $salary
 * @property int $is_active
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $shift_id
 * @property string|null $shift_type
 * @property string|null $fingerprint_template
 * @property string|null $fingerprint_enrolled_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Designation|null $designation
 * @property-read \App\Models\EmployeeType|null $employeeType
 * @property-read Employee|null $hod
 * @property-read Employee|null $management
 * @property-read \App\Models\PaySlip|null $paySlip
 * @property-read Employee|null $reportingManager
 * @property-read \App\Models\PayslipType|null $salaryType
 * @property-read \App\Models\Shift|null $shift
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAadharNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountHolderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankIdentifierCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBloodGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBranchLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCompanyDoj($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartmentHierarchy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDesignationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDocuments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEducation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactRelation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEsicNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFamilyDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFingerprintEnrolledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFingerprintTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFoodType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereHobbies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereHodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereInsuranceContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereInsuranceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereInsurerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereManagementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMentorBuddyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMonthlyStipend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePanNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePermanentAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePermanentCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePermanentCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePermanentPincode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePermanentState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePresentAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePresentCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePresentCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePresentPincode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePresentState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereReportingManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSalaryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereShiftType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTaxPayerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUanNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUserId($value)
 */
	class Employee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $document_id
 * @property string $document_value
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereDocumentValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereUpdatedAt($value)
 */
	class EmployeeDocument extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property numeric $ctc
 * @property numeric $basic_percentage
 * @property int $is_pf_enabled
 * @property int $is_esic_enabled
 * @property int $overtime_enabled
 * @property string $overtime_formula
 * @property int|null $structure_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereBasicPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereCtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereIsEsicEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereIsPfEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereOvertimeEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereOvertimeFormula($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereUpdatedAt($value)
 */
	class EmployeeSalary extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $state_id
 * @property bool $pf_enabled
 * @property bool $esic_enabled
 * @property bool $pt_enabled
 * @property bool $lwf_enabled
 * @property string|null $uan_number
 * @property string|null $esic_number
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereEsicEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereEsicNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereLwfEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig wherePfEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig wherePtEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereUanNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStatutoryConfig whereUpdatedAt($value)
 */
	class EmployeeStatutoryConfig extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string $status
 * @property bool $is_anonymous
 * @property array<array-key, mixed>|null $department_ids
 * @property array<array-key, mixed>|null $audience_rules
 * @property string $frequency
 * @property \Illuminate\Support\Carbon|null $last_sent_at
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyAlert> $alerts
 * @property-read int|null $alerts_count
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyQuestion> $questions
 * @property-read int|null $questions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyResponse> $responses
 * @property-read int|null $responses_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereAudienceRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereDepartmentIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereIsAnonymous($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereLastSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSurvey whereUpdatedAt($value)
 */
	class EmployeeSurvey extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $ctc_applicable
 * @property bool $pf_applicable
 * @property bool $esic_applicable
 * @property bool $pt_applicable
 * @property bool $lwf_applicable
 * @property bool $tds_applicable
 * @property float $flat_tds_rate
 * @property bool $attendance_prorata
 * @property bool $is_system
 * @property bool $is_active
 * @property int $sort_order
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereAttendanceProrata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereCtcApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereEsicApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereFlatTdsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereLwfApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType wherePfApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType wherePtApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereTdsApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeType whereUpdatedAt($value)
 */
	class EmployeeType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch_id
 * @property string $department_id
 * @property string $employee_id
 * @property string $title
 * @property string $start_date
 * @property string $end_date
 * @property string $color
 * @property string|null $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 */
	class Event extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $event_id
 * @property int $employee_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventEmployee whereUpdatedAt($value)
 */
	class EventEmployee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tax_declaration_id
 * @property string $section_code
 * @property string $exemption_type
 * @property float $amount
 * @property string|null $proof_file
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail whereExemptionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail whereProofFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail whereSectionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail whereTaxDeclarationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExemptionDetail whereUpdatedAt($value)
 */
	class ExemptionDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $resignation_id
 * @property int $user_id
 * @property string $item_name
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int|null $completed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $completedBy
 * @property-read \App\Models\ExitResignation $resignation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereResignationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitChecklistItem whereUserId($value)
 */
	class ExitChecklistItem extends \Eloquent {}
}

namespace App\Models{
/**
 * Exit Management — main resignation/exit record.
 *
 * Workflow: pending → manager_approved → hr_approved → completed.
 * Rejection at any stage closes the flow with status manager_rejected / hr_rejected.
 *
 * @property int $id
 * @property int $user_id
 * @property int $created_by
 * @property string $reason
 * @property \Illuminate\Support\Carbon $resignation_date
 * @property \Illuminate\Support\Carbon $last_working_day
 * @property int|null $notice_period_days
 * @property string $status
 * @property int|null $manager_id
 * @property \Illuminate\Support\Carbon|null $manager_action_at
 * @property string|null $manager_note
 * @property int|null $hr_id
 * @property \Illuminate\Support\Carbon|null $hr_action_at
 * @property string|null $hr_note
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ExitChecklistItem> $checklist
 * @property-read int|null $checklist_count
 * @property-read \App\Models\FnfSettlement|null $fnf
 * @property-read \App\Models\User|null $hr
 * @property-read \App\Models\User|null $manager
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereHrActionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereHrId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereHrNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereLastWorkingDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereManagerActionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereManagerNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereNoticePeriodDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereResignationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExitResignation whereUserId($value)
 */
	class ExitResignation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $account_id
 * @property float $amount
 * @property string $date
 * @property int $expense_category_id
 * @property int $payee_id
 * @property int $payment_type_id
 * @property string|null $referal_id
 * @property string|null $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AccountList|null $accounts
 * @property-read \App\Models\Employee|null $employee_payees
 * @property-read \App\Models\ExpenseType|null $expense_categorys
 * @property-read \App\Models\Payees|null $payees
 * @property-read \App\Models\PaymentType|null $payment_types
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereExpenseCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense wherePayeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense wherePaymentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereReferalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereUpdatedAt($value)
 */
	class Expense extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseType whereUpdatedAt($value)
 */
	class ExpenseType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $lang
 * @property string $content
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExperienceCertificate whereUpdatedAt($value)
 */
	class ExperienceCertificate extends \Eloquent {}
}

namespace App\Models{
/**
 * Full-and-Final settlement attached to an exit resignation.
 *
 * total_amount   = sum of earnings columns
 * deductions     = sum of deduction columns
 * final_amount   = total_amount - deductions
 *
 * Computed in the controller on save() so the snapshot is always consistent.
 *
 * @property int $id
 * @property int $resignation_id
 * @property int $user_id
 * @property numeric $pending_salary
 * @property numeric $leave_encashment
 * @property numeric $gratuity
 * @property numeric $bonus
 * @property numeric $other_earnings
 * @property numeric $total_amount
 * @property numeric $notice_recovery
 * @property numeric $asset_recovery
 * @property numeric $tax_deduction
 * @property numeric $other_deductions
 * @property numeric $deductions
 * @property numeric $final_amount
 * @property string $status
 * @property string|null $remarks
 * @property \Illuminate\Support\Carbon|null $paid_on
 * @property int|null $processed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ExitResignation $resignation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereAssetRecovery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereBonus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereDeductions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereFinalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereGratuity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereLeaveEncashment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereNoticeRecovery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereOtherDeductions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereOtherEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement wherePaidOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement wherePendingSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereProcessedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereResignationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereTaxDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FnfSettlement whereUserId($value)
 */
	class FnfSettlement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $lang
 * @property string $content
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenerateOfferLetter whereUpdatedAt($value)
 */
	class GenerateOfferLetter extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch
 * @property int $goal_type
 * @property string $start_date
 * @property string $end_date
 * @property string|null $subject
 * @property string|null $rating
 * @property string|null $target_achievement
 * @property string|null $description
 * @property int $status
 * @property int $progress
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branches
 * @property-read \App\Models\GoalType|null $goalType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereGoalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereTargetAchievement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalTracking whereUpdatedAt($value)
 */
	class GoalTracking extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoalType whereUpdatedAt($value)
 */
	class GoalType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $assigned_by
 * @property int|null $cycle_id
 * @property int|null $increment_id
 * @property string $title
 * @property string|null $issues
 * @property array<array-key, mixed>|null $action_steps
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property bool $auto_initiated
 * @property string|null $final_remarks
 * @property string $final_outcome
 * @property \Illuminate\Support\Carbon|null $outcome_decided_at
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $assignedBy
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\GrIncrement|null $incrementRecord
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrComebackPlanReview> $reviews
 * @property-read int|null $reviews_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereActionSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereAssignedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereAutoInitiated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereFinalOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereFinalRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereIncrementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereIssues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereOutcomeDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlan whereUpdatedAt($value)
 */
	class GrComebackPlan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $plan_id
 * @property int $reviewer_id
 * @property \Illuminate\Support\Carbon $review_date
 * @property string $progress
 * @property int|null $rating
 * @property string|null $strengths
 * @property string|null $improvements
 * @property string|null $comments
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\GrComebackPlan $plan
 * @property-read \App\Models\Employee|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereImprovements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrComebackPlanReview whereUpdatedAt($value)
 */
	class GrComebackPlanReview extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cycle_id
 * @property int $employee_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $goal_deadline
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereGoalDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrCycleEmployee whereUpdatedAt($value)
 */
	class GrCycleEmployee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cycle_id
 * @property int $employee_id
 * @property int|null $rating_id
 * @property numeric $old_ctc
 * @property numeric $new_ctc
 * @property numeric $increment_pct
 * @property numeric $increment_amount
 * @property \Illuminate\Support\Carbon $effective_date
 * @property string $status
 * @property int|null $approved_by
 * @property int|null $proposed_by
 * @property \Illuminate\Support\Carbon|null $proposed_at
 * @property bool $synced_to_payroll
 * @property bool $letter_generated
 * @property string|null $remarks
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employee|null $proposer
 * @property-read \App\Models\GrRating|null $rating
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereEffectiveDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereIncrementAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereIncrementPct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereLetterGenerated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereNewCtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereOldCtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereProposedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereProposedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereRatingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereSyncedToPayroll($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrIncrement whereUpdatedAt($value)
 */
	class GrIncrement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $generation_id
 * @property int $employee_id
 * @property string|null $remarks
 * @property int $assigned_by
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\GrKpiGeneration|null $generation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereAssignedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereGenerationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiAssignment whereUpdatedAt($value)
 */
	class GrKpiAssignment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanySize whereUpdatedAt($value)
 */
	class GrKpiCompanySize extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiCompanyType whereUpdatedAt($value)
 */
	class GrKpiCompanyType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $cycle_id
 * @property string $job_role
 * @property string|null $department
 * @property string|null $company_size
 * @property string|null $industry
 * @property string|null $city
 * @property string|null $country
 * @property string|null $seniority_level
 * @property string|null $work_model
 * @property string|null $company_type
 * @property string|null $target_timeframe
 * @property int $no_of_items
 * @property string|null $content_json
 * @property string|null $pdf_path
 * @property string $ai_mode
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $manager_reviewed_at
 * @property \Illuminate\Support\Carbon|null $hod_reviewed_at
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read mixed $content
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereAiMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereCompanySize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereCompanyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereContentJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereHodReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereJobRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereManagerReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereNoOfItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration wherePdfPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereSeniorityLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereTargetTimeframe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiGeneration whereWorkModel($value)
 */
	class GrKpiGeneration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiIndustry whereUpdatedAt($value)
 */
	class GrKpiIndustry extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiSeniorityLevel whereUpdatedAt($value)
 */
	class GrKpiSeniorityLevel extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiTimeframe whereUpdatedAt($value)
 */
	class GrKpiTimeframe extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrKpiWorkModel whereUpdatedAt($value)
 */
	class GrKpiWorkModel extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cycle_id
 * @property int $employee_id
 * @property string $title
 * @property string|null $description
 * @property string|null $kpi
 * @property numeric $weightage
 * @property \Illuminate\Support\Carbon|null $deadline
 * @property string $status
 * @property string $approval
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $manager_remarks
 * @property int $progress
 * @property numeric|null $self_rating
 * @property string|null $self_remarks
 * @property numeric|null $manager_rating
 * @property string|null $manager_rating_remarks
 * @property numeric|null $hod_rating
 * @property string|null $hod_rating_remarks
 * @property string|null $document
 * @property string|null $document_name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $approver
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereDocumentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereHodRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereHodRatingRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereKpi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereManagerRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereManagerRatingRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereManagerRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereSelfRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereSelfRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrMission whereWeightage($value)
 */
	class GrMission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cycle_id
 * @property int $employee_id
 * @property numeric|null $self_rating
 * @property numeric|null $manager_rating
 * @property numeric|null $head_rating
 * @property numeric|null $final_rating
 * @property string|null $grade
 * @property string|null $calibration_category
 * @property bool $is_calibrated
 * @property bool $is_frozen
 * @property string|null $calibration_notes
 * @property int|null $calibrated_by
 * @property \Illuminate\Support\Carbon|null $frozen_at
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereCalibratedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereCalibrationCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereCalibrationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereFinalRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereFrozenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereHeadRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereIsCalibrated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereIsFrozen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereManagerRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereSelfRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrRating whereUpdatedAt($value)
 */
	class GrRating extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cycle_id
 * @property int $employee_id
 * @property string $review_type
 * @property int $reviewer_id
 * @property numeric|null $rating
 * @property array<array-key, mixed>|null $ratings_json
 * @property string|null $strengths
 * @property string|null $improvements
 * @property string|null $comments
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employee|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereImprovements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereRatingsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereReviewType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrReview whereUpdatedAt($value)
 */
	class GrReview extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $from_employee_id
 * @property int $to_employee_id
 * @property string $message
 * @property string|null $badge
 * @property int|null $cycle_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read \App\Models\Employee|null $fromEmployee
 * @property-read \App\Models\Employee|null $toEmployee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereBadge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereFromEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereToEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrShoutout whereUpdatedAt($value)
 */
	class GrShoutout extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $cycle_id
 * @property int $employee_id
 * @property int $manager_id
 * @property \Illuminate\Support\Carbon $meeting_date
 * @property string|null $notes
 * @property array<array-key, mixed>|null $discussion_points
 * @property array<array-key, mixed>|null $action_items
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PerformanceCycle|null $cycle
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employee|null $manager
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereActionItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereCycleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereDiscussionPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereMeetingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrSyncUp whereUpdatedAt($value)
 */
	class GrSyncUp extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $category
 * @property string $title
 * @property string $description
 * @property string|null $status
 * @property bool|null $is_anonymous
 * @property string|null $anonymous_token
 * @property int|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $assignedTo
 * @property-read mixed $complainant_display_name
 * @property-read mixed $complainant_name
 * @property-read mixed $status_with_color
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrievanceResponse> $internalNotes
 * @property-read int|null $internal_notes_count
 * @property-read \App\Models\GrievanceResponse|null $latestResponse
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrievanceResponse> $publicResponses
 * @property-read int|null $public_responses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrievanceResponse> $responses
 * @property-read int|null $responses_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance byCategory($category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance byStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance forHR($user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance forUser($user, $anonymousToken = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereAnonymousToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereIsAnonymous($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Grievance withoutTrashed()
 */
	class Grievance extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $grievance_id
 * @property int $responder_id
 * @property string $message
 * @property string|null $response_type
 * @property bool|null $is_internal_note
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read mixed $formatted_message
 * @property-read mixed $message_preview
 * @property-read mixed $responder_name
 * @property-read mixed $response_type_with_color
 * @property-read \App\Models\Grievance|null $grievance
 * @property-read \App\Models\User|null $responder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse byType($type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse internal()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse public()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereGrievanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereIsInternalNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereResponderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereResponseType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GrievanceResponse withoutTrashed()
 */
	class GrievanceResponse extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $start_date
 * @property string|null $end_date
 * @property string $occasion
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $location
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HolidayShiftMapping> $shiftMappings
 * @property-read int|null $shift_mappings_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereOccasion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereUpdatedAt($value)
 */
	class Holiday extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HolidaySetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HolidaySetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HolidaySetting query()
 */
	class HolidaySetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Holiday|null $holiday
 * @property-read \App\Models\Shift|null $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HolidayShiftMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HolidayShiftMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HolidayShiftMapping query()
 */
	class HolidayShiftMapping extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncomeType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncomeType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncomeType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncomeType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncomeType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncomeType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncomeType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncomeType whereUpdatedAt($value)
 */
	class IncomeType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch
 * @property int $department
 * @property int $designation
 * @property string|null $rating
 * @property int $customer_experience
 * @property int $marketing
 * @property int $administration
 * @property int $professionalism
 * @property int $integrity
 * @property int $attendance
 * @property int $created_user
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branches
 * @property-read \App\Models\Department|null $departments
 * @property-read \App\Models\Designation|null $designations
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereAdministration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereAttendance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereCreatedUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereCustomerExperience($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereDesignation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereIntegrity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereMarketing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereProfessionalism($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicator whereUpdatedAt($value)
 */
	class Indicator extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $candidate
 * @property int $employee
 * @property string $date
 * @property string $time
 * @property string $round_type
 * @property string|null $round_label
 * @property string $mode
 * @property string|null $meeting_link
 * @property string $status
 * @property int|null $rating
 * @property string|null $feedback
 * @property string|null $recommendation
 * @property string|null $comment
 * @property string|null $employee_response
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\JobApplication|null $applications
 * @property-read \App\Models\User|null $users
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereCandidate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereEmployee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereEmployeeResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereFeedback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereMeetingLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereRecommendation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereRoundLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereRoundType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterviewSchedule whereUpdatedAt($value)
 */
	class InterviewSchedule extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tax_declaration_id
 * @property string $section_code
 * @property string $investment_type
 * @property float $amount
 * @property string|null $proof_file
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail whereInvestmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail whereProofFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail whereSectionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail whereTaxDeclarationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvestmentDetail whereUpdatedAt($value)
 */
	class InvestmentDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ip
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpRestrict newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpRestrict newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpRestrict query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpRestrict whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpRestrict whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpRestrict whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpRestrict whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IpRestrict whereUpdatedAt($value)
 */
	class IpRestrict extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tax_declaration_id
 * @property string $income_type
 * @property float $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource whereIncomeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource whereTaxDeclarationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItIncomeSource whereUpdatedAt($value)
 */
	class ItIncomeSource extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $requisition_id
 * @property string $title
 * @property string|null $description
 * @property string|null $requirement
 * @property string|null $terms_and_conditions
 * @property int $branch
 * @property int $category
 * @property string|null $skill
 * @property int|null $position
 * @property string|null $start_date
 * @property string|null $end_date
 * @property string|null $status
 * @property string|null $applicant
 * @property string|null $visibility
 * @property string|null $code
 * @property string|null $custom_question
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branches
 * @property-read \App\Models\JobCategory|null $categories
 * @property-read \App\Models\User|null $createdBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereApplicant($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereCustomQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereRequirement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereRequisitionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereSkill($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereTermsAndConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Job whereVisibility($value)
 */
	class Job extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $job
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $source
 * @property string|null $source_detail
 * @property int|null $recruiter_id
 * @property string|null $profile
 * @property string|null $resume
 * @property string|null $cover_letter
 * @property string|null $dob
 * @property string|null $gender
 * @property string|null $address
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property string|null $zip_code
 * @property int $stage
 * @property int $order
 * @property string|null $skill
 * @property int $rating
 * @property string $final_status
 * @property int|null $final_rank
 * @property string|null $final_notes
 * @property int|null $final_decided_by
 * @property \Illuminate\Support\Carbon|null $final_decided_at
 * @property int $is_archive
 * @property string|null $custom_question
 * @property string|null $terms_condition_check
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecruitmentAssessment> $assessments
 * @property-read int|null $assessments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BgvCheck> $bgvChecks
 * @property-read int|null $bgv_checks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DecisionNote> $decisionNotes
 * @property-read int|null $decision_notes_count
 * @property-read \App\Models\User|null $finalDecidedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InterviewSchedule> $interviews
 * @property-read int|null $interviews_count
 * @property-read \App\Models\Job|null $jobs
 * @property-read \App\Models\JobOnBoard|null $offer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PreonboardingItem> $preonboardingItems
 * @property-read int|null $preonboarding_items_count
 * @property-read \App\Models\User|null $recruiter
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCoverLetter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCustomQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereFinalDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereFinalDecidedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereFinalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereFinalRank($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereFinalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereIsArchive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereJob($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereRecruiterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereResume($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereSkill($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereSourceDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereTermsConditionCheck($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereZipCode($value)
 */
	class JobApplication extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $application_id
 * @property int $note_created
 * @property string|null $note
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $noteCreated
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote whereApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote whereNoteCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplicationNote whereUpdatedAt($value)
 */
	class JobApplicationNote extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobCategory whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobCategory whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobCategory whereUpdatedAt($value)
 */
	class JobCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $application
 * @property \Illuminate\Support\Carbon|null $joining_date
 * @property string|null $status
 * @property string|null $job_type
 * @property int|null $days_of_week
 * @property float|null $salary
 * @property string|null $salary_type
 * @property string|null $salary_duration
 * @property array<array-key, mixed>|null $compensation_breakup
 * @property numeric|null $total_ctc
 * @property string $currency
 * @property string|null $offer_letter_path
 * @property \Illuminate\Support\Carbon|null $offer_expiry_date
 * @property \Illuminate\Support\Carbon|null $offer_released_at
 * @property \Illuminate\Support\Carbon|null $accepted_declined_at
 * @property string|null $decline_reason
 * @property string|null $negotiation_notes
 * @property bool $requires_approval
 * @property int|null $approved_by_user_id
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int $convert_to_employee
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\JobApplication|null $applications
 * @property-read \App\Models\User|null $approver
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereAcceptedDeclinedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereApplication($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereApprovedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereCompensationBreakup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereConvertToEmployee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereDaysOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereDeclineReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereJobType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereJoiningDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereNegotiationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereOfferExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereOfferLetterPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereOfferReleasedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereRequiresApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereSalaryDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereSalaryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereTotalCtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOnBoard whereUpdatedAt($value)
 */
	class JobOnBoard extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property int $order
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobStage whereUpdatedAt($value)
 */
	class JobStage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $lang
 * @property string $content
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JoiningLetter whereUpdatedAt($value)
 */
	class JoiningLetter extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $section_name
 * @property int $section_order
 * @property string|null $content
 * @property string $section_type
 * @property string $default_content
 * @property string $section_demo_image
 * @property string $section_blade_file_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereDefaultContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereSectionBladeFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereSectionDemoImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereSectionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereSectionOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereSectionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LandingPageSection whereUpdatedAt($value)
 */
	class LandingPageSection extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $code
 * @property string|null $fullName
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Languages newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Languages newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Languages query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Languages whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Languages whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Languages whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Languages whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Languages whereUpdatedAt($value)
 */
	class Languages extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property string $applied_on
 * @property string $start_date
 * @property string $end_date
 * @property string $day_type
 * @property int|null $substitute_employee_id
 * @property string $substitute_status
 * @property string|null $substitute_token
 * @property string|null $substitute_responded_at
 * @property string $total_leave_days
 * @property string $leave_reason
 * @property string|null $medical_certificate
 * @property int $certificate_verified
 * @property string|null $remark
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $professional_days Total days employee has been with company
 * @property int $professional_months Total months employee has been with company
 * @property int $professional_years Total years employee has been with company
 * @property string|null $calculated_at When professional period was calculated
 * @property-read \App\Models\Employee|null $employees
 * @property-read \App\Models\LeaveType|null $leaveType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereAppliedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereCalculatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereCertificateVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereDayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereLeaveReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereMedicalCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereProfessionalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereProfessionalMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereProfessionalYears($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereSubstituteEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereSubstituteRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereSubstituteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereSubstituteToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereTotalLeaveDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereUpdatedAt($value)
 */
	class Leave extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property int $days
 * @property numeric|null $monthly_credit
 * @property numeric|null $annual_credit
 * @property string $approval_requirement
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property int $is_carry_forward
 * @property numeric|null $max_carry_forward
 * @property int $is_encashable
 * @property numeric|null $encash_rate_per_day
 * @property string $encash_basis
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereAnnualCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereApprovalRequirement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereEncashBasis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereEncashRatePerDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsCarryForward($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsEncashable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereMaxCarryForward($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereMonthlyCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereUpdatedAt($value)
 */
	class LeaveType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $loan_option
 * @property string $title
 * @property float $amount
 * @property string|null $type
 * @property string $start_date
 * @property string $end_date
 * @property string $reason
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereLoanOption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereUpdatedAt($value)
 */
	class Loan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanOption whereUpdatedAt($value)
 */
	class LoanOption extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $user_id
 * @property string $ip
 * @property string $date
 * @property \Illuminate\Support\Carbon|null $logout_at
 * @property string $Details
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $location_address
 * @property string|null $selfie_image
 * @property string|null $logout_selfie
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employees
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereLocationAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereLogoutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereLogoutSelfie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereSelfieImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginDetail whereUserId($value)
 */
	class LoginDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property int|null $department_id
 * @property int|null $designation_id
 * @property int|null $branch_id
 * @property string $skills
 * @property string|null $experience
 * @property int $positions
 * @property string $priority
 * @property string $reason
 * @property string|null $replacement_for
 * @property string|null $salary_range
 * @property string|null $location
 * @property string|null $job_type
 * @property string|null $description
 * @property string|null $generated_jd
 * @property string $status
 * @property string|null $approval_chain
 * @property int $current_approval_step
 * @property \Illuminate\Support\Carbon|null $needed_by
 * @property int|null $job_id
 * @property int $created_by
 * @property int $raised_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RequisitionApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Designation|null $designation
 * @property-read array<string> $approval_chain_array
 * @property-read string|null $next_approver_role
 * @property-read array $skills_array
 * @property-read string $status_badge
 * @property-read \App\Models\Job|null $job
 * @property-read \App\Models\User|null $raisedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereApprovalChain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereCurrentApprovalStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereDesignationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereExperience($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereGeneratedJd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereJobType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereNeededBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition wherePositions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereRaisedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereReplacementFor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereSalaryRange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManpowerRequisition whereUpdatedAt($value)
 */
	class ManpowerRequisition extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch_id
 * @property string $department_id
 * @property string $employee_id
 * @property string $title
 * @property string $date
 * @property string $time
 * @property string|null $note
 * @property string|null $meet_link
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereMeetLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereUpdatedAt($value)
 */
	class Meeting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $meeting_id
 * @property int $employee_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingEmployee whereUpdatedAt($value)
 */
	class MeetingEmployee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $lang
 * @property string $content
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NOC whereUpdatedAt($value)
 */
	class NOC extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $parent_id
 * @property string $lang
 * @property string $content
 * @property string $variables
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Languages|null $language
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplateLangs whereVariables($value)
 */
	class NotificationTemplateLangs extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplates newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplates newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplates query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplates whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplates whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplates whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplates whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationTemplates whereUpdatedAt($value)
 */
	class NotificationTemplates extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $order_id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $card_number
 * @property string|null $card_exp_month
 * @property string|null $card_exp_year
 * @property string $plan_name
 * @property int $plan_id
 * @property float $price
 * @property string $price_currency
 * @property string $txn_id
 * @property string $payment_status
 * @property string $payment_type
 * @property string|null $receipt
 * @property int $user_id
 * @property int $is_refund
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserCoupon|null $total_coupon_used
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCardExpMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCardExpYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCardNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereIsRefund($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePlanName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePriceCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereReceipt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTxnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUserId($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $title
 * @property float $amount
 * @property string|null $type
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtherPayment whereUpdatedAt($value)
 */
	class OtherPayment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $title
 * @property int $number_of_days
 * @property int $hours
 * @property float $rate
 * @property string|null $type
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereNumberOfDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereUpdatedAt($value)
 */
	class Overtime extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $pay_frequency
 * @property int $pay_day
 * @property string $working_days
 * @property string|null $start_month
 * @property bool $status
 * @property bool $is_locked
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule wherePayDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule wherePayFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule whereStartMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySchedule whereWorkingDays($value)
 */
	class PaySchedule extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property float $net_payble
 * @property string $salary_month
 * @property int $status
 * @property float $basic_salary
 * @property string $allowance
 * @property string $commission
 * @property string $loan
 * @property string $saturation_deduction
 * @property string $other_payment
 * @property string $overtime
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employees
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereAllowance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereBasicSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereLoan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereNetPayble($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereOtherPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereOvertime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereSalaryMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereSaturationDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaySlip whereUpdatedAt($value)
 */
	class PaySlip extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $payee_name
 * @property string $contact_number
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees wherePayeeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payees whereUpdatedAt($value)
 */
	class Payees extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $payer_name
 * @property string $contact_number
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer wherePayerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payer whereUpdatedAt($value)
 */
	class Payer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentType whereUpdatedAt($value)
 */
	class PaymentType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $month
 * @property array<array-key, mixed>|null $earnings_json
 * @property array<array-key, mixed>|null $deductions_json
 * @property array<array-key, mixed>|null $benefits_json
 * @property array<array-key, mixed>|null $reimbursements_json
 * @property array<array-key, mixed>|null $statutory_json
 * @property float $gross_salary
 * @property float $total_deductions
 * @property float $employer_contribution
 * @property float $net_salary
 * @property bool $is_locked
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBenefitsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereDeductionsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereEarningsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereEmployerContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereGrossSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereNetSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereReimbursementsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereStatutoryJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereTotalDeductions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereUpdatedAt($value)
 */
	class Payroll extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $month
 * @property int $working_days
 * @property int $present
 * @property int $half_day
 * @property int $absent
 * @property int $leave
 * @property int $late_marks
 * @property int $early_marks
 * @property float $deduction_units
 * @property int $early_half_day
 * @property array<array-key, mixed>|null $policy_summary_json
 * @property numeric $present_effective
 * @property numeric $leave_effective
 * @property numeric $absent_effective
 * @property numeric $hd_deduction
 * @property int $weekly_offs
 * @property int $month_total_days
 * @property array<array-key, mixed>|null $details_json
 * @property int $synced_by
 * @property \Illuminate\Support\Carbon $synced_at
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereAbsent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereAbsentEffective($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereDeductionUnits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereDetailsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereEarlyHalfDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereEarlyMarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereHalfDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereHdDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereLateMarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereLeave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereLeaveEffective($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereMonthTotalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync wherePolicySummaryJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync wherePresent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync wherePresentEffective($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereSyncedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereWeeklyOffs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAttendanceSync whereWorkingDays($value)
 */
	class PayrollAttendanceSync extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $month
 * @property string $title
 * @property float $amount
 * @property string|null $remarks
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialAllowance whereUpdatedAt($value)
 */
	class PayrollSpecialAllowance extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $month
 * @property string $title
 * @property float $amount
 * @property string|null $remarks
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSpecialDeduction whereUpdatedAt($value)
 */
	class PayrollSpecialDeduction extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSupplementaryAdjustment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSupplementaryAdjustment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSupplementaryAdjustment query()
 */
	class PayrollSupplementaryAdjustment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipType whereUpdatedAt($value)
 */
	class PayslipType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon|null $goal_deadline
 * @property \Illuminate\Support\Carbon|null $self_review_start
 * @property \Illuminate\Support\Carbon|null $self_review_end
 * @property \Illuminate\Support\Carbon|null $manager_review_start
 * @property \Illuminate\Support\Carbon|null $manager_review_end
 * @property \Illuminate\Support\Carbon|null $head_review_start
 * @property \Illuminate\Support\Carbon|null $head_review_end
 * @property string|null $calibration_start
 * @property string|null $calibration_end
 * @property string $status
 * @property string $rating_scale
 * @property array<array-key, mixed>|null $settings_json
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrCycleEmployee> $assignedEmployees
 * @property-read int|null $assigned_employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrIncrement> $increments
 * @property-read int|null $increments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrMission> $missions
 * @property-read int|null $missions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrRating> $ratings
 * @property-read int|null $ratings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GrReview> $reviews
 * @property-read int|null $reviews_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereCalibrationEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereCalibrationStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereGoalDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereHeadReviewEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereHeadReviewStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereManagerReviewEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereManagerReviewStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereRatingScale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereSelfReviewEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereSelfReviewStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereSettingsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceCycle whereUpdatedAt($value)
 */
	class PerformanceCycle extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Competencies> $types
 * @property-read int|null $types_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Performance_Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Performance_Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Performance_Type query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Performance_Type whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Performance_Type whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Performance_Type whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Performance_Type whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Performance_Type whereUpdatedAt($value)
 */
	class Performance_Type extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property numeric|null $price
 * @property string $duration
 * @property int $max_users
 * @property int $max_employees
 * @property float $storage_limit
 * @property string|null $enable_chatgpt
 * @property int $trial
 * @property string|null $trial_days
 * @property int $is_disable
 * @property string|null $description
 * @property string|null $image
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereEnableChatgpt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereIsDisable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereMaxEmployees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereMaxUsers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereStorageLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereTrial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereTrialDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereUpdatedAt($value)
 */
	class Plan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $plan_id
 * @property string $duration
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Plan|null $plan
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanRequest whereUserId($value)
 */
	class PlanRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string $category
 * @property string|null $description
 * @property string $file_path
 * @property string $file_name
 * @property string|null $file_mime
 * @property int|null $file_size
 * @property string $version
 * @property bool $is_mandatory
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PolicyAcknowledgement> $acknowledgements
 * @property-read int|null $acknowledgements_count
 * @property-read \App\Models\User|null $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereFileMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereIsMandatory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereVersion($value)
 */
	class Policy extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $policy_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $acknowledged_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Policy $policy
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement whereAcknowledgedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement wherePolicyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyAcknowledgement whereUserId($value)
 */
	class PolicyAcknowledgement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $candidate_id
 * @property string $category
 * @property string $item_label
 * @property string $status
 * @property string|null $notes
 * @property string|null $document_path
 * @property \Illuminate\Support\Carbon|null $due_by
 * @property \Illuminate\Support\Carbon|null $completed_on
 * @property int|null $owner_user_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\JobApplication|null $candidate
 * @property-read \App\Models\User|null $owner
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereCompletedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereDocumentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereDueBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereItemLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereOwnerUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreonboardingItem whereUpdatedAt($value)
 */
	class PreonboardingItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $joined_on
 * @property int $day_milestone
 * @property \Illuminate\Support\Carbon $review_date
 * @property string $outcome
 * @property int|null $rating
 * @property string|null $strengths
 * @property string|null $improvements
 * @property string|null $manager_comments
 * @property int|null $reviewer_user_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\User|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereDayMilestone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereImprovements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereJoinedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereManagerComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereReviewerUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereUpdatedAt($value)
 */
	class ProbationReview extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $designation_id
 * @property string $promotion_title
 * @property string $promotion_date
 * @property string $description
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Designation|null $designation
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereDesignationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion wherePromotionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion wherePromotionTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Promotion whereUpdatedAt($value)
 */
	class Promotion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $candidate_id
 * @property string $assessment_type
 * @property string $title
 * @property \Illuminate\Support\Carbon|null $scheduled_on
 * @property \Illuminate\Support\Carbon|null $completed_on
 * @property int $max_score
 * @property int|null $score
 * @property int $passing_score
 * @property string $outcome
 * @property string|null $feedback
 * @property string|null $document_path
 * @property int|null $evaluator_user_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\JobApplication|null $candidate
 * @property-read \App\Models\User|null $evaluator
 * @property-read string $pass_fail
 * @property-read string $pass_fail_badge
 * @property-read int|null $percentage
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereAssessmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereCompletedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereDocumentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereEvaluatorUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereFeedback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereMaxScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment wherePassingScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereScheduledOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecruitmentAssessment whereUpdatedAt($value)
 */
	class RecruitmentAssessment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $percentage
 * @property int $minimum_threshold_amount
 * @property int $is_enable
 * @property string $guideline
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting whereGuideline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting whereIsEnable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting whereMinimumThresholdAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralSetting whereUpdatedAt($value)
 */
	class ReferralSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $plan_id
 * @property numeric $plan_price
 * @property int $commission
 * @property int $referral_code
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction wherePlanPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction whereReferralCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReferralTransaction whereUpdatedAt($value)
 */
	class ReferralTransaction extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $component_id
 * @property string $claim_month
 * @property float $amount
 * @property string $status
 * @property string|null $remarks
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereClaimMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereComponentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementClaim whereUpdatedAt($value)
 */
	class ReimbursementClaim extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $requisition_id
 * @property int $actor_user_id
 * @property string $actor_role
 * @property string|null $action
 * @property string|null $comments
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $actor
 * @property-read \App\Models\ManpowerRequisition $requisition
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereActorRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereActorUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereRequisitionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequisitionApproval whereUpdatedAt($value)
 */
	class RequisitionApproval extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $notice_date
 * @property string $resignation_date
 * @property string $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation whereNoticeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation whereResignationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resignation whereUpdatedAt($value)
 */
	class Resignation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string $type
 * @property string $calculation_type
 * @property float|null $value
 * @property string|null $formula
 * @property float|null $max_limit
 * @property bool $is_taxable
 * @property bool $is_pf_applicable
 * @property bool $is_esic_applicable
 * @property string $frequency
 * @property string|null $condition_rule
 * @property bool $status
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereCalculationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereConditionRule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereFormula($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereIsEsicApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereIsPfApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereIsTaxable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereMaxLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryComponent whereValue($value)
 */
	class SalaryComponent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property numeric $old_ctc
 * @property numeric $new_ctc
 * @property numeric $increment_amount
 * @property numeric $increment_percentage
 * @property \Illuminate\Support\Carbon $effective_date
 * @property string|null $arrears_month
 * @property bool $arrears_paid
 * @property numeric $arrears_amount
 * @property string|null $remarks
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereArrearsAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereArrearsMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereArrearsPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereEffectiveDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereIncrementAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereIncrementPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereNewCtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereOldCtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryIncrementHistory whereUpdatedAt($value)
 */
	class SalaryIncrementHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $country
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStructure whereUpdatedAt($value)
 */
	class SalaryStructure extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $deduction_option
 * @property string $title
 * @property float $amount
 * @property string|null $type
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereDeductionOption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaturationDeduction whereUpdatedAt($value)
 */
	class SaturationDeduction extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $screenshot_path
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $captured_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $screenshot_url
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor whereCapturedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor whereScreenshotPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreenMonitor whereUserId($value)
 */
	class ScreenMonitor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SetSalary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SetSalary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SetSalary query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SetSalary whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SetSalary whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SetSalary whereUpdatedAt($value)
 */
	class SetSalary extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereUpdatedAt($value)
 */
	class Shift extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $state_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereStateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereUpdatedAt($value)
 */
	class State extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property bool $status
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryComponent whereUpdatedAt($value)
 */
	class StatutoryComponent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $component_id
 * @property int|null $state_id
 * @property float|null $min_salary
 * @property float|null $max_salary
 * @property string $employee_contribution_type
 * @property float $employee_value
 * @property string $employer_contribution_type
 * @property float $employer_value
 * @property float|null $max_limit
 * @property string $frequency
 * @property string|null $applicable_gender
 * @property \Illuminate\Support\Carbon $effective_from
 * @property bool $status
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereApplicableGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereComponentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereEffectiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereEmployeeContributionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereEmployeeValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereEmployerContributionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereEmployerValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereMaxLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereMaxSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereMinSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatutoryRule whereUpdatedAt($value)
 */
	class StatutoryRule extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $structure_id
 * @property int $component_id
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent whereComponentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StructureComponent whereUpdatedAt($value)
 */
	class StructureComponent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $survey_id
 * @property int $response_id
 * @property int|null $employee_id
 * @property string $alert_type
 * @property string $risk_level
 * @property string $message
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\SurveyResponse $response
 * @property-read \App\Models\EmployeeSurvey $survey
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereAlertType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereResponseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereRiskLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereSurveyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAlert whereUpdatedAt($value)
 */
	class SurveyAlert extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $response_id
 * @property int $question_id
 * @property string|null $answer_value
 * @property float|null $rating_value
 * @property string|null $text_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SurveyQuestion $question
 * @property-read \App\Models\SurveyResponse $response
 * @property-read \App\Models\SurveySentimentAnalysis|null $sentiment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer whereAnswerValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer whereRatingValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer whereResponseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer whereTextValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyAnswer whereUpdatedAt($value)
 */
	class SurveyAnswer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $survey_id
 * @property string $question_text
 * @property string $question_type
 * @property array<array-key, mixed>|null $options
 * @property bool $is_required
 * @property bool $is_enps
 * @property int $order_no
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyAnswer> $answers
 * @property-read int|null $answers_count
 * @property-read \App\Models\EmployeeSurvey $survey
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereIsEnps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereOrderNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereQuestionText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereQuestionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereSurveyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyQuestion whereUpdatedAt($value)
 */
	class SurveyQuestion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $survey_id
 * @property int|null $employee_id
 * @property bool $is_anonymous
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyAnswer> $answers
 * @property-read int|null $answers_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\EmployeeSurvey $survey
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse whereIsAnonymous($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse whereSurveyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveyResponse whereUpdatedAt($value)
 */
	class SurveyResponse extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $answer_id
 * @property string $sentiment
 * @property array<array-key, mixed>|null $topic
 * @property string $emotion
 * @property string $risk_level
 * @property bool $hr_alert
 * @property string|null $ai_summary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SurveyAnswer $answer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereAiSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereAnswerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereEmotion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereHrAlert($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereRiskLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereSentiment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereTopic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SurveySentimentAnalysis whereUpdatedAt($value)
 */
	class SurveySentimentAnalysis extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $current_company
 * @property string|null $current_designation
 * @property numeric|null $experience_years
 * @property string|null $skills
 * @property string|null $preferred_locations
 * @property string|null $linkedin_url
 * @property string|null $portfolio_url
 * @property string|null $resume_path
 * @property numeric|null $current_ctc
 * @property numeric|null $expected_ctc
 * @property int|null $notice_period_days
 * @property string|null $source
 * @property string|null $source_detail
 * @property int|null $linked_application_id
 * @property int|null $assigned_recruiter_id
 * @property string|null $tags
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $last_engaged_at
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read array<string> $skills_array
 * @property-read array $tags_array
 * @property-read \App\Models\JobApplication|null $linkedApplication
 * @property-read \App\Models\User|null $recruiter
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereAssignedRecruiterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereCurrentCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereCurrentCtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereCurrentDesignation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereExpectedCtc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereExperienceYears($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereLastEngagedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereLinkedApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereLinkedinUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereNoticePeriodDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate wherePortfolioUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate wherePreferredLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereResumePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereSourceDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TalentPoolCandidate whereUpdatedAt($value)
 */
	class TalentPoolCandidate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $financial_year
 * @property string $tax_regime
 * @property string $declaration_status
 * @property bool $is_rented_house
 * @property bool $is_home_loan
 * @property bool $is_rental_income
 * @property float $rent_paid
 * @property string|null $landlord_name
 * @property string|null $landlord_pan
 * @property float $home_loan_interest
 * @property float $rental_income_amount
 * @property array<array-key, mixed>|null $compare_json
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $remarks
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereCompareJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereDeclarationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereFinancialYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereHomeLoanInterest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereIsHomeLoan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereIsRentalIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereIsRentedHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereLandlordName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereLandlordPan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereRentPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereRentalIncomeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereTaxRegime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxDeclaration whereUpdatedAt($value)
 */
	class TaxDeclaration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $template_name
 * @property string $prompt
 * @property string $module
 * @property string $field_json
 * @property int $is_tone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereFieldJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereIsTone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereModule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template wherePrompt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereUpdatedAt($value)
 */
	class Template extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $notice_date
 * @property string $termination_date
 * @property string $termination_type
 * @property string $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\TerminationType|null $terminationType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereNoticeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereTerminationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereTerminationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereUpdatedAt($value)
 */
	class Termination extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationType whereUpdatedAt($value)
 */
	class TerminationType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property int $employee_id
 * @property string $priority
 * @property string $end_date
 * @property string|null $description
 * @property string|null $attachment
 * @property string $ticket_code
 * @property int $ticket_created
 * @property int $created_by
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $createdBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTicketCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTicketCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUpdatedAt($value)
 */
	class Ticket extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ticket_id
 * @property int $employee_id
 * @property string|null $description
 * @property string|null $attachment
 * @property int $created_by
 * @property int $is_read
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $users
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketReply whereUpdatedAt($value)
 */
	class TicketReply extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string|null $client_name
 * @property string|null $task
 * @property string|null $category
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string $date
 * @property float $hours
 * @property string $billable
 * @property string $status
 * @property string|null $remark
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $employee
 * @property-read \App\Models\Employee|null $employees
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereBillable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereClientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereTask($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeSheet whereUpdatedAt($value)
 */
	class TimeSheet extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch
 * @property string $firstname
 * @property string $lastname
 * @property string $contact
 * @property string $email
 * @property string|null $address
 * @property string|null $expertise
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property-read \App\Models\Branch|null $branches
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereExpertise($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainer whereUpdatedAt($value)
 */
	class Trainer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $branch
 * @property int $trainer_option
 * @property int $training_type
 * @property int $trainer
 * @property float $training_cost
 * @property int $employee
 * @property string $start_date
 * @property string $end_date
 * @property string|null $description
 * @property int $performance
 * @property int $status
 * @property string|null $remarks
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branches
 * @property-read \App\Models\Employee|null $employees
 * @property-read \App\Models\Trainer|null $trainers
 * @property-read \App\Models\TrainingType|null $types
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereEmployee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training wherePerformance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereTrainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereTrainerOption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereTrainingCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereTrainingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereUpdatedAt($value)
 */
	class Training extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingType whereUpdatedAt($value)
 */
	class TrainingType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property numeric $req_amount
 * @property int $req_user_id
 * @property int $status
 * @property string|null $date
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder whereReqAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder whereReqUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionOrder whereUpdatedAt($value)
 */
	class TransactionOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $branch_id
 * @property int $department_id
 * @property string $transfer_date
 * @property string $description
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereTransferDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transfer whereUpdatedAt($value)
 */
	class Transfer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $from_account_id
 * @property int $to_account_id
 * @property string $date
 * @property float $amount
 * @property int $payment_type_id
 * @property string|null $referal_id
 * @property string|null $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PaymentType|null $payment_types
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereFromAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance wherePaymentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereReferalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereToAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferBalance whereUpdatedAt($value)
 */
	class TransferBalance extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $start_date
 * @property string $end_date
 * @property string $purpose_of_visit
 * @property string $place_of_visit
 * @property string $description
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel wherePlaceOfVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel wherePurposeOfVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Travel whereUpdatedAt($value)
 */
	class Travel extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string $type
 * @property string $avatar
 * @property string $lang
 * @property int|null $plan
 * @property string|null $plan_expire_date
 * @property int $requested_plan
 * @property string|null $trial_expire_date
 * @property int $trial_plan
 * @property int $is_login_enable
 * @property float $storage_limit
 * @property string|null $last_login
 * @property int $is_active
 * @property int $referral_code
 * @property int $used_referral_code
 * @property int $commission_amount
 * @property int $active_status
 * @property int $is_disable
 * @property int $dark_mode
 * @property string $messenger_color
 * @property string $created_by
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BackgroundScreenshot> $backgroundScreenshots
 * @property-read int|null $background_screenshots_count
 * @property-read \App\Models\Plan|null $currentPlan
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ScreenMonitor> $screenMonitors
 * @property-read int|null $screen_monitors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActiveStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCommissionAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsDisable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsLoginEnable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMessengerColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePlanExpireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereReferralCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRequestedPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStorageLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTrialExpireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTrialPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsedReferralCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\MustVerifyEmail {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user
 * @property int $coupon
 * @property string|null $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Coupon|null $coupon_detail
 * @property-read \App\Models\User|null $userDetail
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon whereCoupon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoupon whereUser($value)
 */
	class UserCoupon extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $template_id
 * @property int $user_id
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailTemplate whereUserId($value)
 */
	class UserEmailTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $tab_id
 * @property string $url
 * @property string|null $page_title
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon $last_seen_at
 * @property int $duration_seconds
 * @property int $focus_seconds
 * @property bool $is_active
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $display_label
 * @property-read string $duration_human
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereFocusSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit wherePageTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereTabId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPageVisit whereUserId($value)
 */
	class UserPageVisit extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Utility newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Utility newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Utility query()
 */
	class Utility extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warning_to
 * @property int $warning_by
 * @property string $subject
 * @property string $warning_date
 * @property string $description
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereWarningBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereWarningDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warning whereWarningTo($value)
 */
	class Warning extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $module
 * @property string $url
 * @property string $method
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereModule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Webhook whereUrl($value)
 */
	class Webhook extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $title
 * @property string $meeting_id
 * @property string $user_id
 * @property string|null $password
 * @property string $start_date
 * @property int $duration
 * @property string|null $start_url
 * @property string|null $join_url
 * @property string|null $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereJoinUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereStartUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ZoomMeeting whereUserId($value)
 */
	class ZoomMeeting extends \Eloquent {}
}

