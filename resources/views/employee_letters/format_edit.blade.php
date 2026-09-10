@extends('layouts.admin')
@section('page-title') {{ __('Edit format') }} — {{ $format->typeLabel() }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employee-letters.formats') }}">{{ __('Letter Formats') }}</a></li>
    <li class="breadcrumb-item">{{ $format->typeLabel() }}</li>
@endsection

@section('content')
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{ Form::open(['route' => ['employee-letters.formats.update', $format->type], 'method' => 'post', 'files' => true]) }}
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('1. Upload company format (optional)') }}</h5>
            <small class="text-muted">{{ __('Word or PDF letterhead / signed format. Keep this as the official file. Generation still uses the HTML below.') }}</small>
        </div>
        <div class="card-body">
            <input type="file" name="format_file" class="form-control" accept=".pdf,.doc,.docx">
            @if($format->file_path)
                <div class="mt-2 small">
                    {{ __('Current file') }}:
                    <a href="{{ asset('storage/'.$format->file_path) }}" target="_blank">{{ $format->file_name }}</a>
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('2. HTML format used when issuing the letter') }}</h5>
        </div>
        <div class="card-body">
            <h6 class="mb-2">{{ __('Placeholders') }}</h6>
            <div class="row text-muted small mb-3">
                @foreach(\App\Models\LetterFormat::placeholders() as $tag => $lbl)
                    <div class="col-md-4 mb-1"><span class="text-primary">{{ $tag }}</span> — {{ $lbl }}</div>
                @endforeach
            </div>
            <textarea name="content" class="summernote-simple" id="content">{!! $format->content !!}</textarea>
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('employee-letters.formats') }}" class="btn btn-light border">{{ __('Back') }}</a>
            <button class="btn btn-primary">{{ __('Save format') }}</button>
        </div>
    </div>
    {{ Form::close() }}
@endsection
