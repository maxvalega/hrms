@extends('layouts.admin')
@section('page-title') {{ __('Letter Formats') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employee-letters.index') }}">{{ __('Employee Letters') }}</a></li>
    <li class="breadcrumb-item">{{ __('Letter Formats') }}</li>
@endsection

@push('css-page')
<style>
    .lf-card{border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:18px;height:100%;}
    .lf-card h6{margin:0 0 6px;font-weight:700;}
    .lf-hint{font-size:.8rem;color:#64748b;}
    .lf-file{font-size:.75rem;color:#0f172a;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:8px 10px;margin-top:10px;}
</style>
@endpush

@section('action-button')
    <a href="{{ route('employee-letters.index') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-send me-1"></i>{{ __('Issue a letter') }}
    </a>
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="alert alert-info border-0 mb-3">
        <strong>{{ __('Where to upload the format') }}</strong>
        <div class="small mt-1">{{ __('This is the place. Save the HTML format (with placeholders) and optionally upload your Word/PDF letterhead file for each letter type. After that, go to Issue Letters to generate and email it.') }}</div>
    </div>

    <div class="row g-3">
        @foreach(\App\Models\LetterFormat::TYPES as $type => $label)
            @php $fmt = $formats[$type] ?? null; @endphp
            <div class="col-md-6">
                <div class="lf-card">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <h6>{{ __($label) }}</h6>
                            <div class="lf-hint">
                                @if($type === 'offer') {{ __('Generated and emailed to the candidate / employee.') }}
                                @elseif($type === 'appointment') {{ __('Issued after joining.') }}
                                @elseif($type === 'confirmation') {{ __('Issued after probation.') }}
                                @else {{ __('Issued after a salary revision.') }}
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('employee-letters.formats.edit', $type) }}" class="btn btn-sm btn-primary">
                            {{ __('Edit / Upload') }}
                        </a>
                    </div>
                    @if($fmt && $fmt->file_name)
                        <div class="lf-file"><i class="ti ti-paperclip me-1"></i>{{ $fmt->file_name }}</div>
                    @else
                        <div class="lf-file text-muted">{{ __('No Word/PDF file uploaded yet. A default HTML format is ready.') }}</div>
                    @endif
                    <div class="small text-muted mt-2">{{ __('Last updated') }}: {{ $fmt?->updated_at?->format('d M Y H:i') ?: '—' }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
