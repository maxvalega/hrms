@extends('layouts.admin')
@section('page-title')
    {{ __('My Reimbursements') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Reimbursements') }}</li>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Apply Reimbursement') }}</h5>
                </div>
                <div class="card-body">
                    @if (empty($monthWindow) || !is_array($monthWindow))
                        <div class="alert alert-danger">{{ __('Reimbursement apply form is not available. Please contact admin.') }}</div>
                    @else
                    <form method="POST" action="{{ route('payroll.my-reimbursements.store') }}" enctype="multipart/form-data" id="reimbursement-apply-form">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">{{ __('Employee') }}</label>
                            <input type="text" class="form-control" value="{{ $employee->name }}" disabled>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('Component') }} <span class="text-danger">*</span></label>
                            <input type="text" name="component_name" class="form-control"
                                value="{{ old('component_name') }}"
                                placeholder="{{ __('e.g. Travel, Food, Medical') }}" required>
                            @error('component_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label">{{ __('Current Month') }} <span class="text-danger">*</span></label>
                            <select name="current_month" id="current_month" class="form-control" required>
                                @foreach (($monthWindow['current_month_options'] ?? []) as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('current_month', $monthWindow['default_current_month'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @if (!empty($monthWindow['in_grace']) && empty($monthWindow['previous_locked']))
                                <small class="text-muted">{{ __('Until the 5th, previous month stays open unless HR locks it.') }}</small>
                            @elseif (!empty($monthWindow['previous_locked']))
                                <small class="text-muted">{{ __('Previous month is locked by HR. Use Previous Month below to claim it.') }}</small>
                            @endif
                        </div>

                        @if (!empty($monthWindow['show_previous_dropdown']))
                            <div class="mb-2" id="previous_month_wrap">
                                <label class="form-label">{{ __('Previous Month') }}</label>
                                <select name="previous_month" id="previous_month" class="form-control">
                                    <option value="">{{ __('— Apply for current month —') }}</option>
                                    <option value="{{ $monthWindow['previous_month'] }}"
                                        {{ old('previous_month') === ($monthWindow['previous_month'] ?? null) ? 'selected' : '' }}>
                                        {{ date('M Y', strtotime(($monthWindow['previous_month'] ?? date('Y-m', strtotime('first day of last month'))) . '-01')) }}
                                        @if (!empty($monthWindow['previous_locked']))
                                            ({{ __('Locked — still allowed') }})
                                        @endif
                                    </option>
                                </select>
                                <small class="text-muted">{{ __('Select previous month only if you want to claim for that month.') }}</small>
                            </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control"
                                value="{{ old('amount') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('Remarks') }}</label>
                            <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Receipt Attachment') }}</label>
                            <input type="file" name="attachment" class="form-control"
                                accept=".jpg,.jpeg,.pdf,image/jpeg,application/pdf">
                            <small class="text-muted">{{ __('JPEG or PDF only. Max size 5 MB.') }}</small>
                            @error('attachment')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <button class="btn btn-primary w-100">{{ __('Submit Claim') }}</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('My Claims') }}</h5>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Component') }}</th>
                                    <th>{{ __('Month') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Receipt') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Remarks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($claims as $claim)
                                    <tr>
                                        <td>{{ $claim->component_name ?: ('#' . $claim->component_id) }}</td>
                                        <td>{{ $claim->claim_month }}</td>
                                        <td>{{ number_format((float) $claim->amount, 2) }}</td>
                                        <td>
                                            @if (!empty($claim->attachment))
                                                <a href="{{ asset('storage/' . $claim->attachment) }}" target="_blank"
                                                    rel="noopener" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($claim->status === 'approved')
                                                <span class="badge bg-success">{{ __('Approved') }}</span>
                                            @elseif ($claim->status === 'rejected')
                                                <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $claim->remarks ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('No claims yet. Submit your first reimbursement.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
