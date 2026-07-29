@extends('layouts.admin')

@section('page-title')
    {{ __('Employee') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ url('employee') }}">{{ __('Employee') }}</a></li>
    <li class="breadcrumb-item">{{ __('Manage Employee') }}</li>
@endsection

@section('action-button')
    <div class="float-end">
        @php
            $canEditEmployeeRecord = \Auth::user()->can('Edit Employee')
                || (\Auth::user()->type == 'employee' && (int) \Auth::id() === (int) ($employee->user_id ?? 0));
        @endphp
        @if ($canEditEmployeeRecord)
            <a href="{{ route('employee.edit', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}"
                data-bs-toggle="tooltip" title="{{ __('Edit') }}"class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i>
            </a>
        @endif
        @if (\Auth::user()->can('Edit User') && !empty($employee->user_id))
            <a href="{{ route('user.reset', \Illuminate\Support\Facades\Crypt::encrypt($employee->user_id)) }}"
                data-bs-toggle="tooltip" title="{{ __('Reset Password') }}" class="btn btn-sm btn-warning ms-1">
                <i class="ti ti-key"></i>
            </a>
        @endif
    </div>
    <div class="text-end mb-3">
        <div class="d-flex justify-content-end drp-languages">
            <ul class="list-unstyled mb-0 m-2">
                <li class="dropdown dash-h-item drp-language">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#"
                        role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="drp-text hide-mob text-primary"> {{ __('Joining Letter') }}
                            <i class="ti ti-chevron-down drp-arrow nocolor hide-mob"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown">
                        <a href="{{ route('joiningletter.download.pdf', $employee->id) }}" class=" btn-icon dropdown-item"
                            data-bs-toggle="tooltip" data-bs-placement="top" target="_blanks"><i
                                class="ti ti-download ">&nbsp;</i>{{ __('PDF') }}</a>

                        <a href="{{ route('joininglatter.download.doc', $employee->id) }}" class=" btn-icon dropdown-item"
                            data-bs-toggle="tooltip" data-bs-placement="top" target="_blanks"><i
                                class="ti ti-download ">&nbsp;</i>{{ __('DOC') }}</a>
                    </div>
                </li>
            </ul>
            <ul class="list-unstyled mb-0 m-2">
                <li class="dropdown dash-h-item drp-language">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#"
                        role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="drp-text hide-mob text-primary"> {{ __('Experience Certificate') }}
                            <i class="ti ti-chevron-down drp-arrow nocolor hide-mob"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown">
                        <a href="{{ route('exp.download.pdf', $employee->id) }}" class=" btn-icon dropdown-item"
                            data-bs-toggle="tooltip" data-bs-placement="top" target="_blanks"><i
                                class="ti ti-download ">&nbsp;</i>{{ __('PDF') }}</a>

                        <a href="{{ route('exp.download.doc', $employee->id) }}" class=" btn-icon dropdown-item"
                            data-bs-toggle="tooltip" data-bs-placement="top" target="_blanks"><i
                                class="ti ti-download ">&nbsp;</i>{{ __('DOC') }}</a>
                    </div>
                </li>
            </ul>
            <ul class="list-unstyled mb-0 m-2">
                <li class="dropdown dash-h-item drp-language">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#"
                        role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="drp-text hide-mob text-primary"> {{ __('NOC') }}
                            <i class="ti ti-chevron-down drp-arrow nocolor hide-mob"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown">
                        <a href="{{ route('noc.download.pdf', $employee->id) }}" class=" btn-icon dropdown-item"
                            data-bs-toggle="tooltip" data-bs-placement="top" target="_blanks"><i
                                class="ti ti-download ">&nbsp;</i>{{ __('PDF') }}</a>

                        <a href="{{ route('noc.download.doc', $employee->id) }}" class=" btn-icon dropdown-item"
                            data-bs-toggle="tooltip" data-bs-placement="top" target="_blanks"><i
                                class="ti ti-download ">&nbsp;</i>{{ __('DOC') }}</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    @php
        $familyData = json_decode($employee->family_details ?? '', true);
        $hobbyData = json_decode($employee->hobbies ?? '', true);
        $educationData = json_decode($employee->education ?? '', true);
        $familyData = is_array($familyData) ? $familyData : [];
        $hobbyData = is_array($hobbyData) ? $hobbyData : [];
        $educationData = is_array($educationData) ? $educationData : [];
    @endphp
    <div class="row">
        <div class="col-xl-12">
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="card ">
                        <div class="card-body employee-detail-body fulls-card">
                            <h5>{{ __('Personal Detail') }}</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Employee ID') }} : </strong>
                                        <span>{{ $employeesId }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm font-style">
                                        <strong class="font-bold">{{ __('Name') }} :</strong>
                                        <span>{{ $employee->name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm font-style">
                                        <strong class="font-bold">{{ __('Email') }} :</strong>
                                        <span>{{ $employee->email }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Date of Birth') }} :</strong>
                                        <span>{{ \Auth::user()->dateFormat($employee->dob) }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Phone') }} :</strong>
                                        <span>{{ $employee->phone }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Address') }} :</strong>
                                        <span>{{ $employee->present_address ?? $employee->address }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Permanent Address') }} :</strong>
                                        <span>{{ $employee->permanent_address }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Blood Group') }} :</strong>
                                        <span>{{ $employee->blood_group }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Food Type') }} :</strong>
                                        <span>{{ $employee->food_type }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Emergency Contact Name') }} :</strong>
                                        <span>{{ $employee->emergency_contact_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Emergency Contact Phone') }} :</strong>
                                        <span>{{ $employee->emergency_contact_phone }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Insurance ID') }} :</strong>
                                        <span>{{ $employee->insurance_id }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Name of Insurer') }} :</strong>
                                        <span>{{ $employee->insurer_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Insurance Contact Person') }} :</strong>
                                        <span>{{ $employee->insurance_contact_person }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Family Details') }} :</strong>
                                        <span>
                                            {{ __('Father') }}: {{ $familyData['father_name'] ?? '-' }},
                                            {{ __('Mother') }}: {{ $familyData['mother_name'] ?? '-' }},
                                            {{ __('Spouse') }}: {{ $familyData['spouse_name'] ?? '-' }},
                                            {{ __('Children') }}: {{ $familyData['children_count'] ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Hobbies') }} :</strong>
                                        <span>
                                            {{ __('Indoor') }}: {{ $hobbyData['indoor'] ?? '-' }},
                                            {{ __('Outdoor') }}: {{ $hobbyData['outdoor'] ?? '-' }},
                                            {{ __('Other') }}: {{ $hobbyData['other'] ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Education') }} :</strong>
                                        <span>
                                            {{ __('Qualification') }}: {{ $educationData['qualification'] ?? '-' }},
                                            {{ __('Specialization') }}: {{ $educationData['specialization'] ?? '-' }},
                                            {{ __('Institute') }}: {{ $educationData['institute'] ?? '-' }},
                                            {{ __('Passing Year') }}: {{ $educationData['passing_year'] ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Salary Type') }} :</strong>
                                        <span>{{ !empty($employee->salaryType) ? $employee->salaryType->name : '' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Basic Salary') }} :</strong>
                                        <span>{{ $employee->salary }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">

                    <div class="card ">
                        <div class="card-body employee-detail-body fulls-card emp-card">
                            <h5>{{ __('Company Detail') }}</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Branch') }} : </strong>
                                        <span>{{ !empty($employee->branch) ? $employee->branch->name : '' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm font-style">
                                        <strong class="font-bold">{{ __('Department') }} :</strong>
                                        <span>{{ !empty($employee->department) ? $employee->department->name : '' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Department Hierarchy') }} :</strong>
                                        <span>{{ $employee->department_hierarchy }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Reporting Manager') }} :</strong>
                                        <span>{{ !empty($employee->reportingManager) ? $employee->reportingManager->name : __('N/A') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('HOD') }} :</strong>
                                        <span>{{ !empty($employee->hod) ? $employee->hod->name : __('N/A') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Management') }} :</strong>
                                        <span>{{ !empty($employee->management) ? $employee->management->name : __('N/A') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Designation') }} :</strong>
                                        <span>{{ !empty($employee->designation) ? $employee->designation->name : '' }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Date Of Joining') }} :</strong>
                                        <span>{{ \Auth::user()->dateFormat($employee->company_doj) }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Shift') }} :</strong>
                                        <span>
                                            @if(!empty($employee->shift))
                                                <span class="badge badge-sm bg-primary">{{ $employee->shift->name }}</span>
                                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i') }}</small>
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Employee Code') }} :</strong>
                                        <span>{{ !empty($employee->biometric_emp_id) ? $employee->biometric_emp_id : '' }}</span>
                                    </div>
                                </div> --}}

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 col-md-6">

                    <div class="card ">
                        <div class="card-body employee-detail-body fulls-card emp-card">
                            <h5>{{ __('Document Detail') }}</h5>
                            <hr>
                            <div class="row">
                                @php
                                    $employeedoc = $employee->documents()->pluck('document_value', 'document_id');
                                    $logo = \App\Models\Utility::get_file('uploads/document');
                                    $uploadPath = \App\Models\Utility::get_file('uploads/documentUpload');
                                    $uploadedDocuments = $uploadedDocuments ?? collect();
                                    $hasTypeDocs = !$documents->isEmpty();
                                    $hasUploadedDocs = !$uploadedDocuments->isEmpty();
                                @endphp

                                @if ($hasTypeDocs)
                                    @foreach ($documents as $key => $document)
                                        <div class="col-md-6 mb-2">
                                            <div class="info text-sm">
                                                <strong class="font-bold">{{ $document->name }} : </strong>
                                                <span><a href="{{ !empty($employeedoc[$document->id]) ? $logo . '/' . $employeedoc[$document->id] : '' }}"
                                                        target="_blank">{{ !empty($employeedoc[$document->id]) ? $employeedoc[$document->id] : '-' }}</a></span>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if ($hasUploadedDocs)
                                    @if ($hasTypeDocs)
                                        <div class="col-12"><hr></div>
                                    @endif
                                    <div class="col-12">
                                        <style>
                                            .emp-doc-list { display: flex; flex-direction: column; gap: 10px; width: 100%; }
                                            .emp-doc-item {
                                                display: flex;
                                                align-items: flex-start;
                                                justify-content: space-between;
                                                gap: 12px;
                                                width: 100%;
                                                max-width: 100%;
                                                box-sizing: border-box;
                                                padding: 10px 12px;
                                                border: 1px solid #e9ecef;
                                                border-radius: 8px;
                                                background: #fafbfc;
                                            }
                                            .emp-doc-item__text {
                                                min-width: 0;
                                                flex: 1 1 auto;
                                                word-break: break-word;
                                            }
                                            .emp-doc-item__text .emp-doc-name { display: block; font-weight: 600; color: #1e293b; }
                                            .emp-doc-item__text .emp-doc-desc { display: block; margin-top: 2px; font-size: 12px; color: #64748b; }
                                            .emp-doc-item__actions {
                                                display: flex;
                                                flex-shrink: 0;
                                                align-items: center;
                                                gap: 8px;
                                            }
                                            .emp-doc-item__actions a {
                                                display: inline-flex;
                                                align-items: center;
                                                justify-content: center;
                                                width: 32px;
                                                height: 32px;
                                                border-radius: 6px;
                                                color: #fff !important;
                                                text-decoration: none;
                                                margin: 0;
                                                padding: 0;
                                            }
                                            .emp-doc-item__actions a.emp-doc-dl { background: #51459d; }
                                            .emp-doc-item__actions a.emp-doc-view { background: #6c757d; }
                                            @media (max-width: 576px) {
                                                .emp-doc-item { flex-direction: column; align-items: stretch; }
                                                .emp-doc-item__actions { justify-content: flex-end; }
                                            }
                                        </style>
                                        <div class="emp-doc-list">
                                            @foreach ($uploadedDocuments as $uploaded)
                                                <div class="emp-doc-item">
                                                    <div class="emp-doc-item__text">
                                                        <span class="emp-doc-name">{{ $uploaded->name }}</span>
                                                        @if (!empty($uploaded->description))
                                                            <span class="emp-doc-desc">{{ $uploaded->description }}</span>
                                                        @endif
                                                    </div>
                                                    @if (!empty($uploaded->document))
                                                        <div class="emp-doc-item__actions">
                                                            <a class="emp-doc-dl" href="{{ $uploadPath . '/' . $uploaded->document }}"
                                                                download title="{{ __('Download') }}">
                                                                <i class="ti ti-download"></i>
                                                            </a>
                                                            <a class="emp-doc-view" href="{{ $uploadPath . '/' . $uploaded->document }}"
                                                                target="_blank" title="{{ __('Preview') }}">
                                                                <i class="ti ti-eye"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if (!$hasTypeDocs && !$hasUploadedDocs)
                                    <div class="text-center">
                                        {{ __('No Document Added.') }}
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">

                    <div class="card ">
                        <div class="card-body employee-detail-body fulls-card emp-card">
                            <h5>{{ __('Bank Account Detail') }}</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Account Holder Name') }} : </strong>
                                        <span>{{ $employee->account_holder_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm font-style">
                                        <strong class="font-bold">{{ __('Account Number') }} :</strong>
                                        <span>{{ $employee->account_number }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Bank Name') }} :</strong>
                                        <span>{{ $employee->bank_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Bank Identifier Code') }} :</strong>
                                        <span>{{ $employee->bank_identifier_code }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Branch Location') }} :</strong>
                                        <span>{{ $employee->branch_location }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Tax Payer Id') }} :</strong>
                                        <span>{{ $employee->tax_payer_id }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('Aadhar Number') }} :</strong>
                                        <span>{{ $employee->aadhar_number ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('PAN Number') }} :</strong>
                                        <span>{{ $employee->pan_number ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('UAN Number') }} :</strong>
                                        <span>{{ $employee->uan_number ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info text-sm">
                                        <strong class="font-bold">{{ __('ESIC Number') }} :</strong>
                                        <span>{{ $employee->esic_number ?? '—' }}</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
