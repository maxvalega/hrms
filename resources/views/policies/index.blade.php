@extends('layouts.admin')
@section('page-title') {{ __('Company Policies') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Policies') }}</li>
@endsection

@push('css-page')
<style>
    .pol-stat{border:1px solid var(--bs-border-color);border-radius:12px;background:#fff;padding:14px 16px;display:flex;align-items:center;gap:12px;}
    .pol-stat .pol-icon{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
    .pol-stat .pol-num{font-size:1.45rem;font-weight:700;line-height:1;color:#0f172a;}
    .pol-stat .pol-lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-top:3px;}
    .pol-stat.t-all .pol-icon      {background:linear-gradient(135deg,#6366f1,#8b5cf6);}
    .pol-stat.t-active .pol-icon   {background:linear-gradient(135deg,#10b981,#059669);}
    .pol-stat.t-archived .pol-icon {background:linear-gradient(135deg,#94a3b8,#64748b);}

    .pol-cat-badge{font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px;}
    .pol-cat-hr      {background:#dbeafe;color:#1d4ed8;}
    .pol-cat-leave   {background:#dcfce7;color:#166534;}
    .pol-cat-it      {background:#ede9fe;color:#6d28d9;}
    .pol-cat-conduct {background:#fef3c7;color:#b45309;}
    .pol-cat-other   {background:#f1f5f9;color:#475569;}

    .pol-status-pill{font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.3px;text-transform:uppercase;}
    .pol-st-active   {background:#dcfce7;color:#166534;}
    .pol-st-archived {background:#fee2e2;color:#991b1b;}

    .ack-pill{font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;}
    .ack-yes {background:#dcfce7;color:#166534;}
    .ack-no  {background:#fef3c7;color:#b45309;}

    .pol-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
    .pol-table td{vertical-align:middle;}
    .pol-empty{text-align:center;padding:48px 16px;color:#94a3b8;}
    .pol-empty i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6">
            <div class="pol-stat t-all">
                <span class="pol-icon"><i class="ti ti-files"></i></span>
                <div>
                    <div class="pol-num">{{ $totals['all'] }}</div>
                    <div class="pol-lbl">{{ __('All Policies') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="pol-stat t-active">
                <span class="pol-icon"><i class="ti ti-check"></i></span>
                <div>
                    <div class="pol-num">{{ $totals['active'] }}</div>
                    <div class="pol-lbl">{{ __('Active') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12">
            <div class="pol-stat t-archived">
                <span class="pol-icon"><i class="ti ti-archive"></i></span>
                <div>
                    <div class="pol-num">{{ $totals['archived'] }}</div>
                    <div class="pol-lbl">{{ __('Archived') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1"><i class="ti ti-files me-2"></i>{{ __('Company Policies') }}</h5>
                <small class="text-muted">{{ __('View and acknowledge policies') }}</small>
            </div>
            @if(!empty($canManage))
                <a href="{{ route('policies.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-upload me-1"></i>{{ __('Upload Policy') }}
                </a>
            @endif
        </div>

        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1 small">{{ __('Search') }}</label>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="{{ __('Search by title…') }}" value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small">{{ __('Category') }}</label>
                    <select name="category" class="form-control form-control-sm">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach($categories as $code => $label)
                            <option value="{{ $code }}" {{ ($filters['category'] ?? '') === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small">{{ __('Status') }}</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="active"   {{ ($filters['status'] ?? '') === 'active'   ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="archived" {{ ($filters['status'] ?? '') === 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                        <option value="all"      {{ ($filters['status'] ?? '') === 'all'      ? 'selected' : '' }}>{{ __('All') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-grow-1"><i class="ti ti-filter me-1"></i>{{ __('Filter') }}</button>
                    @if(!empty($filters['q']) || !empty($filters['category']) || ($filters['status'] ?? 'active') !== 'active')
                        <a href="{{ route('policies.index') }}" class="btn btn-light btn-sm border" title="{{ __('Clear') }}"><i class="ti ti-x"></i></a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            @php
                $hasNew = !$policies->isEmpty();
                $hasLegacy = !empty($legacyPolicies) && $legacyPolicies->isNotEmpty();
                $showArchivedOnly = ($filters['status'] ?? 'active') === 'archived';
            @endphp

            @if(!$hasNew && (!$hasLegacy || $showArchivedOnly))
                <div class="pol-empty">
                    <i class="ti ti-file-off"></i>
                    @if(($totals['all'] ?? 0) == 0)
                        <p class="mb-2">{{ __('No company policies have been uploaded yet.') }}</p>
                        <p class="mb-3 small text-muted">{{ __('HR must upload documents here (Upload Policy) or under Company Policy. Leave rules in the Leave module are separate from this document library.') }}</p>
                    @else
                        <p class="mb-2">{{ __('No policies found.') }}</p>
                        <p class="mb-3 small text-muted">{{ __('Try clearing filters or switching Status to All.') }}</p>
                    @endif
                    @if(!empty($canManage))
                        <a href="{{ route('policies.create') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-upload me-1"></i>{{ __('Upload your first policy') }}
                        </a>
                    @endif
                </div>
            @else
                @if($hasNew)
                <div class="table-responsive">
                    <table class="table pol-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Title') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Version') }}</th>
                                <th class="text-center">{{ __('Acks') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('My Status') }}</th>
                                <th>{{ __('Uploaded') }}</th>
                                <th class="pe-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($policies as $p)
                            @php $iAcked = in_array($p->id, $myAckIds, true); @endphp
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('policies.show', $p->id) }}" class="text-decoration-none">
                                        <strong>{{ $p->title }}</strong>
                                    </a>
                                    @if($p->is_mandatory)
                                        <span class="badge bg-danger ms-1" title="{{ __('Mandatory') }}" style="font-size:.6rem;">{{ __('Mandatory') }}</span>
                                    @endif
                                    @if($p->description)
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($p->description, 80) }}</div>
                                    @endif
                                </td>
                                <td><span class="pol-cat-badge pol-cat-{{ $p->category }}">{{ $p->categoryLabel() }}</span></td>
                                <td><small class="text-muted">v{{ $p->version }}</small></td>
                                <td class="text-center"><strong>{{ $p->acknowledgements_count ?? 0 }}</strong></td>
                                <td><span class="pol-status-pill pol-st-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
                                <td>
                                    @if($iAcked)
                                        <span class="ack-pill ack-yes"><i class="ti ti-check me-1"></i>{{ __('Acknowledged') }}</span>
                                    @else
                                        <span class="ack-pill ack-no"><i class="ti ti-clock me-1"></i>{{ __('Pending') }}</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $p->created_at->diffForHumans() }}</small></td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('policies.show', $p->id) }}" class="btn btn-light border" title="{{ __('View') }}">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('policies.file', $p->id) }}" target="_blank" class="btn btn-light border" title="{{ __('Open file') }}">
                                            <i class="ti ti-download"></i>
                                        </a>
                                        @if(!empty($canManage))
                                            <a href="{{ route('policies.edit', $p->id) }}" class="btn btn-light border" title="{{ __('Edit') }}">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            <form method="POST" action="{{ route('policies.destroy', $p->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this policy? Acknowledgement history will be removed.') }}');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-light border text-danger" title="{{ __('Delete') }}"><i class="ti ti-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <small class="text-muted">{{ __('Showing :from to :to of :total', ['from' => $policies->firstItem() ?? 0, 'to' => $policies->lastItem() ?? 0, 'total' => $policies->total()]) }}</small>
                    {{ $policies->links() }}
                </div>
                @endif

                @if($hasLegacy && !$showArchivedOnly && empty($filters['category']))
                    <div class="px-3 pt-3 pb-2 border-top">
                        <h6 class="mb-1">{{ __('Company Policy documents') }}</h6>
                        <small class="text-muted">{{ __('Uploaded under the older Company Policy menu — visible to all employees.') }}</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table pol-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">{{ __('Title') }}</th>
                                    <th>{{ __('Branch') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="pe-3 text-end">{{ __('File') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($legacyPolicies as $lp)
                                    @php $policyPath = \App\Models\Utility::get_file('uploads/companyPolicy'); @endphp
                                    <tr>
                                        <td class="ps-3"><strong>{{ $lp->title }}</strong></td>
                                        <td>{{ !empty($lp->branches) ? $lp->branches->name : '—' }}</td>
                                        <td><small class="text-muted">{{ \Illuminate\Support\Str::limit($lp->description ?? '', 100) }}</small></td>
                                        <td class="pe-3 text-end">
                                            @if(!empty($lp->attachment))
                                                <a href="{{ $policyPath . '/' . $lp->attachment }}" target="_blank" class="btn btn-sm btn-light border" title="{{ __('Open') }}">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                <a href="{{ $policyPath . '/' . $lp->attachment }}" download class="btn btn-sm btn-light border" title="{{ __('Download') }}">
                                                    <i class="ti ti-download"></i>
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
