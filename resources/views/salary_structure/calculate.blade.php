@extends('layouts.admin')
@section('page-title')
    {{ __('Salary Structure Calculator') }}
@endsection

@push('css-page')
<style>
    /* Prevent horizontal clipping of content */
    .dash-content { overflow-x: hidden; }
    .dash-content > .row,
    .dash-content > section > .row { margin-left: 0 !important; margin-right: 0 !important; }
    .ss-page-wrap { padding-left: 12px; padding-right: 12px; }
    .ss-page-wrap .row { margin-left: -8px; margin-right: -8px; }
    .ss-page-wrap [class*="col-"] { padding-left: 8px; padding-right: 8px; }
    .ss-table-wrap { overflow-x: auto; }

    .ss-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,.04); overflow: visible; }
    .ss-card .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 14px 20px; }
    .ss-card .card-header h6 { font-weight: 700; font-size: .9375rem; margin: 0; color: #0f172a; }
    .ss-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .ss-table th { background: #f1f5f9; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; padding: 10px 16px; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
    .ss-table td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .ss-table tr:hover { background: #fafbfc; }
    .ss-table .ss-component { font-weight: 600; color: #1e293b; }
    .ss-table .ss-note { font-size: .8rem; color: #94a3b8; font-weight: 400; }
    .ss-table .ss-amount { font-weight: 700; color: #0f172a; text-align: right; font-variant-numeric: tabular-nums; }
    .ss-table .ss-amount-monthly { font-weight: 600; color: #64748b; text-align: right; font-variant-numeric: tabular-nums; }
    .ss-table .ss-deduction { color: #dc2626 !important; }
    .ss-table .ss-employer { color: #7c3aed !important; }

    .ss-section-row td { background: #f8fafc; font-weight: 700 !important; color: #1e3a8a !important; font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; border-bottom: 2px solid #e2e8f0; border-top: 2px solid #e2e8f0; }
    .ss-total-row td { background: #f1f5f9; font-weight: 800 !important; border-top: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1; }
    .ss-grand-total td { background: linear-gradient(135deg, #0c1d4d, #1e3a8a); color: #fff !important; font-weight: 800; font-size: .9375rem; }
    .ss-grand-total .ss-amount, .ss-grand-total .ss-amount-monthly { color: #4ade80 !important; font-size: 1rem; }

    .ss-rule-card { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 16px; margin-top: 16px; }
    .ss-rule-card h6 { font-weight: 700; color: #92400e; font-size: .85rem; margin-bottom: 10px; }
    .ss-rule-card ul { margin: 0; padding-left: 18px; }
    .ss-rule-card li { font-size: .8125rem; color: #78716c; line-height: 1.6; margin-bottom: 4px; }
    .ss-rule-card li strong { color: #451a03; }

    .ss-input-card { border-radius: 14px; background: #ffffff; border: 1px solid #e5e7eb; }
    .ss-input-card .card-body { padding: 24px; }
    .ss-input-card label { font-size: .8125rem; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
    .ss-input-card .form-control { border-radius: 8px; border: 1px solid #d1d5db; background: #f9fafb; color: #111827; font-weight: 600; }
    .ss-input-card .form-control::placeholder { color: #9ca3af; }
    .ss-input-card .form-control:focus { background: #ffffff; border-color: #0d9488; color: #111827; box-shadow: 0 0 0 .15rem rgba(13,148,136,.15); }
    .ss-input-card .form-check-label { color: #1f2937; font-size: .8125rem; font-weight: 500; }
    .ss-input-card .form-check-input:checked { background-color: #0d9488; border-color: #0d9488; }

    .ss-summary-box { border-radius: 10px; padding: 14px 18px; text-align: center; }
    .ss-summary-box .ss-sum-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; opacity: .7; }
    .ss-summary-box .ss-sum-value { font-size: 1.25rem; font-weight: 900; margin-top: 4px; letter-spacing: -.02em; }
    .ss-summary-box .ss-sum-sub { font-size: .7rem; opacity: .5; }

    .ss-badge-fixed { display: inline-block; font-size: .65rem; font-weight: 700; padding: 2px 8px; border-radius: 100px; background: #dbeafe; color: #1e40af; }
    .ss-badge-percent { display: inline-block; font-size: .65rem; font-weight: 700; padding: 2px 8px; border-radius: 100px; background: #dcfce7; color: #166534; }
    .ss-badge-formula { display: inline-block; font-size: .65rem; font-weight: 700; padding: 2px 8px; border-radius: 100px; background: #fef3c7; color: #92400e; }
    .ss-badge-factory { display: inline-block; font-size: .65rem; font-weight: 600; padding: 2px 8px; border-radius: 100px; background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
    <div class="container-fluid ss-page-wrap">
    <div class="row">
        {{-- INPUT FORM --}}
        <div class="col-12 mb-3">
            <div class="card ss-input-card">
                <div class="card-body">
                    <form method="post" action="{{ route('salary.structure.calculate') }}" id="ss-form">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label>{{ __('Annual CTC (₹)') }}</label>
                                <input type="number" step="1" min="0" name="ctc" id="ss-ctc" class="form-control" value="{{ old('ctc', request('ctc', 1200000)) }}" placeholder="e.g. 1200000" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label>{{ __('Basic %') }}</label>
                                <input type="number" step="0.01" min="1" max="100" name="basic_percentage" id="ss-basic-pct" class="form-control" value="{{ old('basic_percentage', request('basic_percentage', 50)) }}" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>{{ __('Structure') }}</label>
                                <select class="form-control" name="structure_id" id="ss-structure" required>
                                    @foreach($structures as $structure)
                                        <option value="{{ $structure->id }}" {{ (int)old('structure_id', request('structure_id', $structures->first()->id ?? 0)) === (int)$structure->id ? 'selected' : '' }}>{{ $structure->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="is_pf_enabled" id="ss-pf" value="1" {{ old('is_pf_enabled', request('is_pf_enabled', 1)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ss-pf">{{ __('PF Enabled') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_esic_enabled" id="ss-esic" value="1" {{ old('is_esic_enabled', request('is_esic_enabled', 1)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ss-esic">{{ __('ESIC Enabled') }}</label>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <button class="btn w-100 fw-bold" type="submit" style="background:#0d9488;color:#fff;border:none;">
                                    <i class="ti ti-calculator"></i> {{ __('Calculate') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('salary.structure.index') }}" class="btn btn-sm" style="background:#f3f4f6;color:#1f2937;border:1px solid #e5e7eb;">
                            <i class="ti ti-settings"></i> {{ __('Manage Components') }}
                        </a>
                        <a href="{{ route('payroll.employee.salary') }}" class="btn btn-sm" style="background:#f3f4f6;color:#1f2937;border:1px solid #e5e7eb;">
                            <i class="ti ti-users"></i> {{ __('Employee Salaries') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($result))
        @php
            $a = $result['annual'];
            $m = $result['monthly'];
            $meta = $result['meta'];
            $compsA = $a['components'] ?? [];
            $compsM = $m['components'] ?? [];

            // Calculate values from the breakdown
            $basicA = (float)($compsA['BASIC'] ?? $a['basic'] ?? 0);
            $hraA   = (float)($compsA['HRA'] ?? $a['hra'] ?? 0);
            $convA  = (float)($compsA['CONVEYANCE'] ?? $a['conveyance'] ?? 0);
            $medA   = (float)($compsA['MEDICAL'] ?? $a['medical'] ?? 0);
            $specA  = (float)($compsA['SPECIAL_ALLOWANCE'] ?? $a['special'] ?? 0);
            $grossA = (float)($compsA['GROSS'] ?? $a['gross'] ?? 0);

            // Deductions
            $pfEmpA = (float)($compsA['PF_EMPLOYEE'] ?? $a['pf'] ?? 0);
            $esicEmpA = (float)($compsA['ESIC_EMPLOYEE'] ?? $a['esic_employee'] ?? 0);

            // Employer
            $pfErA = (float)($compsA['PF_EMPLOYER'] ?? 0);
            // Calculate employer PF if not in components (it's same as employee PF in most cases)
            if($pfErA == 0 && $pfEmpA > 0) {
                $pfErA = $pfEmpA; // Mirror employee PF
            }
            $esicErA = (float)($compsA['ESIC_EMPLOYER'] ?? $a['esic_employer'] ?? 0);
            $gratuityA = (float)($compsA['GRATUITY'] ?? $a['gratuity'] ?? 0);

            $totalDeductionsA = $pfEmpA + $esicEmpA;
            $totalEmployerA = $pfErA + $esicErA + $gratuityA;
            $netA = $grossA - $totalDeductionsA;
            $ctcCheck = $grossA + $totalEmployerA;

            // Monthly values
            $basicM = round($basicA / 12, 2);
            $hraM = round($hraA / 12, 2);
            $convM = round($convA / 12, 2);
            $medM = round($medA / 12, 2);
            $specM = round($specA / 12, 2);
            $grossM = round($grossA / 12, 2);
            $pfEmpM = round($pfEmpA / 12, 2);
            $esicEmpM = round($esicEmpA / 12, 2);
            $pfErM = round($pfErA / 12, 2);
            $esicErM = round($esicErA / 12, 2);
            $gratuityM = round($gratuityA / 12, 2);
            $totalDeductionsM = round($totalDeductionsA / 12, 2);
            $totalEmployerM = round($totalEmployerA / 12, 2);
            $netM = round($netA / 12, 2);
        @endphp

        {{-- SUMMARY BOXES --}}
        <div class="col-12 mb-3">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="ss-summary-box" style="background:#eff6ff;color:#1e3a8a;">
                        <div class="ss-sum-label">{{ __('Annual CTC') }}</div>
                        <div class="ss-sum-value">₹{{ number_format($meta['ctc_annual']) }}</div>
                        <div class="ss-sum-sub">₹{{ number_format($meta['ctc_monthly']) }}/{{ __('mo') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="ss-summary-box" style="background:#dcfce7;color:#166534;">
                        <div class="ss-sum-label">{{ __('Gross Salary') }}</div>
                        <div class="ss-sum-value">₹{{ number_format($grossA) }}</div>
                        <div class="ss-sum-sub">₹{{ number_format($grossM) }}/{{ __('mo') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="ss-summary-box" style="background:#fee2e2;color:#991b1b;">
                        <div class="ss-sum-label">{{ __('Total Deductions') }}</div>
                        <div class="ss-sum-value">-₹{{ number_format($totalDeductionsA) }}</div>
                        <div class="ss-sum-sub">-₹{{ number_format($totalDeductionsM) }}/{{ __('mo') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="ss-summary-box" style="background:#f0fdf4;color:#15803d;border:2px solid #bbf7d0;">
                        <div class="ss-sum-label">{{ __('Net In-Hand') }}</div>
                        <div class="ss-sum-value">₹{{ number_format($netA) }}</div>
                        <div class="ss-sum-sub">₹{{ number_format($netM) }}/{{ __('mo') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SALARY STRUCTURE TABLE --}}
        <div class="col-lg-8">
            <div class="card ss-card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6>{{ __('Salary Structure Breakdown') }}</h6>
                    <span class="badge bg-primary">CTC: ₹{{ number_format($meta['ctc_annual']) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="ss-table">
                        <thead>
                            <tr>
                                <th style="width:35%;">{{ __('Component') }}</th>
                                <th style="width:20%;">{{ __('Calculation') }}</th>
                                <th style="width:22%;" class="text-end">{{ __('Annual (₹)') }}</th>
                                <th style="width:23%;" class="text-end">{{ __('Monthly (₹)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- SECTION: CTC --}}
                            <tr class="ss-section-row"><td colspan="4"><i class="ti ti-coin" style="margin-right:6px;"></i>{{ __('CTC (Cost to Company)') }}</td></tr>
                            <tr>
                                <td class="ss-component">{{ __('Annual CTC') }}</td>
                                <td><span class="ss-badge-fixed">{{ __('Input') }}</span></td>
                                <td class="ss-amount">{{ number_format($meta['ctc_annual']) }}</td>
                                <td class="ss-amount-monthly">{{ number_format($meta['ctc_monthly']) }}</td>
                            </tr>

                            {{-- SECTION: EARNINGS --}}
                            <tr class="ss-section-row"><td colspan="4"><i class="ti ti-plus" style="margin-right:6px;"></i>{{ __('Earnings') }}</td></tr>

                            <tr>
                                <td class="ss-component">
                                    {{ __('Basic Salary') }}
                                    <div class="ss-note">{{ $meta['basic_percentage'] }}% {{ __('of CTC') }}</div>
                                </td>
                                <td><span class="ss-badge-percent">{{ $meta['basic_percentage'] }}% {{ __('of CTC') }}</span></td>
                                <td class="ss-amount">{{ number_format($basicA) }}</td>
                                <td class="ss-amount-monthly">{{ number_format($basicM) }}</td>
                            </tr>

                            <tr>
                                <td class="ss-component">
                                    {{ __('House Rent Allowance') }}
                                    <div class="ss-note">50% {{ __('of Basic') }}</div>
                                </td>
                                <td><span class="ss-badge-percent">50% {{ __('of Basic') }}</span></td>
                                <td class="ss-amount">{{ number_format($hraA) }}</td>
                                <td class="ss-amount-monthly">{{ number_format($hraM) }}</td>
                            </tr>

                            <tr>
                                <td class="ss-component">{{ __('Conveyance Allowance') }}</td>
                                <td><span class="ss-badge-fixed">{{ __('Fixed') }}</span></td>
                                <td class="ss-amount">{{ number_format($convA) }}</td>
                                <td class="ss-amount-monthly">{{ number_format($convM) }}</td>
                            </tr>

                            <tr>
                                <td class="ss-component">{{ __('Medical Allowance') }}</td>
                                <td><span class="ss-badge-fixed">{{ __('Fixed') }}</span></td>
                                <td class="ss-amount">{{ number_format($medA) }}</td>
                                <td class="ss-amount-monthly">{{ number_format($medM) }}</td>
                            </tr>

                            <tr>
                                <td class="ss-component">
                                    {{ __('Special Allowance') }}
                                    <div class="ss-note">{{ __('Balancing figure') }}</div>
                                </td>
                                <td><span class="ss-badge-formula">{{ __('Auto') }}</span></td>
                                <td class="ss-amount">{{ number_format($specA) }}</td>
                                <td class="ss-amount-monthly">{{ number_format($specM) }}</td>
                            </tr>

                            {{-- GROSS TOTAL --}}
                            <tr class="ss-total-row">
                                <td colspan="2" style="font-size:.9rem;">{{ __('Gross Salary') }}</td>
                                <td class="ss-amount" style="font-size:.95rem;color:#16a34a;">{{ number_format($grossA) }}</td>
                                <td class="ss-amount-monthly" style="font-size:.9rem;color:#16a34a;">{{ number_format($grossM) }}</td>
                            </tr>

                            {{-- SECTION: EMPLOYEE DEDUCTIONS --}}
                            <tr class="ss-section-row"><td colspan="4"><i class="ti ti-minus" style="margin-right:6px;"></i>{{ __('Employee Deductions') }}</td></tr>

                            <tr>
                                <td class="ss-component">
                                    {{ __('Employee Provident Fund') }}
                                    <div class="ss-note">12% {{ __('of Basic, capped ₹1,800/mo') }}</div>
                                </td>
                                <td><span class="ss-badge-formula">12% {{ __('Basic') }}</span></td>
                                <td class="ss-amount ss-deduction">{{ $pfEmpA > 0 ? '-'.number_format($pfEmpA) : '—' }}</td>
                                <td class="ss-amount-monthly ss-deduction">{{ $pfEmpM > 0 ? '-'.number_format($pfEmpM) : '—' }}</td>
                            </tr>

                            <tr>
                                <td class="ss-component">
                                    {{ __('ESIC Employee') }}
                                    <div class="ss-note">0.75% {{ __('of Basic (if Gross ≤ ₹21,000/mo)') }}</div>
                                </td>
                                <td><span class="ss-badge-formula">0.75% {{ __('Basic') }}</span></td>
                                <td class="ss-amount ss-deduction">{{ $esicEmpA > 0 ? '-'.number_format($esicEmpA) : '—' }}</td>
                                <td class="ss-amount-monthly ss-deduction">{{ $esicEmpM > 0 ? '-'.number_format($esicEmpM) : '—' }}</td>
                            </tr>

                            <tr class="ss-total-row">
                                <td colspan="2" style="font-size:.9rem;">{{ __('Total Deductions') }}</td>
                                <td class="ss-amount" style="font-size:.95rem;color:#dc2626;">-{{ number_format($totalDeductionsA) }}</td>
                                <td class="ss-amount-monthly" style="font-size:.9rem;color:#dc2626;">-{{ number_format($totalDeductionsM) }}</td>
                            </tr>

                            {{-- NET PAY --}}
                            <tr class="ss-grand-total">
                                <td colspan="2" style="font-size:1rem;padding:16px;">
                                    <i class="ti ti-wallet" style="margin-right:6px;"></i>{{ __('Net In-Hand Salary') }}
                                </td>
                                <td class="ss-amount" style="padding:16px;">₹{{ number_format($netA) }}</td>
                                <td class="ss-amount-monthly" style="padding:16px;">₹{{ number_format($netM) }}</td>
                            </tr>

                            {{-- SECTION: EMPLOYER CONTRIBUTIONS --}}
                            <tr class="ss-section-row"><td colspan="4"><i class="ti ti-building-bank" style="margin-right:6px;"></i>{{ __('Employer Contributions (Above CTC)') }}</td></tr>

                            <tr>
                                <td class="ss-component">
                                    {{ __('Employer Provident Fund') }}
                                    <div class="ss-note">12% {{ __('of Basic, capped ₹1,800/mo') }}</div>
                                </td>
                                <td><span class="ss-badge-formula">12% {{ __('Basic') }}</span></td>
                                <td class="ss-amount ss-employer">{{ $pfErA > 0 ? number_format($pfErA) : '—' }}</td>
                                <td class="ss-amount-monthly ss-employer">{{ $pfErM > 0 ? number_format($pfErM) : '—' }}</td>
                            </tr>

                            <tr>
                                <td class="ss-component">
                                    {{ __('ESIC Employer') }}
                                    <div class="ss-note">3.25% {{ __('of Basic (if Gross ≤ ₹21,000/mo)') }}</div>
                                </td>
                                <td><span class="ss-badge-formula">3.25% {{ __('Basic') }}</span></td>
                                <td class="ss-amount ss-employer">{{ $esicErA > 0 ? number_format($esicErA) : '—' }}</td>
                                <td class="ss-amount-monthly ss-employer">{{ $esicErM > 0 ? number_format($esicErM) : '—' }}</td>
                            </tr>

                            <tr>
                                <td class="ss-component">
                                    {{ __('Gratuity') }}
                                    <div class="ss-note">4.81% {{ __('of Basic (Basic/26×15/12)') }}</div>
                                </td>
                                <td><span class="ss-badge-formula">4.81% {{ __('Basic') }}</span></td>
                                <td class="ss-amount ss-employer">{{ $gratuityA > 0 ? number_format($gratuityA) : '—' }}</td>
                                <td class="ss-amount-monthly ss-employer">{{ $gratuityM > 0 ? number_format($gratuityM) : '—' }}</td>
                            </tr>

                            <tr class="ss-total-row">
                                <td colspan="2" style="font-size:.9rem;">{{ __('Total Employer Cost') }}</td>
                                <td class="ss-amount" style="font-size:.95rem;color:#7c3aed;">{{ number_format($totalEmployerA) }}</td>
                                <td class="ss-amount-monthly" style="font-size:.9rem;color:#7c3aed;">{{ number_format($totalEmployerM) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT SIDE: RULES & NOTES --}}
        <div class="col-lg-4">
            {{-- PF Rules --}}
            <div class="card ss-card mb-3">
                <div class="card-header" style="background:#fef3c7;border-color:#fde68a;">
                    <h6 style="color:#92400e;"><i class="ti ti-alert-triangle" style="margin-right:6px;"></i>{{ __('PF Rules') }}</h6>
                </div>
                <div class="card-body" style="font-size:.8125rem;color:#475569;line-height:1.7;">
                    <div class="mb-3">
                        <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">{{ __('Rule 1: Mandatory PF') }}</div>
                        {{ __('If Basic < ₹15,000/month (₹1,80,000 annual), PF is mandatory at 12% for both Employer & Employee.') }}
                    </div>
                    <div class="mb-3">
                        <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">{{ __('Rule 2: PF Cap') }}</div>
                        {{ __('PF contribution is capped at ₹1,800/month (₹21,600/year) — i.e., 12% of ₹15,000 max.') }}
                    </div>
                    <div class="mb-3">
                        <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">{{ __('Rule 3: Opt Out') }}</div>
                        {{ __('Employee can opt out of PF only if Basic ≥ ₹15,000/month AND was not under PF from start of employment.') }}
                    </div>

                    @php
                        $basicMonthly = $basicA / 12;
                        $pfMandatory = $basicMonthly < 15000;
                    @endphp
                    <div class="mt-3 p-2 rounded" style="background:{{ $pfMandatory ? '#dcfce7' : '#fef3c7' }};border:1px solid {{ $pfMandatory ? '#86efac' : '#fde68a' }};">
                        <strong style="color:{{ $pfMandatory ? '#166534' : '#92400e' }};">
                            @if($pfMandatory)
                                <i class="ti ti-check"></i> {{ __('PF is MANDATORY for this CTC') }}
                            @else
                                <i class="ti ti-info-circle"></i> {{ __('PF is Optional (Basic ≥ ₹15,000/mo)') }}
                            @endif
                        </strong>
                        <div style="font-size:.75rem;color:#64748b;margin-top:2px;">
                            {{ __('Basic/month:') }} ₹{{ number_format($basicMonthly) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- ESIC Rules --}}
            <div class="card ss-card mb-3">
                <div class="card-header" style="background:#dbeafe;border-color:#93c5fd;">
                    <h6 style="color:#1e40af;"><i class="ti ti-shield-check" style="margin-right:6px;"></i>{{ __('ESIC Rules') }}</h6>
                </div>
                <div class="card-body" style="font-size:.8125rem;color:#475569;line-height:1.7;">
                    <div class="mb-2">
                        {{ __('ESIC applies if Gross Salary ≤ ₹21,000/month (₹2,52,000/year).') }}
                    </div>
                    <table class="table table-sm mb-2" style="font-size:.8rem;">
                        <tr><td>{{ __('Employee Contribution') }}</td><td class="fw-bold text-end">0.75% {{ __('of Basic') }}</td></tr>
                        <tr><td>{{ __('Employer Contribution') }}</td><td class="fw-bold text-end">3.25% {{ __('of Basic') }}</td></tr>
                    </table>
                    @php $esicApplicable = ($grossA / 12) <= 21000; @endphp
                    <div class="p-2 rounded" style="background:{{ $esicApplicable ? '#dcfce7' : '#f1f5f9' }};border:1px solid {{ $esicApplicable ? '#86efac' : '#e2e8f0' }};">
                        <strong style="color:{{ $esicApplicable ? '#166534' : '#64748b' }};">
                            @if($esicApplicable)
                                <i class="ti ti-check"></i> {{ __('ESIC Applicable') }}
                            @else
                                <i class="ti ti-x"></i> {{ __('ESIC Not Applicable (Gross > ₹21K/mo)') }}
                            @endif
                        </strong>
                    </div>
                </div>
            </div>

            {{-- Gratuity Rules --}}
            <div class="card ss-card mb-3">
                <div class="card-header" style="background:#ede9fe;border-color:#c4b5fd;">
                    <h6 style="color:#5b21b6;"><i class="ti ti-heart-handshake" style="margin-right:6px;"></i>{{ __('Gratuity') }}</h6>
                </div>
                <div class="card-body" style="font-size:.8125rem;color:#475569;line-height:1.7;">
                    <div class="mb-2"><strong>{{ __('Formula:') }}</strong> Basic Salary / 26 × 15 / 12</div>
                    <div class="mb-2">{{ __('Equivalent to 4.81% of Basic Salary.') }}</div>
                    <div class="mb-2"><strong>{{ __('Years of Service') }}</strong> = {{ __('Rounded off:') }}</div>
                    <ul class="mb-0" style="padding-left:18px;">
                        <li>≥ 6 {{ __('months') }} → {{ __('round up (e.g. 4.6 yrs → 5 yrs)') }}</li>
                        <li>&lt; 6 {{ __('months') }} → {{ __('ignore') }}</li>
                    </ul>
                </div>
            </div>

            {{-- Cost Breakdown --}}
            <div class="card ss-card">
                <div class="card-header" style="background:linear-gradient(135deg,#0c1d4d,#1e3a8a);border:none;">
                    <h6 style="color:#fff;"><i class="ti ti-report-analytics" style="margin-right:6px;"></i>{{ __('Total Cost to Company') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0" style="font-size:.8125rem;">
                        <tr>
                            <td>{{ __('Gross Salary') }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($grossA) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('+ Employer PF') }}</td>
                            <td class="text-end fw-bold" style="color:#7c3aed;">₹{{ number_format($pfErA) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('+ Employer ESIC') }}</td>
                            <td class="text-end fw-bold" style="color:#7c3aed;">₹{{ number_format($esicErA) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('+ Gratuity') }}</td>
                            <td class="text-end fw-bold" style="color:#7c3aed;">₹{{ number_format($gratuityA) }}</td>
                        </tr>
                        <tr style="border-top:2px solid #1e3a8a;">
                            <td style="font-weight:800;font-size:.9rem;">{{ __('Total CTC') }}</td>
                            <td class="text-end" style="font-weight:900;font-size:1rem;color:#1e3a8a;">₹{{ number_format($ctcCheck) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        @else
        {{-- No result yet - show instructions --}}
        <div class="col-12">
            <div class="card ss-card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-calculator" style="font-size:3rem;color:#94a3b8;"></i>
                    <h5 class="mt-3 mb-2" style="color:#64748b;">{{ __('Enter CTC to Calculate Salary Structure') }}</h5>
                    <p class="text-muted mb-0" style="max-width:400px;margin:0 auto;">
                        {{ __('Enter the Annual CTC above and click Calculate to see the full salary breakdown with Basic, HRA, PF, ESIC, Gratuity and Net In-Hand salary.') }}
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
    </div>
@endsection
