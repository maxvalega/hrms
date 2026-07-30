@extends('layouts.admin')
@section('page-title')
    {{ __('Set Salary') }} — {{ $employee->name }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ url('setsalary') }}">{{ __('Set Salary') }}</a></li>
    <li class="breadcrumb-item">{{ $employee->name }}</li>
@endsection

@push('css-page')
<style>
    .ss-table { width:100%; border-collapse:collapse; font-size:.875rem; }
    .ss-table th { background:#f1f5f9; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; padding:10px 16px; border-bottom:2px solid #e2e8f0; }
    .ss-table td { padding:10px 16px; border-bottom:1px solid #f1f5f9; }
    .ss-table tr:hover { background:#fafbfc; }
    .ss-component { font-weight:600; color:#1e293b; }
    .ss-note { font-size:.75rem; color:#94a3b8; font-weight:400; }
    .ss-amount { font-weight:700; color:#0f172a; text-align:right; font-variant-numeric:tabular-nums; }
    .ss-amount-m { font-weight:600; color:#64748b; text-align:right; font-variant-numeric:tabular-nums; }
    .ss-deduction { color:#dc2626!important; }
    .ss-employer { color:#7c3aed!important; }
    .ss-section-row td { background:#f8fafc; font-weight:700!important; color:#1e3a8a!important; font-size:.8rem; text-transform:uppercase; letter-spacing:.06em; border-bottom:2px solid #e2e8f0; border-top:2px solid #e2e8f0; }
    .ss-total-row td { background:#f1f5f9; font-weight:800!important; border-top:2px solid #cbd5e1; }
    .ss-grand-total td { background:linear-gradient(135deg,#0c1d4d,#1e3a8a); color:#fff!important; font-weight:800; }
    .ss-grand-total .ss-amount, .ss-grand-total .ss-amount-m { color:#4ade80!important; font-size:1rem; }
    .ss-badge { display:inline-block; font-size:.65rem; font-weight:700; padding:2px 8px; border-radius:100px; }
    .ss-badge-pct { background:#dcfce7; color:#166534; }
    .ss-badge-fix { background:#dbeafe; color:#1e40af; }
    .ss-badge-auto { background:#fef3c7; color:#92400e; }
    .ss-sum-box { border-radius:10px; padding:14px 18px; text-align:center; }
    .ss-sum-label { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; opacity:.7; }
    .ss-sum-value { font-size:1.25rem; font-weight:900; margin-top:4px; letter-spacing:-.02em; }
    .ss-sum-sub { font-size:.7rem; opacity:.5; }
    .ss-config-card { border-radius:14px; background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%); border:none; }
    .ss-config-card label { font-size:.8125rem; font-weight:600; color:rgba(255,255,255,.85); margin-bottom:4px; }
    .ss-config-card .form-control, .ss-config-card .form-select { border-radius:8px; border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.1); color:#fff; font-weight:600; }
    .ss-config-card .form-control::placeholder { color:rgba(255,255,255,.4); }
    .ss-config-card .form-control:focus, .ss-config-card .form-select:focus { background:rgba(255,255,255,.15); border-color:rgba(255,255,255,.5); color:#fff; box-shadow:none; }
    .ss-config-card .form-check-label { color:rgba(255,255,255,.85); font-size:.8125rem; }
    .ss-config-card .form-check-input:checked { background-color:#4ade80; border-color:#4ade80; }
    .ss-config-card .form-select option { color:#1e293b; background:#fff; }
</style>
@endpush

@section('content')
    <div class="row">

        {{-- SALARY CONFIGURATION FORM --}}
        <div class="col-12 mb-3">
            <div class="card ss-config-card">
                <div class="card-body" style="padding:24px;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.95rem;">
                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                        </div>
                        <div>
                            <h5 class="mb-0" style="color:#fff;font-weight:700;">{{ $employee->name }}</h5>
                            <small style="color:rgba(255,255,255,.6);">{{ \Auth::user()->employeeIdFormat($employee->employee_id) }}</small>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('payroll.employee.salary.save') }}">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label>{{ __('Annual CTC') }}</label>
                                <input type="number" step="1" min="0" name="ctc" class="form-control" value="{{ $salaryConfig->ctc ?? '' }}" placeholder="e.g. 1200000" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label>{{ __('Basic %') }}</label>
                                <input type="number" step="0.01" min="1" max="100" name="basic_percentage" class="form-control" value="{{ $salaryConfig->basic_percentage ?? 50 }}" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>{{ __('Structure') }}</label>
                                <select class="form-control form-select" name="structure_id" required>
                                    @foreach($structures as $structure)
                                        <option value="{{ $structure->id }}" {{ (int)($salaryConfig->structure_id ?? 0) === (int)$structure->id ? 'selected' : '' }}>{{ $structure->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="is_pf_enabled" id="pf-check" value="1" {{ !empty($salaryConfig) && $salaryConfig->is_pf_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pf-check">{{ __('PF Enabled') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_esic_enabled" id="esic-check" value="1" {{ !empty($salaryConfig) && $salaryConfig->is_esic_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label" for="esic-check">{{ __('ESIC Enabled') }}</label>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <button class="btn btn-light w-100 fw-bold" type="submit">
                                    <i class="ti ti-device-floppy"></i> {{ __('Save') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- SALARY BREAKDOWN (shown only if salary is configured) --}}
        @if(!empty($salaryBreakdown) && !isset($salaryBreakdown['error']))
        @php
            $bd = $salaryBreakdown;
            $totals = $bd['totals'] ?? [];
            $comps = $bd['earnings'] ?? [];
            $ctcAnnual = $bd['ctc_annual'] ?? 0;

            $earningsMap = [];
            foreach ($comps as $e) {
                $key = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $e['name'] ?? ''));
                $earningsMap[$key] = (float)($e['amount'] ?? 0);
            }

            $basicA = $earningsMap['BASIC'] ?? 0;
            $hraA = $earningsMap['HRA'] ?? $earningsMap['HOUSE_RENT_ALLOWANCE'] ?? 0;
            $convA = $earningsMap['CONVEYANCE'] ?? $earningsMap['CONVEYANCE_ALLOWANCE'] ?? 0;
            $medA = $earningsMap['MEDICAL'] ?? $earningsMap['MEDICAL_ALLOWANCE'] ?? 0;
            $specA = $earningsMap['SPECIAL_ALLOWANCE'] ?? 0;
            $grossA = (float)($totals['gross_annual'] ?? 0);

            $deductionsMap = [];
            foreach (($bd['deductions'] ?? []) as $d) {
                $key = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $d['name'] ?? ''));
                $deductionsMap[$key] = (float)($d['amount'] ?? 0);
            }
            foreach (($bd['statutory']['deductions'] ?? []) as $sd) {
                $key = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $sd['name'] ?? ''));
                if (($sd['amount'] ?? 0) > 0) $deductionsMap[$key] = (float)$sd['amount'];
            }
            $pfEmpA = $deductionsMap['PF_EMPLOYEE'] ?? 0;
            $esicEmpA = $deductionsMap['ESIC_EMPLOYEE'] ?? 0;

            $benefitsMap = [];
            foreach (($bd['benefits'] ?? []) as $b) {
                $key = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $b['name'] ?? ''));
                $benefitsMap[$key] = (float)($b['amount'] ?? 0);
            }
            foreach (($bd['statutory']['benefits'] ?? []) as $sb) {
                $key = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $sb['name'] ?? ''));
                if (($sb['amount'] ?? 0) > 0) $benefitsMap[$key] = (float)$sb['amount'];
            }
            $pfErA = $benefitsMap['PF_EMPLOYER'] ?? $benefitsMap['EMPLOYER_PF'] ?? 0;
            if ($pfErA == 0 && $pfEmpA > 0) $pfErA = $pfEmpA;
            $esicErA = $benefitsMap['ESIC_EMPLOYER'] ?? 0;
            $gratuityA = $benefitsMap['GRATUITY'] ?? 0;

            $totalDeductA = $pfEmpA + $esicEmpA;
            $totalEmployerA = $pfErA + $esicErA + $gratuityA;
            $netA = $grossA - $totalDeductA;
            $basicPct = $salaryConfig->basic_percentage ?? 50;
        @endphp

        {{-- Summary boxes --}}
        <div class="col-12 mb-3">
            <div class="row g-3">
                <div class="col-lg-3 col-6">
                    <div class="ss-sum-box" style="background:#eff6ff;color:#1e3a8a;">
                        <div class="ss-sum-label">{{ __('Annual CTC') }}</div>
                        <div class="ss-sum-value">{{ \Auth::user()->priceFormat($ctcAnnual) }}</div>
                        <div class="ss-sum-sub">{{ \Auth::user()->priceFormat($ctcAnnual / 12) }}/{{ __('mo') }}</div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="ss-sum-box" style="background:#dcfce7;color:#166534;">
                        <div class="ss-sum-label">{{ __('Gross Salary') }}</div>
                        <div class="ss-sum-value">{{ \Auth::user()->priceFormat($grossA) }}</div>
                        <div class="ss-sum-sub">{{ \Auth::user()->priceFormat($grossA / 12) }}/{{ __('mo') }}</div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="ss-sum-box" style="background:#fee2e2;color:#991b1b;">
                        <div class="ss-sum-label">{{ __('Total Deductions') }}</div>
                        <div class="ss-sum-value">-{{ \Auth::user()->priceFormat($totalDeductA) }}</div>
                        <div class="ss-sum-sub">-{{ \Auth::user()->priceFormat($totalDeductA / 12) }}/{{ __('mo') }}</div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="ss-sum-box" style="background:#f0fdf4;color:#15803d;border:2px solid #bbf7d0;">
                        <div class="ss-sum-label">{{ __('Net In-Hand') }}</div>
                        <div class="ss-sum-value">{{ \Auth::user()->priceFormat($netA) }}</div>
                        <div class="ss-sum-sub">{{ \Auth::user()->priceFormat($netA / 12) }}/{{ __('mo') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Salary Structure Table --}}
        <div class="col-lg-8 mb-3">
            <div class="card mb-0" style="border-radius:12px;overflow:hidden;">
                <div class="card-header d-flex align-items-center justify-content-between" style="background:#f8fafc;">
                    <h5 class="mb-0">
                        <i class="ti ti-receipt" style="margin-right:6px;color:#1e3a8a;"></i>
                        {{ __('Salary Structure') }}
                    </h5>
                    <div class="d-flex gap-2">
                        @if($salaryStructure)
                        <span class="badge bg-primary">{{ $salaryStructure->name }}</span>
                        @endif
                        <span class="badge bg-dark">{{ __('Basic') }}: {{ $basicPct }}%</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="ss-table">
                            <thead>
                                <tr>
                                    <th style="width:40%;">{{ __('Component') }}</th>
                                    <th style="width:15%;">{{ __('Type') }}</th>
                                    <th style="width:22%;" class="text-end">{{ __('Annual') }}</th>
                                    <th style="width:23%;" class="text-end">{{ __('Monthly') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="ss-section-row"><td colspan="4"><i class="ti ti-plus" style="margin-right:6px;"></i>{{ __('Earnings') }}</td></tr>

                                <tr>
                                    <td class="ss-component">{{ __('Basic Salary') }}<div class="ss-note">{{ $basicPct }}% {{ __('of CTC') }}</div></td>
                                    <td><span class="ss-badge ss-badge-pct">{{ $basicPct }}%</span></td>
                                    <td class="ss-amount">{{ number_format($basicA) }}</td>
                                    <td class="ss-amount-m">{{ number_format($basicA / 12) }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('House Rent Allowance') }}<div class="ss-note">50% {{ __('of Basic') }}</div></td>
                                    <td><span class="ss-badge ss-badge-pct">50%</span></td>
                                    <td class="ss-amount">{{ number_format($hraA) }}</td>
                                    <td class="ss-amount-m">{{ number_format($hraA / 12) }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('Conveyance Allowance') }}</td>
                                    <td><span class="ss-badge ss-badge-fix">{{ __('Fixed') }}</span></td>
                                    <td class="ss-amount">{{ number_format($convA) }}</td>
                                    <td class="ss-amount-m">{{ number_format($convA / 12) }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('Medical Allowance') }}</td>
                                    <td><span class="ss-badge ss-badge-fix">{{ __('Fixed') }}</span></td>
                                    <td class="ss-amount">{{ number_format($medA) }}</td>
                                    <td class="ss-amount-m">{{ number_format($medA / 12) }}</td>
                                </tr>

                                @if($specA > 0)
                                <tr>
                                    <td class="ss-component">{{ __('Special Allowance') }}<div class="ss-note">{{ __('Balancing figure') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">{{ __('Auto') }}</span></td>
                                    <td class="ss-amount">{{ number_format($specA) }}</td>
                                    <td class="ss-amount-m">{{ number_format($specA / 12) }}</td>
                                </tr>
                                @endif

                                <tr class="ss-total-row">
                                    <td colspan="2" style="font-size:.9rem;"><i class="ti ti-sum" style="margin-right:4px;"></i>{{ __('Gross Salary') }}</td>
                                    <td class="ss-amount" style="font-size:.95rem;color:#16a34a;">{{ number_format($grossA) }}</td>
                                    <td class="ss-amount-m" style="font-size:.9rem;color:#16a34a;">{{ number_format($grossA / 12) }}</td>
                                </tr>

                                <tr class="ss-section-row"><td colspan="4"><i class="ti ti-minus" style="margin-right:6px;"></i>{{ __('Employee Deductions') }}</td></tr>

                                <tr>
                                    <td class="ss-component">{{ __('PF (Employee)') }}<div class="ss-note">12% {{ __('of Basic, cap 1,800/mo') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">12%</span></td>
                                    <td class="ss-amount ss-deduction">{{ $pfEmpA > 0 ? '-'.number_format($pfEmpA) : '—' }}</td>
                                    <td class="ss-amount-m ss-deduction">{{ $pfEmpA > 0 ? '-'.number_format($pfEmpA / 12) : '—' }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('ESIC (Employee)') }}<div class="ss-note">0.75% {{ __('of Basic') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">0.75%</span></td>
                                    <td class="ss-amount ss-deduction">{{ $esicEmpA > 0 ? '-'.number_format($esicEmpA) : '—' }}</td>
                                    <td class="ss-amount-m ss-deduction">{{ $esicEmpA > 0 ? '-'.number_format($esicEmpA / 12) : '—' }}</td>
                                </tr>

                                <tr class="ss-total-row">
                                    <td colspan="2" style="font-size:.9rem;">{{ __('Total Deductions') }}</td>
                                    <td class="ss-amount" style="color:#dc2626;font-size:.95rem;">-{{ number_format($totalDeductA) }}</td>
                                    <td class="ss-amount-m" style="color:#dc2626;font-size:.9rem;">-{{ number_format($totalDeductA / 12) }}</td>
                                </tr>

                                <tr class="ss-grand-total">
                                    <td colspan="2" style="font-size:1rem;padding:16px;"><i class="ti ti-wallet" style="margin-right:6px;"></i>{{ __('Net In-Hand Salary') }}</td>
                                    <td class="ss-amount" style="padding:16px;">{{ number_format($netA) }}</td>
                                    <td class="ss-amount-m" style="padding:16px;">{{ number_format($netA / 12) }}</td>
                                </tr>

                                <tr class="ss-section-row"><td colspan="4"><i class="ti ti-building-bank" style="margin-right:6px;"></i>{{ __('Employer Contributions') }}</td></tr>

                                <tr>
                                    <td class="ss-component">{{ __('PF (Employer)') }}<div class="ss-note">12% {{ __('of Basic, cap 1,800/mo') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">12%</span></td>
                                    <td class="ss-amount ss-employer">{{ $pfErA > 0 ? number_format($pfErA) : '—' }}</td>
                                    <td class="ss-amount-m ss-employer">{{ $pfErA > 0 ? number_format($pfErA / 12) : '—' }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('ESIC (Employer)') }}<div class="ss-note">3.25% {{ __('of Basic') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">3.25%</span></td>
                                    <td class="ss-amount ss-employer">{{ $esicErA > 0 ? number_format($esicErA) : '—' }}</td>
                                    <td class="ss-amount-m ss-employer">{{ $esicErA > 0 ? number_format($esicErA / 12) : '—' }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('Gratuity') }}<div class="ss-note">4.81% {{ __('of Basic') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">4.81%</span></td>
                                    <td class="ss-amount ss-employer">{{ $gratuityA > 0 ? number_format($gratuityA) : '—' }}</td>
                                    <td class="ss-amount-m ss-employer">{{ $gratuityA > 0 ? number_format($gratuityA / 12) : '—' }}</td>
                                </tr>

                                <tr class="ss-total-row">
                                    <td colspan="2" style="font-size:.9rem;">{{ __('Total Employer Cost') }}</td>
                                    <td class="ss-amount" style="color:#7c3aed;font-size:.95rem;">{{ number_format($totalEmployerA) }}</td>
                                    <td class="ss-amount-m" style="color:#7c3aed;font-size:.9rem;">{{ number_format($totalEmployerA / 12) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right side: Employee Info & Rules --}}
        <div class="col-lg-4 mb-3">
            <div class="card mb-3" style="border-radius:12px;">
                <div class="card-header py-3" style="background:#fef3c7;border-bottom:1px solid #fde68a;">
                    <h6 class="mb-0" style="color:#92400e;font-weight:700;font-size:.875rem;"><i class="ti ti-alert-triangle" style="margin-right:6px;"></i>{{ __('PF Rules') }}</h6>
                </div>
                <div class="card-body" style="font-size:.8rem;color:#475569;line-height:1.65;">
                    <div class="mb-2"><strong style="color:#0f172a;">{{ __('Rule 1:') }}</strong> {{ __('Basic < 15,000/mo → PF mandatory 12%') }}</div>
                    <div class="mb-2"><strong style="color:#0f172a;">{{ __('Rule 2:') }}</strong> {{ __('PF capped at 1,800/month') }}</div>
                    @php $basicMo = $basicA / 12; @endphp
                    <div class="mt-2 p-2 rounded" style="background:{{ $basicMo < 15000 ? '#dcfce7' : '#f1f5f9' }};">
                        <strong style="color:{{ $basicMo < 15000 ? '#166534' : '#64748b' }};font-size:.75rem;">
                            {{ $basicMo < 15000 ? '✓ '.__('PF Mandatory') : 'ⓘ '.__('PF Optional (Basic ≥ 15K/mo)') }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="card mb-3" style="border-radius:12px;">
                <div class="card-header py-3" style="background:#dbeafe;border-bottom:1px solid #93c5fd;">
                    <h6 class="mb-0" style="color:#1e40af;font-weight:700;font-size:.875rem;"><i class="ti ti-shield-check" style="margin-right:6px;"></i>{{ __('ESIC Rules') }}</h6>
                </div>
                <div class="card-body" style="font-size:.8rem;color:#475569;">
                    <div class="mb-2">{{ __('Gross ≤ 21,000/mo → ESIC applies') }}</div>
                    <div class="mb-1">{{ __('Employee:') }} <strong>0.75%</strong> · {{ __('Employer:') }} <strong>3.25%</strong></div>
                    @php $esicAppl = ($grossA / 12) <= 21000; @endphp
                    <div class="mt-2 p-2 rounded" style="background:{{ $esicAppl ? '#dcfce7' : '#f1f5f9' }};">
                        <strong style="color:{{ $esicAppl ? '#166534' : '#64748b' }};font-size:.75rem;">
                            {{ $esicAppl ? '✓ '.__('ESIC Applicable') : '✕ '.__('ESIC Not Applicable') }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="card" style="border-radius:12px;">
                <div class="card-header py-3" style="background:#ede9fe;border-bottom:1px solid #c4b5fd;">
                    <h6 class="mb-0" style="color:#5b21b6;font-weight:700;font-size:.875rem;"><i class="ti ti-heart-handshake" style="margin-right:6px;"></i>{{ __('Gratuity') }}</h6>
                </div>
                <div class="card-body" style="font-size:.8rem;color:#475569;">
                    <div class="mb-1"><strong>{{ __('Formula:') }}</strong> Basic / 26 × 15 / 12 = 4.81%</div>
                </div>
            </div>
        </div>

        @else
        {{-- No salary configured yet --}}
        <div class="col-12">
            <div class="card" style="border-radius:12px;border:2px dashed #e2e8f0;">
                <div class="card-body text-center py-5">
                    <i class="ti ti-calculator" style="font-size:3rem;color:#94a3b8;"></i>
                    <h5 class="mt-3 mb-2" style="color:#64748b;">{{ __('No Salary Configured') }}</h5>
                    <p class="text-muted mb-0">{{ __('Enter CTC above and click Save to configure salary for this employee.') }}</p>
                </div>
            </div>
        </div>
        @endif

    </div>
@endsection
