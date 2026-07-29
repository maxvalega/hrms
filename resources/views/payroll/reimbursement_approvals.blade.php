@extends('layouts.admin')
@section('page-title')
    {{ __('Reimbursement Approvals') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Reimbursement Approvals') }}</li>
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

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Team Reimbursement Claims') }}</h5>
            <small class="text-muted">{{ __('Approve claims for employees who report to you') }}</small>
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
                                <td>{{ number_format((float) $claim->amount, 2) }}</td>
                                <td>
                                    @if (!empty($claim->attachment))
                                        <a href="{{ asset('storage/' . $claim->attachment) }}" target="_blank" rel="noopener"
                                            class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($claim->status === 'paid')
                                        <span class="badge bg-primary">{{ __('Paid') }}</span>
                                    @elseif ($claim->status === 'approved')
                                        <span class="badge bg-success">{{ __('Approved') }}</span>
                                    @elseif ($claim->status === 'rejected')
                                        <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ __('Pending') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($claim->status === 'pending')
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
                                    @elseif ($claim->status === 'paid')
                                        <span class="badge bg-primary px-3">{{ __('PAID') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    {{ __('No team reimbursement claims found. Claims appear here when your reportees apply.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
