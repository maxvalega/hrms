@extends('layouts.admin')

@section('page-title')
    {{ __('Employee Set Salary') }} — {{ $employee->name }}
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
</style>
@endpush

@section('content')
    <div class="row">

        {{-- ═══════════════════════════════════════════════════
             SALARY STRUCTURE BREAKDOWN (from Payroll Module)
             ═══════════════════════════════════════════════════ --}}
        @if(!empty($salaryBreakdown) && !isset($salaryBreakdown['error']))
        @php
            $bd = $salaryBreakdown;
            $totals = $bd['totals'] ?? [];
            $comps = $bd['earnings'] ?? [];
            $ctcAnnual = $bd['ctc_annual'] ?? 0;

            // Get component amounts from earnings array
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

            // Deductions
            $deductionsMap = [];
            foreach (($bd['deductions'] ?? []) as $d) {
                $key = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $d['name'] ?? ''));
                $deductionsMap[$key] = (float)($d['amount'] ?? 0);
            }
            // Also check statutory deductions
            foreach (($bd['statutory']['deductions'] ?? []) as $sd) {
                $key = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $sd['name'] ?? ''));
                if (($sd['amount'] ?? 0) > 0) $deductionsMap[$key] = (float)$sd['amount'];
            }
            $pfEmpA = $deductionsMap['PF_EMPLOYEE'] ?? 0;
            $esicEmpA = $deductionsMap['ESIC_EMPLOYEE'] ?? 0;

            // Employer contributions
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

            {{-- Statutory Breakdown - Center Row --}}
            <div class="row g-3 mt-1">
                {{-- Employee PF --}}
                <div class="col-lg col-6">
                    <div class="ss-sum-box" style="background:#fef3c7;color:#92400e;padding:12px 14px;">
                        <div class="ss-sum-label"><i class="ti ti-shield-check" style="margin-right:3px;"></i>{{ __('Employee PF') }}</div>
                        <div class="ss-sum-value" style="font-size:1.1rem;">{{ $pfEmpA > 0 ? \Auth::user()->priceFormat($pfEmpA) : '—' }}</div>
                        <div class="ss-sum-sub">{{ $pfEmpA > 0 ? \Auth::user()->priceFormat($pfEmpA / 12) . '/mo' : '12% of Basic' }}</div>
                    </div>
                </div>
                {{-- Employee ESIC --}}
                <div class="col-lg col-6">
                    <div class="ss-sum-box" style="background:#dbeafe;color:#1e40af;padding:12px 14px;">
                        <div class="ss-sum-label"><i class="ti ti-heart-handshake" style="margin-right:3px;"></i>{{ __('Employee ESIC') }}</div>
                        <div class="ss-sum-value" style="font-size:1.1rem;">{{ $esicEmpA > 0 ? \Auth::user()->priceFormat($esicEmpA) : '—' }}</div>
                        <div class="ss-sum-sub">{{ $esicEmpA > 0 ? \Auth::user()->priceFormat($esicEmpA / 12) . '/mo' : '0.75% of Gross' }}</div>
                    </div>
                </div>
                {{-- Employer PF --}}
                <div class="col-lg col-6">
                    <div class="ss-sum-box" style="background:#fef9c3;color:#854d0e;padding:12px 14px;">
                        <div class="ss-sum-label"><i class="ti ti-building-bank" style="margin-right:3px;"></i>{{ __('Employer PF') }}</div>
                        <div class="ss-sum-value" style="font-size:1.1rem;">{{ $pfErA > 0 ? \Auth::user()->priceFormat($pfErA) : '—' }}</div>
                        <div class="ss-sum-sub">{{ $pfErA > 0 ? \Auth::user()->priceFormat($pfErA / 12) . '/mo' : '12% of Basic' }}</div>
                    </div>
                </div>
                {{-- Employer ESIC --}}
                <div class="col-lg col-6">
                    <div class="ss-sum-box" style="background:#e0e7ff;color:#3730a3;padding:12px 14px;">
                        <div class="ss-sum-label"><i class="ti ti-building-bank" style="margin-right:3px;"></i>{{ __('Employer ESIC') }}</div>
                        <div class="ss-sum-value" style="font-size:1.1rem;">{{ $esicErA > 0 ? \Auth::user()->priceFormat($esicErA) : '—' }}</div>
                        <div class="ss-sum-sub">{{ $esicErA > 0 ? \Auth::user()->priceFormat($esicErA / 12) . '/mo' : '3.25% of Gross' }}</div>
                    </div>
                </div>
                {{-- Gratuity --}}
                <div class="col-lg col-6">
                    <div class="ss-sum-box" style="background:#ede9fe;color:#5b21b6;padding:12px 14px;">
                        <div class="ss-sum-label"><i class="ti ti-coin" style="margin-right:3px;"></i>{{ __('Gratuity') }}</div>
                        <div class="ss-sum-value" style="font-size:1.1rem;">{{ $gratuityA > 0 ? \Auth::user()->priceFormat($gratuityA) : '—' }}</div>
                        <div class="ss-sum-sub">{{ $gratuityA > 0 ? \Auth::user()->priceFormat($gratuityA / 12) . '/mo' : '4.81% of Basic' }}</div>
                    </div>
                </div>
                {{-- Total Employer Cost --}}
                <div class="col-lg col-6">
                    <div class="ss-sum-box" style="background:#faf5ff;color:#7c3aed;padding:12px 14px;border:2px solid #c4b5fd;">
                        <div class="ss-sum-label"><i class="ti ti-receipt-2" style="margin-right:3px;"></i>{{ __('Employer Cost') }}</div>
                        <div class="ss-sum-value" style="font-size:1.1rem;">{{ \Auth::user()->priceFormat($totalEmployerA) }}</div>
                        <div class="ss-sum-sub">{{ \Auth::user()->priceFormat($totalEmployerA / 12) }}/{{ __('mo') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Full Salary Structure Table --}}
        <div class="col-lg-12 mb-3">
            <div class="card mb-0" style="border-radius:12px;overflow:hidden;">
                <div class="card-header d-flex align-items-center justify-content-between" style="background:#f8fafc;">
                    <h5 class="mb-0">
                        <i class="ti ti-receipt" style="margin-right:6px;color:#1e3a8a;"></i>
                        {{ __('Salary Structure') }} — {{ $employee->name }}
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
                                    <th style="width:22%;" class="text-end">{{ __('Annual (₹)') }}</th>
                                    <th style="width:23%;" class="text-end">{{ __('Monthly (₹)') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- CTC --}}
                                <tr class="ss-section-row"><td colspan="4"><i class="ti ti-coin" style="margin-right:6px;"></i>{{ __('Cost to Company') }}</td></tr>
                                <tr>
                                    <td class="ss-component">{{ __('Annual CTC') }}</td>
                                    <td><span class="ss-badge ss-badge-fix">{{ __('Input') }}</span></td>
                                    <td class="ss-amount">{{ number_format($ctcAnnual) }}</td>
                                    <td class="ss-amount-m">{{ number_format($ctcAnnual / 12) }}</td>
                                </tr>

                                {{-- EARNINGS --}}
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

                                {{-- EMPLOYEE DEDUCTIONS --}}
                                <tr class="ss-section-row"><td colspan="4"><i class="ti ti-minus" style="margin-right:6px;"></i>{{ __('Employee Deductions') }}</td></tr>

                                <tr>
                                    <td class="ss-component">{{ __('Employee Provident Fund (PF)') }}<div class="ss-note">12% {{ __('of Basic, cap ₹1,800/mo') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">12%</span></td>
                                    <td class="ss-amount ss-deduction">{{ $pfEmpA > 0 ? '-'.number_format($pfEmpA) : '—' }}</td>
                                    <td class="ss-amount-m ss-deduction">{{ $pfEmpA > 0 ? '-'.number_format($pfEmpA / 12) : '—' }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('ESIC Employee') }}<div class="ss-note">0.75% {{ __('of Gross (if ≤ ₹21K/mo)') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">0.75%</span></td>
                                    <td class="ss-amount ss-deduction">{{ $esicEmpA > 0 ? '-'.number_format($esicEmpA) : '—' }}</td>
                                    <td class="ss-amount-m ss-deduction">{{ $esicEmpA > 0 ? '-'.number_format($esicEmpA / 12) : '—' }}</td>
                                </tr>

                                <tr class="ss-total-row">
                                    <td colspan="2" style="font-size:.9rem;">{{ __('Total Deductions') }}</td>
                                    <td class="ss-amount" style="color:#dc2626;font-size:.95rem;">-{{ number_format($totalDeductA) }}</td>
                                    <td class="ss-amount-m" style="color:#dc2626;font-size:.9rem;">-{{ number_format($totalDeductA / 12) }}</td>
                                </tr>

                                {{-- NET PAY --}}
                                <tr class="ss-grand-total">
                                    <td colspan="2" style="font-size:1rem;padding:16px;"><i class="ti ti-wallet" style="margin-right:6px;"></i>{{ __('Net In-Hand Salary') }}</td>
                                    <td class="ss-amount" style="padding:16px;">{{ number_format($netA) }}</td>
                                    <td class="ss-amount-m" style="padding:16px;">{{ number_format($netA / 12) }}</td>
                                </tr>

                                {{-- EMPLOYER CONTRIBUTIONS --}}
                                <tr class="ss-section-row"><td colspan="4"><i class="ti ti-building-bank" style="margin-right:6px;"></i>{{ __('Employer Contributions') }}</td></tr>

                                <tr>
                                    <td class="ss-component">{{ __('Employer Provident Fund') }}<div class="ss-note">12% {{ __('of Basic, cap ₹1,800/mo') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">12%</span></td>
                                    <td class="ss-amount ss-employer">{{ $pfErA > 0 ? number_format($pfErA) : '—' }}</td>
                                    <td class="ss-amount-m ss-employer">{{ $pfErA > 0 ? number_format($pfErA / 12) : '—' }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('ESIC Employer') }}<div class="ss-note">3.25% {{ __('of Gross (if ≤ ₹21K/mo)') }}</div></td>
                                    <td><span class="ss-badge ss-badge-auto">3.25%</span></td>
                                    <td class="ss-amount ss-employer">{{ $esicErA > 0 ? number_format($esicErA) : '—' }}</td>
                                    <td class="ss-amount-m ss-employer">{{ $esicErA > 0 ? number_format($esicErA / 12) : '—' }}</td>
                                </tr>

                                <tr>
                                    <td class="ss-component">{{ __('Gratuity') }}<div class="ss-note">4.81% {{ __('of Basic (Basic/26×15/12)') }}</div></td>
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

        {{-- PF/ESIC Rules & Employee Config are hidden from employee view --}}

        @elseif(isset($salaryBreakdown['error']))
        {{-- Salary not configured in payroll module --}}
        <div class="col-12 mb-3">
            <div class="card" style="border-radius:12px;border:2px dashed #fde68a;background:#fffbeb;">
                <div class="card-body text-center py-4">
                    <i class="ti ti-alert-triangle" style="font-size:2.5rem;color:#d97706;"></i>
                    <h5 class="mt-2 mb-1" style="color:#92400e;">{{ __('Salary Structure Not Configured') }}</h5>
                    <p class="text-muted mb-3">{{ __('CTC has not been set for this employee in the Payroll module. Configure it to see the full salary breakdown.') }}</p>
                    @if(in_array(\Auth::user()->type, ['company', 'super admin']))
                        <a href="{{ route('payroll.employee.salary') }}" class="btn btn-sm btn-warning">
                            <i class="ti ti-settings"></i> {{ __('Configure Employee Salary') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @else
        <div class="col-12 mb-3">
            <div class="card" style="border-radius:12px;border:2px dashed #e2e8f0;">
                <div class="card-body text-center py-4">
                    <i class="ti ti-calculator" style="font-size:2.5rem;color:#94a3b8;"></i>
                    <h5 class="mt-2 mb-1" style="color:#64748b;">{{ __('No Salary Structure Found') }}</h5>
                    <p class="text-muted mb-3">{{ __('Set up CTC and salary structure for this employee to view the full breakdown.') }}</p>
                    @if(in_array(\Auth::user()->type, ['company', 'super admin']))
                        <a href="{{ route('payroll.employee.salary') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus"></i> {{ __('Setup Employee Salary') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

    {{-- ── Salary Increment History ─────────────────────────── --}}
    <div class="col-12 mb-3">
        <div class="card" style="border-radius:12px;">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="ti ti-history me-2"></i>{{ __('Salary Increment History') }}</h6>
                <span class="badge bg-light text-dark border">{{ $salaryHistory->count() }} {{ __('records') }}</span>
            </div>
            <div class="card-body p-0">
                @if($salaryHistory->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-clock" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">{{ __('No increment history found for this employee.') }}</p>
                    </div>
                @else
                <div class="table-responsive">
                    <table class="ss-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Effective Date') }}</th>
                                <th style="text-align:right;">{{ __('Previous CTC') }}</th>
                                <th style="text-align:right;">{{ __('New CTC') }}</th>
                                <th style="text-align:right;">{{ __('Increment Amount') }}</th>
                                <th style="text-align:right;">{{ __('Increment %') }}</th>
                                <th>{{ __('Remarks') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salaryHistory as $i => $h)
                            <tr>
                                <td><span class="ss-badge ss-badge-pct">{{ $salaryHistory->count() - $i }}</span></td>
                                <td><strong>{{ \Carbon\Carbon::parse($h->effective_date)->format('d M Y') }}</strong></td>
                                <td style="text-align:right;color:#64748b;">₹{{ number_format($h->old_ctc, 0) }}</td>
                                <td style="text-align:right;font-weight:700;color:#0f172a;">₹{{ number_format($h->new_ctc, 0) }}</td>
                                <td style="text-align:right;color:#16a34a;font-weight:700;">+₹{{ number_format($h->increment_amount, 0) }}</td>
                                <td style="text-align:right;">
                                    <span class="ss-badge ss-badge-pct">+{{ number_format($h->increment_percentage, 2) }}%</span>
                                </td>
                                <td><small class="text-muted">{{ $h->remarks ?? '—' }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    </div>
@endsection
