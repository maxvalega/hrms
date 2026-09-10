@extends('layouts.admin')
@section('page-title') {{ __('Exit Detail') }} — {{ optional($r->user)->name }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('exit-management.index') }}">{{ __('Exit Management') }}</a></li>
    <li class="breadcrumb-item">{{ optional($r->user)->name }}</li>
@endsection

@push('css-page')
<style>
    /* timeline */
    .ex-timeline{display:flex;justify-content:space-between;gap:8px;position:relative;padding:14px 0 8px;}
    .ex-timeline::before{content:'';position:absolute;top:34px;left:24px;right:24px;height:2px;background:#e2e8f0;z-index:0;}
    .ex-step{flex:1;text-align:center;position:relative;z-index:1;}
    .ex-step .ex-dot{width:38px;height:38px;border-radius:50%;background:#e2e8f0;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;font-size:18px;border:3px solid #fff;box-shadow:0 0 0 2px #e2e8f0;}
    .ex-step.done .ex-dot{background:#10b981;color:#fff;box-shadow:0 0 0 2px #10b981;}
    .ex-step.active .ex-dot{background:#6366f1;color:#fff;box-shadow:0 0 0 2px #6366f1;}
    .ex-step.rejected .ex-dot{background:#ef4444;color:#fff;box-shadow:0 0 0 2px #ef4444;}
    .ex-step .ex-lbl{font-size:.7rem;color:#64748b;margin-top:6px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;}
    .ex-step.done .ex-lbl,.ex-step.active .ex-lbl{color:#0f172a;}

    /* status pill */
    .ex-status-pill{font-size:.68rem;font-weight:700;padding:4px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:.3px;}
    .badge-pending  {background:#fef3c7;color:#b45309;}
    .badge-mgr-ok   {background:#dbeafe;color:#1d4ed8;}
    .badge-hr-ok    {background:#dcfce7;color:#166534;}
    .badge-rejected {background:#fee2e2;color:#991b1b;}
    .badge-done     {background:#e0e7ff;color:#3730a3;}

    /* checklist */
    .chk-item{padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;display:flex;align-items:center;gap:10px;background:#fff;transition:.12s;}
    .chk-item.done{background:#f0fdf4;border-color:#bbf7d0;}
    .chk-item .chk-name{flex:1;}
    .chk-item.done .chk-name{text-decoration:line-through;color:#15803d;}
    .chk-item .chk-meta{font-size:.7rem;color:#94a3b8;}

    /* fnf */
    .fnf-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #e2e8f0;}
    .fnf-row:last-child{border-bottom:0;}
    .fnf-summary{background:linear-gradient(135deg,#eef2ff,#fff);border:1px solid #c7d2fe;border-radius:10px;padding:14px;}
    .fnf-summary .net{font-size:1.6rem;font-weight:800;color:#1e40af;}

    .meta-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;}
    .meta-card .lbl{font-size:.66rem;text-transform:uppercase;color:#94a3b8;letter-spacing:.4px;font-weight:600;}
    .meta-card .val{font-weight:700;color:#0f172a;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @php $step = $r->timelineStep(); $rejected = in_array($r->status, ['manager_rejected','hr_rejected'], true); @endphp

    {{-- ───────── Header card with timeline ───────── --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="mb-1">{{ optional($r->user)->name }}</h4>
                    <div class="text-muted small">{{ optional($r->user)->email }}</div>
                    <div class="mt-2"><span class="ex-status-pill {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span></div>
                </div>
                <div class="text-end">
                    <a href="{{ route('exit-management.index') }}" class="btn btn-light border btn-sm">
                        <i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}
                    </a>
                </div>
            </div>

            <div class="ex-timeline mt-3">
                @php
                    $steps = [
                        1 => ['Submitted',   'ti-file-text'],
                        2 => ['Manager',     'ti-user-check'],
                        3 => ['HR Approval', 'ti-shield-check'],
                        4 => ['Checklist',   'ti-list-check'],
                        5 => ['FNF',         'ti-coin'],
                        6 => ['Completed',   'ti-circle-check'],
                    ];
                @endphp
                @foreach($steps as $n => [$lbl, $icon])
                    @php
                        $cls = '';
                        if ($rejected && (($r->status === 'manager_rejected' && $n === 2) || ($r->status === 'hr_rejected' && $n === 3))) {
                            $cls = 'rejected';
                        } elseif ($n < $step) { $cls = 'done'; }
                        elseif ($n == $step) { $cls = 'active'; }
                    @endphp
                    <div class="ex-step {{ $cls }}">
                        <span class="ex-dot"><i class="ti {{ $icon }}"></i></span>
                        <div class="ex-lbl">{{ __($lbl) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">

            {{-- ───────── Resignation details ───────── --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-file-text me-1"></i>{{ __('Resignation Details') }}</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="meta-card">
                                <div class="lbl">{{ __('Resignation Date') }}</div>
                                <div class="val">{{ $r->resignation_date->format('d M Y') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="meta-card">
                                <div class="lbl">{{ __('Last Working Day') }}</div>
                                <div class="val">{{ $r->last_working_day->format('d M Y') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="meta-card">
                                <div class="lbl">{{ __('Notice Period') }}</div>
                                <div class="val">{{ $r->notice_period_days }} {{ __('days') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="lbl small text-uppercase text-muted fw-bold">{{ __('Reason') }}</div>
                        <div class="mt-1">{{ $r->reason }}</div>
                    </div>

                    @if($r->manager_action_at)
                        <hr>
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-{{ $r->status === 'manager_rejected' ? 'x text-danger' : 'check text-success' }} mt-1"></i>
                            <div class="flex-grow-1">
                                <strong>{{ optional($r->manager)->name ?? __('Manager') }}</strong>
                                <span class="text-muted small ms-2">{{ $r->manager_action_at->format('d M Y · h:i A') }}</span>
                                @if($r->manager_note)<div class="mt-1 small">{{ $r->manager_note }}</div>@endif
                            </div>
                        </div>
                    @endif
                    @if($r->hr_action_at)
                        <hr>
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-{{ $r->status === 'hr_rejected' ? 'x text-danger' : 'check text-success' }} mt-1"></i>
                            <div class="flex-grow-1">
                                <strong>{{ optional($r->hr)->name ?? __('HR') }}</strong>
                                <span class="text-muted small ms-2">{{ $r->hr_action_at->format('d M Y · h:i A') }}</span>
                                @if($r->hr_note)<div class="mt-1 small">{{ $r->hr_note }}</div>@endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ───────── Assigned company assets (jemini.co.in only) ───────── --}}
            @if(!empty($assetLifecycle))
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0"><i class="ti ti-device-laptop me-1"></i>{{ __('Company assets with this employee') }}</h6>
                        @if(!empty($canReturnAssets) && $assignedAssets->count() && !in_array($r->status, ['completed', 'manager_rejected', 'hr_rejected'], true))
                            <form method="POST" action="{{ route('exit-management.assets.return', $r->id) }}" onsubmit="return confirm('{{ __('Take back all assigned assets and restore them to inventory?') }}')">
                                @csrf
                                <button class="btn btn-sm btn-warning">
                                    <i class="ti ti-transfer-in me-1"></i>{{ __('Take all back to inventory') }}
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="card-body">
                        @forelse($assignedAssets as $asset)
                            <div class="chk-item">
                                <i class="ti ti-device-laptop fs-5 text-primary"></i>
                                <div class="chk-name">
                                    <strong>{{ $asset->asset_code }} · {{ $asset->name }}</strong>
                                    <div class="chk-meta">
                                        {{ $asset->category ?: __('Asset') }}
                                        @if($asset->serial_number) · {{ $asset->serial_number }} @endif
                                        · {{ $asset->conditionLabel() }}
                                    </div>
                                </div>
                                @if(!empty($canReturnAssets))
                                    <a href="{{ route('account-assets.show', $asset->id) }}" class="btn btn-sm btn-light border">{{ __('History') }}</a>
                                    <a href="#" class="btn btn-sm btn-warning"
                                        data-ajax-popup="true" data-size="lg"
                                        data-title="{{ __('Take back asset') }}"
                                        data-url="{{ route('account-assets.return.form', $asset->id) }}?reason=exit&resignation_id={{ $r->id }}">
                                        {{ __('Take back') }}
                                    </a>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted small mb-0">{{ __('No company assets are currently assigned. They may already have been restored to inventory.') }}</p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- ───────── Checklist (HR-only edit; everyone sees status) ───────── --}}
            @if(in_array($r->status, ['hr_approved', 'completed'], true))
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="ti ti-list-check me-1"></i>{{ __('Exit Checklist') }}</h6>
                        @php
                            $totalItems = $r->checklist->count();
                            $doneItems  = $r->checklist->where('status', 'completed')->count();
                        @endphp
                        <small class="text-muted">{{ $doneItems }} / {{ $totalItems }} {{ __('completed') }}</small>
                    </div>
                    <div class="card-body">
                        @forelse($r->checklist as $item)
                            <div class="chk-item {{ $item->status === 'completed' ? 'done' : '' }}">
                                <i class="ti {{ $item->status === 'completed' ? 'ti-circle-check text-success' : 'ti-circle' }} fs-5"></i>
                                <div class="chk-name">
                                    <strong>{{ $item->item_name }}</strong>
                                    @if($item->completed_at)
                                        <div class="chk-meta">{{ __('Done') }} {{ $item->completed_at->diffForHumans() }}</div>
                                    @endif
                                </div>
                                @if($isHr && $r->status === 'hr_approved')
                                    <form method="POST" action="{{ route('exit-management.checklist.toggle', [$r->id, $item->id]) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm {{ $item->status === 'completed' ? 'btn-light border' : 'btn-success' }}">
                                            {{ $item->status === 'completed' ? __('Undo') : __('Mark Done') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('exit-management.checklist.delete', [$r->id, $item->id]) }}" class="d-inline" onsubmit="return confirm('{{ __('Remove this item?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light border text-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted small mb-0">{{ __('No checklist items.') }}</p>
                        @endforelse

                        @if($isHr && $r->status === 'hr_approved')
                            <form method="POST" action="{{ route('exit-management.checklist.add', $r->id) }}" class="d-flex gap-2 mt-3">
                                @csrf
                                <input type="text" name="item_name" class="form-control form-control-sm" required maxlength="200"
                                       placeholder="{{ __('Add custom item (e.g. SIM card returned)') }}">
                                <button class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>{{ __('Add') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ───────── FNF Settlement ───────── --}}
            @if(in_array($r->status, ['hr_approved', 'completed'], true) && $r->fnf)
                @php $fnf = $r->fnf; @endphp
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="ti ti-coin me-1"></i>{{ __('Full & Final Settlement') }}</h6>
                        <span class="ex-status-pill {{ $fnf->status === 'paid' ? 'badge-done' : ($fnf->status === 'finalised' ? 'badge-hr-ok' : 'badge-pending') }}">
                            {{ ucfirst($fnf->status) }}
                        </span>
                    </div>
                    <div class="card-body">
                        @if($isHr && $r->status === 'hr_approved')
                            <form method="POST" action="{{ route('exit-management.fnf.save', $r->id) }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <h6 class="text-success small text-uppercase fw-bold mb-2">{{ __('Earnings') }}</h6>
                                        @foreach([
                                            'pending_salary'   => __('Pending Salary'),
                                            'leave_encashment' => __('Leave Encashment'),
                                            'gratuity'         => __('Gratuity'),
                                            'bonus'            => __('Bonus / Incentive'),
                                            'other_earnings'   => __('Other Earnings'),
                                        ] as $key => $lbl)
                                            <div class="mb-2">
                                                <label class="form-label small mb-1">{{ $lbl }}</label>
                                                <input type="number" step="0.01" min="0" name="{{ $key }}" class="form-control form-control-sm fnf-input"
                                                       data-side="earn" value="{{ old($key, $fnf->$key) }}">
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-danger small text-uppercase fw-bold mb-2">{{ __('Deductions') }}</h6>
                                        @foreach([
                                            'notice_recovery'  => __('Notice Recovery'),
                                            'asset_recovery'   => __('Asset Recovery'),
                                            'tax_deduction'    => __('Tax (TDS)'),
                                            'other_deductions' => __('Other Deductions'),
                                        ] as $key => $lbl)
                                            <div class="mb-2">
                                                <label class="form-label small mb-1">{{ $lbl }}</label>
                                                <input type="number" step="0.01" min="0" name="{{ $key }}" class="form-control form-control-sm fnf-input"
                                                       data-side="ded" value="{{ old($key, $fnf->$key) }}">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="form-group mt-3">
                                    <label class="form-label small">{{ __('Remarks') }}</label>
                                    <textarea name="remarks" class="form-control form-control-sm" rows="2" maxlength="1000">{{ old('remarks', $fnf->remarks) }}</textarea>
                                </div>

                                <div class="fnf-summary mt-3">
                                    <div class="fnf-row"><span>{{ __('Total Earnings') }}</span><strong id="sumEarn">₹{{ number_format($fnf->total_amount, 2) }}</strong></div>
                                    <div class="fnf-row"><span>{{ __('Total Deductions') }}</span><strong class="text-danger" id="sumDed">₹{{ number_format($fnf->deductions, 2) }}</strong></div>
                                    <div class="fnf-row"><span class="fw-bold">{{ __('Net Payable') }}</span><span class="net" id="sumNet">₹{{ number_format($fnf->final_amount, 2) }}</span></div>
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" name="action" value="save" class="btn btn-light border btn-sm">
                                        <i class="ti ti-device-floppy me-1"></i>{{ __('Save Draft') }}
                                    </button>
                                    <button type="submit" name="action" value="finalise" class="btn btn-primary btn-sm"
                                            onclick="return confirm('{{ __('Finalise the FNF? Amounts will be locked.') }}')">
                                        <i class="ti ti-check me-1"></i>{{ __('Finalise') }}
                                    </button>
                                    @if($fnf->status === 'finalised')
                                        <button type="submit" name="action" value="paid" class="btn btn-success btn-sm">
                                            <i class="ti ti-cash me-1"></i>{{ __('Mark as Paid') }}
                                        </button>
                                    @endif
                                </div>
                            </form>
                        @else
                            {{-- Read-only summary (employee/manager view, or after completion) --}}
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="text-success small text-uppercase fw-bold mb-2">{{ __('Earnings') }}</h6>
                                    <div class="fnf-row"><span>{{ __('Pending Salary') }}</span><strong>₹{{ number_format($fnf->pending_salary, 2) }}</strong></div>
                                    <div class="fnf-row"><span>{{ __('Leave Encashment') }}</span><strong>₹{{ number_format($fnf->leave_encashment, 2) }}</strong></div>
                                    <div class="fnf-row"><span>{{ __('Gratuity') }}</span><strong>₹{{ number_format($fnf->gratuity, 2) }}</strong></div>
                                    <div class="fnf-row"><span>{{ __('Bonus') }}</span><strong>₹{{ number_format($fnf->bonus, 2) }}</strong></div>
                                    <div class="fnf-row"><span>{{ __('Other Earnings') }}</span><strong>₹{{ number_format($fnf->other_earnings, 2) }}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-danger small text-uppercase fw-bold mb-2">{{ __('Deductions') }}</h6>
                                    <div class="fnf-row"><span>{{ __('Notice Recovery') }}</span><strong>₹{{ number_format($fnf->notice_recovery, 2) }}</strong></div>
                                    <div class="fnf-row"><span>{{ __('Asset Recovery') }}</span><strong>₹{{ number_format($fnf->asset_recovery, 2) }}</strong></div>
                                    <div class="fnf-row"><span>{{ __('Tax (TDS)') }}</span><strong>₹{{ number_format($fnf->tax_deduction, 2) }}</strong></div>
                                    <div class="fnf-row"><span>{{ __('Other Deductions') }}</span><strong>₹{{ number_format($fnf->other_deductions, 2) }}</strong></div>
                                </div>
                            </div>
                            <div class="fnf-summary mt-3">
                                <div class="fnf-row"><span>{{ __('Total Earnings') }}</span><strong>₹{{ number_format($fnf->total_amount, 2) }}</strong></div>
                                <div class="fnf-row"><span>{{ __('Total Deductions') }}</span><strong class="text-danger">₹{{ number_format($fnf->deductions, 2) }}</strong></div>
                                <div class="fnf-row"><span class="fw-bold">{{ __('Net Payable') }}</span><span class="net">₹{{ number_format($fnf->final_amount, 2) }}</span></div>
                            </div>
                            @if($fnf->paid_on)
                                <div class="alert alert-success small mt-3 mb-0">
                                    <i class="ti ti-check-circle me-1"></i>{{ __('Paid on :date', ['date' => $fnf->paid_on->format('d M Y')]) }}
                                </div>
                            @endif
                            @if($fnf->remarks)
                                <hr><div class="small"><strong>{{ __('Remarks:') }}</strong> {{ $fnf->remarks }}</div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

        </div>

        {{-- ───────── Side panel: actions ───────── --}}
        <div class="col-lg-4">

            {{-- Manager actions --}}
            @if($canMgrAct)
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-user-check me-1"></i>{{ __('Manager Action Required') }}</h6></div>
                    <div class="card-body">
                        <p class="small text-muted">{{ __('Review the resignation and approve or reject.') }}</p>
                        <form method="POST" action="{{ route('exit-management.manager.approve', $r->id) }}" class="mb-2">
                            @csrf
                            <textarea name="note" class="form-control form-control-sm mb-2" rows="2" maxlength="1000" placeholder="{{ __('Optional note…') }}"></textarea>
                            <button class="btn btn-success btn-sm w-100" onclick="return confirm('{{ __('Approve this resignation and forward to HR?') }}')">
                                <i class="ti ti-check me-1"></i>{{ __('Approve & Forward to HR') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('exit-management.manager.reject', $r->id) }}">
                            @csrf
                            <textarea name="note" class="form-control form-control-sm mb-2" rows="2" maxlength="1000" required placeholder="{{ __('Reason for rejection (required)…') }}"></textarea>
                            <button class="btn btn-light border text-danger btn-sm w-100">
                                <i class="ti ti-x me-1"></i>{{ __('Reject') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- HR actions --}}
            @if($isHr && in_array($r->status, ['pending', 'manager_approved'], true))
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-shield-check me-1"></i>{{ __('HR Action') }}</h6></div>
                    <div class="card-body">
                        @if($r->status === 'manager_approved')
                            <form method="POST" action="{{ route('exit-management.hr.approve', $r->id) }}" class="mb-2">
                                @csrf
                                <textarea name="note" class="form-control form-control-sm mb-2" rows="2" maxlength="1000" placeholder="{{ __('Optional note…') }}"></textarea>
                                <button class="btn btn-success btn-sm w-100" onclick="return confirm('{{ __('Approve and start the exit process?') }}')">
                                    <i class="ti ti-check me-1"></i>{{ __('HR Approve & Start Exit') }}
                                </button>
                            </form>
                        @else
                            <p class="small text-muted">{{ __('Awaiting manager approval. You may reject directly if needed.') }}</p>
                        @endif
                        <form method="POST" action="{{ route('exit-management.hr.reject', $r->id) }}">
                            @csrf
                            <textarea name="note" class="form-control form-control-sm mb-2" rows="2" maxlength="1000" required placeholder="{{ __('Reason for rejection (required)…') }}"></textarea>
                            <button class="btn btn-light border text-danger btn-sm w-100">
                                <i class="ti ti-x me-1"></i>{{ __('Reject') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Mark Complete (HR, when ready) --}}
            @if($isHr && $r->status === 'hr_approved')
                @php
                    $chkDone = $r->checklistComplete();
                    $fnfReady = $r->fnf && in_array($r->fnf->status, ['finalised', 'paid'], true);
                @endphp
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-circle-check me-1"></i>{{ __('Finalise Exit') }}</h6></div>
                    <div class="card-body">
                        <ul class="small mb-3 ps-3">
                            <li class="{{ $chkDone ? 'text-success' : 'text-muted' }}">
                                <i class="ti ti-{{ $chkDone ? 'check' : 'circle' }} me-1"></i>{{ __('Checklist completed') }}
                            </li>
                            <li class="{{ $fnfReady ? 'text-success' : 'text-muted' }}">
                                <i class="ti ti-{{ $fnfReady ? 'check' : 'circle' }} me-1"></i>{{ __('FNF finalised') }}
                            </li>
                        </ul>
                        <form method="POST" action="{{ route('exit-management.complete', $r->id) }}">
                            @csrf
                            <button class="btn btn-primary btn-sm w-100" {{ ($chkDone && $fnfReady) ? '' : 'disabled' }}
                                    onclick="return confirm('{{ __('Mark this exit complete? This cannot be undone.') }}')">
                                <i class="ti ti-flag me-1"></i>{{ __('Mark Exit Complete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Reporting Manager card --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-users me-1"></i>{{ __('Approvers') }}</h6></div>
                <div class="card-body">
                    <div class="meta-card mb-2">
                        <div class="lbl">{{ __('Manager') }}</div>
                        <div class="val">{{ optional($r->manager)->name ?? '—' }}</div>
                    </div>
                    <div class="meta-card">
                        <div class="lbl">{{ __('HR') }}</div>
                        <div class="val">{{ optional($r->hr)->name ?? __('Pending') }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('script-page')
<script>
(function(){
    // Live FNF totals
    const inputs = document.querySelectorAll('.fnf-input');
    const sumEarn = document.getElementById('sumEarn');
    const sumDed  = document.getElementById('sumDed');
    const sumNet  = document.getElementById('sumNet');
    if (!inputs.length || !sumEarn) return;
    function fmt(n){ return '₹' + n.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    function recalc(){
        let e=0, d=0;
        inputs.forEach(i => {
            const v = parseFloat(i.value) || 0;
            if (i.dataset.side === 'earn') e += v; else d += v;
        });
        sumEarn.textContent = fmt(e);
        sumDed.textContent  = fmt(d);
        sumNet.textContent  = fmt(e - d);
    }
    inputs.forEach(i => i.addEventListener('input', recalc));
})();
</script>
@endpush
