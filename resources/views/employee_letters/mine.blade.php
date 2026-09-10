@extends('layouts.admin')
@section('page-title') {{ __('My Letters') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Letters') }}</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Letters issued to you') }}</h5>
            <small class="text-muted">{{ __('Offer, appointment, confirmation and increment letters from HR.') }}</small>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                        <tr>
                            <td>{{ $letter->issued_at?->format('d M Y') }}</td>
                            <td>{{ $letter->typeLabel() }}</td>
                            <td>{{ $letter->subject }}</td>
                            <td class="text-end">
                                <a href="{{ route('employee-letters.show', $letter->id) }}" class="btn btn-sm btn-light border">{{ __('View') }}</a>
                                <a href="{{ route('employee-letters.pdf', $letter->id) }}" class="btn btn-sm btn-primary">{{ __('PDF') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No letters have been issued to you yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($letters, 'hasPages') && $letters->hasPages())
            <div class="card-body pt-0">{{ $letters->links() }}</div>
        @endif
    </div>
@endsection
