<ul class="nav nav-pills mb-3" role="tablist" style="gap:4px;flex-wrap:wrap;">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.dashboard') ? 'active' : '' }}" href="{{ route('growth-review.dashboard') }}">
            <i class="ti ti-layout-dashboard me-1"></i>{{ __('Dashboard') }}
        </a>
    </li>
    @if(Auth::user()->type == 'company' || Auth::user()->type == 'hr')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.cycles*') ? 'active' : '' }}" href="{{ route('growth-review.cycles') }}">
            <i class="ti ti-repeat me-1"></i>{{ __('Cycles') }}
        </a>
    </li>
    @endif
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.missions*') ? 'active' : '' }}" href="{{ route('growth-review.missions') }}">
            <i class="ti ti-target me-1"></i>{{ __('Missions') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.kpi-generator.index') || request()->routeIs('growth-review.kpi-generator.show') || request()->routeIs('growth-review.kpi-generator.generate') ? 'active' : '' }}" href="{{ route('growth-review.kpi-generator.index') }}">
            <i class="ti ti-sparkles me-1"></i>{{ __('KRA / KPI Generator') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.kpi-generator.my-assigned') ? 'active' : '' }}" href="{{ route('growth-review.kpi-generator.my-assigned') }}">
            <i class="ti ti-target me-1"></i>{{ __('My KPIs') }}
        </a>
    </li>
    @if(Auth::user()->type == 'company' || Auth::user()->type == 'hr')
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle {{ request()->routeIs('growth-review.masters*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
            <i class="ti ti-database me-1"></i>{{ __('KPI Masters') }}
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('growth-review.masters.index','industries') }}">{{ __('Industries') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('growth-review.masters.index','company-sizes') }}">{{ __('Company Sizes') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('growth-review.masters.index','seniority-levels') }}">{{ __('Seniority Levels') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('growth-review.masters.index','work-models') }}">{{ __('Work Models') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('growth-review.masters.index','company-types') }}">{{ __('Company Types') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('growth-review.masters.index','timeframes') }}">{{ __('Target Timeframes') }}</a></li>
        </ul>
    </li>
    @endif
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.shoutouts*') ? 'active' : '' }}" href="{{ route('growth-review.shoutouts') }}">
            <i class="ti ti-speakerphone me-1"></i>{{ __('Shoutouts') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.sync-ups*') ? 'active' : '' }}" href="{{ route('growth-review.sync-ups') }}">
            <i class="ti ti-messages me-1"></i>{{ __('Sync Ups') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.comeback*') ? 'active' : '' }}" href="{{ route('growth-review.comeback') }}">
            <i class="ti ti-arrow-back-up me-1"></i>{{ __('Comeback Plans') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.reviews*') ? 'active' : '' }}" href="{{ route('growth-review.reviews') }}">
            <i class="ti ti-clipboard-check me-1"></i>{{ __('Reviews') }}
        </a>
    </li>
    @if(Auth::user()->type == 'company' || Auth::user()->type == 'hr')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.calibration*') ? 'active' : '' }}" href="{{ route('growth-review.calibration') }}">
            <i class="ti ti-adjustments me-1"></i>{{ __('Calibration') }}
        </a>
    </li>
    @endif
    @php
        $__incEmp = \App\Models\Employee::where('user_id', Auth::id())->first();
        $__isManager = $__incEmp && \App\Models\Employee::where('created_by', Auth::user()->creatorId())
            ->where(function($q) use ($__incEmp) {
                $q->where('reporting_manager_id', $__incEmp->id)
                  ->orWhere('hod_id', $__incEmp->id)
                  ->orWhere('management_id', $__incEmp->id);
            })->exists();
    @endphp
    @if(in_array(Auth::user()->type, ['company', 'super admin']) || $__isManager)
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('growth-review.increments*') ? 'active' : '' }}" href="{{ route('growth-review.increments') }}">
            <i class="ti ti-chart-arrows-vertical me-1"></i>{{ __('Increments') }}
        </a>
    </li>
    @endif
</ul>
