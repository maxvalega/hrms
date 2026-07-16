@extends('layouts.admin')

@section('page-title')
    {{ __('Dashboard') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="ti ti-home me-1"></i>{{ __('Dashboard') }}</a></li>
@endsection
@php
    $setting = App\Models\Utility::settings();
    $icons = \App\Models\Utility::get_file('uploads/job/icons/');
@endphp

@push('css-page')
    <style>
        /* ─────────────────────────────────────────────────────────────
           DASHBOARD — refined stats cards
           ───────────────────────────────────────────────────────────── */
        .dash-stats-card {
            border: 1px solid rgba(15, 23, 42, .06);
            border-radius: 14px;
            background: #fff;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .dash-stats-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-from, #6366f1), var(--accent-to, #8b5cf6));
            opacity: .85;
        }
        .dash-stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px -8px rgba(15, 23, 42, .12);
            border-color: rgba(15, 23, 42, .1);
        }
        .dash-stats-card .card-body { padding: 18px 20px; }
        .dash-stats-card.tone-primary { --accent-from: #6366f1; --accent-to: #8b5cf6; }
        .dash-stats-card.tone-amber   { --accent-from: #f59e0b; --accent-to: #ef4444; }
        .dash-stats-card.tone-teal    { --accent-from: #14b8a6; --accent-to: #06b6d4; }
        .dash-stats-card.tone-rose    { --accent-from: #ec4899; --accent-to: #f43f5e; }

        .dash-stats-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
            color: #fff;
            box-shadow: 0 4px 12px -4px rgba(15, 23, 42, .25);
        }
        .dash-stats-label {
            font-size: .68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #64748b;
            margin: 0;
        }
        .dash-stats-title {
            font-size: .92rem;
            font-weight: 600;
            margin: 2px 0 0 0;
        }
        .dash-stats-title a {
            color: #0f172a;
            text-decoration: none;
        }
        .dash-stats-title a:hover { color: var(--accent-from, #6366f1); }
        .dash-stats-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1;
            margin: 0;
            background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Total Staff bifurcation grid — distinct color per employee type */
        .staff-mini-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 6px;
            border-top: 1px dashed rgba(15, 23, 42, .12);
            padding-top: 12px;
            margin-top: 14px;
        }
        @media (max-width: 575px) {
            .staff-mini-grid { grid-template-columns: repeat(3, 1fr); }
        }
        .staff-cell {
            background: var(--cell-bg, #f1f5f9);
            border: 1px solid var(--cell-border, rgba(15, 23, 42, .08));
            border-radius: 9px;
            padding: 8px 4px 6px;
            text-align: center;
            line-height: 1.1;
            transition: transform .12s ease;
        }
        .staff-cell:hover { transform: translateY(-1px); }
        .staff-cell .sc-code {
            font-size: .62rem;
            font-weight: 700;
            color: var(--cell-fg, #475569);
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .staff-cell .sc-count {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }
        .staff-cell.t-FT  { --cell-bg: #dbeafe; --cell-border: #93c5fd; --cell-fg: #1d4ed8; }
        .staff-cell.t-PT  { --cell-bg: #ede9fe; --cell-border: #c4b5fd; --cell-fg: #6d28d9; }
        .staff-cell.t-CON { --cell-bg: #fef3c7; --cell-border: #fcd34d; --cell-fg: #b45309; }
        .staff-cell.t-MT  { --cell-bg: #ccfbf1; --cell-border: #5eead4; --cell-fg: #0f766e; }
        .staff-cell.t-INT { --cell-bg: #fce7f3; --cell-border: #f9a8d4; --cell-fg: #be185d; }
        .staff-cell.t-OTH { --cell-bg: #f3f4f6; --cell-border: #d1d5db; --cell-fg: #4b5563; }

        /* ─────────────────────────────────────────────────────────────
           Shared dashboard card / section styling
           ───────────────────────────────────────────────────────────── */
        .dash-section-card {
            border: 1px solid rgba(15, 23, 42, .06);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            overflow: hidden;
        }
        .dash-section-card > .card-header,
        .dash-section-card > .dash-section-head {
            background: linear-gradient(135deg, #f8fafc 0%, #fff 60%);
            border-bottom: 1px solid rgba(15, 23, 42, .06);
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .dash-section-title {
            margin: 0;
            font-size: .98rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: .2px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dash-section-title i {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            font-size: 14px;
        }
        .dash-section-meta {
            font-size: .72rem;
            color: #64748b;
            font-weight: 500;
        }
        .dash-section-card > .card-body { padding: 18px; }

        /* Fix invisible text on active btn-outline-primary inside section header */
        .dash-section-card .btn-outline-primary {
            color: #0d9488;
            border-color: #0d9488;
            background: #fff;
        }
        .dash-section-card .btn-outline-primary:hover {
            background: #f0fdfa;
            color: #0d9488;
            border-color: #0d9488;
        }
        .dash-section-card .btn-outline-primary.active,
        .dash-section-card .btn-outline-primary:active {
            background: #0d9488 !important;
            color: #ffffff !important;
            border-color: #0d9488 !important;
            box-shadow: none;
        }
        .dash-section-card .btn-outline-primary.active i,
        .dash-section-card .btn-outline-primary:active i { color: #ffffff !important; }

        /* Login / Logout strip */
        .dash-login-strip {
            background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
            border: 1px solid rgba(59, 130, 246, .15);
            border-radius: 12px;
            padding: 10px 16px;
            font-size: .82rem;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: center;
        }
        .dash-login-strip .item { display: inline-flex; align-items: center; gap: 6px; color: #475569; }
        .dash-login-strip .item .ti { color: #3b82f6; }
        .dash-login-strip .item strong { color: #1e293b; font-weight: 600; }

        /* Mark Attendance card refinements */
        .attendance-dashboard-card {
            border: 1px solid rgba(15, 23, 42, .06) !important;
            border-radius: 14px;
            background: linear-gradient(135deg, #fafbff 0%, #fff 100%);
        }
        .attendance-block {
            border-radius: 12px !important;
            border: 1px solid rgba(15, 23, 42, .08) !important;
            background: #fff !important;
        }
        .attendance-block.attendance-clock-in  { border-left: 4px solid #6366f1 !important; }
        .attendance-block.attendance-clock-out { border-left: 4px solid #ef4444 !important; }

        /* Pending leave approvals — premium look */
        .pending-leave-card {
            border: 1px solid rgba(239, 68, 68, .15) !important;
            border-radius: 14px !important;
            overflow: hidden;
        }
        .pending-leave-card > .card-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fff 100%) !important;
            border-bottom: 1px solid rgba(239, 68, 68, .15) !important;
        }
        .pending-leave-card .leave-pulse {
            display: inline-flex;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #ef4444;
            box-shadow: 0 0 0 0 rgba(239,68,68,.7);
            animation: dashPulse 1.8s infinite;
            margin-right: 6px;
        }
        @keyframes dashPulse {
            0%   { box-shadow: 0 0 0 0   rgba(239,68,68,.55); }
            70%  { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
            100% { box-shadow: 0 0 0 0   rgba(239,68,68,0); }
        }

        /* Storage progress bar premium */
        .storage-bar-wrap {
            background: #f1f5f9;
            border-radius: 999px;
            height: 10px;
            overflow: hidden;
        }
        .storage-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 999px;
            transition: width .4s ease;
        }

        /* Empty-state illustration */
        .dash-empty {
            text-align: center;
            color: #94a3b8;
            padding: 32px 16px;
            font-size: .82rem;
        }
        .dash-empty i { font-size: 2.4rem; opacity: .3; display: block; margin-bottom: 6px; }

        .dashboard-analytics-card {
            border: 0;
            border-radius: 14px;
            box-shadow: var(--bs-box-shadow-sm);
        }

        .dashboard-kpi-card,
        .dashboard-chart-card {
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            background: var(--bs-body-bg);
            height: 100%;
        }

        .dashboard-kpi-card {
            padding: 14px 16px;
        }

        .dashboard-kpi-label {
            color: var(--bs-secondary-color);
            font-size: 12px;
            letter-spacing: .2px;
            margin-bottom: 6px;
            display: block;
        }

        .dashboard-kpi-value {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
        }

        .dashboard-chart-card {
            padding: 14px;
        }

        .dashboard-chart-title {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .attendance-dashboard-card .card-body {
            padding: 1rem 1.25rem;
        }
        .attendance-block {
            background: var(--bs-body-bg);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .attendance-block.attendance-clock-in { border-left: 3px solid var(--bs-primary); }
        .attendance-block.attendance-clock-out { border-left: 3px solid var(--bs-danger); }
        .attendance-time-display {
            display: flex;
            align-items: center;
            min-height: 38px;
        }

        /* Notification icon highlighting animations */
        .notification-highlight {
            animation: notificationPulse 2s ease-in-out infinite;
        }

        @keyframes notificationPulse {
            0% {
                transform: scale(1);
                filter: brightness(1);
            }
            25% {
                transform: scale(1.1);
                filter: brightness(1.2) drop-shadow(0 0 8px rgba(239, 68, 68, 0.6));
            }
            50% {
                transform: scale(1.15);
                filter: brightness(1.3) drop-shadow(0 0 12px rgba(239, 68, 68, 0.8));
            }
            75% {
                transform: scale(1.1);
                filter: brightness(1.2) drop-shadow(0 0 8px rgba(239, 68, 68, 0.6));
            }
            100% {
                transform: scale(1);
                filter: brightness(1);
            }
        }

        .notification-glow {
            animation: notificationGlow 3s ease-in-out infinite;
        }

        @keyframes notificationGlow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.2);
            }
        }

        .notification-badge-bounce {
            animation: badgeBounce 1.5s ease-in-out infinite;
        }

        @keyframes badgeBounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-3px);
            }
        }

        /* Notification icon states */
        .notification-icon.has-notifications {
            color: #dc2626 !important;
        }

        .notification-icon.has-notifications i {
            animation: notificationShake 0.5s ease-in-out;
        }

        @keyframes notificationShake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
        .attendance-time-value {
            font-size: 1.125rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .attendance-block .btn { font-weight: 600; }

        /* ─────────────────────────────────────────────────────────────
           DASHBOARD — premium welcome hero banner
           ───────────────────────────────────────────────────────────── */
        .dash-hero {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            padding: 26px 30px;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 45%, #ec4899 100%);
            color: #fff;
            box-shadow: 0 18px 38px -16px rgba(124, 58, 237, .55);
        }
        .dash-hero::before, .dash-hero::after {
            content: ""; position: absolute; border-radius: 50%;
            background: rgba(255,255,255,.08); pointer-events: none;
        }
        .dash-hero::before { width: 220px; height: 220px; right: -60px; top: -80px; }
        .dash-hero::after  { width: 160px; height: 160px; right: 120px; bottom: -90px; background: rgba(255,255,255,.05); }
        .dash-hero .dash-hero-inner { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: 18px; align-items: center; justify-content: space-between; }
        .dash-hero .dash-hero-left { display: flex; align-items: center; gap: 16px; min-width: 0; flex: 1; }
        .dash-hero .dash-hero-avatar {
            width: 64px; height: 64px; border-radius: 18px;
            background: rgba(255,255,255,.18); backdrop-filter: blur(8px);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.6rem; font-weight: 700; color: #fff; flex-shrink: 0;
            border: 2px solid rgba(255,255,255,.25); overflow: hidden;
        }
        .dash-hero .dash-hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .dash-hero .dash-hero-text { min-width: 0; }
        .dash-hero .dash-hero-greeting {
            font-size: .76rem; font-weight: 600; letter-spacing: .8px;
            text-transform: uppercase; opacity: .82; margin: 0 0 4px;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .dash-hero .dash-hero-name { font-size: 1.6rem; font-weight: 700; line-height: 1.2; margin: 0; letter-spacing: -.4px; }
        .dash-hero .dash-hero-sub { font-size: .85rem; opacity: .9; margin-top: 4px; }
        .dash-hero .dash-hero-right {
            display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
        }
        .dash-hero .hero-date-pill {
            background: rgba(255,255,255,.16); backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,.25);
            padding: 10px 14px; border-radius: 12px;
            display: inline-flex; align-items: center; gap: 10px; color: #fff;
            min-width: 130px;
        }
        .dash-hero .hero-date-pill .hd-day { font-size: 1.4rem; font-weight: 700; line-height: 1; }
        .dash-hero .hero-date-pill .hd-meta { font-size: .68rem; text-transform: uppercase; letter-spacing: .5px; opacity: .85; }
        .dash-hero .hero-time {
            font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums;
            display: flex; align-items: center; gap: 6px;
        }
        .dash-hero .hero-quick-btn {
            background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.28);
            color: #fff; padding: 9px 14px; border-radius: 10px;
            font-size: .82rem; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: .15s; backdrop-filter: blur(6px);
        }
        .dash-hero .hero-quick-btn:hover {
            background: rgba(255,255,255,.28); color: #fff; transform: translateY(-1px);
        }
        .dash-hero .hero-quick-btn.is-primary {
            background: #fff; color: #4338ca;
        }
        .dash-hero .hero-quick-btn.is-primary:hover { background: #f1f5f9; color: #3730a3; }

        @media (max-width: 768px) {
            .dash-hero { padding: 20px 22px; }
            .dash-hero .dash-hero-name { font-size: 1.3rem; }
            .dash-hero::before { width: 160px; height: 160px; right: -50px; top: -60px; }
            .dash-hero::after  { display: none; }
        }

        /* Trim FullCalendar empty space below the date grid on the dashboard */
        .dash-cal-body { padding: 8px 10px 4px; }
        .dash-cal-body .fc { font-size: .82rem; }
        .dash-cal-body .fc .fc-toolbar.fc-header-toolbar { margin-bottom: .35em; }
        .dash-cal-body .fc .fc-view-harness { min-height: 0; }
        .dash-cal-body .fc .fc-daygrid-day-frame { min-height: 38px; }
        /* kill the trailing ~1rem white-strip FullCalendar leaves at the bottom */
        .dash-cal-body .fc .fc-scrollgrid { border-bottom: 0; }
        .dash-cal-body + * { margin-top: 0 !important; }

        /* Dashboard calendar: attendance (merged in /event/get_event_data) */
        .fc .fc-event.attn-cal-present,
        .fc-event.attn-cal-present {
            background-color: #d1e7dd !important;
            border-color: #198754 !important;
            color: #0f5132 !important;
        }
        .fc .fc-event.attn-cal-late,
        .fc-event.attn-cal-late {
            background-color: #ffe5d0 !important;
            border-color: #fd7e14 !important;
            color: #664d03 !important;
        }
        .fc .fc-event.attn-cal-absent,
        .fc-event.attn-cal-absent {
            background-color: #f8d7da !important;
            border-color: #dc3545 !important;
            color: #842029 !important;
        }
        .fc .fc-event.attn-cal-leave,
        .fc-event.attn-cal-leave {
            background-color: #e2e3e5 !important;
            border-color: #6c757d !important;
            color: #41464b !important;
        }
        .fc .fc-event.attn-cal-halfday,
        .fc-event.attn-cal-halfday {
            background-color: #fff3cd !important;
            border-color: #ffc107 !important;
            color: #664d03 !important;
        }
    </style>
@endpush

@section('content')
    @php
        $authUser   = \Auth::user();
        $heroHour   = (int) now()->format('H');
        $heroGreet  = $heroHour < 12 ? __('Good Morning') : ($heroHour < 17 ? __('Good Afternoon') : __('Good Evening'));
        $heroIcon   = $heroHour < 12 ? 'ti-sun' : ($heroHour < 17 ? 'ti-sun-high' : 'ti-moon-stars');
        $heroName   = $authUser->name ?? __('there');
        $heroFirst  = strtoupper(substr($heroName, 0, 1));
        $heroAvatar = '';
        if (!empty($authUser->avatar)) {
            try { $heroAvatar = \App\Models\Utility::get_file('uploads/avatar/') . $authUser->avatar; } catch (\Throwable $e) {}
        }
        $heroEmpRow = \App\Models\Employee::where('user_id', $authUser->id)->first();
        $heroCompanyName = '';
        try {
            if (in_array($authUser->type, ['company', 'super admin'])) {
                $heroCompanyName = $authUser->name;
            } else {
                $creatorId = method_exists($authUser, 'creatorId') ? $authUser->creatorId() : ($authUser->created_by ?? null);
                if ($creatorId) {
                    $heroCompanyName = \App\Models\User::where('id', $creatorId)->value('name') ?? '';
                }
            }
        } catch (\Throwable $e) {}
    @endphp

    {{-- ─── Welcome hero banner ─── --}}
    <div class="dash-hero">
        <div class="dash-hero-inner">
            <div class="dash-hero-left">
                <div class="dash-hero-avatar">
                    @if($heroAvatar)
                        <img src="{{ $heroAvatar }}" alt="{{ $heroName }}">
                    @else
                        {{ $heroFirst }}
                    @endif
                </div>
                <div class="dash-hero-text">
                    <p class="dash-hero-greeting">
                        <i class="ti {{ $heroIcon }}"></i>{{ $heroGreet }}
                        @if($heroCompanyName && $authUser->type !== 'company' && $authUser->type !== 'super admin')
                            <span style="opacity:.85; margin-left:6px;">· {{ $heroCompanyName }}</span>
                        @endif
                    </p>
                    <h2 class="dash-hero-name">{{ $heroName }} 👋</h2>
                    <div class="dash-hero-sub">
                        @if($heroEmpRow && $heroEmpRow->designation)
                            {{ optional($heroEmpRow->designation)->name }}
                            @if(optional($heroEmpRow->branches)->name) · {{ $heroEmpRow->branches->name }} @endif
                            @if($heroCompanyName && $authUser->type !== 'company' && $authUser->type !== 'super admin')
                                · <strong>{{ $heroCompanyName }}</strong>
                            @endif
                        @elseif($heroCompanyName && $authUser->type !== 'company' && $authUser->type !== 'super admin')
                            {{ $heroCompanyName }}
                        @else
                            {{ __('Welcome back to your dashboard') }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="dash-hero-right">
                <div class="hero-date-pill">
                    <i class="ti ti-calendar-event" style="font-size:1.3rem;"></i>
                    <div>
                        <div class="hd-day">{{ now()->format('d') }} {{ now()->format('M') }}</div>
                        <div class="hd-meta">{{ now()->format('l') }} · <span id="dashHeroClock">{{ now()->format('h:i A') }}</span></div>
                    </div>
                </div>
                @if(\Route::has('grievances.create'))
                    <a href="{{ route('grievances.create') }}" class="hero-quick-btn" title="{{ __('Raise Grievance') }}">
                        <i class="ti ti-message-circle-plus"></i> {{ __('Grievance') }}
                    </a>
                @endif
                @if(\Route::has('exit-management.create'))
                    <a href="{{ route('exit-management.index') }}" class="hero-quick-btn" title="{{ __('Exit Management') }}">
                        <i class="ti ti-logout"></i> {{ __('Exit') }}
                    </a>
                @endif
                <a href="{{ route('profile') }}" class="hero-quick-btn is-primary">
                    <i class="ti ti-user-circle"></i> {{ __('My Profile') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

        {{-- Login time & last logout time --}}
        @if ((isset($currentLoginDetail) && !empty($currentLoginDetail)) || (isset($lastLogoutDetail) && !empty($lastLogoutDetail) && !empty($lastLogoutDetail->logout_at)))
            <div class="col-12 mb-3">
                <div class="dash-login-strip">
                    @if (!empty($currentLoginDetail))
                        <span class="item">
                            <i class="ti ti-login"></i>
                            <strong>{{ __('Login') }}:</strong>
                            {{ \Auth::user()->dateFormat($currentLoginDetail->date) ?? $currentLoginDetail->date }}
                        </span>
                    @endif
                    @if (!empty($lastLogoutDetail) && $lastLogoutDetail->logout_at)
                        <span class="item">
                            <i class="ti ti-logout"></i>
                            <strong>{{ __('Last logout') }}:</strong>
                            {{ \Auth::user()->dateFormat($lastLogoutDetail->logout_at->format('Y-m-d H:i:s')) ?? $lastLogoutDetail->logout_at->format('M d, Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Mark Attendance card --}}
        @if(isset($officeTime) && (!empty($showAttendanceCard) || \Auth::user()->type == 'employee'))
            <div class="col-12 mb-3">
                <div class="card attendance-dashboard-card dash-section-card">
                    <div class="card-header">
                        <h5 class="dash-section-title"><i class="ti ti-clock"></i>{{ __('Mark Attendance') }}</h5>
                        <small class="dash-section-meta"><i class="ti ti-building me-1"></i>{{ __('Office Time') }}: {{ $officeTime['startTime'] ?? '09:00' }} – {{ $officeTime['endTime'] ?? '18:00' }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 attendance-actions">
                            <div class="col-md-6">
                                <div class="attendance-block attendance-clock-in rounded border p-3 h-100">
                                    <div class="attendance-label text-muted small text-uppercase mb-2">{{ __('Clock In') }}</div>
                                    @if (!empty($employeeAttendance) && !empty($employeeAttendance->clock_in))
                                        <div class="attendance-time-display">
                                            <span class="attendance-time-value">{{ \Auth::user()->timeFormat($employeeAttendance->clock_in) }}</span>
                                            <small class="d-block text-muted mt-1">{{ __('Clocked in – use Clock Out below') }}</small>
                                        </div>
                                    @else
                                        {{ Form::open(['url' => 'attendanceemployee/attendance', 'method' => 'post', 'id' => 'clockInForm', 'enctype' => 'multipart/form-data']) }}
                                        <input type="hidden" name="device_type" id="device_type" value="">
                                        <input type="hidden" name="latitude" id="latitude" value="">
                                        <input type="hidden" name="longitude" id="longitude" value="">
                                        <input type="hidden" name="address" id="address" value="">
                                        <input type="hidden" name="photo_base64" id="photo_base64" value="">
                                        <button type="button" id="clock_in" class="btn btn-primary w-100" data-hrms-open-clock-in="1">
                                            <i class="ti ti-login me-1"></i>{{ __('Clock In') }}
                                        </button>
                                        {{ Form::close() }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="attendance-block attendance-clock-out rounded border p-3 h-100">
                                    <div class="attendance-label text-muted small text-uppercase mb-2">{{ __('Clock Out') }}</div>
                                    @if (!empty($employeeAttendance) && (empty($employeeAttendance->clock_out) || $employeeAttendance->clock_out == '00:00:00'))
                                        {{ Form::model($employeeAttendance, ['route' => ['attendanceemployee.update', $employeeAttendance->id], 'method' => 'PUT', 'id' => 'clockOutForm']) }}
                                        <input type="hidden" name="device_type_out" id="device_type_out" value="">
                                        <input type="hidden" name="latitude_out" id="latitude_out" value="">
                                        <input type="hidden" name="longitude_out" id="longitude_out" value="">
                                        <input type="hidden" name="address_out" id="address_out" value="">
                                        <input type="hidden" name="photo_base64_out" id="photo_base64_out" value="">
                                        <input type="hidden" name="out" value="1">
                                        <button type="button" id="clock_out" class="btn btn-danger w-100" data-hrms-open-clock-out="1">
                                            <i class="ti ti-logout me-1"></i>{{ __('Clock Out') }}
                                        </button>
                                        {{ Form::close() }}
                                    @else
                                        <div class="attendance-time-display">
                                            <span class="attendance-time-value text-muted">{{ __('—') }}</span>
                                            <small class="d-block text-muted mt-1">{{ __('Clock in to start') }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @php
                            $todaySessions = $todaySessions ?? collect();
                            $todaySessionsCompleted = $todaySessions->filter(function ($s) {
                                $cout = trim((string) ($s->clock_out ?? '00:00:00'));
                                return $cout !== '' && $cout !== '00:00:00';
                            })->values();
                        @endphp
                        @if($todaySessionsCompleted->isNotEmpty())
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="mb-2"><i class="ti ti-list me-1"></i>{{ __("Today's sessions") }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('#') }}</th>
                                                <th>{{ __('Clock In') }}</th>
                                                <th>{{ __('Clock Out') }}</th>
                                                <th>{{ __('Duration') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $totalSeconds = 0; @endphp
                                            @foreach($todaySessionsCompleted as $idx => $sess)
                                                @php
                                                    $cin = trim((string) ($sess->clock_in ?? '00:00:00'));
                                                    $cout = trim((string) ($sess->clock_out ?? '00:00:00'));
                                                    if ($cin === '') { $cin = '00:00:00'; }
                                                    if ($cout === '' || $cout === '00:00:00') { $cout = '00:00:00'; }
                                                    $isOpen = ($cout === '00:00:00');
                                                    $diffSec = 0;
                                                    if (!$isOpen) {
                                                        try {
                                                            $t1 = \Carbon\Carbon::parse('1970-01-01 ' . $cin);
                                                            $t2 = \Carbon\Carbon::parse('1970-01-01 ' . $cout);
                                                            $diffSec = (int) $t1->diffInSeconds($t2, false);
                                                            if ($diffSec < 0) { $diffSec = 0; }
                                                            $totalSeconds += $diffSec;
                                                        } catch (\Throwable $e) {
                                                            $diffSec = 0;
                                                        }
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>{{ $idx + 1 }}</td>
                                                    <td>{{ \Auth::user()->timeFormat($cin) }}</td>
                                                    <td>
                                                        @if($isOpen)
                                                            <span class="text-muted">{{ __('—') }}</span>
                                                        @else
                                                            {{ \Auth::user()->timeFormat($cout) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($isOpen)
                                                            <span class="badge bg-warning text-dark">{{ __('In progress') }}</span>
                                                        @else
                                                            @php
                                                                $h = (int) floor($diffSec / 3600);
                                                                $m = (int) floor(($diffSec % 3600) / 60);
                                                                if ($h < 0) { $h = 0; }
                                                                if ($m < 0) { $m = 0; }
                                                            @endphp
                                                            {{ sprintf('%d h %02d m', $h, $m) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">{{ __('Total time today') }}:</th>
                                                <th>
                                                    @php
                                                        $totalSeconds = max(0, (int) ($totalSeconds ?? 0));
                                                        $h = (int) floor($totalSeconds / 3600);
                                                        $m = (int) floor(($totalSeconds % 3600) / 60);
                                                    @endphp
                                                    {{ sprintf('%d h %02d m', $h, $m) }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if (\Auth::user()->type == 'employee')
            @if (!empty($pendingSubstituteLeaves) && $pendingSubstituteLeaves->count() > 0)
                <div class="col-12">
                    <div class="alert alert-warning d-flex align-items-center justify-content-between">
                        <div>
                            <strong>{{ __('Substitute requests pending.') }}</strong>
                            {{ __('You have') }} {{ $pendingSubstituteLeaves->count() }} {{ __('leave request(s) to review.') }}
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#substituteRequestModal">
                            {{ __('Review') }}
                        </button>
                    </div>
                </div>

                <div class="modal fade" id="substituteRequestModal" tabindex="-1" role="dialog"
                    aria-labelledby="substituteRequestModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="substituteRequestModalLabel">
                                    {{ __('Substitute Leave Requests') }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @foreach ($pendingSubstituteLeaves as $leave)
                                    <div class="border rounded p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="me-3">
                                                <div class="fw-semibold">{{ optional($leave->employees)->name }}</div>
                                                <div class="text-muted small">
                                                    {{ optional($leave->leaveType)->title }}
                                                </div>
                                                <div class="text-muted small">
                                                    {{ $leave->start_date }} to {{ $leave->end_date }}
                                                </div>
                                                <div class="text-muted small">
                                                    {{ ucwords(str_replace('_', ' ', $leave->day_type)) }}
                                                </div>
                                                @if (!empty($leave->leave_reason))
                                                    <div class="mt-2">{{ $leave->leave_reason }}</div>
                                                @endif
                                            </div>
                                            <div class="text-end">
                                                <form method="POST" action="{{ route('leave.substitute.respond') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="leave_id" value="{{ $leave->id }}">
                                                    <input type="hidden" name="action" value="accept">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        {{ __('Accept') }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('leave.substitute.respond') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="leave_id" value="{{ $leave->id }}">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        {{ __('Reject') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-xxl-6 order-2 order-xxl-2">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-6">
                                <h5>{{ __('Calendar') }}</h5>
                                <input type="hidden" id="path_admin" value="{{ url('/') }}">
                            </div>
                            <div class="col-lg-6">
                                <label for=""></label>
                                @if (isset($setting['is_enabled']) && $setting['is_enabled'] == 'on')
                                    <select class="form-control" name="calender_type" id="calender_type"
                                        style="float: right;width: 155px;" onchange="get_data()">
                                        <option value="google_calender">{{ __('Google Calendar') }}</option>
                                        <option value="local_calender" selected="true">
                                            {{ __('Local Calendar') }}</option>
                                    </select>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-2 dash-cal-body">
                        <div id='event_calendar' class='calendar'></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 order-1 order-xxl-1">
                <div class="card h-100">
                    <div class="card-header card-body table-border-style">
                        <h5>{{ __('Meeting schedule') }}</h5>
                    </div>
                    <div class="card-body" style="max-height: 460px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Meeting title') }}</th>
                                        <th>{{ __('Meeting Date') }}</th>
                                        <th>{{ __('Meeting Time') }}</th>
                                        <th>{{ __('Link') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="list">
                                    @foreach ($meetings as $meeting)
                                        <tr>
                                            <td>{{ $meeting->title }}</td>
                                            <td>{{ \Auth::user()->dateFormat($meeting->date) }}</td>
                                            <td>{{ \Auth::user()->timeFormat($meeting->time) }}</td>
                                            <td>
                                                @if($meeting->meet_link)
                                                    <a href="{{ $meeting->meet_link }}" target="_blank" class="btn btn-sm btn-success py-0 px-2">
                                                        <i class="ti ti-video me-1"></i>{{ __('Join') }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12 col-lg-12 col-md-12 order-3 order-xxl-3">
                <div class="card">
                    <div class="card-header card-body table-border-style">
                        <h5>{{ __('Announcement List') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Start Date') }}</th>
                                        <th>{{ __('End Date') }}</th>
                                        <th>{{ __('Description') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="list">
                                    @foreach ($announcements as $announcement)
                                        <tr>
                                            <td>{{ $announcement->title }}</td>
                                            <td>{{ \Auth::user()->dateFormat($announcement->start_date) }}</td>
                                            <td>{{ \Auth::user()->dateFormat($announcement->end_date) }}</td>
                                            <td>{{ $announcement->description }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- ── KPI Stats Row (4 cards, equal height, gradient accent) ── --}}
            <div class="col-12">
                <div class="row g-3 mb-2">
                    {{-- Total Staff with bifurcation --}}
                    <div class="col-xl-3 col-md-6">
                        <div class="card dash-stats-card tone-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="dash-stats-label">{{ __('Total') }}</div>
                                        <div class="dash-stats-title"><a href="{{ route('user.index') }}">{{ __('Staff') }}</a></div>
                                    </div>
                                    <span class="dash-stats-icon"><i class="ti ti-users"></i></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-end mt-3">
                                    <span class="dash-stats-value">{{ $totalStaff ?? $countEmployee }}</span>
                                </div>
                                @if(!empty($staffBreakdown))
                                    <div class="staff-mini-grid">
                                        @foreach($staffBreakdown as $row)
                                            <div class="staff-cell t-{{ $row['code'] }}" title="{{ $row['name'] }}: {{ $row['count'] }}">
                                                <div class="sc-code">{{ $row['code'] }}</div>
                                                <div class="sc-count">{{ $row['count'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Today's Not Clocked-in --}}
                    <div class="col-xl-3 col-md-6">
                        <div class="card dash-stats-card tone-amber">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="dash-stats-label">{{ __('Today') }}</div>
                                        <div class="dash-stats-title"><a href="{{ route('attendanceemployee.index') }}">{{ __("Not Clocked-in") }}</a></div>
                                    </div>
                                    <span class="dash-stats-icon"><i class="ti ti-user-off"></i></span>
                                </div>
                                <div class="mt-3">
                                    <span class="dash-stats-value">{{ count($notClockIns ?? []) }}</span>
                                </div>
                                <small class="text-muted d-block mt-2"><i class="ti ti-clock-off me-1"></i>{{ __('Employees pending check-in') }}</small>
                            </div>
                        </div>
                    </div>

                    {{-- Active Jobs --}}
                    <div class="col-xl-3 col-md-6">
                        <div class="card dash-stats-card tone-teal">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="dash-stats-label">{{ __('Active') }}</div>
                                        <div class="dash-stats-title"><a href="{{ route('job.index') }}">{{ __('Active Jobs') }}</a></div>
                                    </div>
                                    <span class="dash-stats-icon"><i class="ti ti-rocket"></i></span>
                                </div>
                                <div class="mt-3">
                                    <span class="dash-stats-value">{{ $activeJob }}</span>
                                </div>
                                <small class="text-muted d-block mt-2"><i class="ti ti-circle-check me-1"></i>{{ __('Currently hiring') }}</small>
                            </div>
                        </div>
                    </div>

                    {{-- Attrition Analysis (Full-time only, last 12 months) --}}
                    <div class="col-xl-3 col-md-6">
                        @php
                            $rate = (float) ($attritionRate ?? 0);
                            // Tone shifts as attrition rises: teal (healthy) → amber → rose
                            $attritionTone = $rate < 8 ? 'tone-teal' : ($rate < 15 ? 'tone-amber' : 'tone-rose');
                            $attritionTrend = $rate < 8 ? __('Healthy') : ($rate < 15 ? __('Watch') : __('High'));
                            $attritionTrendIcon = $rate < 8 ? 'ti-trending-down' : 'ti-trending-up';
                        @endphp
                        <div class="card dash-stats-card {{ $attritionTone }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="dash-stats-label">{{ __('Annual') }}</div>
                                        <div class="dash-stats-title">{{ __('Attrition · FT') }}</div>
                                    </div>
                                    <span class="dash-stats-icon"><i class="ti ti-user-minus"></i></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-end mt-3">
                                    <span class="dash-stats-value">{{ number_format($rate, 1) }}%</span>
                                    <small class="text-muted">
                                        <i class="ti {{ $attritionTrendIcon }} me-1"></i>{{ $attritionTrend }}
                                    </small>
                                </div>
                                <div class="d-flex gap-3 mt-2" style="font-size:.7rem;color:#64748b;">
                                    <span><strong style="color:#0f172a;">{{ $attritionLeft ?? 0 }}</strong> {{ __('left') }}</span>
                                    <span class="text-muted">·</span>
                                    <span>{{ __('Avg HC') }} <strong style="color:#0f172a;">{{ $attritionAvgHc ?? 0 }}</strong></span>
                                    <span class="text-muted ms-auto" title="{{ __('Last 12 months, Full-time only') }}">
                                        <i class="ti ti-info-circle"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (Auth::user()->type == 'company' || Auth::user()->type == 'hr')
                <div class="col-12">
                    <div class="card dash-section-card">
                        <div class="card-header">
                            <h5 class="dash-section-title"><i class="ti ti-chart-pie"></i>{{ __('Analytics Overview') }}</h5>
                            <small class="dash-section-meta">{{ __('Visual breakdown of current HR metrics') }} · {{ now()->format('d M Y') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                {{-- Today's Department-wise Attendance --}}
                                <div class="col-lg-7 col-md-12">
                                    <div class="dashboard-chart-card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="dashboard-chart-title mb-0">
                                                <i class="ti ti-calendar-stats me-1 text-primary"></i>{{ __('Daily Attendance — Department-wise') }}
                                            </h6>
                                            <span class="badge bg-light text-dark">{{ now()->format('d M Y') }}</span>
                                        </div>
                                        @if(!empty($dailyAttendanceByDept))
                                            <div id="daily-attendance-chart"></div>
                                        @else
                                            <div class="text-center text-muted py-5" style="font-size:.85rem;">
                                                <i class="ti ti-mood-empty d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
                                                {{ __('No department data to show.') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Team Distribution (donut by Employee Type) --}}
                                <div class="col-lg-5 col-md-12">
                                    <div class="dashboard-chart-card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="dashboard-chart-title mb-0">
                                                <i class="ti ti-users-group me-1 text-primary"></i>{{ __('Team Distribution') }}
                                            </h6>
                                            <small class="text-muted">{{ __('By type') }}</small>
                                        </div>
                                        @if(!empty($teamDistribution))
                                            <div id="team-distribution-chart"></div>
                                        @else
                                            <div class="text-center text-muted py-5" style="font-size:.85rem;">
                                                <i class="ti ti-mood-empty d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
                                                {{ __('No team data yet.') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ((Auth::user()->type == 'company' || Auth::user()->type == 'hr') && !empty($monthlyEmployeeData))
                <div class="col-12">
                    <div class="card dash-section-card">
                        <div class="card-header">
                            <h5 class="dash-section-title"><i class="ti ti-trending-up"></i>{{ __('Monthly Employee Count') }}</h5>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary active" id="empChartToggleJoined">
                                    <i class="ti ti-user-plus me-1"></i>{{ __('New Joined') }}
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="empChartToggleTotal">
                                    <i class="ti ti-users me-1"></i>{{ __('Total Strength') }}
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="monthly-employee-chart" style="min-height:320px;"></div>
                        </div>
                    </div>
                </div>
            @endif

            @if (Auth::user()->type == 'company' || Auth::user()->type == 'hr')
                @if (!empty($pendingLeaveApprovals) && $pendingLeaveApprovals->count() > 0)
                    <div class="col-12">
                        <div class="card pending-leave-card">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 w-100">
                                    <div>
                                        <h5 class="dash-section-title mb-1">
                                            <i class="ti ti-alarm" style="background:linear-gradient(135deg,#ef4444,#f59e0b);"></i>
                                            {{ __('Pending Leave Approvals') }}
                                        </h5>
                                        <small class="text-muted">
                                            <span class="leave-pulse"></span>
                                            <strong class="text-danger">{{ $pendingLeaveApprovals->count() }}</strong> {{ __('awaiting your action') }}
                                        </small>
                                    </div>
                                    <a href="{{ route('leave.index') }}" class="btn btn-sm btn-danger">
                                        {{ __('View All') }} <i class="ti ti-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead style="background:#fafafa;">
                                            <tr style="font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; color:#64748b;">
                                                <th class="ps-3 py-3">{{ __('Employee') }}</th>
                                                <th>{{ __('Leave Type') }}</th>
                                                <th>{{ __('Dates') }}</th>
                                                <th class="text-center">{{ __('Days') }}</th>
                                                <th class="pe-3 text-end">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pendingLeaveApprovals as $leave)
                                                @php
                                                    $empName = !empty($leave->employees) ? $leave->employees->name : 'N/A';
                                                    $initials = collect(preg_split('/\s+/', trim($empName)))
                                                        ->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
                                                @endphp
                                                <tr>
                                                    <td class="ps-3 py-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;">
                                                                {{ strtoupper($initials ?: 'NA') }}
                                                            </span>
                                                            <strong>{{ $empName }}</strong>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge" style="background:#ede9fe;color:#5b21b6;font-weight:600;">
                                                            {{ !empty($leave->leaveType) ? $leave->leaveType->title : 'N/A' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <i class="ti ti-calendar me-1"></i>{{ \Auth::user()->dateFormat($leave->start_date) }}
                                                            <span class="mx-1">→</span>
                                                            {{ \Auth::user()->dateFormat($leave->end_date) }}
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge" style="background:#fff7ed;color:#c2410c;font-weight:700;font-size:.78rem;">
                                                            {{ $leave->total_leave_days }}
                                                        </span>
                                                    </td>
                                                    <td class="pe-3 text-end">
                                                        <a href="{{ route('leave.edit', $leave->id) }}" class="btn btn-sm btn-light border me-1" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                        <a href="{{ route('leave.edit', $leave->id) }}#approve" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="{{ __('Approve') }}">
                                                            <i class="ti ti-check"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <div class="col-xxl-12">
                <div class="row g-3">
                    <div class="col-xl-5">
                        @if (\Auth::user()->type == 'company')
                            <div class="card dash-section-card mb-3">
                                <div class="card-header">
                                    <h5 class="dash-section-title"><i class="ti ti-database"></i>{{ __('Storage Status') }}</h5>
                                    <small class="dash-section-meta">
                                        <strong>{{ $users->storage_limit }}MB</strong> / {{ $plan->storage_limit }}MB
                                    </small>
                                </div>
                                <div class="card-body">
                                    @php $storagePct = $plan->storage_limit > 0 ? round(($users->storage_limit / $plan->storage_limit) * 100, 1) : 0; @endphp
                                    <div class="d-flex justify-content-between mb-2" style="font-size:.78rem;">
                                        <span class="text-muted">{{ __('Used') }}</span>
                                        <strong>{{ $storagePct }}%</strong>
                                    </div>
                                    <div class="storage-bar-wrap">
                                        <div class="storage-bar-fill" style="width: {{ min($storagePct, 100) }}%;"></div>
                                    </div>
                                    <div id="device-chart" class="mt-3"></div>
                                </div>
                            </div>
                        @endif

                        <div class="card dash-section-card mb-3">
                            <div class="card-header">
                                <h5 class="dash-section-title"><i class="ti ti-calendar-event" style="background:linear-gradient(135deg,#0ea5e9,#06b6d4);"></i>{{ __('Meeting Schedule') }}</h5>
                                <small class="dash-section-meta">{{ count($meetings ?? []) }} {{ __('upcoming') }}</small>
                            </div>
                            <div class="card-body p-0" style="height: 290px; overflow:auto">
                                @if(!empty($meetings) && count($meetings) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead style="background:#fafafa;">
                                                <tr style="font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">
                                                    <th class="ps-3 py-2">{{ __('Title') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Time') }}</th>
                                                    <th class="pe-3">{{ __('Link') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($meetings as $meeting)
                                                    <tr>
                                                        <td class="ps-3"><strong>{{ $meeting->title }}</strong></td>
                                                        <td><small class="text-muted"><i class="ti ti-calendar me-1"></i>{{ \Auth::user()->dateFormat($meeting->date) }}</small></td>
                                                        <td><small class="text-muted"><i class="ti ti-clock me-1"></i>{{ \Auth::user()->timeFormat($meeting->time) }}</small></td>
                                                        <td class="pe-3">
                                                            @if($meeting->meet_link)
                                                                <a href="{{ $meeting->meet_link }}" target="_blank" class="btn btn-sm btn-success py-0 px-2">
                                                                    <i class="ti ti-video me-1"></i>{{ __('Join') }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="dash-empty"><i class="ti ti-calendar-off"></i>{{ __('No meetings scheduled.') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="card dash-section-card">
                            <div class="card-header">
                                <h5 class="dash-section-title"><i class="ti ti-user-off" style="background:linear-gradient(135deg,#ef4444,#f43f5e);"></i>{{ __("Not Clocked In Today") }}</h5>
                                <small class="dash-section-meta">{{ count($notClockIns ?? []) }} {{ __('employees') }}</small>
                            </div>
                            <div class="card-body p-0" style="height: 290px; overflow:auto">
                                @if(!empty($notClockIns) && count($notClockIns) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead style="background:#fafafa;">
                                                <tr style="font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">
                                                    <th class="ps-3 py-2">{{ __('Name') }}</th>
                                                    <th class="pe-3 text-end">{{ __('Status') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($notClockIns as $notClockIn)
                                                    @php
                                                        $initials = collect(preg_split('/\s+/', trim($notClockIn->name ?? '')))
                                                            ->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-3">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#94a3b8,#cbd5e1);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;">
                                                                    {{ strtoupper($initials ?: 'NA') }}
                                                                </span>
                                                                <strong>{{ $notClockIn->name }}</strong>
                                                            </div>
                                                        </td>
                                                        <td class="pe-3 text-end">
                                                            <span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:600;">
                                                                <i class="ti ti-x me-1"></i>{{ __('Absent') }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="dash-empty"><i class="ti ti-checks"></i>{{ __('Everyone is clocked in.') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-7">
                        <div class="card dash-section-card">
                            <div class="card-header">
                                <h5 class="dash-section-title"><i class="ti ti-calendar" style="background:linear-gradient(135deg,#10b981,#14b8a6);"></i>{{ __('Calendar') }}</h5>
                                <input type="hidden" id="path_admin" value="{{ url('/') }}">
                                @if (isset($setting['is_enabled']) && $setting['is_enabled'] == 'on')
                                    <select class="form-control form-control-sm" name="calender_type" id="calender_type"
                                        style="width: 160px;" onchange="get_data()">
                                        <option value="google_calender">{{ __('Google Calendar') }}</option>
                                        <option value="local_calender" selected="true">{{ __('Local Calendar') }}</option>
                                    </select>
                                @endif
                            </div>
                            <div class="card-body card-635">
                                <div id='calendar' class='calendar'></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (\Auth::user()->type == 'company')
                <div class="col-xl-12 col-lg-12 col-md-12">
                    <div class="card dash-section-card">
                        <div class="card-header">
                            <h5 class="dash-section-title"><i class="ti ti-speakerphone" style="background:linear-gradient(135deg,#f59e0b,#ef4444);"></i>{{ __('Announcement List') }}</h5>
                            <small class="dash-section-meta">{{ count($announcements ?? []) }} {{ __('updates') }}</small>
                        </div>
                        <div class="card-body p-0" style="height: 324px; overflow:auto">
                            @if(!empty($announcements) && count($announcements) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead style="background:#fafafa;">
                                            <tr style="font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">
                                                <th class="ps-3 py-2">{{ __('Title') }}</th>
                                                <th>{{ __('Start') }}</th>
                                                <th>{{ __('End') }}</th>
                                                <th class="pe-3">{{ __('Description') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($announcements as $announcement)
                                                <tr>
                                                    <td class="ps-3"><strong>{{ $announcement->title }}</strong></td>
                                                    <td><small class="text-muted"><i class="ti ti-calendar me-1"></i>{{ \Auth::user()->dateFormat($announcement->start_date) }}</small></td>
                                                    <td><small class="text-muted">{{ \Auth::user()->dateFormat($announcement->end_date) }}</small></td>
                                                    <td class="pe-3"><small>{{ \Illuminate\Support\Str::limit($announcement->description, 120) }}</small></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="dash-empty"><i class="ti ti-speakerphone-off"></i>{{ __('No announcements yet.') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if (\Auth::user()->type != 'company')
                <div class="col-xl-12 col-lg-12 col-md-12">
                    <div class="card">
                        <div class="card-header card-body table-border-style">
                            <h5>{{ __('Announcement List') }}</h5>
                        </div>
                        <div class="card-body" style="height: 270px; overflow:auto">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Title') }}</th>
                                            <th>{{ __('Start Date') }}</th>
                                            <th>{{ __('End Date') }}</th>
                                            <th>{{ __('Description') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @foreach ($announcements as $announcement)
                                            <tr>
                                                <td>{{ $announcement->title }}</td>
                                                <td>{{ \Auth::user()->dateFormat($announcement->start_date) }}</td>
                                                <td>{{ \Auth::user()->dateFormat($announcement->end_date) }}</td>
                                                <td>{{ $announcement->description }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Camera Modal for Clock In -->
    @if (\Auth::user()->type == 'employee' || !empty($showAttendanceCard))
    <div class="modal fade" id="cameraModal" tabindex="-1" role="dialog" aria-labelledby="cameraModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cameraModalLabel">{{ __('Capture Photo for Attendance') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-info small mb-2"><i class="ti ti-photo me-1"></i>{{ __('Real-time snapshot only — no screen or video recording.') }}</p>
                    <div class="camera-wrapper" style="position: relative; width: 100%; max-width: 640px; margin: 0 auto;">
                        <video id="camera-video" autoplay playsinline style="width: 100%; max-height: 480px; border: 2px solid #ddd; border-radius: 8px;"></video>
                        <img id="captured-photo" style="display: none; width: 100%; max-height: 480px; border: 2px solid #ddd; border-radius: 8px;" />
                        <canvas id="photo-canvas" style="display: none;"></canvas>
                    </div>
                    <div class="mt-3">
                        <button type="button" id="capture-btn" class="btn btn-success" onclick="capturePhoto()">
                            <i class="ti ti-camera"></i> {{ __('Take Snapshot') }}
                        </button>
                        <button type="button" id="recapture-btn" class="btn btn-warning" onclick="recapturePhoto()" style="display: none;">
                            <i class="ti ti-refresh"></i> {{ __('Retake Snapshot') }}
                        </button>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted" id="location-info">{{ __('Getting your location...') }}</small>
                    </div>
                    <div class="mt-2 text-start" id="camera-busy-fallback-in" style="display: none;">
                        <p class="small text-warning mb-1">{{ __('If the live camera failed, close Zoom/Teams/Meet, then retry or use upload below.') }}</p>
                        <button type="button" class="btn btn-outline-primary btn-sm me-1 mb-1" id="retry-live-camera-in">{{ __('Retry live camera') }}</button>
                        <label for="camera-file-busy-in" class="btn btn-outline-secondary btn-sm mb-0">{{ __('Choose / capture photo') }}</label>
                        <input type="file" id="camera-file-busy-in" accept="image/*" capture="user" style="display: none;">
                    </div>
                    <p class="text-muted small mt-2 mb-0"><i class="ti ti-user-check me-1"></i>{{ __('This photo will be verified with your profile photo for clock-in.') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="submitClockIn()">
                        <i class="ti ti-clock"></i> {{ __('Clock In') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Camera Modal for Clock Out -->
    @if (\Auth::user()->type == 'employee' || !empty($showAttendanceCard))
    <div class="modal fade" id="cameraModalClockOut" tabindex="-1" role="dialog" aria-labelledby="cameraModalClockOutLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cameraModalClockOutLabel">{{ __('Capture Photo for Checkout') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-info small mb-2"><i class="ti ti-photo me-1"></i>{{ __('Real-time snapshot only — no screen or video recording.') }}</p>
                    <div class="camera-wrapper-out" style="position: relative; width: 100%; max-width: 640px; margin: 0 auto;">
                        <video id="camera-video-out" autoplay playsinline style="width: 100%; max-height: 480px; border: 2px solid #ddd; border-radius: 8px;"></video>
                        <img id="captured-photo-out" style="display: none; width: 100%; max-height: 480px; border: 2px solid #ddd; border-radius: 8px;" />
                        <canvas id="photo-canvas-out" style="display: none;"></canvas>
                    </div>
                    <div class="mt-3">
                        <button type="button" id="capture-btn-out" class="btn btn-success" onclick="capturePhotoOut()">
                            <i class="ti ti-camera"></i> {{ __('Take Snapshot') }}
                        </button>
                        <button type="button" id="recapture-btn-out" class="btn btn-warning" onclick="recapturePhotoOut()" style="display: none;">
                            <i class="ti ti-refresh"></i> {{ __('Retake Snapshot') }}
                        </button>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted" id="location-info-out">{{ __('Getting your location...') }}</small>
                    </div>
                    <div class="mt-2 text-start" id="camera-busy-fallback-out" style="display: none;">
                        <p class="small text-warning mb-1">{{ __('If the live camera failed, close Zoom/Teams/Meet, then retry or use upload below.') }}</p>
                        <button type="button" class="btn btn-outline-primary btn-sm me-1 mb-1" id="retry-live-camera-out">{{ __('Retry live camera') }}</button>
                        <label for="camera-file-busy-out" class="btn btn-outline-secondary btn-sm mb-0">{{ __('Choose / capture photo') }}</label>
                        <input type="file" id="camera-file-busy-out" accept="image/*" capture="user" style="display: none;">
                    </div>
                    <p class="text-muted small mt-2 mb-0"><i class="ti ti-user-check me-1"></i>{{ __('This photo will be verified with your profile photo for checkout (same as clock-in).') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-danger" onclick="submitClockOut()">
                        <i class="ti ti-clock"></i> {{ __('Clock Out') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection



@push('script-page')
    <script src="{{ asset('assets/js/plugins/main.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>

    @if (!empty($pendingSubstituteLeaves) && $pendingSubstituteLeaves->count() > 0)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modalEl = document.getElementById('substituteRequestModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
        </script>
    @endif

    @if (Auth::user()->type == 'company' || Auth::user()->type == 'hr')
        <script type="text/javascript">
            $(document).ready(function() {
                get_data();
                initDashboardCharts();
            });

            function get_data() {
                var calender_type = $('#calender_type :selected').val();

                $('#calendar').removeClass('local_calender');
                $('#calendar').removeClass('google_calender');
                if (calender_type == undefined) {
                    calender_type = 'local_calender';
                }
                $('#calendar').addClass(calender_type);

                $.ajax({
                    url: $("#path_admin").val() + "/event/get_event_data",
                    method: "POST",
                    data: {
                        "_token": "{{ csrf_token() }}",
                        'calender_type': calender_type
                    },
                    success: function(data) {

                        var etitle;
                        var etype;
                        var etypeclass;
                        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay'
                            },
                            buttonText: {
                                timeGridDay: "{{ __('Day') }}",
                                timeGridWeek: "{{ __('Week') }}",
                                dayGridMonth: "{{ __('Month') }}"
                            },
                            // slotLabelFormat: {
                            //     hour: '2-digit',
                            //     minute: '2-digit',
                            //     hour12: false,
                            // },
                            themeSystem: 'bootstrap',
                            slotDuration: '00:10:00',
                            allDaySlot: true,
                            navLinks: true,
                            droppable: true,
                            selectable: true,
                            selectMirror: true,
                            editable: true,
                            dayMaxEvents: true,
                            handleWindowResize: true,
                            events: data,
                            // height: 'auto',
                            // timeFormat: 'H(:mm)',
                        });
                        calendar.render();
                    }
                });
            };

            function initDashboardCharts() {
                if (typeof ApexCharts === 'undefined') {
                    return;
                }

                if (document.querySelector("#ticket-status-chart")) {
                    const ticketChart = new ApexCharts(document.querySelector("#ticket-status-chart"), {
                        chart: {
                            type: 'donut',
                            height: 230,
                            toolbar: {
                                show: false
                            }
                        },
                        labels: ["{{ __('Open') }}", "{{ __('Closed') }}"],
                        series: [{{ $countOpenTicket }}, {{ $countCloseTicket }}],
                        legend: {
                            position: 'bottom'
                        },
                        dataLabels: {
                            enabled: true
                        },
                        stroke: {
                            width: 2
                        },
                    });
                    ticketChart.render();
                }

                // ── Daily Department-wise Attendance (stacked horizontal bar) ──
                const dailyAttnEl = document.querySelector("#daily-attendance-chart");
                if (dailyAttnEl && typeof ApexCharts !== 'undefined') {
                    const deptData = @json($dailyAttendanceByDept ?? []);
                    if (deptData.length > 0) {
                        const categories = deptData.map(d => d.department);
                        const series = [
                            { name: "{{ __('Present') }}",    data: deptData.map(d => d.present) },
                            { name: "{{ __('Half Day') }}",   data: deptData.map(d => d.half_day) },
                            { name: "{{ __('Leave') }}",      data: deptData.map(d => d.leave) },
                            { name: "{{ __('Absent') }}",     data: deptData.map(d => d.absent) },
                            { name: "{{ __('Not Marked') }}", data: deptData.map(d => d.not_marked) },
                        ];
                        const dailyAttnChart = new ApexCharts(dailyAttnEl, {
                            chart: {
                                type: 'bar',
                                height: Math.max(260, deptData.length * 38 + 80),
                                stacked: true,
                                toolbar: { show: false },
                                fontFamily: 'inherit',
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: true,
                                    borderRadius: 5,
                                    borderRadiusApplication: 'end',
                                    barHeight: '60%',
                                },
                            },
                            colors: ['#10b981', '#f59e0b', '#94a3b8', '#ef4444', '#cbd5e1'],
                            series: series,
                            xaxis: {
                                categories: categories,
                                labels: { style: { fontSize: '11px' } },
                            },
                            yaxis: { labels: { style: { fontSize: '12px', fontWeight: 600 } } },
                            legend: { position: 'top', horizontalAlign: 'left', fontSize: '12px', markers: { width: 10, height: 10, radius: 3 } },
                            dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 700 }, formatter: v => v > 0 ? v : '' },
                            stroke: { width: 0 },
                            tooltip: { theme: 'light' },
                            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                        });
                        dailyAttnChart.render();
                    }
                }

                // ── Team Distribution (donut, gradient palette) ──
                if (document.querySelector("#team-distribution-chart")) {
                    const teamData = @json($teamDistribution ?? []);
                    if (teamData.length > 0) {
                        // Color by employee type code (matches the staff bifurcation grid)
                        const colorMap = {
                            'FT':  '#3b82f6', // blue (Full-time)
                            'PT':  '#8b5cf6', // violet (Part-time)
                            'CON': '#f59e0b', // amber (Consultant)
                            'MT':  '#14b8a6', // teal (Mgmt Trainee)
                            'INT': '#ec4899', // pink (Intern)
                            'OTH': '#94a3b8',
                        };
                        const teamChart = new ApexCharts(document.querySelector("#team-distribution-chart"), {
                            chart: {
                                type: 'donut',
                                height: 280,
                                toolbar: { show: false },
                                fontFamily: 'inherit',
                            },
                            labels: teamData.map(d => d.label),
                            series: teamData.map(d => d.count),
                            colors: teamData.map(d => colorMap[d.code] || '#cbd5e1'),
                            legend: {
                                position: 'bottom',
                                fontSize: '12px',
                                markers: { width: 10, height: 10, radius: 5 },
                                itemMargin: { horizontal: 8, vertical: 4 },
                            },
                            dataLabels: {
                                enabled: true,
                                style: { fontSize: '11px', fontWeight: 600 },
                                dropShadow: { enabled: false },
                                formatter: function (val, opts) {
                                    return opts.w.globals.series[opts.seriesIndex];
                                },
                            },
                            stroke: { width: 3, colors: ['#fff'] },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '68%',
                                        labels: {
                                            show: true,
                                            name: { show: true, fontSize: '13px', color: '#64748b', offsetY: -4 },
                                            value: { show: true, fontSize: '24px', fontWeight: 700, color: '#0f172a', offsetY: 6 },
                                            total: {
                                                show: true,
                                                showAlways: true,
                                                label: '{{ __("Total") }}',
                                                fontSize: '12px',
                                                fontWeight: 600,
                                                color: '#64748b',
                                                formatter: function (w) {
                                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                                },
                                            },
                                        },
                                    },
                                },
                            },
                            tooltip: {
                                theme: 'light',
                                y: { formatter: v => v + ' {{ __("members") }}' },
                            },
                        });
                        teamChart.render();
                    }
                }

                // Monthly Employee Count Chart
                const empChartEl = document.querySelector("#monthly-employee-chart");
                if (empChartEl && typeof ApexCharts !== 'undefined') {
                    const joinedData = @json($monthlyEmployeeData ?? []);
                    const totalData = @json($monthlyEmployeeTotal ?? []);

                    const joinedLabels = joinedData.map(d => d.label);
                    const joinedSeries = joinedData.map(d => d.count);
                    const totalLabels = totalData.map(d => d.label);
                    const totalSeries = totalData.map(d => d.total);

                    let empChart = null;

                    function renderEmpChart(mode) {
                        if (empChart) empChart.destroy();
                        const isJoined = mode === 'joined';
                        empChart = new ApexCharts(empChartEl, {
                            chart: { type: 'bar', height: 320, toolbar: { show: false },
                                animations: { enabled: true, easing: 'easeinout', speed: 600 }
                            },
                            series: [{ name: isJoined ? '{{ __("New Joined") }}' : '{{ __("Total Employees") }}',
                                       data: isJoined ? joinedSeries : totalSeries }],
                            xaxis: { categories: isJoined ? joinedLabels : totalLabels,
                                     labels: { rotate: -45, style: { fontSize: '11px' } } },
                            yaxis: { labels: { style: { fontSize: '12px' } },
                                     forceNiceScale: true, min: 0 },
                            colors: [isJoined ? '#4361ee' : '#059669'],
                            plotOptions: { bar: { borderRadius: 6, columnWidth: '50%',
                                dataLabels: { position: 'top' } } },
                            dataLabels: { enabled: true, offsetY: -20,
                                style: { fontSize: '12px', fontWeight: 700, colors: ['#1e293b'] } },
                            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                            tooltip: { y: { formatter: function(v) { return v + ' {{ __("employees") }}'; } } }
                        });
                        empChart.render();
                    }

                    renderEmpChart('joined');

                    document.getElementById('empChartToggleJoined').addEventListener('click', function() {
                        this.classList.add('active'); document.getElementById('empChartToggleTotal').classList.remove('active');
                        renderEmpChart('joined');
                    });
                    document.getElementById('empChartToggleTotal').addEventListener('click', function() {
                        this.classList.add('active'); document.getElementById('empChartToggleJoined').classList.remove('active');
                        renderEmpChart('total');
                    });
                }
            }
        </script>
    @else
        <script>
            $(document).ready(function() {
                get_data();
            });

            function get_data() {
                var calender_type = $('#calender_type :selected').val();

                $('#event_calendar').removeClass('local_calender');
                $('#event_calendar').removeClass('google_calender');
                if (calender_type == undefined) {
                    calender_type = 'local_calender';
                }
                $('#event_calendar').addClass(calender_type);

                $.ajax({
                    url: $("#path_admin").val() + "/event/get_event_data",
                    method: "POST",
                    data: {
                        "_token": "{{ csrf_token() }}",
                        'calender_type': calender_type
                    },
                    success: function(data) {
                        var etitle;
                        var etype;
                        var etypeclass;
                        var calendar = new FullCalendar.Calendar(document.getElementById('event_calendar'), {
                            // Compact, fixed height so the card doesn't leave a tall empty strip
                            // below it next to the meeting-schedule column.
                            height: 460,
                            handleWindowResize: true,
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay'
                            },
                            buttonText: {
                                timeGridDay: "{{ __('Day') }}",
                                timeGridWeek: "{{ __('Week') }}",
                                dayGridMonth: "{{ __('Month') }}"
                            },
                            // slotLabelFormat: {
                            //     hour: '2-digit',
                            //     minute: '2-digit',
                            //     hour12: false,
                            // },
                            themeSystem: 'bootstrap',
                            slotDuration: '00:10:00',
                            allDaySlot: true,
                            navLinks: true,
                            droppable: true,
                            selectable: true,
                            selectMirror: true,
                            editable: true,
                            dayMaxEvents: true,
                            handleWindowResize: true,
                            events: data,
                            // height: 'auto',
                            // timeFormat: 'H(:mm)',

                        });

                        calendar.render();
                    }
                });
            };
        </script>
    @endif

    @if (\Auth::user()->type == 'company')
        <script>
            (function() {
                var options = {
                    series: [{{ round($storage_limit, 2) }}],
                    chart: {
                        height: 350,
                        type: 'radialBar',
                        offsetY: -20,
                        sparkline: {
                            enabled: true
                        }
                    },
                    plotOptions: {
                        radialBar: {
                            startAngle: -90,
                            endAngle: 90,
                            track: {
                                background: "#e7e7e7",
                                strokeWidth: '97%',
                                margin: 5, // margin is in pixels
                            },
                            dataLabels: {
                                name: {
                                    show: true
                                },
                                value: {
                                    offsetY: -50,
                                    fontSize: '20px'
                                }
                            }
                        }
                    },
                    grid: {
                        padding: {
                            top: -10
                        }
                    },
                    colors: ["#6FD943"],
                    labels: ['Used'],
                };
                var chart = new ApexCharts(document.querySelector("#device-chart"), options);
                chart.render();
            })();
        </script>
    @endif

    @if (\Auth::user()->type == 'employee' || !empty($showAttendanceCard))
        <script>
            // Detect device type
            function detectDeviceType() {
                const userAgent = navigator.userAgent.toLowerCase();
                const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
                const isTablet = /(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/.test(userAgent);
                
                if (isMobile && !isTablet) {
                    return 'Mobile';
                } else if (isTablet) {
                    return 'Tablet';
                } else {
                    return 'Desktop';
                }
            }

            // Get geolocation
            function getLocation() {
                return new Promise((resolve, reject) => {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            async (position) => {
                                const lat = position.coords.latitude;
                                const lon = position.coords.longitude;
                                
                                // Get address from coordinates using reverse geocoding
                                try {
                                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`);
                                    const data = await response.json();
                                    const address = data.display_name || 'Address not found';
                                    
                                    resolve({
                                        latitude: lat,
                                        longitude: lon,
                                        address: address
                                    });
                                } catch (error) {
                                    resolve({
                                        latitude: lat,
                                        longitude: lon,
                                        address: `Lat: ${lat}, Long: ${lon}`
                                    });
                                }
                            },
                            (error) => {
                                console.error('Error getting location:', error);
                                resolve({
                                    latitude: null,
                                    longitude: null,
                                    address: 'Location not available'
                                });
                            }
                        );
                    } else {
                        resolve({
                            latitude: null,
                            longitude: null,
                            address: 'Geolocation not supported'
                        });
                    }
                });
            }

            // Camera Modal
            let videoStream = null;
            let clockInModalInstance = null;
            let clockOutModalInstance = null;
            const CAMERA_CONSTRAINTS = [
                { video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }, audio: false },
                { video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false },
                { video: { facingMode: 'user' }, audio: false },
                { video: true, audio: false }
            ];
            var cameraStartDelayMs = 280;

            function cameraErrorMessage(error) {
                const name = error && error.name ? error.name : 'UnknownError';
                const raw = error && error.message ? String(error.message) : '';
                if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                    return 'Camera permission denied. Allow camera access for localhost in browser settings.';
                }
                if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                    return 'No camera device found. Please connect/enable a webcam.';
                }
                if (name === 'NotReadableError' || name === 'TrackStartError') {
                    return 'Camera is busy in another app (Zoom/Teams/Meet). Close that app and try again.';
                }
                if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') {
                    return 'Requested camera mode is not supported on this device.';
                }
                if (name === 'SecurityError') {
                    return 'Camera blocked by browser security policy.';
                }
                if (name === 'AbortError') {
                    return 'Camera start was aborted. Close camera-using apps and retry.';
                }
                if (raw.toLowerCase().includes('device in use') || raw.toLowerCase().includes('could not start video source')) {
                    return 'Camera is already in use by another application.';
                }
                return 'Unable to access camera. Please allow permissions and close other apps using camera.';
            }

            function isCameraBusyError(error) {
                if (!error) {
                    return false;
                }
                const name = error.name || '';
                const raw = String(error.message || '').toLowerCase();
                return name === 'NotReadableError' || name === 'TrackStartError'
                    || raw.indexOf('device in use') !== -1 || raw.indexOf('could not start video source') !== -1;
            }

            async function acquireVideoStream() {
                let lastCameraError = null;
                for (let i = 0; i < CAMERA_CONSTRAINTS.length; i++) {
                    try {
                        return await navigator.mediaDevices.getUserMedia(CAMERA_CONSTRAINTS[i]);
                    } catch (e) {
                        lastCameraError = e;
                    }
                }
                if (lastCameraError && isCameraBusyError(lastCameraError)) {
                    try {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        const vids = devices.filter(function (d) { return d.kind === 'videoinput'; });
                        for (let j = 0; j < vids.length; j++) {
                            const devId = vids[j].deviceId;
                            if (!devId) {
                                continue;
                            }
                            try {
                                return await navigator.mediaDevices.getUserMedia({
                                    video: { deviceId: { exact: devId } },
                                    audio: false
                                });
                            } catch (e2) {
                                lastCameraError = e2;
                            }
                        }
                    } catch (ignore) {
                        // ignore
                    }
                }
                if (lastCameraError && isCameraBusyError(lastCameraError)) {
                    await new Promise(function (r) { setTimeout(r, 550); });
                    try {
                        return await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    } catch (eRetry) {
                        lastCameraError = eRetry;
                    }
                }
                if (lastCameraError) {
                    throw lastCameraError;
                }
                throw new Error('Camera could not be initialized.');
            }

            async function waitForVideoReady(videoEl, timeoutMs = 4000) {
                const startedAt = Date.now();
                while (Date.now() - startedAt < timeoutMs) {
                    if (videoEl.readyState >= 2 && videoEl.videoWidth > 0 && videoEl.videoHeight > 0) {
                        return true;
                    }
                    await new Promise((resolve) => setTimeout(resolve, 120));
                }
                return false;
            }

            function resetClockInCaptureUI(clearPhoto = true) {
                const video = document.getElementById('camera-video');
                const captured = document.getElementById('captured-photo');
                const captureBtn = document.getElementById('capture-btn');
                const recaptureBtn = document.getElementById('recapture-btn');
                if (video) video.style.display = 'block';
                if (captured) {
                    captured.style.display = 'none';
                    if (clearPhoto) captured.removeAttribute('src');
                }
                if (captureBtn) {
                    captureBtn.style.display = 'inline-block';
                    captureBtn.disabled = true;
                }
                if (recaptureBtn) recaptureBtn.style.display = 'none';
                if (clearPhoto) {
                    const hiddenPhoto = document.getElementById('photo_base64');
                    if (hiddenPhoto) hiddenPhoto.value = '';
                }
                const fb = document.getElementById('camera-busy-fallback-in');
                if (fb) fb.style.display = 'none';
                const fin = document.getElementById('camera-file-busy-in');
                if (fin) fin.value = '';
            }

            function showModalById(modalId) {
                const el = document.getElementById(modalId);
                if (!el) return null;
                const BS = typeof bootstrap !== 'undefined' ? bootstrap : (typeof window.bootstrap !== 'undefined' ? window.bootstrap : null);
                if (BS && BS.Modal) {
                    return BS.Modal.getOrCreateInstance(el);
                }
                if (typeof $ !== 'undefined' && typeof $(el).modal === 'function') {
                    return {
                        show: function () { $(el).modal('show'); },
                        hide: function () { $(el).modal('hide'); }
                    };
                }
                return {
                    show: function () {
                        el.classList.add('show');
                        el.style.display = 'block';
                        el.removeAttribute('aria-hidden');
                        el.setAttribute('aria-modal', 'true');
                        document.body.classList.add('modal-open');
                        document.body.style.overflow = 'hidden';
                    },
                    hide: function () {
                        el.classList.remove('show');
                        el.style.display = 'none';
                        el.setAttribute('aria-hidden', 'true');
                        el.removeAttribute('aria-modal');
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                    }
                };
            }

            function applyCapturedPreviewClockIn(photoBase64) {
                document.getElementById('photo_base64').value = photoBase64;
                document.getElementById('captured-photo').src = photoBase64;
                document.getElementById('captured-photo').style.display = 'block';
                document.getElementById('camera-video').style.display = 'none';
                document.getElementById('capture-btn').style.display = 'none';
                document.getElementById('recapture-btn').style.display = 'inline-block';
            }
            
            function stopAllCameraHardware() {
                stopCamera();
                stopCameraOut();
                var v1 = document.getElementById('camera-video');
                if (v1) v1.srcObject = null;
                var v2 = document.getElementById('camera-video-out');
                if (v2) v2.srcObject = null;
            }

            function scheduleClockInCameraStart() {
                var scheduled = false;
                return function () {
                    if (scheduled) return;
                    scheduled = true;
                    setTimeout(function () {
                        startCamera();
                    }, cameraStartDelayMs);
                };
            }

            function openCameraModal() {
                stopAllCameraHardware();
                resetClockInCaptureUI(true);
                var modalEl = document.getElementById('cameraModal');
                clockInModalInstance = showModalById('cameraModal');
                var scheduleStart = scheduleClockInCameraStart();
                if (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', scheduleStart, { once: true });
                }
                if (clockInModalInstance && typeof clockInModalInstance.show === 'function') {
                    clockInModalInstance.show();
                }
                setTimeout(function () {
                    scheduleStart();
                }, 650);
            }

            async function startCamera() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Camera is not supported in this browser. Use latest Chrome/Edge and HTTPS or localhost.');
                    return;
                }
                try {
                    stopCamera();
                    const video = document.getElementById('camera-video');
                    video.muted = true;
                    video.setAttribute('playsinline', 'true');
                    document.getElementById('location-info').textContent = 'Starting camera...';
                    const stream = await acquireVideoStream();
                    const fbIn = document.getElementById('camera-busy-fallback-in');
                    if (fbIn) fbIn.style.display = 'none';
                    videoStream = stream;
                    video.srcObject = videoStream;
                    await video.play();
                    const ready = await waitForVideoReady(video);
                    if (!ready) {
                        throw new Error('Camera stream started but no frame was received.');
                    }
                    document.getElementById('location-info').textContent = 'Camera ready. Capture your snapshot.';
                    const captureBtn = document.getElementById('capture-btn');
                    if (captureBtn) captureBtn.disabled = false;
                } catch (error) {
                    console.error('Error accessing camera:', error);
                    const msg = cameraErrorMessage(error);
                    document.getElementById('location-info').textContent = msg;
                    if (isCameraBusyError(error)) {
                        const fb = document.getElementById('camera-busy-fallback-in');
                        if (fb) fb.style.display = 'block';
                    } else {
                        alert(msg);
                    }
                }
            }

            function capturePhoto() {
                const video = document.getElementById('camera-video');
                const canvas = document.getElementById('photo-canvas');
                const context = canvas.getContext('2d');
                if (!videoStream || video.readyState < 2 || !video.videoWidth || !video.videoHeight) {
                    alert('Camera is still loading. Please wait 1-2 seconds and try again.');
                    return;
                }
                
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                const photoBase64 = canvas.toDataURL('image/jpeg');
                applyCapturedPreviewClockIn(photoBase64);
            }

            function recapturePhoto() {
                resetClockInCaptureUI(true);
                if (!videoStream) {
                    startCamera();
                } else {
                    const captureBtn = document.getElementById('capture-btn');
                    if (captureBtn) captureBtn.disabled = false;
                }
            }

            function stopCamera() {
                if (videoStream) {
                    videoStream.getTracks().forEach(function (track) { track.stop(); });
                    videoStream = null;
                }
                var v = document.getElementById('camera-video');
                if (v) v.srcObject = null;
            }

            async function submitClockIn() {
                // Set device type
                document.getElementById('device_type').value = detectDeviceType();
                
                // Get location
                const locationData = await getLocation();
                document.getElementById('latitude').value = locationData.latitude;
                document.getElementById('longitude').value = locationData.longitude;
                document.getElementById('address').value = locationData.address;
                
                // Check if photo is captured
                if (!document.getElementById('photo_base64').value) {
                    alert('Please capture your photo before clocking in.');
                    return;
                }
                
                // Stop camera
                stopCamera();
                
                // Close modal
                if (!clockInModalInstance) {
                    clockInModalInstance = showModalById('cameraModal');
                }
                if (clockInModalInstance && typeof clockInModalInstance.hide === 'function') {
                    clockInModalInstance.hide();
                }
                
                // Submit form
                document.getElementById('clockInForm').submit();
            }

            // Close camera when modal is closed
            (function bindClockInModalHiddenEvent() {
                const modalEl = document.getElementById('cameraModal');
                if (!modalEl) return;
                modalEl.addEventListener('hidden.bs.modal', function () {
                    stopCamera();
                    resetClockInCaptureUI(true);
                });
            })();

            // Clock Out Functions
            let videoStreamOut = null;

            function resetClockOutCaptureUI(clearPhoto = true) {
                const video = document.getElementById('camera-video-out');
                const captured = document.getElementById('captured-photo-out');
                const captureBtn = document.getElementById('capture-btn-out');
                const recaptureBtn = document.getElementById('recapture-btn-out');
                if (video) video.style.display = 'block';
                if (captured) {
                    captured.style.display = 'none';
                    if (clearPhoto) captured.removeAttribute('src');
                }
                if (captureBtn) {
                    captureBtn.style.display = 'inline-block';
                    captureBtn.disabled = true;
                }
                if (recaptureBtn) recaptureBtn.style.display = 'none';
                if (clearPhoto) {
                    const hiddenPhoto = document.getElementById('photo_base64_out');
                    if (hiddenPhoto) hiddenPhoto.value = '';
                }
                const fb = document.getElementById('camera-busy-fallback-out');
                if (fb) fb.style.display = 'none';
                const fout = document.getElementById('camera-file-busy-out');
                if (fout) fout.value = '';
            }

            function applyCapturedPreviewClockOut(photoBase64Out) {
                document.getElementById('photo_base64_out').value = photoBase64Out;
                document.getElementById('captured-photo-out').src = photoBase64Out;
                document.getElementById('captured-photo-out').style.display = 'block';
                document.getElementById('camera-video-out').style.display = 'none';
                document.getElementById('capture-btn-out').style.display = 'none';
                document.getElementById('recapture-btn-out').style.display = 'inline-block';
            }
            
            function scheduleClockOutCameraStart() {
                var scheduled = false;
                return function () {
                    if (scheduled) return;
                    scheduled = true;
                    setTimeout(function () {
                        startCameraOut();
                    }, cameraStartDelayMs);
                };
            }

            function openCameraModalClockOut() {
                stopAllCameraHardware();
                resetClockOutCaptureUI(true);
                var modalEl = document.getElementById('cameraModalClockOut');
                clockOutModalInstance = showModalById('cameraModalClockOut');
                var scheduleStart = scheduleClockOutCameraStart();
                if (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', scheduleStart, { once: true });
                }
                if (clockOutModalInstance && typeof clockOutModalInstance.show === 'function') {
                    clockOutModalInstance.show();
                }
                setTimeout(function () {
                    scheduleStart();
                }, 650);
            }

            async function startCameraOut() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Camera is not supported in this browser. Use latest Chrome/Edge and HTTPS or localhost.');
                    return;
                }
                try {
                    stopCameraOut();
                    const videoOut = document.getElementById('camera-video-out');
                    videoOut.muted = true;
                    videoOut.setAttribute('playsinline', 'true');
                    document.getElementById('location-info-out').textContent = 'Starting camera...';
                    const stream = await acquireVideoStream();
                    const fbOut = document.getElementById('camera-busy-fallback-out');
                    if (fbOut) fbOut.style.display = 'none';
                    videoStreamOut = stream;
                    videoOut.srcObject = videoStreamOut;
                    await videoOut.play();
                    const ready = await waitForVideoReady(videoOut);
                    if (!ready) {
                        throw new Error('Camera stream started but no frame was received.');
                    }
                    document.getElementById('location-info-out').textContent = 'Camera ready. Capture your snapshot.';
                    const captureBtnOut = document.getElementById('capture-btn-out');
                    if (captureBtnOut) captureBtnOut.disabled = false;
                } catch (error) {
                    console.error('Error accessing camera:', error);
                    const msg = cameraErrorMessage(error);
                    document.getElementById('location-info-out').textContent = msg;
                    if (isCameraBusyError(error)) {
                        const fb = document.getElementById('camera-busy-fallback-out');
                        if (fb) fb.style.display = 'block';
                    } else {
                        alert(msg);
                    }
                }
            }

            function capturePhotoOut() {
                const videoOut = document.getElementById('camera-video-out');
                const canvasOut = document.getElementById('photo-canvas-out');
                const contextOut = canvasOut.getContext('2d');
                if (!videoStreamOut || videoOut.readyState < 2 || !videoOut.videoWidth || !videoOut.videoHeight) {
                    alert('Camera is still loading. Please wait 1-2 seconds and try again.');
                    return;
                }
                
                canvasOut.width = videoOut.videoWidth;
                canvasOut.height = videoOut.videoHeight;
                contextOut.drawImage(videoOut, 0, 0, canvasOut.width, canvasOut.height);
                
                const photoBase64Out = canvasOut.toDataURL('image/jpeg');
                applyCapturedPreviewClockOut(photoBase64Out);
            }

            function recapturePhotoOut() {
                resetClockOutCaptureUI(true);
                if (!videoStreamOut) {
                    startCameraOut();
                } else {
                    const captureBtnOut = document.getElementById('capture-btn-out');
                    if (captureBtnOut) captureBtnOut.disabled = false;
                }
            }

            function stopCameraOut() {
                if (videoStreamOut) {
                    videoStreamOut.getTracks().forEach(function (track) { track.stop(); });
                    videoStreamOut = null;
                }
                var v = document.getElementById('camera-video-out');
                if (v) v.srcObject = null;
            }

            async function submitClockOut() {
                // Set device type
                document.getElementById('device_type_out').value = detectDeviceType();
                
                // Get location
                const locationData = await getLocation();
                document.getElementById('latitude_out').value = locationData.latitude;
                document.getElementById('longitude_out').value = locationData.longitude;
                document.getElementById('address_out').value = locationData.address;
                
                // Check if photo is captured
                if (!document.getElementById('photo_base64_out').value) {
                    alert('Please capture your photo before clocking out.');
                    return;
                }
                
                // Stop camera
                stopCameraOut();
                
                // Close modal
                if (!clockOutModalInstance) {
                    clockOutModalInstance = showModalById('cameraModalClockOut');
                }
                if (clockOutModalInstance && typeof clockOutModalInstance.hide === 'function') {
                    clockOutModalInstance.hide();
                }
                
                // Submit form
                document.getElementById('clockOutForm').submit();
            }

            // Close camera when modal is closed
            (function bindClockOutModalHiddenEvent() {
                const modalEl = document.getElementById('cameraModalClockOut');
                if (!modalEl) return;
                modalEl.addEventListener('hidden.bs.modal', function () {
                    stopCameraOut();
                    resetClockOutCaptureUI(true);
                });
            })();

            (function hrmsBindCameraBusyFileFallbacks() {
                function bindFile(idInput, idInfo, applyFn, idFallback) {
                    var inp = document.getElementById(idInput);
                    if (!inp) return;
                    inp.addEventListener('change', function (e) {
                        var file = e.target.files && e.target.files[0];
                        if (!file) return;
                        var reader = new FileReader();
                        reader.onload = function (evt) {
                            var base64 = evt && evt.target ? evt.target.result : '';
                            if (!base64) return;
                            if (idInput === 'camera-file-busy-in') {
                                stopCamera();
                            } else {
                                stopCameraOut();
                            }
                            applyFn(base64);
                            var infoEl = document.getElementById(idInfo);
                            if (infoEl) infoEl.textContent = 'Photo selected. Submit when ready.';
                            var fb = document.getElementById(idFallback);
                            if (fb) fb.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    });
                }
                bindFile('camera-file-busy-in', 'location-info', applyCapturedPreviewClockIn, 'camera-busy-fallback-in');
                bindFile('camera-file-busy-out', 'location-info-out', applyCapturedPreviewClockOut, 'camera-busy-fallback-out');
            })();

            (function hrmsBindRetryLiveCamera() {
                var rin = document.getElementById('retry-live-camera-in');
                if (rin) {
                    rin.addEventListener('click', function () {
                        resetClockInCaptureUI(true);
                        stopCamera();
                        var info = document.getElementById('location-info');
                        if (info) info.textContent = 'Retrying camera...';
                        setTimeout(function () { startCamera(); }, 200);
                    });
                }
                var rout = document.getElementById('retry-live-camera-out');
                if (rout) {
                    rout.addEventListener('click', function () {
                        resetClockOutCaptureUI(true);
                        stopCameraOut();
                        var info = document.getElementById('location-info-out');
                        if (info) info.textContent = 'Retrying camera...';
                        setTimeout(function () { startCameraOut(); }, 200);
                    });
                }
            })();

            // Expose handlers for inline onclick attributes (modal buttons).
            window.openCameraModal = openCameraModal;
            window.capturePhoto = capturePhoto;
            window.recapturePhoto = recapturePhoto;
            window.submitClockIn = submitClockIn;
            window.openCameraModalClockOut = openCameraModalClockOut;
            window.capturePhotoOut = capturePhotoOut;
            window.recapturePhotoOut = recapturePhotoOut;
            window.submitClockOut = submitClockOut;

            (function hrmsBindDashboardAttendanceButtons() {
                function bind() {
                    const cin = document.getElementById('clock_in');
                    if (cin && cin.getAttribute('data-hrms-open-clock-in') === '1' && cin.getAttribute('data-hrms-bound') !== '1') {
                        cin.setAttribute('data-hrms-bound', '1');
                        cin.addEventListener('click', function (e) {
                            e.preventDefault();
                            if (typeof window.openCameraModal === 'function') {
                                window.openCameraModal();
                            }
                        });
                    }
                    const cout = document.getElementById('clock_out');
                    if (cout && cout.getAttribute('data-hrms-open-clock-out') === '1' && cout.getAttribute('data-hrms-bound') !== '1') {
                        cout.setAttribute('data-hrms-bound', '1');
                        cout.addEventListener('click', function (e) {
                            e.preventDefault();
                            if (typeof window.openCameraModalClockOut === 'function') {
                                window.openCameraModalClockOut();
                            }
                        });
                    }
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bind);
                } else {
                    bind();
                }
            })();

            // Notification Highlighting and Voice System
            (function() {
                let previousNotificationCounts = {
                    substitute: {{ $pendingSubstituteCount ?? 0 }},
                    leave: {{ $managerPendingLeaveCount ?? 0 }},
                    exit: {{ $exitPendingCount ?? 0 }},
                    recruitment: {{ $rnSummary['total'] ?? 0 }}
                };

                // Check for new notifications periodically
                function checkForNewNotifications() {
                    fetch('{{ route("dashboard") }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.notifications) {
                            const currentCounts = {
                                substitute: data.notifications.substitute_count || 0,
                                leave: data.notifications.leave_count || 0,
                                exit: data.notifications.exit_count || 0,
                                recruitment: data.notifications.recruitment_count || 0
                            };

                            // Check each notification type for new items
                            Object.keys(currentCounts).forEach(type => {
                                if (currentCounts[type] > previousNotificationCounts[type]) {
                                    handleNewNotification(type, currentCounts[type], previousNotificationCounts[type]);
                                }
                            });

                            previousNotificationCounts = currentCounts;
                        }
                    })
                    .catch(error => console.log('Notification check error:', error));
                }

                // Handle new notification with highlighting and voice
                function handleNewNotification(type, currentCount, previousCount) {
                    const newCount = currentCount - previousCount;
                    
                    // Find the notification icon for this type
                    let iconElement = null;
                    let message = '';
                    
                    switch(type) {
                        case 'substitute':
                            iconElement = document.querySelector('.notification-toggle');
                            message = `आपको ${newCount} नई सब्स्टिट्यूट लीव रिक्वेस्ट मिली है`;
                            break;
                        case 'leave':
                            iconElement = document.querySelector('.leave-notification-toggle');
                            message = `आपको ${newCount} नई लीव अप्रूवल रिक्वेस्ट मिली है`;
                            break;
                        case 'exit':
                            iconElement = document.querySelector('.exit-notification-toggle');
                            message = `आपको ${newCount} नई रिजाइनेशन नोटिफिकेशन मिली है`;
                            break;
                        case 'recruitment':
                            iconElement = document.querySelector('.ti-briefcase').closest('a');
                            message = `आपको ${newCount} नई रिक्रूटमेंट नोटिफिकेशन मिली है`;
                            break;
                    }

                    if (iconElement) {
                        // Add highlighting classes
                        iconElement.classList.add('notification-highlight', 'notification-glow');
                        
                        // Add pulse effect to badge
                        const badge = iconElement.querySelector('.dash-h-badge');
                        if (badge) {
                            badge.classList.add('notification-badge-bounce');
                        }

                        // Remove highlighting after 5 seconds
                        setTimeout(() => {
                            iconElement.classList.remove('notification-highlight', 'notification-glow');
                            if (badge) {
                                badge.classList.remove('notification-badge-bounce');
                            }
                        }, 5000);

                        // Trigger voice announcement
                        if (typeof window.playNotification === 'function') {
                            window.playNotification(message, 'info');
                        }
                    }
                }

                // Check notifications every 30 seconds
                setInterval(checkForNewNotifications, 30000);

                // Initial check when page loads
                setTimeout(checkForNewNotifications, 2000);

                // Add notification icon styling on page load
                document.addEventListener('DOMContentLoaded', function() {
                    // Check for existing notifications and add styling
                    const notificationIcons = document.querySelectorAll('.notification-toggle, .leave-notification-toggle, .exit-notification-toggle');
                    notificationIcons.forEach(icon => {
                        const badge = icon.querySelector('.dash-h-badge');
                        if (badge && parseInt(badge.textContent) > 0) {
                            icon.classList.add('notification-icon', 'has-notifications');
                        }
                    });
                });

                // Expose function for manual notification testing
                window.testNotification = function(type = 'substitute', count = 1) {
                    handleNewNotification(type, previousNotificationCounts[type] + count, previousNotificationCounts[type]);
                    previousNotificationCounts[type] += count;
                };
            })();
        </script>
    @endif

    {{-- Hero banner: live clock --}}
    <script>
    (function () {
        const el = document.getElementById('dashHeroClock');
        if (!el) return;
        function tick() {
            const d = new Date();
            let h = d.getHours(), m = d.getMinutes();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            el.textContent = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + ampm;
        }
        setInterval(tick, 30000);  // refresh every 30s — minute precision is enough
        tick();
    })();
    </script>
@endpush
