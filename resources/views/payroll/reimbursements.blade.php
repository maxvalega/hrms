@extends('layouts.admin')
@section('page-title')
    {{ __('Payroll - Reimbursements') }}
@endsection

@section('content')
    @include('payroll._nav')

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('New Claim') }}</h5></div>
                <div class="card-body">
                    {{-- OLD: <form method="POST" action="{{ route('payroll.reimbursements.store') }}"> --}}
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
                            {{-- OLD dropdown (salary components with category=reimbursement):
                            <select name="component_id" class="form-control" required>
                                @foreach($components as $component)
                                    <option value="{{ $component->id }}">{{ $component->name }}</option>
                                @endforeach
                            </select>
                            --}}
                            <input type="text" name="component_name" class="form-control" value="{{ old('component_name') }}" placeholder="{{ __('e.g. Travel, Food, Medical') }}" required>
                            @error('component_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2"><label class="form-label">{{ __('Claim Month') }}</label><input type="month" name="claim_month" class="form-control" required></div>
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
                <div class="card-header"><h5 class="mb-0">{{ __('Claims') }}</h5></div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Employee ID') }}</th>
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
                                        <td>{{ $claim->employee_id }}</td>
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
                                        <td>{{ ucfirst($claim->status) }}</td>
                                        <td>
                                            @if($claim->status === 'pending')
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
