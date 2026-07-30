@extends('layouts.admin')
@section('page-title') {{ __('Increments') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('Increments') }}</li>
@endsection
@push('css-page')
<style>
    .inc-status{font-size:.7rem;padding:2px 10px;border-radius:20px;font-weight:600;}
    .inc-proposed{background:#fef3c7;color:#92400e;}.inc-manager_proposed{background:#e0e7ff;color:#3730a3;}.inc-approved{background:#dcfce7;color:#166534;}.inc-applied{background:#dbeafe;color:#1e40af;}.inc-rejected{background:#fee2e2;color:#991b1b;}
    .inc-proposer{font-size:.72rem;color:#6366f1;margin-top:2px;}
    .inc-pct-wrap{display:inline-flex;align-items:center;gap:4px;cursor:pointer;}
    .inc-pct-wrap:hover .inc-pct-edit-icon{opacity:1;}
    .inc-pct-edit-icon{opacity:0;font-size:.75rem;color:#6366f1;transition:opacity .15s;}
    .inc-pct-input{width:68px;text-align:center;font-weight:700;font-size:.88rem;border:1.5px solid #8b5cf6;border-radius:7px;padding:3px 4px;outline:none;color:#16a34a;}
    .inc-pct-input:focus{box-shadow:0 0 0 3px rgba(139,92,246,.15);}
    .inc-pct-saving{opacity:.5;pointer-events:none;}
    .inc-pct-saved{border-color:#10b981 !important;background:#ecfdf5;}

    /* ── Budget Summary (Excel-style) ─────────────────────────── */
    .budget-wrap{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    @media (max-width: 991px){.budget-wrap{grid-template-columns:1fr;}}
    .budget-side{border:1px solid var(--bs-border-color);border-radius:12px;overflow:hidden;background:#fff;}
    .budget-side-head{padding:8px 14px;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;display:flex;justify-content:space-between;align-items:center;}
    .budget-side.current .budget-side-head{background:#fef9c3;color:#854d0e;}
    .budget-side.revised .budget-side-head{background:#fce7f3;color:#9d174d;}
    .budget-grid{display:grid;grid-template-columns:1.1fr 1.1fr 1.1fr;}
    .budget-cell{padding:10px 12px;border-top:1px solid #e2e8f0;}
    .budget-cell + .budget-cell{border-left:1px solid #e2e8f0;}
    .budget-cell .b-label{font-size:.68rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;line-height:1.2;}
    .budget-cell .b-value{font-size:1.05rem;font-weight:700;color:#0f172a;margin-top:2px;}
    .budget-cell.is-blue .b-value{color:#0369a1;} .budget-cell.is-blue{background:#eff6ff;}
    .budget-cell.is-green .b-value{color:#15803d;} .budget-cell.is-green{background:#ecfdf5;}
    .budget-cell.is-amber .b-value{color:#a16207;} .budget-cell.is-amber{background:#fef9c3;}
    .budget-cell.is-pink .b-value{color:#be185d;}  .budget-cell.is-pink{background:#fdf2f8;}
    .budget-input-row{display:flex;gap:10px;padding:10px 12px;background:#fbcfe8;border-top:1px solid #f9a8d4;align-items:center;}
    .budget-input-row label{font-size:.72rem;font-weight:600;color:#831843;margin:0;min-width:100px;}
    .budget-input-row input{width:120px;border:1px solid #f9a8d4;border-radius:6px;padding:4px 8px;font-size:.85rem;font-weight:600;text-align:right;background:#fff;}
    .budget-input-row input:focus{outline:none;border-color:#db2777;box-shadow:0 0 0 2px rgba(219,39,119,.15);}
    .budget-input-row .b-or{font-size:.7rem;color:#9d174d;font-weight:700;}
</style>
@endpush
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3">
        <div class="card-body py-3">
            <form class="d-flex align-items-end gap-3 flex-wrap">
                <div><label class="form-label mb-1">{{ __('Cycle') }}</label>
                    <select name="cycle_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:200px;">
                        @foreach($cycles as $c)<option value="{{ $c->id }}" {{ $cycleId==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                @if(Auth::user()->type === 'company' || Auth::user()->type === 'super admin')
                <div><label class="form-label mb-1">{{ __('Department') }}</label>
                    <select name="department_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:180px;">
                        <option value="">{{ __('All Departments') }}</option>
                        @foreach($departments as $dept)<option value="{{ $dept->id }}" {{ ($deptId ?? '')==$dept->id?'selected':'' }}>{{ $dept->name }}</option>@endforeach
                    </select>
                </div>
                @endif
                @if(($viewerRole ?? 'admin') === 'admin')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal"><i class="ti ti-calculator me-1"></i>{{ __('Generate Increments') }}</button>
                @endif
                @if(in_array($viewerRole ?? 'admin', ['manager', 'management']))
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#proposeModal"><i class="ti ti-plus me-1"></i>{{ __('Propose Increment') }}</button>
                @endif
            </form>
            @if($increments->isNotEmpty())
            <a href="{{ route('growth-review.increments.export', ['cycle_id' => $cycleId, 'department_id' => $deptId]) }}" class="btn btn-sm btn-success ms-2"><i class="ti ti-file-spreadsheet me-1"></i>{{ __('Export Excel') }}</a>
            @endif
        </div>
    </div>

    {{-- ── Budget Summary: Current vs Revised ───────────────────── --}}
    @if(isset($budget) && $budget['total_headcount'] > 0)
    @php
        $fmt = fn($n) => '₹' . number_format((float)$n, 0, '.', ',');
    @endphp
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="ti ti-calculator-filled me-1"></i>{{ __('Budget Summary') }}
                @if($cycle)<small class="text-muted ms-2">— {{ $cycle->name }}</small>@endif
            </h6>

            <div class="budget-wrap">
                {{-- LEFT: CURRENT --}}
                <div class="budget-side current">
                    <div class="budget-side-head">
                        <span><i class="ti ti-database me-1"></i>{{ __('Current') }}</span>
                        <span style="font-size:.7rem;font-weight:500;opacity:.8;">{{ __('Existing CTC & headcount') }}</span>
                    </div>
                    <div class="budget-grid">
                        <div class="budget-cell is-blue">
                            <div class="b-label">{{ __('Total CTC') }}</div>
                            <div class="b-value">{{ $fmt($budget['total_ctc']) }}</div>
                        </div>
                        <div class="budget-cell is-green">
                            <div class="b-label">{{ __('Headcount – Eligible') }}</div>
                            <div class="b-value">{{ $budget['eligible_headcount'] }}</div>
                        </div>
                        <div class="budget-cell is-amber">
                            <div class="b-label">{{ __('CTC – Eligible') }}</div>
                            <div class="b-value">{{ $fmt($budget['eligible_ctc_current']) }}</div>
                        </div>
                        <div class="budget-cell is-blue">
                            <div class="b-label">{{ __('Total Headcount') }}</div>
                            <div class="b-value">{{ $budget['total_headcount'] }}</div>
                        </div>
                        <div class="budget-cell is-green">
                            <div class="b-label">{{ __('Headcount – Not Eligible') }}</div>
                            <div class="b-value">{{ $budget['ineligible_headcount'] }}</div>
                        </div>
                        <div class="budget-cell is-amber">
                            <div class="b-label">{{ __('CTC – Not Eligible') }}</div>
                            <div class="b-value">{{ $fmt($budget['ineligible_ctc']) }}</div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: REVISED — NEW CTC --}}
                <div class="budget-side revised" id="budget-revised"
                     data-eligible-ctc="{{ $budget['eligible_ctc_current'] }}"
                     data-eligible-hc="{{ $budget['eligible_headcount'] }}"
                     data-ineligible-ctc="{{ $budget['ineligible_ctc'] }}"
                     data-ineligible-hc="{{ $budget['ineligible_headcount'] }}"
                     data-current-increment="{{ $budget['eligible_increment'] }}"
                     data-cycle-id="{{ $cycleId }}"
                     data-department-id="{{ $deptId ?? '' }}"
                     data-goal-seek-url="{{ route('growth-review.increments.goal-seek') }}"
                     data-csrf="{{ csrf_token() }}">
                    <div class="budget-side-head">
                        <span><i class="ti ti-trending-up me-1"></i>{{ __('Revised — New CTC') }}</span>
                        <span style="font-size:.7rem;font-weight:500;opacity:.8;">{{ __('Plan increase by % or amount') }}</span>
                    </div>
                    <div class="budget-input-row">
                        <label>{{ __('% Increase') }}</label>
                        <input type="number" id="b-pct-input" min="0" max="100" step="0.01"
                               value="{{ $budget['eligible_pct'] }}" placeholder="0.00">
                        <span class="b-or">{{ __('OR') }}</span>
                        <label>{{ __('Amount Increment') }}</label>
                        <input type="number" id="b-amt-input" min="0" step="0.01"
                               value="{{ number_format((float) $budget['eligible_increment'], 2, '.', '') }}" placeholder="0.00">
                        @if(($viewerRole ?? 'admin') === 'admin')
                        <button type="button" id="btn-goal-seek" class="btn btn-sm btn-primary ms-2" style="background:#be185d;border-color:#be185d;">
                            <i class="ti ti-target me-1"></i>{{ __('Goal Seek') }}
                        </button>
                        @endif
                        <small class="ms-auto text-muted" style="font-size:.7rem;">{{ __('Type a target, then click Goal Seek to scale all rows proportionally') }}</small>
                    </div>
                    <div class="budget-grid">
                        <div class="budget-cell is-pink">
                            <div class="b-label">{{ __('Total Revised CTC') }}</div>
                            <div class="b-value" id="b-revised-total-ctc">{{ $fmt($budget['total_ctc'] + $budget['eligible_increment']) }}</div>
                        </div>
                        <div class="budget-cell is-pink">
                            <div class="b-label">{{ __('Headcount – Eligible') }}</div>
                            <div class="b-value">{{ $budget['eligible_headcount'] }}</div>
                        </div>
                        <div class="budget-cell is-pink">
                            <div class="b-label">{{ __('Revised CTC – Eligible') }}</div>
                            <div class="b-value" id="b-revised-eligible-ctc">{{ $fmt($budget['eligible_ctc_current'] + $budget['eligible_increment']) }}</div>
                        </div>
                        <div class="budget-cell is-pink">
                            <div class="b-label">{{ __('Total Headcount') }}</div>
                            <div class="b-value">{{ $budget['total_headcount'] }}</div>
                        </div>
                        <div class="budget-cell is-pink">
                            <div class="b-label">{{ __('Headcount – Not Eligible') }}</div>
                            <div class="b-value">{{ $budget['ineligible_headcount'] }}</div>
                        </div>
                        <div class="budget-cell is-pink">
                            <div class="b-label">{{ __('CTC – Not Eligible') }}</div>
                            <div class="b-value">{{ $fmt($budget['ineligible_ctc']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Rating') }}</th>
                            <th class="text-end">{{ __('Old CTC') }}</th>
                            <th class="text-center">{{ __('Inc %') }}</th>
                            <th class="text-end">{{ __('Inc Amount') }}</th>
                            <th class="text-end">{{ __('New CTC') }}</th>
                            <th class="text-end">{{ __('Net Monthly') }}</th>
                            <th class="text-end">{{ __('Diff/Month') }}</th>
                            <th>{{ __('Effective') }}</th>
                            @if(($viewerRole ?? 'admin') !== 'employee')
                            <th>{{ __('Status') }}</th>
                            @endif
                            @if(($viewerRole ?? 'admin') !== 'employee')
                            <th>{{ __('Purpose') }}</th>
                            <th>{{ __('Actions') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($increments as $inc)
                        <tr data-inc-id="{{ $inc->id }}" data-old-ctc="{{ $inc->old_ctc }}">
                            <td><strong>{{ $inc->employee->name ?? '—' }}</strong></td>
                            <td><span class="badge bg-primary">{{ $inc->rating->final_rating ?? '—' }}/5</span></td>
                            <td class="text-end">{{ number_format($inc->old_ctc) }}</td>
                            <td class="text-center inc-pct-cell">
                                @if(!$inc->synced_to_payroll)
                                    <span class="inc-pct-wrap" data-url="{{ route('growth-review.increments.update', $inc->id) }}" data-pct="{{ $inc->increment_pct }}" data-status="{{ $inc->status }}" data-effective="{{ $inc->effective_date->format('Y-m-d') }}">
                                        <strong class="text-success inc-pct-val">{{ $inc->increment_pct }}%</strong>
                                        <i class="ti ti-pencil inc-pct-edit-icon"></i>
                                    </span>
                                @else
                                    <strong class="text-success">{{ $inc->increment_pct }}%</strong>
                                @endif
                            </td>
                            <td class="text-end inc-amt-cell">
                                @if(!$inc->synced_to_payroll)
                                    <span class="inc-pct-wrap inc-amt-wrap" data-url="{{ route('growth-review.increments.update', $inc->id) }}" data-amt="{{ $inc->increment_amount }}" data-status="{{ $inc->status }}" data-effective="{{ $inc->effective_date->format('Y-m-d') }}">
                                        <strong class="text-success inc-amt-val">+{{ number_format($inc->increment_amount) }}</strong>
                                        <i class="ti ti-pencil inc-pct-edit-icon"></i>
                                    </span>
                                @else
                                    <strong class="text-success">+{{ number_format($inc->increment_amount) }}</strong>
                                @endif
                            </td>
                            <td class="text-end inc-new-ctc-cell"><strong>{{ number_format($inc->new_ctc) }}</strong></td>
                            @php
                                $sal = $salaryData[$inc->employee_id] ?? null;
                                $basicPct = $sal->basic_percentage ?? 50;
                                // Approximate net monthly: CTC/12 minus PF(12% of basic) minus ESIC(0.75% of basic if applicable)
                                $oldGrossMonthly = round($inc->old_ctc / 12);
                                $newGrossMonthly = round($inc->new_ctc / 12);
                                $oldBasicMonthly = round($oldGrossMonthly * $basicPct / 100);
                                $newBasicMonthly = round($newGrossMonthly * $basicPct / 100);
                                $oldPf = ($sal && $sal->is_pf_enabled) ? min(round($oldBasicMonthly * 0.12), 1800) : 0;
                                $newPf = ($sal && $sal->is_pf_enabled) ? min(round($newBasicMonthly * 0.12), 1800) : 0;
                                $oldEsic = ($sal && $sal->is_esic_enabled && $oldGrossMonthly <= 21000) ? round($oldBasicMonthly * 0.0075) : 0;
                                $newEsic = ($sal && $sal->is_esic_enabled && $newGrossMonthly <= 21000) ? round($newBasicMonthly * 0.0075) : 0;
                                $oldNet = $oldGrossMonthly - $oldPf - $oldEsic;
                                $newNet = $newGrossMonthly - $newPf - $newEsic;
                                $diffNet = $newNet - $oldNet;
                            @endphp
                            <td class="text-end"><strong>{{ number_format($newNet) }}</strong><br><small class="text-muted">was {{ number_format($oldNet) }}</small></td>
                            <td class="text-end"><strong class="text-success">+{{ number_format($diffNet) }}</strong></td>
                            <td>{{ $inc->effective_date->format('d M Y') }}</td>
                            @if(($viewerRole ?? 'admin') !== 'employee')
                            <td>
                                @if($inc->status === 'proposed' && !$inc->synced_to_payroll)
                                    <span class="inc-status inc-proposed">{{ __('Proposed Change') }}</span>
                                    <div class="mt-1">
                                        <span class="inc-pct-wrap inc-status-amt-wrap" data-url="{{ route('growth-review.increments.update', $inc->id) }}" data-amt="{{ $inc->increment_amount }}" data-status="{{ $inc->status }}" data-effective="{{ $inc->effective_date->format('Y-m-d') }}">
                                            <small class="text-muted">{{ __('Amt:') }}</small>
                                            <strong class="text-success inc-amt-val" style="font-size:.82rem;">{{ number_format($inc->increment_amount) }}</strong>
                                            <i class="ti ti-pencil inc-pct-edit-icon"></i>
                                        </span>
                                    </div>
                                @elseif($inc->status === 'manager_proposed')
                                    @if(($viewerRole ?? 'admin') !== 'employee')
                                        <span class="inc-status inc-manager_proposed"><i class="ti ti-send me-1"></i>{{ __('Manager Proposed') }}</span>
                                        @if($inc->proposer)
                                            <div class="inc-proposer"><i class="ti ti-user me-1"></i>{{ $inc->proposer->name }} · {{ $inc->proposed_at?->diffForHumans() }}</div>
                                        @endif
                                    @else
                                        <span class="inc-status inc-proposed">{{ __('Under Review') }}</span>
                                    @endif
                                @else
                                    <span class="inc-status inc-{{ $inc->status }}">{{ ucfirst($inc->status) }}</span>
                                    @if($inc->synced_to_payroll)<br><small class="text-success"><i class="ti ti-check"></i> Synced</small>@endif
                                @endif
                            </td>
                            @endif
                            @if(($viewerRole ?? 'admin') !== 'employee')
                            <td class="inc-purpose-cell">
                                @if(!$inc->synced_to_payroll)
                                    <span class="inc-pct-wrap inc-purpose-wrap" data-url="{{ route('growth-review.increments.update', $inc->id) }}" data-purpose="{{ $inc->remarks ?? '' }}" data-status="{{ $inc->status }}" data-effective="{{ $inc->effective_date->format('Y-m-d') }}" data-pct="{{ $inc->increment_pct }}">
                                        <span class="inc-purpose-val" style="font-size:.82rem;color:#475569;">{{ $inc->remarks ?: '—' }}</span>
                                        <i class="ti ti-pencil inc-pct-edit-icon"></i>
                                    </span>
                                @else
                                    <span style="font-size:.82rem;color:#475569;">{{ $inc->remarks ?: '—' }}</span>
                                @endif
                            </td>
                            @endif
                            @if(($viewerRole ?? 'admin') !== 'employee')
                            <td>
                                <div class="d-flex gap-1">
                                    @php $vr = $viewerRole ?? 'admin'; @endphp

                                    {{-- Management/Admin: approve/reject manager proposals --}}
                                    @if(in_array($vr, ['management', 'admin']) && $inc->status === 'manager_proposed')
                                    <form method="POST" action="{{ route('growth-review.increments.approve', $inc->id) }}" class="d-inline">@csrf
                                        <input type="hidden" name="action" value="approved">
                                        <button class="btn btn-sm btn-success" title="Approve & Sync"><i class="ti ti-check"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('growth-review.increments.approve', $inc->id) }}" class="d-inline">@csrf
                                        <input type="hidden" name="action" value="rejected">
                                        <button class="btn btn-sm btn-danger" title="Reject"><i class="ti ti-x"></i></button>
                                    </form>
                                    @endif

                                    {{-- Admin: approve/reject system-proposed --}}
                                    @if($vr === 'admin' && $inc->status === 'proposed')
                                    <form method="POST" action="{{ route('growth-review.increments.approve', $inc->id) }}" class="d-inline">@csrf
                                        <input type="hidden" name="action" value="approved">
                                        <button class="btn btn-sm btn-success" title="Approve & Sync"><i class="ti ti-check"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('growth-review.increments.approve', $inc->id) }}" class="d-inline">@csrf
                                        <input type="hidden" name="action" value="rejected">
                                        <button class="btn btn-sm btn-danger" title="Reject"><i class="ti ti-x"></i></button>
                                    </form>
                                    @endif

                                    {{-- Manager: propose button (send to management) --}}
                                    @if($vr === 'manager' && in_array($inc->status, ['proposed', 'rejected']))
                                    <form method="POST" action="{{ route('growth-review.increments.propose', $inc->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Send this increment proposal to Management for approval?') }}')">@csrf
                                        <button class="btn btn-sm btn-primary" title="Send to Management"><i class="ti ti-send me-1"></i>{{ __('Propose') }}</button>
                                    </form>
                                    @endif

                                    @if($vr === 'manager' && $inc->status === 'manager_proposed')
                                    <span class="text-muted" style="font-size:.78rem;"><i class="ti ti-clock"></i> {{ __('Awaiting approval') }}</span>
                                    @endif

                                    {{-- Increment Letter --}}
                                    @if(in_array($inc->status, ['approved', 'applied']))
                                    <a href="{{ route('growth-review.increments.letter', $inc->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Download Increment Letter') }}"><i class="ti ti-file-text me-1"></i>{{ __('Letter') }}</a>
                                    @endif
                                </div>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="12" class="text-center text-muted">{{ __('No increments generated yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if($increments->isNotEmpty())
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end"><strong>{{ __('Total') }}</strong></td>
                            <td class="text-end text-success inc-total-amt"><strong>+{{ number_format((float) $increments->sum('increment_amount'), 2) }}</strong></td>
                            <td colspan="7"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Generate Modal --}}
    <div class="modal fade" id="generateModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="POST" action="{{ route('growth-review.increments.generate') }}">@csrf
            <input type="hidden" name="cycle_id" value="{{ $cycleId }}">
            <div class="modal-header"><h5 class="modal-title"><i class="ti ti-calculator me-2"></i>{{ __('Generate Increment Proposals') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('Effective Date') }} <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <label class="form-label">{{ __('Grade-wise Increment Slabs (%)') }}</label>
                <div class="table-responsive">
                    <table class="table table-sm" id="slabTable">
                        <thead><tr><th>{{ __('Grade') }}</th><th>{{ __('Increment %') }}</th></tr></thead>
                        <tbody>
                            @foreach(['A+'=>15,'A'=>12,'B+'=>10,'B'=>8,'C+'=>5,'C'=>3,'D'=>0] as $g=>$p)
                            <tr><td><strong>{{ $g }}</strong></td><td><input type="number" class="form-control form-control-sm slab-pct" data-grade="{{ $g }}" value="{{ $p }}" min="0" max="100" step="0.5" style="width:100px;"></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="slabs" id="slabsJson">
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary" onclick="buildSlabs()"><i class="ti ti-check me-1"></i>{{ __('Generate') }}</button></div>
        </form>
    </div></div></div>

    {{-- Propose Increment Modal (Manager) --}}
    @if(in_array($viewerRole ?? 'admin', ['manager', 'management']))
    <div class="modal fade" id="proposeModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:14px;border:none;">
        <form method="POST" action="{{ route('growth-review.increments.store-proposal') }}">@csrf
            <input type="hidden" name="cycle_id" value="{{ $cycleId }}">
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:14px 14px 0 0;">
                <h5 class="modal-title"><i class="ti ti-plus me-2"></i>{{ __('Propose Increment') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('Employee') }} <span class="text-danger">*</span></label>
                    <select name="employee_id" id="proposeEmpSelect" class="form-control" required>
                        <option value="">{{ __('Select Employee') }}</option>
                        @foreach($teamEmployees ?? [] as $te)
                            <option value="{{ $te->id }}" data-ctc="{{ $te->current_ctc ?? 0 }}">{{ $te->name }} ({{ $te->employee_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3 p-3" style="background:#f8fafc;border-radius:10px;" id="proposeCtcInfo" hidden>
                    <small class="text-muted">{{ __('Current CTC') }}</small>
                    <strong id="proposeCurrentCtc" class="d-block"></strong>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">{{ __('Increment Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="increment_amount" id="proposeAmt" class="form-control" min="1" step="1" required placeholder="e.g. 10000">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('Effective Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">{{ __('Purpose / Remarks') }}</label>
                    <textarea name="remarks" class="form-control" rows="2" maxlength="500" placeholder="{{ __('Reason for this increment…') }}"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border:none;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>{{ __('Send to Management') }}</button>
            </div>
        </form>
    </div></div></div>
    @endif
@endsection

@push('script-page')
<script>
function buildSlabs() {
    var slabs = [];
    document.querySelectorAll('.slab-pct').forEach(function(inp) {
        slabs.push({ grade: inp.dataset.grade, pct: parseFloat(inp.value) || 0 });
    });
    document.getElementById('slabsJson').value = JSON.stringify(slabs);
}

// Inline Inc % and Amount editing
(function(){
    var CSRF = '{{ csrf_token() }}';
    var activeWrap = null;

    function fmt(n){ return Number(n).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

    function inlineEdit(wrap, mode){
        // mode: 'pct', 'amt', 'purpose'
        if (wrap.querySelector('.inc-pct-input')) return;

        var isPurpose = mode === 'purpose';
        var isAmt = mode === 'amt';
        var oldVal = isPurpose ? (wrap.dataset.purpose || '') : (parseFloat(isAmt ? wrap.dataset.amt : wrap.dataset.pct) || 0);
        var valEl = wrap.querySelector(isPurpose ? '.inc-purpose-val' : (isAmt ? '.inc-amt-val' : '.inc-pct-val'));
        var iconEl = wrap.querySelector('.inc-pct-edit-icon');
        valEl.style.display = 'none';
        if (iconEl) iconEl.style.display = 'none';

        var input = document.createElement('input');
        input.className = 'inc-pct-input';
        if (isPurpose) {
            input.type = 'text'; input.maxLength = 500; input.placeholder = '{{ __("Enter purpose…") }}';
            input.style.width = '160px'; input.style.textAlign = 'left'; input.style.fontWeight = '400'; input.style.color = '#475569';
        } else if (isAmt) {
            input.type = 'number'; input.min = 0; input.step = 1; input.style.width = '90px';
        } else {
            input.type = 'number'; input.min = 0; input.max = 100; input.step = 0.5;
        }
        input.value = oldVal;
        wrap.prepend(input);
        input.focus();
        input.select();
        activeWrap = wrap;

        function save(){
            var newVal = isPurpose ? input.value.trim() : parseFloat(input.value);
            if (!isPurpose) {
                if (isNaN(newVal) || newVal < 0) { cancel(); return; }
                if (!isAmt && newVal > 100) { cancel(); return; }
            }
            if (newVal === oldVal) { cancel(); return; }

            input.classList.add('inc-pct-saving');
            var tr = wrap.closest('tr');
            var oldCtc = parseFloat(tr.dataset.oldCtc) || 0;
            var url = wrap.dataset.url;
            var effective = wrap.dataset.effective;
            var status = wrap.dataset.status;

            var body = {
                effective_date: effective,
                status: status,
                remarks: isPurpose ? newVal : null,
                _method: 'PUT',
            };
            if (isPurpose) {
                body.increment_pct = parseFloat(wrap.dataset.pct) || 0;
            } else if (isAmt) {
                body.increment_amount = newVal;
            } else {
                body.increment_pct = newVal;
            }

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            }).then(function(r){ return r.json(); }).then(function(j){
                if (!j || !j.ok) { alert((j && j.error) || 'Failed'); cancel(); return; }

                if (isPurpose) {
                    wrap.dataset.purpose = newVal;
                    valEl.textContent = newVal || '—';
                } else {
                    var pct = j.pct, incAmt = j.amount, newCtc = j.new_ctc;

                    var pctWrap = tr.querySelector('.inc-pct-wrap:not(.inc-amt-wrap):not(.inc-purpose-wrap)');
                    if (pctWrap) {
                        pctWrap.dataset.pct = pct;
                        var pctVal = pctWrap.querySelector('.inc-pct-val');
                        if (pctVal) pctVal.textContent = pct + '%';
                    }

                    // Update all amount wraps (inc-amt-wrap + inc-status-amt-wrap)
                    tr.querySelectorAll('.inc-amt-wrap, .inc-status-amt-wrap').forEach(function(aw){
                        aw.dataset.amt = incAmt;
                        var av = aw.querySelector('.inc-amt-val');
                        if (av) av.textContent = (aw.classList.contains('inc-status-amt-wrap') ? '' : '+') + fmt(incAmt);
                    });

                    tr.querySelector('.inc-new-ctc-cell').innerHTML = '<strong>' + fmt(newCtc) + '</strong>';
                }

                input.classList.remove('inc-pct-saving');
                input.classList.add('inc-pct-saved');
                setTimeout(function(){ cleanup(); }, 400);
                recalcTotal();
            }).catch(function(){
                input.classList.remove('inc-pct-saving');
                alert('Failed to save.');
                cancel();
            });
        }

        function cancel(){ cleanup(); }
        function cleanup(){
            input.remove();
            valEl.style.display = '';
            if (iconEl) iconEl.style.display = '';
            activeWrap = null;
        }

        input.addEventListener('keydown', function(e){
            if (e.key === 'Enter') { e.preventDefault(); save(); }
            if (e.key === 'Escape') { cancel(); }
        });
        input.addEventListener('blur', function(){
            setTimeout(function(){ if (activeWrap === wrap) save(); }, 150);
        });
    }

    document.addEventListener('click', function(e){
        var wrap = e.target.closest('.inc-pct-wrap');
        if (!wrap) return;
        var mode = wrap.classList.contains('inc-purpose-wrap') ? 'purpose'
            : (wrap.classList.contains('inc-amt-wrap') || wrap.classList.contains('inc-status-amt-wrap')) ? 'amt'
            : 'pct';
        inlineEdit(wrap, mode);
    });

    function recalcTotal(){
        var total = 0;
        document.querySelectorAll('.inc-amt-wrap').forEach(function(w){
            total += parseFloat(w.dataset.amt) || 0;
        });
        var el = document.querySelector('.inc-total-amt');
        if (el) el.innerHTML = '<strong>+' + fmt(total) + '</strong>';
    }
})();

// ── Budget summary: live preview when user types % or amount ──────
(function(){
    var box = document.getElementById('budget-revised');
    if (!box) return;
    var pctIn   = document.getElementById('b-pct-input');
    var amtIn   = document.getElementById('b-amt-input');
    var revTot  = document.getElementById('b-revised-total-ctc');
    var revElig = document.getElementById('b-revised-eligible-ctc');

    var eligibleCtc   = parseFloat(box.dataset.eligibleCtc)   || 0;
    var ineligibleCtc = parseFloat(box.dataset.ineligibleCtc) || 0;
    var totalCtc      = eligibleCtc + ineligibleCtc;

    function fmtINR(n){
        return '₹' + Number(n).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function recalc(source){
        var inc;
        if (source === 'pct') {
            var pct = parseFloat(pctIn.value) || 0;
            inc = eligibleCtc * pct / 100;
            amtIn.value = (Math.round(inc * 100) / 100).toFixed(2);
        } else {
            inc = parseFloat(amtIn.value) || 0;
            var pct2 = eligibleCtc > 0 ? (inc / eligibleCtc * 100) : 0;
            pctIn.value = (Math.round(pct2 * 100) / 100).toString();
        }
        revElig.textContent = fmtINR(eligibleCtc + inc);
        revTot.textContent  = fmtINR(totalCtc + inc);
    }

    pctIn.addEventListener('input', function(){ recalc('pct'); });
    amtIn.addEventListener('input', function(){ recalc('amt'); });

    // ── Goal Seek: scale all rows in DB to match the target amount ──
    var btn = document.getElementById('btn-goal-seek');
    if (btn) {
        btn.addEventListener('click', function(){
            var target = parseFloat(amtIn.value);
            if (isNaN(target) || target < 0) {
                alert('{{ __("Enter a valid target amount first.") }}');
                amtIn.focus();
                return;
            }
            var msg = '{{ __("Goal Seek will scale every editable increment in this scope so the total equals") }} ' +
                      fmtINR(target) + '.\n\n{{ __("Already-synced rows will be skipped. Continue?") }}';
            if (!confirm(msg)) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="ti ti-loader me-1"></i>{{ __("Scaling…") }}';

            var deptIdRaw = box.dataset.departmentId;
            fetch(box.dataset.goalSeekUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': box.dataset.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    cycle_id: parseInt(box.dataset.cycleId, 10),
                    department_id: deptIdRaw ? parseInt(deptIdRaw, 10) : null,
                    target_amount: target,
                }),
            })
            .then(function(r){ return r.json().then(function(j){ return {status: r.status, body: j}; }); })
            .then(function(res){
                if (!res.body || !res.body.ok) {
                    alert((res.body && res.body.error) || 'Goal Seek failed.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-target me-1"></i>{{ __("Goal Seek") }}';
                    return;
                }
                var msg = res.body.updated + ' editable row(s) updated.';
                if (res.body.fixed_count > 0) {
                    msg += '\n' + res.body.fixed_count + ' locked/synced row(s) kept fixed at ' + fmtINR(res.body.fixed_total) + '.';
                    msg += '\nDistributed: ' + fmtINR(res.body.distributable) + ' across editable rows by rating.';
                }
                msg += '\nFinal total: ' + fmtINR(res.body.achieved_total);
                var diff = Math.abs(res.body.achieved_total - res.body.requested_target);
                if (diff > 1) msg += '\n(Rounding drift: ' + fmtINR(diff) + ')';
                alert(msg);
                window.location.reload();
            })
            .catch(function(){
                alert('Goal Seek request failed.');
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-target me-1"></i>{{ __("Goal Seek") }}';
            });
        });
    }
})();

// Propose modal — show current CTC on employee select
(function(){
    var sel = document.getElementById('proposeEmpSelect');
    if (!sel) return;
    var info = document.getElementById('proposeCtcInfo');
    var ctcEl = document.getElementById('proposeCurrentCtc');
    sel.addEventListener('change', function(){
        var opt = sel.options[sel.selectedIndex];
        var ctc = parseFloat(opt.dataset.ctc) || 0;
        if (ctc > 0) {
            ctcEl.textContent = Number(ctc).toLocaleString('en-IN');
            info.hidden = false;
        } else {
            info.hidden = true;
        }
    });
})();
</script>
@endpush
