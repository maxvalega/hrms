@php

    $logo = \App\Models\Utility::get_file('uploads/logo/');
    $company_logo = \App\Models\Utility::GetLogo();
    $users = \Auth::user();
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
    $currantLang = $users->currentLanguage();
    $emailTemplate = App\Models\EmailTemplate::getemailTemplate();
    $lang = Auth::user()->lang;
@endphp

@if (isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on')
    <nav class="dash-sidebar light-sidebar transprent-bg">
    @else
        <nav class="dash-sidebar light-sidebar">
@endif

{{-- <nav class="dash-sidebar light-sidebar {{ isset($cust_theme_bg) && $cust_theme_bg == 'on' ? 'transprent-bg' : '' }}"> --}}

<div class="navbar-wrapper">
    <div class="m-header main-logo">
        <a href="{{ route('dashboard') }}" class="b-brand" style="display:inline-flex;align-items:center;gap:.6rem;text-decoration:none;">
            <!-- ========   change your logo hear   ============ -->
            <img src="{{ $logo . (isset($company_logo) && !empty($company_logo) ? $company_logo . '?' . time() : 'logo-dark.png' . '?' . time()) }}"
                alt="{{ config('app.name', 'HRMGo') }}" class="logo logo-lg" style="height: 40px;">
            <span style="text-align:left;line-height:1;">
                <span style="display:block;font-size:1.2rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;">Jemini</span>
                <span style="display:block;font-size:.5rem;color:#64748b;letter-spacing:.1em;text-transform:uppercase;margin-top:.2rem;font-weight:600;">By People, For People</span>
            </span>
        </a>
    </div>
    <div class="navbar-content">
        <ul class="dash-navbar">

            <!-- dashboard-->
            <li class="dash-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-home"></i></span>
                    <span class="dash-mtext">{{ __('Dashboard') }}</span>
                </a>
            </li>
            <!--dashboard-->

            <!-- reports -->
            @if (Gate::check('Manage Report'))
                <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'report' || request()->routeIs('report.*') || request()->routeIs('attendance.export-monthly-excel') ? 'active dash-trigger' : '' }}">
                    <a href="#" class="dash-link">
                        <span class="dash-micon"><i class="ti ti-report-analytics"></i></span>
                        <span class="dash-mtext">{{ __('Reports') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="dash-submenu">
                        <li class="dash-item {{ request()->routeIs('report.monthly.attendance') ? 'active' : '' }}">
                            <a class="dash-link" href="{{ route('report.monthly.attendance') }}">{{ __('Monthly Attendance') }}</a>
                        </li>
                        <li class="dash-item {{ request()->routeIs('report.leave') ? 'active' : '' }}">
                            <a class="dash-link" href="{{ route('report.leave') }}">{{ __('Leave Report') }}</a>
                        </li>
                        <li class="dash-item {{ request()->routeIs('report.payroll') ? 'active' : '' }}">
                            <a class="dash-link" href="{{ route('report.payroll') }}">{{ __('Payroll Report') }}</a>
                        </li>
                        <li class="dash-item {{ request()->routeIs('report.income-expense') ? 'active' : '' }}">
                            <a class="dash-link" href="{{ route('report.income-expense') }}">{{ __('Income vs Expense') }}</a>
                        </li>
                        <li class="dash-item {{ request()->routeIs('report.account.statement') ? 'active' : '' }}">
                            <a class="dash-link" href="{{ route('report.account.statement') }}">{{ __('Account Statement') }}</a>
                        </li>
                        <li class="dash-item {{ request()->routeIs('report.timesheet') ? 'active' : '' }}">
                            <a class="dash-link" href="{{ route('report.timesheet') }}">{{ __('Timesheet') }}</a>
                        </li>
                    </ul>
                </li>
            @endif
            <!-- /reports -->

            <!-- user-->
            @if (\Auth::user()->type == 'super admin')
                <li class="dash-item">
                    <a href="{{ route('user.index') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-user"></i></span><span class="dash-mtext">{{ __('Companies') }}</span></a>
                </li>
                <li class="dash-item {{ Request::routeIs('demo-inquiries') ? 'active' : '' }}">
                    <a href="{{ route('demo-inquiries') }}" class="dash-link">
                        <span class="dash-micon"><i class="ti ti-rocket"></i></span>
                        <span class="dash-mtext">{{ __('Demo Inquiries') }}</span>
                        @php $demoCount = \DB::table('demo_requests')->where('status','new')->count(); @endphp
                        @if($demoCount > 0)
                        <span class="badge bg-danger ms-auto" style="font-size:.65rem;">{{ $demoCount }}</span>
                        @endif
                    </a>
                </li>
            @else
                @if (Gate::check('Manage User') ||
                        Gate::check('Manage Role') ||
                        Gate::check('Manage Employee Profile') ||
                        Gate::check('Manage Employee Last Login'))
                    <li
                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'user' || Request::segment(1) == 'roles' || Request::segment(1) == 'lastlogin'
                            ? ' active dash-trigger'
                            : '' }} ">
                        <a href="#!" class="dash-link"><span class="dash-micon"><i
                                    class="ti ti-users"></i></span><span
                                class="dash-mtext">{{ __('Staff') }}</span><span class="dash-arrow"><i
                                    data-feather="chevron-right"></i></span></a>
                        <ul
                            class="dash-submenu {{ Request::route()->getName() == 'user.index' || Request::route()->getName() == 'users.create' || Request::route()->getName() == 'user.edit' || Request::route()->getName() == 'lastlogin' ? ' active' : '' }} ">
                            @can('Manage User')
                                <li class="dash-item {{ Request::segment(1) == 'lastlogin' ? 'active' : '' }} ">
                                    <a class="dash-link" href="{{ route('user.index') }}">{{ __('User') }}</a>
                                </li>
                            @endcan
                            @can('Manage Role')
                                <li class="dash-item">
                                    <a class="dash-link" href="{{ route('roles.index') }}">{{ __('Role') }}</a>
                                </li>
                            @endcan
                            @can('Manage Employee Profile')
                                <li class="dash-item">
                                    <a class="dash-link"
                                        href="{{ route('employee.profile') }}">{{ __('Employee Profile') }}</a>
                                </li>
                            @endcan
                            {{-- @can('Manage Employee Last Login')
                                <li class="dash-item">
                                    <a class="dash-link" href="{{ route('lastlogin') }}">{{ __('Last Login') }}</a>
                                </li>
                            @endcan --}}

                        </ul>
                    </li>
                @endif
            @endif
            <!-- user-->

            <!-- employee-->
            @if (Gate::check('Manage Employee'))
                @if (\Auth::user()->type == 'employee')
                    @php
                        $employee = App\Models\Employee::where('user_id', \Auth::user()->id)->first();
                    @endphp
                    <li class="dash-item {{ Request::segment(1) == 'employee' ? 'active' : '' }}">
                        <a href="{{ route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}"
                            class="dash-link"><span class="dash-micon"><i class="ti ti-user"></i></span><span
                                class="dash-mtext">{{ __('Employee') }}</span></a>
                    </li>
                @else
                    <li class="dash-item {{ Request::segment(1) == 'employee' ? 'active' : '' }}">
                        <a href="{{ route('employee.index') }}" class="dash-link"><span class="dash-micon"><i
                                    class="ti ti-user"></i></span><span
                                class="dash-mtext">{{ __('Employee') }}</span></a>
                    </li>
                @endif
            @endif
            <!-- employee-->

            <!-- payroll-->
            @if (Gate::check('Manage Set Salary') || (Gate::check('Manage Pay Slip') && \Auth::user()->type != 'employee'))
                <li
                    class="dash-item dash-hasmenu  {{ Request::segment(1) == 'setsalary' ? 'dash-trigger active' : '' }}">
                    <a href="#!" class="dash-link">
                        <span class="dash-micon">
                            <i class="ti ti-receipt">
                            </i>
                        </span>
                        <span class="dash-mtext">
                            {{ __('Payroll') }}
                        </span>
                        <span class="dash-arrow"><i data-feather="chevron-right">
                            </i>
                        </span>
                    </a>
                    <ul class="dash-submenu ">
                        @can('Manage Set Salary')
                            <li class="dash-item {{ Request::segment(1) == 'setsalary' ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('setsalary.index') }}">{{ __('Set Salary') }}</a>
                            </li>
                            <li class="dash-item {{ Request::segment(1) == 'salary-structure' ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('salary.structure.index') }}">{{ __('Dynamic Salary Structure') }}</a>
                            </li>
                            <li class="dash-item {{ request()->routeIs('payroll.schedule') ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('payroll.schedule') }}">{{ __('Pay Schedule') }}</a>
                            </li>
                            <li class="dash-item {{ request()->routeIs('payroll.components') ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('payroll.components') }}">{{ __('Salary Components') }}</a>
                            </li>
                            <li class="dash-item {{ request()->routeIs('payroll.employee.salary') ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('payroll.employee.salary') }}">{{ __('Employee Salary') }}</a>
                            </li>
                            <li class="dash-item {{ request()->routeIs('payroll.reimbursements') ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('payroll.reimbursements') }}">{{ __('Reimbursements') }}</a>
                            </li>
                            <li class="dash-item {{ request()->routeIs('payroll.supplementary') ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('payroll.supplementary') }}">{{ __('Supplementary') }}</a>
                            </li>
                            <li class="dash-item {{ request()->routeIs('payroll.process') ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('payroll.process') }}">{{ __('Payroll Process') }}</a>
                            </li>
                            <li class="dash-item {{ request()->routeIs('statutory.dashboard') || request()->is('statutory/*') || request()->routeIs('statutory.states') || request()->routeIs('statutory.employee.config') ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('statutory.dashboard') }}">{{ __('Statutory Components') }}</a>
                            </li>
                            <li class="dash-item {{ request()->routeIs('it.declaration.review.index') || request()->routeIs('it.declaration.review.show') ? 'active' : '-' }}">
                                <a class="dash-link" href="{{ route('it.declaration.review.index') }}">{{ __('IT Declaration Review') }}</a>
                            </li>
                        @endcan
                        @can('Manage Pay Slip')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('payslip.index') }}">{{ __('Payslip') }}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif
            <!-- payroll-->

            @if (\Auth::user()->type == 'employee')
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'setsalary' ? 'dash-trigger active' : '' }}">
                    <a href="#!" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-receipt"></i></span><span
                            class="dash-mtext">{{ __('Payroll') }}</span><span class="dash-arrow"><i
                                data-feather="chevron-right"></i></span></a>
                    <ul class="dash-submenu">
                        <li class="dash-item {{ Request::segment(1) == 'setsalary' ? 'active' : '-' }}">
                            <a class="dash-link"
                                href="{{ route('setsalary.show', \Illuminate\Support\Facades\Crypt::encrypt(\Auth::user()->id)) }}">{{ __('Salary') }}</a>
                        </li>
                        <li class="dash-item">
                            <a class="dash-link" href="{{ route('payslip.index') }}">{{ __('Payslip') }}</a>
                        </li>
                        <li class="dash-item {{ request()->routeIs('payroll.my-payslips') ? 'active' : '' }}">
                            <a class="dash-link" href="{{ route('payroll.my-payslips') }}">{{ __('My Payslips') }}</a>
                        </li>
                        <li class="dash-item {{ request()->routeIs('it.declaration.index') || request()->routeIs('it.declaration.create') || request()->routeIs('it.declaration.edit') ? 'active' : '-' }}">
                            <a class="dash-link" href="{{ route('it.declaration.index') }}">{{ __('IT Declaration') }}</a>
                        </li>
                    </ul>
                </li>
            @endif

            
            <!-- timesheet-->
            @if (Gate::check('Manage Attendance') || Gate::check('Manage Leave') || Gate::check('Manage TimeSheet') || Gate::check('Manage Holiday'))
                <li
                    class="dash-item dash-hasmenu {{ (Request::segment(1) == 'calender' && Request::segment(2) == 'leave') || Request::segment(1) == 'holiday' ? 'dash-trigger active' : '' }}">
                    <a href="#!" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-clock"></i></span><span
                            class="dash-mtext">{{ __('Time Management') }}</span><span class="dash-arrow"><i
                                data-feather="chevron-right"></i></span></a>
                    <ul class="dash-submenu">
                        @can('Manage TimeSheet')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('timesheet.index') }}">{{ __('Timesheet') }}</a>
                            </li>
                        @endcan
                        @can('Manage Leave')

                            <li class="dash-item {{ Request::segment(1) == 'calender' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('leave.index') }}">{{ __('Manage Leave') }}</a>
                            </li>

                        @endcan
                        @can('Manage Holiday')
                            <li class="dash-item {{ Request::segment(1) == 'holiday' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('holiday.index') }}">{{ __('Holidays') }}</a>
                            </li>
                        @endcan
                        @can('Manage Attendance')
                            <li class="dash-item dash-hasmenu">
                                <a href="#!" class="dash-link"><span
                                        class="dash-mtext">{{ __('Attendance') }}</span><span class="dash-arrow"><i
                                            data-feather="chevron-right"></i></span></a>
                                <ul class="dash-submenu">
                                    <li class="dash-item">
                                        <a class="dash-link"
                                            href="{{ route('attendanceemployee.index') }}">{{ __('Marked Attendance') }}</a>
                                    </li>
                                    @can('Create Attendance')
                                        <li class="dash-item">
                                            <a class="dash-link"
                                                href="{{ route('attendanceemployee.bulkattendance') }}">{{ __('Bulk Attendance') }}</a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcan
                        {{-- remove biometric code --}}
                        {{-- @can('Manage Biometric Attendance')

                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('biometric-attendance.index') }}">{{ __('Biometric Attendance') }}</a>
                            </li>

                        @endcan --}}
                    </ul>
                </li>
            @endif
            <!--timesheet-->

            <!-- People Hub -->
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'people-hub' ? 'dash-trigger active' : '' }}">
                <a href="#!" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-users-group"></i></span>
                    <span class="dash-mtext">{{ __('People Hub') }}</span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    <li class="dash-item {{ request()->routeIs('people-hub.crew') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('people-hub.crew') }}">{{ __('Crew (Org Chart)') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('people-hub.squad') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('people-hub.squad') }}">{{ __('My Squad') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('people-hub.mentor') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('people-hub.mentor') }}">{{ __('Mentor Buddy') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('people-hub.search') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('people-hub.search') }}">{{ __('Search Crew') }}</a>
                    </li>
                </ul>
            </li>

            <!-- Screen Monitor (top-level) -->
            @if(\Auth::user()->type != 'super admin' && \Auth::user()->type != 'employee')
            <li class="dash-item {{ Request::segment(1) == 'screen-monitor' ? 'active' : '' }}">
                <a href="{{ route('screen-monitor.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-device-desktop-analytics"></i></span>
                    <span class="dash-mtext">{{ __('Screen Monitor') }}</span>
                </a>
            </li>
            @endif

            <!-- Screenshot Capture (top-level) -->
            @if(\Auth::user()->type != 'super admin' && \Auth::user()->type != 'employee')
            <li class="dash-item {{ Request::segment(1) == 'bg-screenshot' ? 'active' : '' }}">
                <a href="{{ route('bg-screenshot.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-camera"></i></span>
                    <span class="dash-mtext">{{ __('Screenshot Capture') }}</span>
                </a>
            </li>
            @endif

            <!-- performance-->
            @if (Gate::check('Manage Indicator') || Gate::check('Manage Appraisal') || Gate::check('Manage Goal Tracking'))
                <li class="dash-item dash-hasmenu">
                    <a href="#!" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-3d-cube-sphere"></i></span><span
                            class="dash-mtext">{{ __('Performance') }}</span><span class="dash-arrow"><i
                                data-feather="chevron-right"></i></span></a>
                    <ul class="dash-submenu">
                        @can('Manage Indicator')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('indicator.index') }}">{{ __('Indicator') }}</a>
                            </li>
                        @endcan

                        @can('Manage Appraisal')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('appraisal.index') }}">{{ __('Appraisal') }}</a>
                            </li>
                        @endcan

                        @can('Manage Goal Tracking')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('goaltracking.index') }}">{{ __('Goal Tracking') }}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif
            <!--performance-->

            <!-- Growth Review -->
            <li class="dash-item dash-hasmenu {{ request()->is('growth-review*') ? 'active' : '' }}">
                <a href="#!" class="dash-link"><span class="dash-micon"><i class="ti ti-trending-up"></i></span><span class="dash-mtext">{{ __('Growth Review') }}</span><span class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                <ul class="dash-submenu">
                    <li class="dash-item {{ request()->routeIs('growth-review.dashboard') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    @if(Auth::user()->type == 'company' || Auth::user()->type == 'hr')
                    <li class="dash-item {{ request()->routeIs('growth-review.cycles*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.cycles') }}">{{ __('Performance Cycles') }}</a>
                    </li>
                    @endif
                    <li class="dash-item {{ request()->routeIs('growth-review.missions*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.missions') }}">{{ __('Missions') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('growth-review.shoutouts*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.shoutouts') }}">{{ __('Shoutouts') }}</a>
                    </li>
                    @if(Auth::user()->type == 'company' || Auth::user()->type == 'hr')
                    <li class="dash-item {{ request()->routeIs('growth-review.sync-ups*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.sync-ups') }}">{{ __('Sync Ups') }}</a>
                    </li>
                    @endif
                    <li class="dash-item {{ request()->routeIs('growth-review.comeback*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.comeback') }}">{{ __('Comeback Plans') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('growth-review.reviews*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.reviews') }}">{{ __('Reviews') }}</a>
                    </li>
                    @if(Auth::user()->type == 'company' || Auth::user()->type == 'hr')
                    <li class="dash-item {{ request()->routeIs('growth-review.calibration*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.calibration') }}">{{ __('Calibration') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('growth-review.increments*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('growth-review.increments') }}">{{ __('Increments') }}</a>
                    </li>
                    @endif
                </ul>
            </li>
            <!-- /Growth Review -->

            <!-- Surveys -->
            @php
                $canManageSurveys = Auth::user() && Auth::user()->can('manage-surveys');
                $canSubmitSurveys = Auth::user() && Auth::user()->can('submit-surveys');
                $canViewSurveyAlerts = Auth::user() && Auth::user()->can('view-survey-alerts');
                $canTeamPulse     = false;
                if (Auth::user() && Auth::user()->can('view-team-pulse')) {
                    $emp = \App\Models\Employee::where('user_id', Auth::id())->first();
                    $canTeamPulse = $emp && $emp->isManagerLevel();
                }
            @endphp
            @if($canManageSurveys || $canSubmitSurveys)
            <li class="dash-item dash-hasmenu {{ (request()->is('surveys*') || request()->is('my-surveys*')) ? 'active' : '' }}">
                <a href="#!" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-clipboard-list"></i></span>
                    <span class="dash-mtext">{{ __('Surveys') }}</span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    @if($canSubmitSurveys)
                    <li class="dash-item {{ request()->routeIs('surveys.my') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.my') }}">{{ __('My Surveys') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('surveys.my.history') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.my.history') }}">{{ __('My History') }}</a>
                    </li>
                    @endif
                    @if($canTeamPulse)
                    <li class="dash-item {{ request()->routeIs('surveys.team-pulse') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.team-pulse') }}">{{ __('Team Pulse') }}</a>
                    </li>
                    @endif
                    @if($canManageSurveys)
                    <li class="dash-item {{ request()->routeIs('surveys.index') || request()->routeIs('surveys.create') || request()->routeIs('surveys.edit') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.index') }}">{{ __('Manage Surveys') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('surveys.enps') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.enps') }}">{{ __('eNPS Report') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('surveys.pulse') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.pulse') }}">{{ __('Pulse Trends') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('surveys.reports.departments') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.reports.departments') }}">{{ __('Department Report') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('surveys.reports.managers') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.reports.managers') }}">{{ __('Manager Summary') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('surveys.reports.sentiment') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.reports.sentiment') }}">{{ __('Sentiment Analytics') }}</a>
                    </li>
                    @if($canViewSurveyAlerts)
                    <li class="dash-item {{ request()->routeIs('surveys.alerts') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('surveys.alerts') }}">{{ __('Alerts') }}</a>
                    </li>
                    @endif
                    @endif
                </ul>
            </li>
            @endif
            <!-- /Surveys -->

            <!-- Policies -->
            @if(Auth::user() && Auth::user()->can('view-policies'))
            <li class="dash-item {{ request()->is('policies*') ? 'active' : '' }}">
                <a class="dash-link" href="{{ route('policies.index') }}">
                    <span class="dash-micon"><i class="ti ti-files"></i></span>
                    <span class="dash-mtext">{{ __('Policies') }}</span>
                </a>
            </li>
            @endif
            <!-- /Policies -->

            <!-- Exit Management -->
            @if(Auth::user() && Auth::user()->can('apply-resignation'))
            <li class="dash-item {{ request()->is('exit-management*') ? 'active' : '' }}">
                <a class="dash-link" href="{{ route('exit-management.index') }}">
                    <span class="dash-micon"><i class="ti ti-logout"></i></span>
                    <span class="dash-mtext">{{ __('Exit Management') }}</span>
                </a>
            </li>
            @endif
            <!-- /Exit Management -->

            <!-- Activity Tracker -->
            @if(Auth::user() && Auth::user()->can('manage-activity-tracker') && Route::has('activity-tracker.index'))
            <li class="dash-item dash-hasmenu {{ request()->is('activity-tracker*') ? 'active dash-trigger' : '' }}">
                <a href="#!" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-device-desktop-analytics"></i></span>
                    <span class="dash-mtext">{{ __('Activity Tracker') }}</span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    <li class="dash-item {{ request()->routeIs('activity-tracker.index') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('activity-tracker.index') }}">{{ __('Overview') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('activity-tracker.user-activity') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('activity-tracker.user-activity') }}">{{ __('User Activity') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('activity-tracker.timeline') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('activity-tracker.timeline') }}">{{ __('Screenshot Timeline') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('activity-tracker.app-usage') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('activity-tracker.app-usage') }}">{{ __('App Usage') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('activity-tracker.daily-report') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('activity-tracker.daily-report') }}">{{ __('Daily Report') }}</a>
                    </li>
                    <li class="dash-item {{ request()->routeIs('activity-tracker.token') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('activity-tracker.token') }}">{{ __('Agent Tokens') }}</a>
                    </li>
                </ul>
            </li>
            @elseif(Auth::user() && Auth::user()->can('use-activity-tracker') && Route::has('activity-tracker.token'))
            {{-- Employee-only: just token-issuance UI for self --}}
            <li class="dash-item {{ request()->is('activity-tracker/token*') ? 'active' : '' }}">
                <a class="dash-link" href="{{ route('activity-tracker.token') }}">
                    <span class="dash-micon"><i class="ti ti-device-desktop-analytics"></i></span>
                    <span class="dash-mtext">{{ __('Agent Token') }}</span>
                </a>
            </li>
            @endif
            <!-- /Activity Tracker -->

            <!--fianance-->
            @if (Gate::check('Manage Account List') ||
                    Gate::check('Manage Payee') ||
                    Gate::check('Manage Payer') ||
                    Gate::check('Manage Deposit') ||
                    Gate::check('Manage Expense') ||
                    Gate::check('Manage Transfer Balance'))
                <li class="dash-item dash-hasmenu">
                    <a href="#!" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-wallet"></i></span><span
                            class="dash-mtext">{{ __('Finance') }}</span><span class="dash-arrow"><i
                                data-feather="chevron-right"></i></span></a>
                    <ul class="dash-submenu">
                        @can('Manage Account List')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('accountlist.index') }}">{{ __('Account List') }}</a>
                            </li>
                        @endcan
                        @can('View Balance Account List')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('accountbalance') }}">{{ __('Account Balance') }}</a>
                            </li>
                        @endcan
                        @can('Manage Payee')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('payees.index') }}">{{ __('Payees') }}</a>
                            </li>
                        @endcan

                        @can('Manage Payer')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('payer.index') }}">{{ __('Payers') }}</a>
                            </li>
                        @endcan

                        @can('Manage Deposit')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('deposit.index') }}">{{ __('Deposit') }}</a>
                            </li>
                        @endcan

                        @can('Manage Expense')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('expense.index') }}">{{ __('Expense') }}</a>
                            </li>
                        @endcan

                        @can('Manage Transfer Balance')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('transferbalance.index') }}">{{ __('Transfer Balance') }}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif
            <!-- fianance-->

            <!--trainning-->
            @if (Gate::check('Manage Trainer') || Gate::check('Manage Training'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'training' ? 'dash-trigger active' : '' }}">
                    <a href="#!" class="dash-link "><span class="dash-micon"><i
                                class="ti ti-school"></i></span><span
                            class="dash-mtext">{{ __('Training') }}</span><span class="dash-arrow"><i
                                data-feather="chevron-right"></i></span></a>
                    <ul class="dash-submenu">
                        @can('Manage Training')
                            <li class="dash-item {{ Request::segment(1) == 'training' ? ' active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('training.index') }}">{{ __('Training List') }}</a>
                            </li>
                        @endcan

                        @can('Manage Trainer')
                            <li class="dash-item ">
                                <a class="dash-link" href="{{ route('trainer.index') }}">{{ __('Trainer') }}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif

            <!-- tranning-->


            <!-- HR-->
            @if (Gate::check('Manage Award') ||
                    Gate::check('Manage Transfer') ||
                    Gate::check('Manage Resignation') ||
                    Gate::check('Manage Travel') ||
                    Gate::check('Manage Promotion') ||
                    Gate::check('Manage Complaint') ||
                    Gate::check('Manage Warning') ||
                    Gate::check('Manage Termination') ||
                    Gate::check('Manage Announcement'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'award' || Request::segment(1) == 'transfer' || Request::segment(1) == 'resignation' || Request::segment(1) == 'travel' || Request::segment(1) == 'promotion' || Request::segment(1) == 'complaint' || Request::segment(1) == 'warning' || Request::segment(1) == 'termination' || Request::segment(1) == 'announcement' ? 'dash-trigger active' : '' }}">
                    <a href="#!" class="dash-link">
                        <span class="dash-micon">
                            <i class="ti ti-user-plus"></i>
                        </span>
                        <span class="dash-mtext">{{ __('Employee History') }}</span>
                        <span class="dash-arrow">
                            <i data-feather="chevron-right"></i>
                        </span>
                    </a>
                    <ul class="dash-submenu">
                        @can('Manage Award')
                            <li class="dash-item {{ Request::segment(1) == 'award' ? 'active' : '' }}">
                                <a class="dash-link" href="{{ route('award.index') }}">{{ __('Award') }}</a>
                            </li>
                        @endcan
                        @can('Manage Transfer')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('transfer.index') }}">{{ __('Transfer') }}</a>
                            </li>
                        @endcan
                        @can('Manage Resignation')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('resignation.index') }}">{{ __('Resignation') }}</a>
                            </li>
                        @endcan
                        @can('Manage Travel')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('travel.index') }}">{{ __('Trip') }}</a>
                            </li>
                        @endcan
                        @can('Manage Promotion')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('promotion.index') }}">{{ __('Promotion') }}</a>
                            </li>
                        @endcan
                        @can('Manage Complaint')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('complaint.index') }}">{{ __('Complaints') }}</a>
                            </li>
                        @endcan
                        @can('Manage Warning')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('warning.index') }}">{{ __('Warning') }}</a>
                            </li>
                        @endcan
                        @can('Manage Termination')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('termination.index') }}">{{ __('Termination') }}</a>
                            </li>
                        @endcan
                        @can('Manage Announcement')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('announcement.index') }}">{{ __('Announcement') }}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif
            <!-- HR-->

            <!-- Grievances Module -->
            @if (in_array(Auth::user()->type, ['super admin', 'company', 'hr']) || Auth::user()->type == 'employee')
                <li class="dash-item {{ request()->routeIs('grievances.*') ? 'active' : '' }}">
                    <a class="dash-link" href="{{ route('grievances.index') }}">
                        <span class="dash-micon">
                            <i class="ti ti-message-circle"></i>
                        </span>
                        <span class="dash-mtext">{{ __('Grievances') }}</span>
                    </a>
                </li>
            @endif

            <!-- recruitment-->
            @if (Gate::check('Manage Job') ||
                    Gate::check('Manage Job Application') ||
                    Gate::check('Manage Job OnBoard') ||
                    Gate::check('Manage Custom Question') ||
                    Gate::check('Manage Interview Schedule') ||
                    Gate::check('Manage Career'))
                <li
                    class="dash-item dash-hasmenu  {{ Request::segment(1) == 'job' || Request::segment(1) == 'job-application' ? 'dash-trigger active' : '' }} ">
                    <a href="#!" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-license"></i></span><span
                            class="dash-mtext">{{ __('Recruitment') }}</span><span class="dash-arrow"><i
                                data-feather="chevron-right"></i></span></a>
                    <ul class="dash-submenu">
                        <li class="dash-item {{ request()->is('recruitment*') ? 'active' : '' }}">
                            <a class="dash-link" href="{{ route('recruitment.dashboard') }}">{{ __('Manpower Requisition') }}</a>
                        </li>
                        @can('Manage Job')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'job.index' ? 'active' : 'dash-hasmenu' }}">
                                <a class="dash-link" href="{{ route('job.index') }}">{{ __('Jobs') }}</a>
                            </li>
                        @endcan
                        @can('Manage Job')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'job.create' ? 'active' : 'dash-hasmenu' }}">
                                <a class="dash-link" href="{{ route('job.create') }}">{{ __('Job Create ') }}</a>
                            </li>
                        @endcan
                        @can('Manage Job Application')
                            <li class="dash-item {{ request()->is('job-application*') ? 'active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('job-application.index') }}">{{ __('Job Application') }}</a>
                            </li>
                        @endcan
                        @can('Manage Job Application')

                            <li class="dash-item {{ request()->is('candidates-job-applications') ? 'active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('job.application.candidate') }}">{{ __('Job Candidate') }}</a>
                            </li>
                        @endcan

                        @can('Manage Job OnBoard')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('job.on.board') }}">{{ __('Job On-Boarding') }}</a>
                            </li>
                        @endcan

                        @can('Manage Custom Question')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('custom-question.index') }}">{{ __('Custom Question') }}</a>
                            </li>
                        @endcan

                        @can('Manage Interview Schedule')
                            <li class="dash-item">
                                <a class="dash-link"
                                    href="{{ route('interview-schedule.index') }}">{{ __('Interview Schedule') }}</a>
                            </li>
                        @endcan

                        @can('Manage Career')
                            <li class="dash-item">
                                <a class="dash-link" href="{{ route('career', [\Auth::user()->creatorId(), $lang]) }}"
                                    target="_blank">{{ __('Career') }}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif
            <!-- recruitment-->
            <!--contract-->
            @can('Manage Contract')
                <li
                    class="dash-item {{ Request::route()->getName() == 'contract.index' || Request::route()->getName() == 'contract.show' ? 'active' : '' }}">
                    <a href="{{ route('contract.index') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-device-floppy"></i></span><span
                            class="dash-mtext">{{ __('Contracts') }}</span></a>
                </li>
            @endcan

            <!--end-->


            <!-- ticket-->
            @can('Manage Ticket')
                <li class="dash-item {{ Request::segment(1) == 'ticket' ? 'active' : '' }}">
                    <a href="{{ route('ticket.index') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-ticket"></i></span><span class="dash-mtext">{{ __('Ticket') }}</span></a>
                </li>
            @endcan

            <!-- Event-->
            @can('Manage Event')
                <li class="dash-item">
                    <a href="{{ route('event.index') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-calendar-event"></i></span><span
                            class="dash-mtext">{{ __('Event') }}</span>
                    </a>
                </li>
            @endcan


            <!--meeting-->
            @can('Manage Meeting')
                <li
                    class="dash-item {{ Request::segment(1) == 'meeting' || Request::segment(2) == 'meeting' ? 'active' : '' }}">
                    <a href="{{ route('meeting.index') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-calendar-time"></i></span><span
                            class="dash-mtext">{{ __('Meeting') }}</span></a>
                </li>
            @endcan


            <!-- Zoom meeting-->
            @can('Manage Zoom meeting')
                @if (\Auth::user()->type != 'super admin')
                    <li class="dash-item {{ Request::segment(1) == 'zoommeeting' ? 'active' : '' }}">
                        <a href="{{ route('zoom-meeting.index') }}" class="dash-link"><span class="dash-micon"><i
                                    class="ti ti-video"></i></span><span
                                class="dash-mtext">{{ __('Zoom Meeting') }}</span></a>
                    </li>
                @endif
            @endcan

            <!-- assets-->
            @if (Gate::check('Manage Assets'))
                <li class="dash-item">
                    <a href="{{ route('account-assets.index') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-medical-cross"></i></span><span
                            class="dash-mtext">{{ __('Assets') }}</span></a>
                </li>
            @endcan


            <!-- document-->
            @if (Gate::check('Manage Document'))
                <li class="dash-item">
                    <a href="{{ route('document-upload.index') }}" class="dash-link"><span
                            class="dash-micon"><i class="ti ti-file"></i></span><span
                            class="dash-mtext">{{ __('Document') }}</span></a>
                </li>
            @endcan

            <!--company policy-->



            @if (Gate::check('Manage Company Policy'))
                <li class="dash-item">
                    <a href="{{ route('company-policy.index') }}" class="dash-link"><span
                            class="dash-micon"><i class="ti ti-pray"></i></span><span
                            class="dash-mtext">{{ __('Company Policy') }}</span></a>
                </li>
            @endcan
            <!--chats-->
            @if (\Auth::user()->type != 'super admin')
                <li class="dash-item {{ Request::segment(1) == 'chats' ? 'active' : '' }}">
                    <a href="{{ url('chats') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-messages"></i></span><span
                            class="dash-mtext">{{ __('Messenger') }}</span>
                        <span class="badge bg-danger ms-auto custom_messanger_counter d-none">0</span></a>
                </li>
                <li class="dash-item {{ Request::segment(1) == 'chat-groups' ? 'active' : '' }}">
                    <a href="{{ route('chat-groups.index') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-users-group"></i></span><span
                            class="dash-mtext">{{ __('Group Chat') }}</span></a>
                </li>
            @endif

            @if (\Auth::user()->type == 'company')
                <li
                    class="dash-item {{ Request::route()->getName() == 'notification-templates.index' || Request::segment(1) == 'notification-templates-lang' ? 'active' : '' }}">
                    <a href="{{ route('notification-templates.index') }}" class="dash-link"><span
                            class="dash-micon"><i class="ti ti-bell"></i></span><span
                            class="dash-mtext">{{ __('Notification Template') }}</span></a>
                </li>
            @endif

            @if (\Auth::user()->type == 'super admin')
                @if (Gate::check('Manage Plan'))
                    <li class="dash-item ">
                        <a href="{{ route('plans.index') }}" class="dash-link"><span
                                class="dash-micon"><i class=" ti ti-trophy"></i></span><span
                                class="dash-mtext">{{ __('Plan') }}</span></a>

                    </li>
                @endif
            @endif
            @if (\Auth::user()->type == 'super admin')
                <li class="dash-item ">
                    <a href="{{ route('plan_request.index') }}" class="dash-link"><span
                            class="dash-micon"><i class="ti ti-arrow-down-right-circle"></i></span><span
                            class="dash-mtext">{{ __('Plan Request') }}</span></a>

                </li>
            @endif


            @if (\Auth::user()->type == 'super admin')
                <li class="dash-item dash-hasmenu  {{ Request::segment(1) == '' ? 'active' : '' }}">
                    <a href="{{ route('referral-program.index') }}" class="dash-link">
                        <span class="dash-micon"><i class="ti ti-discount-2"></i></span><span
                            class="dash-mtext">{{ __('Referral Program') }}</span>
                    </a>
                </li>
            @endif

            @if (Auth::user()->type == 'super admin')
                @if (Gate::check('manage coupon'))
                    <li
                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'coupons' ? 'active' : '' }}">
                        <a href="{{ route('coupons.index') }}" class="dash-link"><span
                                class="dash-micon"><i class="ti ti-gift"></i></span><span
                                class="dash-mtext">{{ __('Coupon') }}</span></a>

                    </li>
                @endif
            @endif
            @if (\Auth::user()->type == 'super admin')
                {{-- @if (Gate::check('Manage Order')) --}}
                <li class="dash-item ">
                    <a href="{{ route('order.index') }}"
                        class="dash-link {{ request()->is('orders*') ? 'active' : '' }}"><span
                            class="dash-micon"><i class="ti ti-shopping-cart"></i></span><span
                            class="dash-mtext">{{ __('Order') }}</span></a>

                </li>
                {{-- @endif --}}
            @endif

            @if (\Auth::user()->type == 'super admin')
                <li
                    class="dash-item {{ Request::route()->getName() == 'email_template.index' || Request::segment(1) == 'email_template_lang' || Request::route()->getName() == 'manageemail.lang' ? 'active' : '' }}">
                    <a href="{{ route('email_template.index') }}" class="dash-link"><span
                            class="dash-micon"><i class="ti ti-template"></i></span><span
                            class="dash-mtext">{{ __('Email Templates') }}</span></a>

                </li>
            @endif
            <!--report-->
            <!-- @if (Gate::check('Manage Report'))
<li class="dash-item dash-hasmenu">
<a href="#!" class="dash-link"><span class="dash-micon"><i
class="ti ti-list"></i></span><span
class="dash-mtext">{{ __('Report') }}</span><span
class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
<ul class="dash-submenu">
@can('Manage Report')
<li class="dash-item">
<a class="dash-link"
href="{{ route('report.income-expense') }}">{{ __('Income Vs Expense') }}</a>
</li>

<li class="dash-item">
<a class="dash-link"
href="{{ route('report.monthly.attendance') }}">{{ __('Monthly Attendance') }}</a>
</li>

<li class="dash-item">
<a class="dash-link"
href="{{ route('report.leave') }}">{{ __('Leave') }}</a>
</li>


<li class="dash-item">
<a class="dash-link"
href="{{ route('report.account.statement') }}">{{ __('Account Statement') }}</a>
</li>



<li class="dash-item">
<a class="dash-link"
href="{{ route('report.timesheet') }}">{{ __('Timesheet') }}</a>
</li>
@endcan


</ul>
</li>
@endif -->


            <!--constant-->
            @if (Gate::check('Manage Department') ||
                    Gate::check('Manage Designation') ||
                    Gate::check('Manage Document Type') ||
                    Gate::check('Manage Branch') ||
                    Gate::check('Manage Award Type') ||
                    Gate::check('Manage Termination Types') ||
                    Gate::check('Manage Payslip Type') ||
                    Gate::check('Manage Allowance Option') ||
                    Gate::check('Manage Loan Options') ||
                    Gate::check('Manage Deduction Options') ||
                    Gate::check('Manage Expense Type') ||
                    Gate::check('Manage Income Type') ||
                    Gate::check('Manage Payment Type') ||
                    Gate::check('Manage Leave Type') ||
                    Gate::check('Manage Training Type') ||
                    Gate::check('Manage Job Category') ||
                    Gate::check('Manage Job Stage'))
                <li
                    class="dash-item dash-hasmenu {{ Request::route()->getName() == 'branch.index' ||Request::route()->getName() == 'department.index' ||Request::route()->getName() == 'designation.index' ||Request::route()->getName() == 'leavetype.index' ||Request::route()->getName() == 'document.index' ||Request::route()->getName() == 'paysliptype.index' ||Request::route()->getName() == 'allowanceoption.index' ||Request::route()->getName() == 'loanoption.index' ||Request::route()->getName() == 'deductionoption.index' ||Request::route()->getName() == 'goaltype.index' ||Request::route()->getName() == 'trainingtype.index' ||Request::route()->getName() == 'awardtype.index' ||Request::route()->getName() == 'terminationtype.index' ||Request::route()->getName() == 'job-category.index' ||Request::route()->getName() == 'job-stage.index' ||Request::route()->getName() == 'performanceType.index' ||Request::route()->getName() == 'competencies.index' ||Request::route()->getName() == 'expensetype.index' ||Request::route()->getName() == 'incometype.index' ||Request::route()->getName() == 'paymenttype.index' ||Request::route()->getName() == 'contract_type.index'? ' active': '' }}">
                    <a href="{{ route('branch.index') }}" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-table"></i></span><span
                            class="dash-mtext">{{ __('HRM System Setup') }}</span></a>
                </li>
                <!-- <ul class="dash-submenu">
@can('Manage Branch')
<li class="dash-item {{ request()->is('branch*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('branch.index') }}">{{ __('Branch') }}</a>
</li>
@endcan
@can('Manage Department')
<li class="dash-item {{ request()->is('department*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('department.index') }}">{{ __('Department') }}</a>
</li>
@endcan
@can('Manage Designation')
<li class="dash-item {{ request()->is('designation*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('designation.index') }}">{{ __('Designation') }}</a>
</li>
@endcan
@can('Manage Document Type')
<li class="dash-item {{ request()->is('document*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('document.index') }}">{{ __('Document Type') }}</a>
</li>
@endcan

@can('Manage Award Type')
<li class="dash-item {{ request()->is('awardtype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('awardtype.index') }}">{{ __('Award Type') }}</a>
</li>
@endcan
@can('Manage Termination Types')
<li
class="dash-item {{ request()->is('terminationtype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('terminationtype.index') }}">{{ __('Termination Type') }}</a>
</li>
@endcan
@can('Manage Payslip Type')
<li class="dash-item {{ request()->is('paysliptype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('paysliptype.index') }}">{{ __('Payslip Type') }}</a>
</li>
@endcan
@can('Manage Allowance Option')
<li
class="dash-item {{ request()->is('allowanceoption*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('allowanceoption.index') }}">{{ __('Allowance Option') }}</a>
</li>
@endcan
@can('Manage Loan Option')
<li class="dash-item {{ request()->is('loanoption*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('loanoption.index') }}">{{ __('Loan Option') }}</a>
</li>
@endcan
@can('Manage Deduction Option')
<li
class="dash-item {{ request()->is('deductionoption*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('deductionoption.index') }}">{{ __('Deduction Option') }}</a>
</li>
@endcan
@can('Manage Expense Type')
<li class="dash-item {{ request()->is('expensetype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('expensetype.index') }}">{{ __('Expense Type') }}</a>
</li>
@endcan
@can('Manage Income Type')
<li class="dash-item {{ request()->is('incometype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('incometype.index') }}">{{ __('Income Type') }}</a>
</li>
@endcan
@can('Manage Payment Type')
<li class="dash-item {{ request()->is('paymenttype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('paymenttype.index') }}">{{ __('Payment Type') }}</a>
</li>
@endcan
@can('Manage Leave Type')
<li class="dash-item {{ request()->is('leavetype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('leavetype.index') }}">{{ __('Leave Type') }}</a>
</li>
@endcan
@can('Manage Termination Type')
<li
class="dash-item {{ request()->is('terminationtype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('terminationtype.index') }}">{{ __('Termination Type') }}</a>
</li>
@endcan
@can('Manage Goal Type')
<li class="dash-item {{ request()->is('goaltype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('goaltype.index') }}">{{ __('Goal Type') }}</a>
</li>
@endcan
@can('Manage Training Type')
<li class="dash-item {{ request()->is('trainingtype*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('trainingtype.index') }}">{{ __('Training Type') }}</a>
</li>
@endcan

@if (\Auth::user()->type !== 'hr')
@can('Manage Job Category')
<li
class="dash-item {{ request()->is('job-category*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('job-category.index') }}">{{ __('Job Category') }}</a>
</li>
@endcan
@endif

@if (\Auth::user()->type !== 'hr')
@can('Manage Job Stage')
<li
class="dash-item {{ request()->is('job-stage*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('job-stage.index') }}">{{ __('Job Stage') }}</a>
</li>
@endcan
@endif

<li
class="dash-item {{ request()->is('performanceType*') ? 'active' : '' }}">
<a class="dash-link"
href="{{ route('performanceType.index') }}">{{ __('Performance Type') }}</a>
</li>

@can('Manage Competencies')
<li class="dash-item {{ request()->is('competencies*') ? 'active' : '' }}">

<a class="dash-link"
href="{{ route('competencies.index') }}">{{ __('Competencies') }}</a>
</li>
@endcan
</ul> -->
            @endif
            <!--constant-->

            @if (\Auth::user()->type == 'super admin')
                @include('landingpage::menu.landingpage')
            @endif

            @if (Gate::check('Manage System Settings'))
                <li class="dash-item ">
                    <a href="{{ route('settings.index') }}" class="dash-link"><span
                            class="dash-micon"><i class="ti ti-settings"></i></span><span
                            class="dash-mtext">{{ __('Settings') }}</span></a>
                </li>
            @endif
            @if (\Auth::user()->type == 'company' || \Auth::user()->type == 'super admin')
                <li class="dash-item {{ request()->routeIs('api-docs') ? 'active' : '' }}">
                    <a href="{{ route('api-docs') }}" class="dash-link"><span
                            class="dash-micon"><i class="ti ti-api"></i></span><span
                            class="dash-mtext">{{ __('Mobile API Panel') }}</span></a>
                </li>
            @endif
            <!--------------------- Start System Setup ----------------------------------->

            @if (\Auth::user()->type != 'super admin')

                @if (Gate::check('Manage Plan') || Gate::check('Manage Order') || Gate::check('Manage Company Settings'))
                    <li class="dash-item dash-hasmenu">
                        <a href="#!" class="dash-link ">
                            <span class="dash-micon"><i class="ti ti-settings"></i></span><span
                                class="dash-mtext">{{ __('System Setup') }}</span><span
                                class="dash-arrow">
                                <i data-feather="chevron-right"></i></span>
                        </a>
                        <ul class="dash-submenu">
                            @if (Gate::check('Manage Company Settings'))
                                <li
                                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'company-setting' ? ' active' : '' }}">
                                    <a href="{{ route('settings.index') }}"
                                        class="dash-link">{{ __('System Settings') }}</a>
                                </li>
                            @endif
                            @if (Gate::check('Manage Plan'))
                                <li
                                    class="dash-item{{ Request::route()->getName() == 'plans.index' || Request::route()->getName() == 'stripe' ? ' active' : '' }}">
                                    <a href="{{ route('plans.index') }}"
                                        class="dash-link">{{ __('Setup Subscription Plan') }}</a>
                                </li>
                            @endif
                            <li
                                class="dash-item{{ Request::route()->getName() == 'referral-program.company' ? ' active' : '' }}">
                                <a href="{{ route('referral-program.company') }}"
                                    class="dash-link">{{ __('Referral Program') }}</a>
                            </li>
                            @if (\Auth::user()->type == 'super admin' || \Auth::user()->type == 'company')
                                <li
                                    class="dash-item {{ Request::segment(1) == 'order' ? 'active' : '' }}">
                                    <a href="{{ route('order.index') }}"
                                        class="dash-link">{{ __('Order') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
            @endif

            <!--------------------- End System Setup ----------------------------------->
</ul>

</div>

{{-- ── Sidebar Footer: User info + Logout ─────────────── --}}
<div class="sidebar-user-footer">
    <div class="suf-avatar">
        @php $suf_profile = \App\Models\Utility::get_file('uploads/avatar/'); @endphp
        <img src="{{ !empty($users->avatar) ? $suf_profile . $users->avatar : $suf_profile . 'avatar.png' }}"
             alt="{{ $users->name }}" class="suf-avatar-img">
    </div>
    <div class="suf-info">
        <span class="suf-name">{{ \Illuminate\Support\Str::limit($users->name, 18) }}</span>
        <span class="suf-role">{{ ucfirst($users->type) }}</span>
    </div>
    <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    <a href="#" class="suf-logout-btn"
       title="{{ __('Logout') }}"
       onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
        <i class="ti ti-logout"></i>
    </a>
</div>
{{-- ── End Sidebar Footer ─────────────────────────────── --}}

</div>
</nav>
