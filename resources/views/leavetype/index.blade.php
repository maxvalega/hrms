@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Leave Type') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Leave Type') }}</li>
@endsection

@section('action-button')
    {{-- @can('Create Branch')
        <a href="#" data-url="{{ route('leavetype.create') }}" data-ajax-popup="true"
            data-title="{{ __('Create New Leave Type') }}" data-bs-toggle="tooltip" title="" class="btn btn-sm btn-primary"
            data-bs-original-title="{{ __('Create') }}">
            <i class="ti ti-plus"></i>
        </a>
    @endcan --}}
@endsection


@php
    $masterData = $masterData ?? ['countryNames' => [], 'stateNames' => [], 'cityNames' => []];
@endphp

@section('content')
    <div class="row">
        <div class="col-12">
            @include('layouts.hrm_setup')
        </div>
        <div class="col-12">
            <ul class="nav nav-tabs mb-3" id="addressMasterTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#list-pane" type="button" role="tab">{{ __('Leave Types') }}</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#country-master-pane" type="button" role="tab">{{ __('Country Master') }}</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#state-master-pane" type="button" role="tab">{{ __('State Master') }}</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#city-master-pane" type="button" role="tab">{{ __('City Master') }}</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="list-pane" role="tabpanel">
            <div class="my-3 d-flex justify-content-end">
                @can('Create Leave Type')
                    <a href="#" data-url="{{ route('leavetype.create') }}" data-ajax-popup="true"
                        data-title="{{ __('Create New Leave Type') }}" data-bs-toggle="tooltip" title=""
                        class="btn btn-sm btn-primary" data-bs-original-title="{{ __('Create') }}">
                        <i class="ti ti-plus"></i>
                    </a>
                @endcan
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body table-border-style">

                            <div class="table-responsive">
                                <table class="table" id="pc-dt-simple">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Leave Type') }}</th>
                                            <th>{{ __('Days / Year') }}</th>
                                            <th>{{ __('Monthly Credit') }}</th>
                                            <th>{{ __('Annual Credit') }}</th>
                                            <th>{{ __('Approval Requirement') }}</th>
                                            <th>{{ __('Country') }}</th>
                                            <th>{{ __('State') }}</th>
                                            <th>{{ __('City') }}</th>
                                            <th width="200px">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($leavetypes as $leavetype)
                                            <tr>
                                                @php
                                                    $annualCredit = $leavetype->annual_credit ?? $leavetype->days;
                                                    $monthlyCredit = $leavetype->monthly_credit ?? round(($annualCredit ?? 0) / 12, 2);
                                                @endphp
                                                <td>{{ $leavetype->title }}</td>
                                                <td>{{ $leavetype->days }}</td>
                                                <td>{{ $monthlyCredit }}</td>
                                                <td>{{ $annualCredit }}</td>
                                                <td>{{ $leavetype->approval_requirement === 'subordinate' ? __('Subordinate Approval') : __('NA') }}</td>
                                                <td>{{ $masterData['countryNames'][$leavetype->country] ?? ($leavetype->country ?? '—') }}</td>
                                                <td>{{ $masterData['stateNames'][$leavetype->state] ?? ($leavetype->state ?? '—') }}</td>
                                                <td>{{ $masterData['cityNames'][$leavetype->city] ?? ($leavetype->city ?? '—') }}</td>
                                                <td class="Action">
                                                    <div class="dt-buttons">
                                                        <span class="float-start">
                                                            @can('Edit Leave Type')
                                                                <div class="action-btn me-2">
                                                                    <a href="#" class="btn btn-sm align-items-center bg-info"
                                                                        data-url="{{ route('leavetype.edit', $leavetype->id) }}"
                                                                        data-ajax-popup="true" data-title="{{ __('Edit Leave Type') }}"
                                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                        data-original-title="{{ __('Edit') }}">
                                                                        <i class="ti ti-pencil text-white"></i>
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                            @can('Delete Leave Type')
                                                                <div class="action-btn ">
                                                                    {!! Form::open([
                                                                        'method' => 'DELETE',
                                                                        'route' => ['leavetype.destroy', $leavetype->id],
                                                                        'id' => 'delete-form-' . $leavetype->id,
                                                                        ]) !!}
                                                                        <a href="#"
                                                                            class="btn btn-sm  align-items-center bs-pass-para bg-danger"
                                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                            class="ti ti-trash text-white text-white"></i></a>
                                                                    {!! Form::close() !!}
                                                                </div>
                                                            @endcan
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                </div>
                @include('partials.address_master_tabs', ['masterData' => $masterData])
            </div>
        </div>
    </div>
@endsection
