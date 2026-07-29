@extends('layouts.admin')
@section('page-title')
    {{ __('Payroll - Reimbursements') }}
@endsection

@section('content')
    @include('payroll._nav')

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

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Lock Month') }}</h5>
                    <small class="text-muted">{{ __('After approving claims, lock a month so the employee apply form moves to the calendar month. Previous month stays available via Previous Month.') }}</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payroll.reimbursements.lock') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Month to lock') }}</label>
                            <select name="lock_month" class="form-control" required>
                                @foreach ($monthOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $value === date('Y-m', strtotime('first day of last month')) ? 'selected' : '' }}>
                                        {{ $label }}
                                        @if (in_array($value, $lockedMonths, true))
                                            — {{ __('Locked') }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button class="btn btn-warning">{{ __('Lock Month') }}</button>
                        </div>
                    </form>

                    @if (!empty($lockedMonths))
                        <div class="mt-3">
                            <label class="form-label">{{ __('Locked months') }}</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($lockedMonths as $locked)
                                    <form method="POST" action="{{ route('payroll.reimbursements.unlock') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="lock_month" value="{{ $locked }}">
                                        <button class="btn btn-sm btn-outline-secondary"
                                            onclick="return confirm('{{ __('Unlock :month?', ['month' => $locked]) }}')">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $locked)->format('M Y') }}
                                            · {{ __('Unlock') }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('New Claim') }}</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payroll.reimbursements.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">{{ __('Employee') }}</label>
                            <select name="employee_id" class="form-control" required>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('Component') }}</label>
                            <input type="text" name="component_name" class="form-control" value="{{ old('component_name') }}" placeholder="{{ __('e.g. Travel, Food, Medical') }}" required>
                            @error('component_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2"><label class="form-label">{{ __('Claim Month') }}</label><input type="month" name="claim_month" class="form-control" value="{{ old('claim_month', date('Y-m')) }}" required></div>
                        <div class="mb-2"><label class="form-label">{{ __('Amount') }}</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                        <div class="mb-2"><label class="form-label">{{ __('Remarks') }}</label><input type="text" name="remarks" class="form-control"></div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Receipt Attachment') }}</label>
                            <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.pdf,image/jpeg,application/pdf">
                            <small class="text-muted">{{ __('JPEG or PDF only. Max size 5 MB.') }}</small>
                            @error('attachment')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <button class="btn btn-primary w-100">{{ __('Submit Claim') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('Claims') }}</h5>
                    <small class="text-muted">{{ __('You can approve only when the employee has no reporting manager. Otherwise the reporting manager must approve.') }}</small>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Employee') }}</th>
                                    <th>{{ __('Component') }}</th>
                                    <th>{{ __('Month') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Receipt') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($claims as $claim)
                                    <tr>
                                        <td>{{ ($employeeNames[$claim->employee_id] ?? null) ?: ('#' . $claim->employee_id) }}</td>
                                        <td>{{ $claim->component_name ?: ('#' . $claim->component_id) }}</td>
                                        <td>{{ $claim->claim_month }}</td>
                                        <td>{{ $claim->amount }}</td>
                                        <td>
                                            @if(!empty($claim->attachment))
                                                <a href="{{ asset('storage/' . $claim->attachment) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                                    {{ __('View') }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ ucfirst($claim->status) }}
                                            @if (!empty($awaitingManager[$claim->id]))
                                                <div><small class="text-muted">{{ __('Awaiting reporting manager') }}</small></div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($claim->status === 'pending' && !empty($hrCanApprove[$claim->id]))
                                                <div class="d-flex gap-1">
                                                    <form method="POST" action="{{ route('payroll.reimbursements.status', $claim->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="approved">
                                                        <button class="btn btn-sm btn-success">{{ __('Approve') }}</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('payroll.reimbursements.status', $claim->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button class="btn btn-sm btn-danger">{{ __('Reject') }}</button>
                                                    </form>
                                                </div>
                                            @elseif($claim->status === 'pending')
                                                <span class="text-muted small">{{ __('View only') }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7">{{ __('No claims found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
