@php
    use App\Models\Utility;
    $users = \Auth::user();
    $currantLang = $users->currentLanguage();
    $languages = \App\Models\Utility::languages();
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
    $unseenCounter = App\Models\ChMessage::where('to_id', Auth::user()->id)
        ->where('seen', 0)
        ->count();
    $groupUnseenCounter = DB::table('chat_group_messages as cgm')
        ->join('chat_group_members as cgmembers', 'cgmembers.chat_group_id', '=', 'cgm.chat_group_id')
        ->where('cgmembers.user_id', Auth::id())
        ->where('cgm.user_id', '!=', Auth::id())
        ->where(function ($query) {
            $query->whereNull('cgmembers.last_read_at')
                ->orWhereColumn('cgm.created_at', '>', 'cgmembers.last_read_at');
        })
        ->count();
    $unseenCounter = $unseenCounter + $groupUnseenCounter;
    $unseen_count = DB::select('SELECT from_id, COUNT(*) AS totalmasseges FROM ch_messages WHERE seen = 0 GROUP BY from_id');

    // Central in-app notifications (all roles except super admin)
    $inAppNotifications = collect();
    $inAppNotificationCount = 0;
    if (Auth::check() && Auth::user()->type !== 'super admin') {
        $inAppNotifications = \App\Services\InAppNotifier::unreadForUser((int) Auth::id(), 20);
        $inAppNotificationCount = \App\Services\InAppNotifier::unreadCount((int) Auth::id());
    }

    // Manager's pending leave notifications
    $managerPendingLeaves = collect();
    $managerPendingLeaveCount = 0;
    $isManager = false;
    
    if (Auth::user()->type !== 'super admin') {
        $currentEmployee = \App\Models\Employee::where('user_id', Auth::id())->first();
        
        if ($currentEmployee) {
            // Check if user is a manager (has subordinates)
            $subordinateIds = \App\Models\Employee::where('reporting_manager_id', $currentEmployee->id)
                ->pluck('id')
                ->toArray();
            
            if (!empty($subordinateIds)) {
                $isManager = true;
                $managerPendingLeaves = \App\Models\Leave::with(['employees', 'leaveType'])
                    ->whereIn('employee_id', $subordinateIds)
                    ->where('status', 'Pending')
                    ->orderBy('created_at', 'desc')
                    ->get();
                $managerPendingLeaveCount = $managerPendingLeaves->count();
            }
        }
        
        // For HR/Company users, show all pending leaves
        if (in_array(Auth::user()->type, ['company', 'hr'])) {
            $isManager = true;
            $managerPendingLeaves = \App\Models\Leave::with(['employees', 'leaveType'])
                ->where('created_by', Auth::user()->creatorId())
                ->where('status', 'Pending')
                ->orderBy('created_at', 'desc')
                ->get();
            $managerPendingLeaveCount = $managerPendingLeaves->count();
        }
    }

    /* ── Resignation notifications (Exit Management module) ─────────────────
       Manager: resignations from direct reports awaiting their action.
       HR:      resignations awaiting HR final approval.
       Both feed into one bell with a single counter. */
    $exitPendingItems = collect();
    $exitPendingCount = 0;
    if (\Schema::hasTable('exit_resignations') && Auth::check()) {
        $authUser = \Auth::user();

        // Manager-side: I'm the line manager → my direct reports' pending requests
        $myEmpRow = \App\Models\Employee::where('user_id', $authUser->id)->first();
        if ($myEmpRow) {
            $reportUserIds = \App\Models\Employee::where(function ($q) use ($myEmpRow) {
                    $q->where('reporting_manager_id', $myEmpRow->id)
                      ->orWhere('hod_id', $myEmpRow->id)
                      ->orWhere('management_id', $myEmpRow->id);
                })
                ->pluck('user_id')
                ->filter();
            if ($reportUserIds->isNotEmpty()) {
                $mgrItems = \App\Models\ExitResignation::with('user')
                    ->where('created_by', $authUser->creatorId())
                    ->whereIn('user_id', $reportUserIds)
                    ->where('status', 'pending')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(function ($r) { $r->__exit_role = 'manager'; return $r; });
                $exitPendingItems = $exitPendingItems->concat($mgrItems);
            }
        }

        // HR-side: company / hr / super admin → manager-approved awaiting HR
        if (in_array($authUser->type, ['company', 'hr', 'super admin'])) {
            $hrItems = \App\Models\ExitResignation::with('user')
                ->where('created_by', $authUser->creatorId())
                ->where('status', 'manager_approved')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($r) { $r->__exit_role = 'hr'; return $r; });
            $exitPendingItems = $exitPendingItems->concat($hrItems);
        }

        $exitPendingItems = $exitPendingItems->unique('id')->values();
        $exitPendingCount = $exitPendingItems->count();
    }
@endphp

<style>
        #leave-notification-wrapper .leave-notification-dropdown {
            min-width: 420px !important;
            max-width: 440px;
            max-height: 460px;
            overflow-y: auto;
        }

        #leave-notification-wrapper .leave-request-item {
            border-bottom: 1px solid var(--bs-border-color);
        }

        #leave-notification-wrapper .leave-request-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.35rem;
            align-items: stretch;
        }

        #leave-notification-wrapper .leave-request-actions .btn {
            width: 100%;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding-left: 0.45rem;
            padding-right: 0.45rem;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            text-indent: 0 !important;
        }

        #leave-notification-wrapper .leave-accept-btn {
            background-color: #6fd943 !important;
            border-color: #6fd943 !important;
            color: #fff !important;
        }

        #leave-notification-wrapper .leave-reject-btn {
            background-color: #ff3a6e !important;
            border-color: #ff3a6e !important;
            color: #fff !important;
        }

        #leave-notification-wrapper .leave-accept-btn:hover,
        #leave-notification-wrapper .leave-accept-btn:focus {
            background-color: #5eb839 !important;
            border-color: #5eb839 !important;
            color: #fff !important;
        }

        #leave-notification-wrapper .leave-reject-btn:hover,
        #leave-notification-wrapper .leave-reject-btn:focus {
            background-color: #d9315e !important;
            border-color: #d9315e !important;
            color: #fff !important;
        }

        #leave-notification-wrapper .leave-request-actions .btn i {
            margin-right: 0.25rem;
        }

        @media (max-width: 575.98px) {
            #leave-notification-wrapper .leave-notification-dropdown {
                min-width: 320px !important;
                max-width: calc(100vw - 24px);
            }

            #leave-notification-wrapper .leave-request-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #leave-notification-wrapper .leave-request-actions .leave-open-btn {
                grid-column: span 2;
            }
        }
    </style>


@if (isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on')
    <header class="dash-header transprent-bg">
    @else
        <header class="dash-header">
@endif
{{-- <header class="dash-header  {{ isset($setting['is_sidebar_transperent']) && $setting['is_sidebar_transperent'] == 'on' ? 'transprent-bg' : '' }}"> --}}

<div class="header-wrapper">
    <div class="me-auto dash-mob-drp">
        <ul class="list-unstyled">
            <li class="dash-h-item mob-hamburger">
                <a href="#!" class="dash-head-link" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box">
                            <div class="hamburger-inner"></div>
                        </div>
                    </div>
                </a>
            </li>

            <li class="dropdown dash-h-item drp-company">
                <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#"
                    role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="theme-avtar">
                        <img alt="#"
                            src="{{ !empty($users->avatar) ? $profile . $users->avatar : $profile . 'avatar.png' }}"
                            class="img-fluid rounded border-2 border border-primary" style="width: 100%; height:100%">
                    </span>
                    <span class="hide-mob ms-2"> {{ 'Hi, ' . Auth::user()->name . '!' }}
                        <i class="ti ti-chevron-down drp-arrow nocolor hide-mob"></i>
                    </span>
                </a>
                <div class="dropdown-menu dash-h-dropdown">
                    <a href="{{ route('profile') }}" class="dropdown-item">
                        <i class="ti ti-user"></i>
                        <span>{{ __('My Profile') }}</span>
                    </a>

                    <a href="{{ route('logout') }}" class="dropdown-item"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ti ti-power"></i>
                        <span>{{ __('Logout') }}</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>


        </ul>
    </div>
    <div class="ms-auto">
        <ul class="list-unstyled">

            @if (Auth::user()->type != 'super admin')
                @impersonating($guard = null)
                    <li class="dropdown dash-h-item drp-company">
                        <a class="btn btn-danger btn-sm me-3" href="{{ route('exit.company') }}"><i class="ti ti-ban"></i>
                            {{ __('Exit Company Login') }}
                        </a>
                    </li>
                @endImpersonating
            @endif

            @if (\Auth::user()->type != 'super admin')
                <li class="dropdown dash-h-item drp-notification">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0 notification-toggle {{ $inAppNotificationCount > 0 ? 'beep' : '' }}"
                        data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-bell"></i>
                        @if ($inAppNotificationCount > 0)
                            <span class="bg-danger dash-h-badge">{{ $inAppNotificationCount > 99 ? '99+' : $inAppNotificationCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end notification-dropdown">
                        <div class="noti-header d-flex justify-content-between align-items-center px-3 pt-2">
                            <h5 class="m-0">{{ __('Notifications') }}</h5>
                            @if ($inAppNotificationCount > 0)
                                <form method="POST" action="{{ route('in-app-notifications.read-all') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0">{{ __('Mark all read') }}</button>
                                </form>
                            @endif
                        </div>
                        <div class="noti-body" id="notification-list" style="max-height:360px;overflow-y:auto;">
                            @forelse ($inAppNotifications as $noti)
                                <div class="px-3 py-2 border-bottom">
                                    <div class="text-muted small text-uppercase">{{ str_replace('_', ' ', $noti->module) }}</div>
                                    <div class="fw-semibold">{{ $noti->title }}</div>
                                    @if (!empty($noti->message))
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($noti->message, 100) }}</div>
                                    @endif
                                    <div class="text-muted small">{{ $noti->created_at ? $noti->created_at->diffForHumans() : '' }}</div>
                                    <div class="mt-1">
                                        <a href="{{ route('in-app-notifications.read', $noti->id) }}" class="text-primary">{{ __('View') }}</a>
                                    </div>
                                </div>
                            @empty
                                <div class="px-3 py-2 text-muted">{{ __('No new notifications.') }}</div>
                            @endforelse
                        </div>
                    </div>
                </li>
            @endif

            {{-- Manager Leave Approval Notifications --}}
            @if ($isManager)
                <li class="dropdown dash-h-item drp-notification" id="leave-notification-wrapper">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0 leave-notification-toggle {{ $managerPendingLeaveCount > 0 ? 'beep' : '' }}"
                        data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false"
                        title="{{ __('Leave Approvals') }}">
                        <i class="ti ti-calendar-event" style="color: #1f2937 !important;"></i>
                        <span class="bg-warning dash-h-badge leave-pending-count" style="{{ $managerPendingLeaveCount > 0 ? '' : 'display:none;' }}">{{ $managerPendingLeaveCount }}</span>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end leave-notification-dropdown">
                        <div class="noti-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0"><i class="ti ti-calendar-check me-1"></i>{{ __('Leave Approvals') }}</h5>
                            <span class="badge bg-warning leave-count-badge">{{ $managerPendingLeaveCount }}</span>
                        </div>
                        <div class="noti-body" id="leave-notification-list">
                            @forelse ($managerPendingLeaves as $leave)
                                <div class="px-3 py-3 border-bottom leave-request-item" data-leave-id="{{ $leave->id }}">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="w-100">
                                            <div class="fw-bold">{{ optional($leave->employees)->name ?? 'Unknown' }}</div>
                                            <div class="text-muted small">{{ optional($leave->leaveType)->title ?? 'Leave' }} • {{ $leave->total_leave_days ?? '-' }} {{ __('days') }}</div>
                                            <div class="text-muted small">{{ \Auth::user()->dateFormat($leave->start_date) }} - {{ \Auth::user()->dateFormat($leave->end_date) }}</div>
                                            <div class="text-muted small fst-italic">{{ \Str::limit($leave->leave_reason ?? '', 60) }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex flex-wrap gap-1 leave-request-actions">
                                        <button type="button" class="btn btn-sm btn-success leave-action-btn leave-accept-btn" data-leave-id="{{ $leave->id }}" data-action="Approved">
                                            <i class="ti ti-check"></i> {{ __('Accept') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger leave-action-btn leave-reject-btn" data-leave-id="{{ $leave->id }}" data-action="Reject">
                                            <i class="ti ti-x"></i> {{ __('Reject') }}
                                        </button>
                                        <a href="{{ route('leave.action', $leave->id) }}" class="btn btn-sm btn-outline-primary leave-open-btn">
                                            <i class="ti ti-eye"></i> {{ __('Open') }}
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="px-3 py-2 text-muted">{{ __('No pending leave requests.') }}</div>
                            @endforelse
                        </div>
                        <div class="noti-footer">
                            <div class="d-grid">
                                <a href="{{ route('leave.index') }}" class="btn dash-head-link justify-content-center text-primary mx-0">
                                    {{ __('View All Leaves') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
            @endif

            {{-- ═══ Resignation / Exit notifications (Exit Management module) ═══ --}}
            @if ($exitPendingCount > 0 || in_array(\Auth::user()->type, ['company','hr','super admin']))
                <li class="dropdown dash-h-item drp-notification" id="exit-notification-wrapper">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0 exit-notification-toggle {{ $exitPendingCount > 0 ? 'beep' : '' }}"
                       data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false"
                       title="{{ __('Resignations') }}">
                        <i class="ti ti-logout" style="color: #1f2937 !important;"></i>
                        @if($exitPendingCount > 0)
                            <span class="bg-danger dash-h-badge">{{ $exitPendingCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end" style="width:380px;max-height:480px;overflow-y:auto;">
                        <div class="noti-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0"><i class="ti ti-logout me-1"></i>{{ __('Resignations') }}</h5>
                            <span class="badge bg-danger">{{ $exitPendingCount }}</span>
                        </div>
                        <div class="noti-body">
                            @forelse($exitPendingItems as $r)
                                <a href="{{ route('exit-management.show', $r->id) }}"
                                   class="px-3 py-2 border-bottom d-flex gap-2 align-items-start text-decoration-none text-body"
                                   style="transition:background .15s;"
                                   onmouseover="this.style.background='#fef2f2';"
                                   onmouseout="this.style.background='';">
                                    <div style="width:32px;height:32px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                                background:{{ $r->__exit_role === 'hr' ? '#fee2e2' : '#fef3c7' }};color:{{ $r->__exit_role === 'hr' ? '#991b1b' : '#b45309' }};">
                                        <i class="ti ti-{{ $r->__exit_role === 'hr' ? 'shield-check' : 'user-check' }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">{{ optional($r->user)->name ?? '—' }}</div>
                                        <div class="text-muted small">
                                            @if($r->__exit_role === 'hr')
                                                {{ __('Awaiting HR final approval') }}
                                            @else
                                                {{ __('Awaiting your manager approval') }}
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            <i class="ti ti-calendar-event"></i>
                                            LWD: {{ optional($r->last_working_day)->format('d M Y') }}
                                            <span class="ms-2">{{ $r->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="px-3 py-3 text-muted small">{{ __('No pending resignations.') }}</div>
                            @endforelse
                        </div>
                        <div class="noti-footer">
                            <div class="d-grid">
                                <a href="{{ route('exit-management.index') }}" class="btn dash-head-link justify-content-center text-primary mx-0">
                                    {{ __('View All Resignations') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
            @endif

            {{-- ═══ Activity Tracker Stop Requests ═══ --}}
            @php
                $atStopCount = 0;
                $atStopItems = collect();
                if(in_array(\Auth::user()->type, ['company','hr','super admin']) && \Schema::hasTable('at_stop_requests') && \Schema::hasTable('at_devices')) {
                    $atCid = \Auth::user()->creatorId();
                    $atDeviceIds = \App\Models\AtDevice::where('created_by', $atCid)->pluck('id');
                    $atStopItems = \App\Models\AtStopRequest::with(['user:id,name,email','device:id,device_name'])
                        ->whereIn('device_id', $atDeviceIds)
                        ->where('status', 'pending')
                        ->orderByDesc('created_at')
                        ->get();
                    $atStopCount = $atStopItems->count();
                }
            @endphp
            @if($atStopCount > 0 || in_array(\Auth::user()->type, ['company','hr','super admin']))
                <li class="dropdown dash-h-item drp-notification" id="at-stop-notification-wrapper">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0 at-stop-notification-toggle {{ $atStopCount > 0 ? 'beep' : '' }}"
                       data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false"
                       title="{{ __('Tracker Stop Requests') }}">
                        <i class="ti ti-device-laptop-off" style="color: #1f2937 !important;"></i>
                        @if($atStopCount > 0)
                            <span class="bg-warning dash-h-badge">{{ $atStopCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end" style="width:400px;max-height:480px;overflow-y:auto;">
                        <div class="noti-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0"><i class="ti ti-device-laptop-off me-1"></i>{{ __('Tracker Stop Requests') }}</h5>
                            <span class="badge bg-warning text-dark">{{ $atStopCount }}</span>
                        </div>
                        <div class="noti-body" id="at-stop-notification-list">
                            @forelse($atStopItems as $sr)
                                <div class="px-3 py-2 border-bottom at-stop-item" data-id="{{ $sr->id }}">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <strong class="small">{{ $sr->user->name ?? '—' }}</strong>
                                            <span class="text-muted small ms-1">{{ $sr->user->email ?? '' }}</span>
                                            <div class="text-muted" style="font-size:11px;"><i class="ti ti-device-laptop"></i> {{ $sr->device->device_name ?? '—' }} &bull; {{ $sr->created_at->diffForHumans() }}</div>
                                            @if($sr->reason)<div class="text-muted small mt-1"><i class="ti ti-message"></i> {{ $sr->reason }}</div>@endif
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-1">
                                        <form method="POST" action="{{ route('activity-tracker.stop-request.review', $sr->id) }}" class="at-stop-review-form">
                                            @csrf
                                            <input type="hidden" name="action" value="approved">
                                            <button type="submit" class="btn btn-xs btn-success py-0 px-2" style="font-size:11px;">
                                                <i class="ti ti-check"></i> {{ __('Approve') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('activity-tracker.stop-request.review', $sr->id) }}" class="at-stop-review-form">
                                            @csrf
                                            <input type="hidden" name="action" value="rejected">
                                            <button type="submit" class="btn btn-xs btn-danger py-0 px-2" style="font-size:11px;">
                                                <i class="ti ti-x"></i> {{ __('Reject') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="px-3 py-3 text-muted small">{{ __('No pending stop requests.') }}</div>
                            @endforelse
                        </div>
                        <div class="noti-footer">
                            <div class="d-grid">
                                <a href="{{ route('activity-tracker.index') }}" class="btn dash-head-link justify-content-center text-primary mx-0">
                                    {{ __('View Activity Tracker') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
            @endif

            {{-- ═══ Recruitment Notifications (Recruitment module) ═══ --}}
            @php
                $rnSummary = \App\Support\RecruitmentNotifications::summary();
            @endphp
            @if(($rnSummary['total'] ?? 0) > 0 || in_array(\Auth::user()->type, ['company','hr','super admin']))
                <li class="dropdown dash-h-item drp-notification">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0 {{ $rnSummary['total'] > 0 ? 'beep' : '' }}"
                       data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false"
                       title="{{ __('Recruitment') }}">
                        <i class="ti ti-briefcase" style="color: #1f2937 !important;"></i>
                        @if($rnSummary['total'] > 0)
                            <span class="bg-primary dash-h-badge">{{ $rnSummary['total'] }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end" style="width:340px;max-height:480px;overflow-y:auto;">
                        <div class="noti-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0"><i class="ti ti-briefcase me-1"></i>{{ __('Recruitment') }}</h5>
                            <span class="badge bg-primary">{{ $rnSummary['total'] }}</span>
                        </div>
                        <div class="noti-body">
                            @forelse($rnSummary['items'] as $it)
                                <a href="{{ $it['url'] }}" class="px-3 py-2 border-bottom d-flex gap-2 align-items-start text-decoration-none text-body" style="transition:background .15s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='';">
                                    <div style="width:32px;height:32px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                        background:{{ ['warning'=>'#fef3c7','info'=>'#dbeafe','success'=>'#d1fae5','danger'=>'#fee2e2','primary'=>'#dbeafe','secondary'=>'#e2e8f0'][$it['color']] ?? '#e2e8f0' }};
                                        color:{{ ['warning'=>'#92400e','info'=>'#1e40af','success'=>'#065f46','danger'=>'#991b1b','primary'=>'#1e3a8a','secondary'=>'#475569'][$it['color']] ?? '#475569' }};">
                                        <i class="ti {{ $it['icon'] }}"></i>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:.82rem;font-weight:600;line-height:1.3;">{{ $it['title'] }}</div>
                                        @if(!empty($it['subtitle']))
                                            <div style="font-size:.72rem;color:#6b7280;margin-top:2px;">{{ $it['subtitle'] }}</div>
                                        @endif
                                        @if(!empty($it['when']))
                                            <div style="font-size:.68rem;color:#9ca3af;margin-top:2px;">{{ $it['when'] }}</div>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div class="px-3 py-4 text-center text-muted">
                                    <i class="ti ti-circle-check" style="font-size:2rem;color:#10b981;"></i>
                                    <div class="mt-2 small">{{ __('All caught up — no pending actions.') }}</div>
                                </div>
                            @endforelse
                        </div>
                        <div class="px-3 py-2 border-top text-center">
                            <a href="{{ route('recruitment.dashboard') }}" class="small text-primary">
                                {{ __('Open Recruitment Dashboard') }} →
                            </a>
                        </div>
                    </div>
                </li>
            @endif

            @php
                // $currantLang = basename(\App::getLocale());
                // $languages = \App\Models\Utility::languages();
                // $lang = isset($users->lang) ? $users->lang : 'en';
                // if ($lang == null) {
                //     $lang = 'en';
                // }
                // if (\Schema::hasTable('languages')) {
                //     $LangName = \App\Models\Languages::where('code', $lang)->first()->fullName;
                // } else {
                //     $LangName = 'english';
                // }

                $lang = isset($users->lang) ? $users->lang : 'en';
                if ($lang == null) {
                    $lang = 'en';
                }
                $LangName = \App\Models\Languages::where('code', $lang)->first()->fullName;
                if (empty($LangName)) {
                    $LangName = new App\Models\Utility();
                    $LangName->fullName = 'English';
                }
            @endphp

            <li class="dropdown dash-h-item drp-language">
                <a class="dash-head-link dropdown-toggle arrow-none me-0 " data-bs-toggle="dropdown" href="#"
                    role="button" aria-haspopup="false" aria-expanded="false" id="dropdownLanguage">
                    <i class="ti ti-world nocolor"></i>
                    <span class="drp-text hide-mob">{{ Str::ucfirst($LangName) }}</span>
                    <i class="ti ti-chevron-down drp-arrow nocolor"></i>
                </a>
                <div class="dropdown-menu dash-h-dropdown dropdown-menu-end" aria-labelledby="dropdownLanguage">
                    {{-- @foreach (App\Models\Utility::languages() as $lang)
                        <a href="{{ route('change.language', $lang) }}"
                            class="dropdown-item {{ basename(App::getLocale()) == $lang ? 'text-danger' : '' }}">{{ Str::upper($lang) }}</a>
                    @endforeach --}}
                    @foreach (App\Models\Utility::languages() as $code => $lang)
                        <a href="{{ route('change.language', $code) }}"
                            class="dropdown-item {{ $currantLang == $code ? 'text-primary' : '' }}">
                            <span>{{ ucFirst($lang) }}</span>
                        </a>
                    @endforeach
                    @if (\Auth::user()->type == 'super admin')
                        <div class="dropdown-divider m-0"></div>
                        <a href="#" class="dropdown-item text-primary" data-size="md"
                            data-url="{{ route('create.language') }}" data-ajax-popup="true"
                            data-title="{{ __('Create New Language') }}"
                            data-bs-toggle="tooltip">{{ __('Create Language') }}</a>
                        <div class="dropdown-divider m-0"></div>
                        <a href="{{ route('manage.language', [basename(App::getLocale())]) }}"
                            class="dropdown-item text-primary">{{ __('Manage Language') }}</a>
                    @endif
                </div>
            </li>

            {{-- Logout button --}}
            <li class="dash-h-item">
                <a class="dash-head-link" href="{{ route('logout') }}" title="{{ __('Logout') }}"
                   onclick="event.preventDefault(); document.getElementById('header-logout-form').submit();"
                   style="color: #dc2626;">
                    <i class="ti ti-power"></i>
                    <span class="hide-mob">{{ __('Logout') }}</span>
                </a>
                <form id="header-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </li>

        </ul>
    </div>
</div>

</header>
@push('scripts')
    {{-- @include('Chatify::layouts.modals') --}}
    <script>
        (function() {
            const hasCounterTarget = $('.custom_messanger_counter').length > 0;
            if (!hasCounterTarget) {
                return;
            }

            const $msgBtn = $('#msg-btn');

            const chatBaseUrl = $('meta[name="url"]').attr('content') || (typeof url !== 'undefined' ? url : '');
            if (!chatBaseUrl) {
                return;
            }

            let isFetchingContacts = false;
            let directUnseenTotal = 0;
            let groupUnseenTotal = 0;

            function parseDirectUnreadCount(contactsHtml) {
                const $temp = $('<div>').html(contactsHtml || '');
                let unseenTotal = 0;

                $temp.find('.messenger-list-item b').each(function() {
                    const value = parseInt($(this).text().trim(), 10);
                    if (!Number.isNaN(value)) {
                        unseenTotal += value;
                    }
                });

                return unseenTotal;
            }

            function updateUnreadCounter() {
                const totalUnread = directUnseenTotal + groupUnseenTotal;
                $('.custom_messanger_counter').text(totalUnread);
                if (totalUnread > 0) {
                    $('.custom_messanger_counter').removeClass('d-none');
                } else {
                    $('.custom_messanger_counter').addClass('d-none');
                }
            }

            function styleContactsForHeader() {
                if (!$msgBtn.length) {
                    return;
                }

                $('.count-listOfContacts').find('.messenger-list-item').each(function() {
                    $('.noti-body .activeStatus').remove();
                    $('.noti-body .avatar').remove();
                    $(this).find('span').remove();
                    $(this).find('p').addClass('d-inline');
                    $(this).find('b').css({
                        position: 'absolute',
                        right: '50px'
                    });
                    $(this).find('tr').remove('td');
                });
            }

            function fetchGroupNotifications() {
                $.ajax({
                    url: "{{ route('chat-groups.header-notifications') }}",
                    method: 'GET',
                    dataType: 'JSON',
                    timeout: 8000,
                    success: function(data) {
                        groupUnseenTotal = (data && data.unread) ? parseInt(data.unread, 10) || 0 : 0;
                        if ($msgBtn.length) {
                            const html = (data && data.html) ? data.html : '<div class="px-2 py-1 text-muted">No group messages</div>';
                            $('.count-listOfGroupContacts').html(html);
                        }
                        updateUnreadCounter();
                    },
                    error: function(xhr) {
                        if (xhr.status === 401) { stopPolling(); }
                    }
                });
            }

            function fetchContactsAndUpdateHeader() {
                if (isFetchingContacts) {
                    return;
                }
                isFetchingContacts = true;

                $.ajax({
                    url: chatBaseUrl + '/getContacts',
                    method: 'GET',
                    data: {
                        _token: '{{ csrf_token() }}',
                        page: 1,
                        type: 'custom',
                    },
                    dataType: 'JSON',
                    timeout: 8000,
                    success: function(data) {
                        const contactsHtml = (data && data.contacts) ? data.contacts : '';
                        directUnseenTotal = parseDirectUnreadCount(contactsHtml);
                        updateUnreadCounter();

                        if ($msgBtn.length) {
                            $('.count-listOfContacts').html(contactsHtml);
                            styleContactsForHeader();
                        }

                        fetchGroupNotifications();
                    },
                    error: function(xhr) {
                        if (xhr.status === 401) { stopPolling(); }
                    },
                    complete: function() {
                        isFetchingContacts = false;
                    },
                });
            }

            if ($msgBtn.length) {
                $msgBtn.on('click', function() {
                    fetchContactsAndUpdateHeader();
                    fetchGroupNotifications();
                });
            }

            var _pollingTimers = [
                setInterval(function () {
                    if (!document.hidden) fetchContactsAndUpdateHeader();
                }, 60000),
                setInterval(function () {
                    if (!document.hidden) fetchGroupNotifications();
                }, 60000)
            ];

            function stopPolling() {
                _pollingTimers.forEach(function(t) { clearInterval(t); });
                _pollingTimers = [];
            }

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    fetchContactsAndUpdateHeader();
                }
            });

            fetchContactsAndUpdateHeader();
            fetchGroupNotifications();
        })();
    </script>

    {{-- Leave Notification Sound & Polling Script --}}
    @if ($isManager)
    <script>
        (function() {
            let lastLeaveCount = {{ $managerPendingLeaveCount }};
            let leaveNotificationEnabled = true;

            // Sound notification function (similar to Chatify)
            function playLeaveNotificationSound() {
                try {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextClass) return;
                    
                    const audioContext = new AudioContextClass();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();

                    // Use a different tone for leave notifications (lower pitch)
                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(660, audioContext.currentTime); // A5 note
                    oscillator.frequency.setValueAtTime(880, audioContext.currentTime + 0.1); // A5 to A5
                    
                    gainNode.gain.setValueAtTime(0.001, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.3, audioContext.currentTime + 0.02);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.3);

                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.3);
                } catch (e) {
                    console.warn('Leave notification sound blocked', e);
                }
            }

            // Voice announcement for new leave request
            function playLeaveVoiceNotification(employeeName) {
                try {
                    if (!window.speechSynthesis) return;
                    const text = employeeName 
                        ? 'New leave request from ' + employeeName
                        : 'New leave request received';
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.rate = 1;
                    utterance.pitch = 1;
                    utterance.volume = 1;
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(utterance);
                } catch (e) {
                    console.warn('Voice notification blocked', e);
                }
            }

            // Fetch pending leaves and update UI
            function fetchPendingLeaves() {
                if (!$('#leave-notification-wrapper').length) return;

                $.ajax({
                    url: '{{ route("leave.pending-subordinates") }}',
                    method: 'GET',
                    data: { last_count: lastLeaveCount },
                    dataType: 'JSON',
                    success: function(response) {
                        if (response.success) {
                            const newCount = response.count || 0;
                            
                            // Check if there are new leaves
                            if (response.has_new && leaveNotificationEnabled) {
                                // Play sound notification
                                playLeaveNotificationSound();
                                
                                // Play voice notification
                                playLeaveVoiceNotification();
                                
                                // Add visual beep effect
                                $('.leave-notification-toggle').addClass('beep');
                                
                                // Show browser notification if permitted
                                showBrowserNotification('New Leave Request', 'You have a new leave approval request');
                            }
                            
                            // Update count badge
                            lastLeaveCount = newCount;
                            updateLeaveCountBadge(newCount);
                            
                            // Update dropdown content
                            $('#leave-notification-list').html(response.html);
                            
                            // Re-bind action buttons
                            bindLeaveActionButtons();
                        }
                    },
                    error: function(xhr, status, error) {
                        if (xhr.status === 401 || xhr.status === 419) {
                            // Session expired - stop polling and redirect to login
                            if (window._leavePollingInterval) clearInterval(window._leavePollingInterval);
                            window.location.href = '{{ route("login") }}';
                            return;
                        }
                        console.warn('Failed to fetch leave notifications:', error);
                    }
                });
            }

            // Update leave count badge
            function updateLeaveCountBadge(count) {
                const $badge = $('.leave-pending-count');
                const $headerBadge = $('.leave-count-badge');
                
                if (count > 0) {
                    $badge.text(count).show();
                    $headerBadge.text(count);
                    $('.leave-notification-toggle').addClass('beep');
                } else {
                    $badge.hide();
                    $headerBadge.text('0');
                    $('.leave-notification-toggle').removeClass('beep');
                }
            }

            // Show browser notification
            function showBrowserNotification(title, body) {
                if (!('Notification' in window)) return;
                
                if (Notification.permission === 'granted') {
                    new Notification(title, { body: body, icon: '{{ asset("assets/images/logo.png") }}' });
                } else if (Notification.permission !== 'denied') {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === 'granted') {
                            new Notification(title, { body: body, icon: '{{ asset("assets/images/logo.png") }}' });
                        }
                    });
                }
            }

            // Bind leave approve/reject buttons
            function bindLeaveActionButtons() {
                $(document).off('click', '.leave-action-btn').on('click', '.leave-action-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const $btn = $(this);
                    const leaveId = $btn.data('leave-id');
                    const action = $btn.data('action');
                    const $item = $btn.closest('.leave-request-item');
                    
                    if (!leaveId || !action) return;
                    
                    // Disable button while processing
                    $btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin"></i>');
                    
                    $.ajax({
                        url: '{{ route("leave.approve-ajax") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            leave_id: leaveId,
                            status: action
                        },
                        dataType: 'JSON',
                        success: function(response) {
                            if (response.success) {
                                // Show success feedback
                                const badge = action === 'Approved'
                                    ? '<span class="badge bg-success">{{ __('Accepted') }}</span>'
                                    : '<span class="badge bg-danger">{{ __('Rejected') }}</span>';

                                $item.find('.leave-request-actions').html(badge);
                                
                                // Fade out and remove item after delay
                                setTimeout(function() {
                                    $item.fadeOut(300, function() {
                                        $(this).remove();
                                        
                                        // Update count
                                        lastLeaveCount = Math.max(0, lastLeaveCount - 1);
                                        updateLeaveCountBadge(lastLeaveCount);
                                        
                                        // Check if list is empty
                                        if ($('#leave-notification-list .leave-request-item').length === 0) {
                                            $('#leave-notification-list').html('<div class="px-3 py-2 text-muted">{{ __("No pending leave requests.") }}</div>');
                                        }
                                    });
                                }, 1000);
                                
                                // Show toast notification
                                if (typeof toastr !== 'undefined') {
                                    toastr.success(response.message);
                                }
                            } else {
                                // Restore button and show error
                                const originalHtml = action === 'Approved' 
                                    ? '<i class="ti ti-check"></i> {{ __("Accept") }}'
                                    : '<i class="ti ti-x"></i> {{ __("Reject") }}';
                                $btn.prop('disabled', false).html(originalHtml);
                                
                                if (typeof toastr !== 'undefined') {
                                    toastr.error(response.error || '{{ __("Failed to process leave.") }}');
                                } else {
                                    alert(response.error || '{{ __("Failed to process leave.") }}');
                                }
                            }
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON || {};
                            const originalHtml = action === 'Approved' 
                                ? '<i class="ti ti-check"></i> {{ __("Accept") }}'
                                : '<i class="ti ti-x"></i> {{ __("Reject") }}';
                            $btn.prop('disabled', false).html(originalHtml);
                            
                            if (typeof toastr !== 'undefined') {
                                toastr.error(response.error || '{{ __("An error occurred.") }}');
                            } else {
                                alert(response.error || '{{ __("An error occurred.") }}');
                            }
                        }
                    });
                });
            }

            // Request notification permission on page load
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }

            // Initial binding
            bindLeaveActionButtons();

            // Poll for new leaves every 30 seconds
            window._leavePollingInterval = setInterval(fetchPendingLeaves, 30000);

            // Fetch immediately on first visit
            setTimeout(fetchPendingLeaves, 1000);

            // Re-fetch when page becomes visible
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    fetchPendingLeaves();
                }
            });

            // Dropdown open handler - refresh content
            $('.leave-notification-toggle').on('click', function() {
                fetchPendingLeaves();
            });
        })();
    </script>
    @endif

    {{-- Resignation / Exit notification sound on page-load count increase --}}
    <script>
        (function () {
            const userKey = 'exitPendingSeen_{{ Auth::id() }}';
            const currentCount = {{ (int) ($exitPendingCount ?? 0) }};
            const prev = parseInt(localStorage.getItem(userKey) || '0', 10);

            // Only beep if count went UP since last page-load (new resignation arrived)
            if (currentCount > prev) {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (Ctx) {
                        const ctx = new Ctx();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(880, ctx.currentTime);
                        osc.frequency.setValueAtTime(1040, ctx.currentTime + 0.12);
                        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.24);
                        gain.gain.setValueAtTime(0.001, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.35, ctx.currentTime + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
                        osc.connect(gain); gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.5);
                    }
                } catch (e) { /* audio blocked, ignore */ }

                // Voice
                try {
                    if (window.speechSynthesis) {
                        const u = new SpeechSynthesisUtterance('New resignation request');
                        u.rate = 1; u.volume = 1;
                        window.speechSynthesis.cancel();
                        window.speechSynthesis.speak(u);
                    }
                } catch (e) { /* speech blocked */ }

                // Browser notification
                try {
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('New Resignation', {
                            body: 'You have a new resignation awaiting your action.',
                        });
                    } else if ('Notification' in window && Notification.permission === 'default') {
                        Notification.requestPermission();
                    }
                } catch (e) { /* ignore */ }
            }

            localStorage.setItem(userKey, String(currentCount));
        })();
    </script>

    {{-- ═══ Activity Tracker Stop-Request real-time polling ═══ --}}
    @if(in_array(\Auth::user()->type, ['company','hr','super admin']))
    <script>
    (function () {
        const POLL_URL   = '{{ route('activity-tracker.stop-requests.poll') }}';
        const SEEN_KEY   = 'atStopSeen_{{ Auth::id() }}';
        const INTERVAL   = 30000; // 30 seconds

        let lastCount = parseInt(localStorage.getItem(SEEN_KEY) || '{{ (int)($atStopCount ?? 0) }}', 10);

        function playBeep() {
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return;
                const ctx  = new Ctx();
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(960, ctx.currentTime);
                osc.frequency.setValueAtTime(1200, ctx.currentTime + 0.15);
                gain.gain.setValueAtTime(0.001, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.4, ctx.currentTime + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
                osc.connect(gain); gain.connect(ctx.destination);
                osc.start(); osc.stop(ctx.currentTime + 0.5);
            } catch (e) {}
        }

        function speakAlert(name) {
            try {
                if (window.speechSynthesis) {
                    const u = new SpeechSynthesisUtterance(name + ' ne tracker band karne ki request ki');
                    u.lang = 'hi-IN'; u.rate = 1; u.volume = 1;
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(u);
                }
            } catch (e) {}
        }

        function browserNotify(name) {
            try {
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('Tracker Stop Request', {
                        body: name + ' ne tracker band karne ki request ki hai.',
                        icon: '/favicon.ico',
                    });
                } else if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            } catch (e) {}
        }

        function poll() {
            fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;

                    const count   = parseInt(data.count, 10) || 0;
                    const $wrap   = $('#at-stop-notification-wrapper');
                    const $list   = $('#at-stop-notification-list');
                    const $toggle = $wrap.find('.at-stop-notification-toggle');
                    const $badge  = $wrap.find('.bg-warning.dash-h-badge');
                    const $hdrBadge = $wrap.find('.noti-header .badge');

                    // Update list HTML
                    $list.html(data.html);

                    // Update badge count
                    if (count > 0) {
                        if ($badge.length) {
                            $badge.text(count);
                        } else {
                            $toggle.append('<span class="bg-warning dash-h-badge">' + count + '</span>');
                        }
                        $hdrBadge.text(count);
                        $toggle.addClass('beep');
                    } else {
                        $badge.remove();
                        $hdrBadge.text(0);
                        $toggle.removeClass('beep');
                    }

                    // New request arrived — alert!
                    if (count > lastCount) {
                        playBeep();
                        // Try to get first new employee name for voice/browser notify
                        const firstName = $list.find('.at-stop-item strong.small').first().text().trim() || 'Ek employee';
                        speakAlert(firstName);
                        browserNotify(firstName);
                    }

                    lastCount = count;
                    localStorage.setItem(SEEN_KEY, String(count));
                })
                .catch(function () { /* network error, try next tick */ });
        }

        // Start polling after 10s delay (let page settle), then every 30s
        setTimeout(function () {
            poll();
            setInterval(poll, INTERVAL);
        }, 10000);

        // Also re-fetch when dropdown opens
        $(document).on('click', '.at-stop-notification-toggle', function () {
            poll();
        });
    })();
    </script>
    @endif
@endpush
