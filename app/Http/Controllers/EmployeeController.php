<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Document;
use App\Models\DucumentUpload;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeType;
use App\Mail\UserCreate;
use App\Models\Plan;
use App\Models\User;
use App\Models\Utility;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\JoiningLetter;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesExport;
use App\Models\Contract;
use App\Models\ExperienceCertificate;
use App\Models\Shift;
use App\Models\LoginDetail;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\NOC;
use App\Models\PaySlip;
use App\Models\Termination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

//use Faker\Provider\File;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (\Auth::user()->can('Manage Employee')) {
            if (Auth::user()->type == 'employee') {
                $employees = Employee::where('user_id', '=', Auth::user()->id)
                    ->with(['branch', 'department', 'designation', 'user', 'reportingManager'])
                    ->get();
            } else {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())
                    ->with(['branch', 'department', 'designation', 'user', 'reportingManager'])
                    ->get();
            }

            return view('employee.index', compact('employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('Create Employee')) {
            $company_settings = Utility::settings();
            $documents        = Document::where('created_by', Auth::user()->creatorId())->get();
            $branches         = Branch::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments      = Department::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations     = Designation::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $managerEmployees    = Employee::where('created_by', Auth::user()->creatorId())->orderBy('name')->pluck('name', 'id');
            $hodEmployees        = Employee::where('created_by', Auth::user()->creatorId())->orderBy('name')->pluck('name', 'id');
            $managementEmployees = Employee::where('created_by', Auth::user()->creatorId())->orderBy('name')->pluck('name', 'id');
            $shifts           = collect();
            if (\Schema::hasTable('shifts')) {
                $shifts = Shift::where('created_by', Auth::user()->creatorId())->orderBy('name')->get()->pluck('name', 'id');
            }
            $employees        = User::where('created_by', Auth::user()->creatorId())->get();
            $employeesId      = Auth::user()->employeeIdFormat($this->employeeNumber());
            $employeeTypes    = EmployeeType::where('is_active', true)->orderBy('sort_order')->get();

            return view('employee.create', compact('employees', 'employeesId', 'departments', 'designations', 'documents', 'branches', 'company_settings', 'shifts', 'managerEmployees', 'hodEmployees', 'managementEmployees', 'employeeTypes'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Get countries for address dropdown (from JSON data).
     */
    public function getAddressCountries()
    {
        $path = base_path('database/data/country_state_city.json');
        if (!file_exists($path)) {
            return response()->json(['countries' => []]);
        }
        $data = json_decode(file_get_contents($path), true);
        return response()->json(['countries' => $data['countries'] ?? []]);
    }

    /**
     * Get states by country for address dropdown.
     */
    public function getAddressStates(Request $request)
    {
        $countryId = trim((string) $request->get('country_id', ''));
        $data = $this->getAddressData();
        if (empty($data)) {
            return response()->json(['states' => []]);
        }

        // Backward compatibility: old employee rows may store country name (e.g. "India") instead of code ("IN").
        if (!isset($data['states'][$countryId])) {
            foreach (($data['countries'] ?? []) as $country) {
                if (strcasecmp((string) ($country['name'] ?? ''), $countryId) === 0) {
                    $countryId = (string) ($country['id'] ?? '');
                    break;
                }
            }
        }

        $states = $data['states'][$countryId] ?? [];
        return response()->json(['states' => $states]);
    }

    /**
     * Get cities by state for address dropdown.
     */
    public function getAddressCities(Request $request)
    {
        $stateId = trim((string) $request->get('state_id', ''));
        $countryId = trim((string) $request->get('country_id', ''));
        $data = $this->getAddressData();
        if (empty($data)) {
            return response()->json(['cities' => []]);
        }

        // Backward compatibility for old value formats where state is saved as name.
        if (!isset($data['cities'][$stateId])) {
            $candidateStates = [];
            if (!empty($countryId)) {
                if (!isset($data['states'][$countryId])) {
                    foreach (($data['countries'] ?? []) as $country) {
                        if (strcasecmp((string) ($country['name'] ?? ''), $countryId) === 0) {
                            $countryId = (string) ($country['id'] ?? '');
                            break;
                        }
                    }
                }
                $candidateStates = $data['states'][$countryId] ?? [];
            } else {
                foreach (($data['states'] ?? []) as $states) {
                    if (is_array($states)) {
                        $candidateStates = array_merge($candidateStates, $states);
                    }
                }
            }

            foreach ($candidateStates as $state) {
                if (strcasecmp((string) ($state['name'] ?? ''), $stateId) === 0) {
                    $stateId = (string) ($state['id'] ?? '');
                    break;
                }
            }
        }

        $cities = $data['cities'][$stateId] ?? [];
        if (empty($cities)) {
            $cities = $this->getFallbackCitiesByCountry($countryId);
        }

        return response()->json(['cities' => $cities]);
    }

    /**
     * Get country/state/city master data from local JSON file.
     */
    protected function getAddressData(): array
    {
        $path = base_path('database/data/country_state_city.json');
        if (!file_exists($path)) {
            return [];
        }

        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Fallback cities when specific state mapping is not available.
     */
    protected function getFallbackCitiesByCountry(string $countryId): array
    {
        $countryId = strtoupper(trim($countryId));
        $nameToCode = [
            'INDIA' => 'IN',
            'UNITED STATES' => 'US',
            'UNITED KINGDOM' => 'UK',
            'UAE' => 'AE',
        ];
        $countryId = $nameToCode[$countryId] ?? $countryId;
        $fallback = [
            'IN' => ['New Delhi', 'Mumbai', 'Bengaluru', 'Hyderabad', 'Ahmedabad', 'Chennai', 'Kolkata', 'Pune', 'Jaipur', 'Lucknow'],
            'US' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'San Diego', 'Dallas', 'San Jose'],
            'UK' => ['London', 'Birmingham', 'Manchester', 'Liverpool', 'Leeds', 'Bristol'],
            'AE' => ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Al Ain'],
        ];

        $cities = $fallback[$countryId] ?? ['Other'];

        return collect($cities)->map(function ($name) {
            return [
                'id' => str_replace(' ', '_', strtolower($name)),
                'name' => $name,
            ];
        })->values()->all();
    }

    public function store(Request $request)
    {
        \Log::info('EMP_STORE_DEBUG', [
            'can_create' => \Auth::user()->can('Create Employee'),
            'dob'        => $request->input('dob'),
            'all_keys'   => array_keys($request->all()),
        ]);
        if (\Auth::user()->can('Create Employee')) {

            $rules = [
                'name' => 'required|max:120',
                'dob' => 'required|date|before_or_equal:' . date('Y-m-d', strtotime('-15 years')) . '|after_or_equal:' . date('Y-m-d', strtotime('-100 years')),
                'gender' => 'required',
                'phone' => 'required',
                'present_address' => 'required',
                'permanent_address' => 'required',
                'family_father_name' => 'nullable|max:120',
                'family_mother_name' => 'nullable|max:120',
                'family_spouse_name' => 'nullable|max:120',
                'family_children_count' => 'nullable|integer|min:0',
                'hobby_indoor' => 'nullable|max:120',
                'hobby_outdoor' => 'nullable|max:120',
                'hobby_other' => 'nullable|max:255',
                'education_qualification' => 'nullable|max:120',
                'education_specialization' => 'nullable|max:120',
                'education_institute' => 'nullable|max:180',
                'education_passing_year' => 'nullable|digits:4',
                'email' => 'required|unique:users|email|max:100',
                'password' => 'required',
                'branch_id' => 'required',
                'department_id' => 'required',
                'designation_id' => 'required',
                'department_hierarchy' => 'nullable|in:Employee,Team Member,Manager,HOD',
                'reporting_manager_id' => 'nullable|exists:employees,id',
                'hod_id' => 'nullable|exists:employees,id',
                'management_id' => 'nullable|exists:employees,id',
                'employee_type_id' => 'nullable|exists:employee_types,id',
                'monthly_stipend' => 'nullable|numeric|min:0',
                'shift_id' => 'nullable|exists:shifts,id',
                'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'food_type' => 'nullable|in:Veg,Vegan,Non-Veg',
                'document' => 'nullable|array',
                'document.*' => 'file',
            ];
            $dobMessages = [
                'dob.required'        => __('Date of Birth is required.'),
                'dob.date'            => __('Please enter a valid Date of Birth.'),
                'dob.before_or_equal' => __('Date of Birth is not valid. Employee must be at least 15 years old.'),
                'dob.after_or_equal'  => __('Date of Birth is not valid. Please enter a realistic date of birth.'),
            ];
            // $rules['biometric_emp_id'] = [
            //     'required',
            //     Rule::unique('employees')->where(function ($query) {
            //         return $query->where('created_by', Auth::user()->creatorId());
            //     })
            // ];

            $validator = \Validator::make(
                $request->all(),
                $rules,
                $dobMessages
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                \Log::info('EMP_STORE_DEBUG_FAIL', ['errors' => $messages->toArray()]);

                return redirect()->back()->withInput()->withErrors($validator)->with('error', $messages->first());
            }

            $requiredDocumentIds = Document::where('created_by', Auth::user()->creatorId())
                ->where('is_required', 1)
                ->pluck('id')
                ->map(function ($id) {
                    return (string) $id;
                })
                ->toArray();
            $uploadedDocumentIds = array_keys($request->file('document', []));
            $missingRequiredIds = array_diff($requiredDocumentIds, $uploadedDocumentIds);
            if (!empty($missingRequiredIds)) {
                return redirect()->back()->withInput()->with('error', __('Please upload all required documents.'));
            }

            $objUser        = User::find(\Auth::user()->creatorId());
            $total_employee = $objUser->countEmployees();
            $plan           = Plan::find($objUser->plan);
            $result         = 1;
            $date = date("Y-m-d H:i:s");
            $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->where('created_by', \Auth::user()->creatorId())->first();

            // new company default language
            if ($default_language == null) {
                $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();
            }

            if ($total_employee < $plan->max_employees || $plan->max_employees == -1) {

                $user = User::create(
                    [
                        'name' => $request['name'],
                        'email' => $request['email'],
                        'password' => Hash::make($request['password']),
                        'type' => 'employee',
                        'lang' => !empty($default_language) ? $default_language->value : 'en',
                        'created_by' => \Auth::user()->creatorId(),
                        'email_verified_at' => $date,
                    ]
                );
                $user->save();
                $user->assignRole('Employee');
            } else {
                return redirect()->back()->with('error', __('Your employee limit is over, Please upgrade plan.'));
            }


            if (!empty($request->document) && !is_null($request->document)) {
                $document_implode = implode(',', array_keys($request->document));
            } else {
                $document_implode = null;
            }


            $employee = Employee::create(
                [
                    'user_id' => $user->id,
                    'name' => $request['name'],
                    'dob' => $request['dob'],
                    'gender' => $request['gender'],
                    'phone' => $request['phone'],
                    'address' => $request['present_address'],
                    'present_address' => $request['present_address'],
                    'permanent_address' => $request['permanent_address'],
                    'present_country' => $request->input('present_country'),
                    'present_state' => $request->input('present_state'),
                    'present_city' => $request->input('present_city'),
                    'present_pincode' => $request->input('present_pincode'),
                    'permanent_country' => $request->input('permanent_country'),
                    'permanent_state' => $request->input('permanent_state'),
                    'permanent_city' => $request->input('permanent_city'),
                    'permanent_pincode' => $request->input('permanent_pincode'),
                    'family_details' => $this->buildStructuredJson([
                        'father_name' => $request['family_father_name'] ?? null,
                        'mother_name' => $request['family_mother_name'] ?? null,
                        'spouse_name' => $request['family_spouse_name'] ?? null,
                        'children_count' => $request['family_children_count'] ?? null,
                    ]),
                    'emergency_contact_name' => $request['emergency_contact_name'],
                    'emergency_contact_phone' => $request['emergency_contact_phone'],
                    'blood_group' => $request['blood_group'],
                    'insurance_id' => $request['insurance_id'],
                    'insurer_name' => $request['insurer_name'],
                    'insurance_contact_person' => $request['insurance_contact_person'],
                    'hobbies' => $this->buildStructuredJson([
                        'indoor' => $request['hobby_indoor'] ?? null,
                        'outdoor' => $request['hobby_outdoor'] ?? null,
                        'other' => $request['hobby_other'] ?? null,
                    ]),
                    'food_type' => $request['food_type'],
                    'education' => $this->buildStructuredJson([
                        'qualification' => $request['education_qualification'] ?? null,
                        'specialization' => $request['education_specialization'] ?? null,
                        'institute' => $request['education_institute'] ?? null,
                        'passing_year' => $request['education_passing_year'] ?? null,
                    ]),
                    'email' => $request['email'],
                    'password' => Hash::make($request['password']),
                    'employee_id' => $this->employeeNumber(),
                    // 'biometric_emp_id' => !empty($request['biometric_emp_id']) ? $request['biometric_emp_id'] : '',
                    'branch_id' => $request['branch_id'],
                    'department_id' => $request['department_id'],
                    'designation_id' => $request['designation_id'],
                    'department_hierarchy' => $request['department_hierarchy'],
                    'reporting_manager_id' => $request['reporting_manager_id'] ?? null,
                    'hod_id' => $request['hod_id'] ?? null,
                    'management_id' => $request['management_id'] ?? null,
                    'employee_type_id' => $request['employee_type_id'] ?? optional(EmployeeType::default())->id,
                    'monthly_stipend' => $request['monthly_stipend'] ?? null,
                    'company_doj' => $request['company_doj'],
                    'shift_id' => $request['shift_id'] ?? null,
                    'shift_type' => $request['shift_type'] ?? 'morning',
                    'documents' => $document_implode,
                    'account_holder_name' => $request['account_holder_name'],
                    'account_number' => $request['account_number'],
                    'bank_name' => $request['bank_name'],
                    'bank_identifier_code' => $request['bank_identifier_code'],
                    'branch_location' => $request['branch_location'],
                    'tax_payer_id' => $request['tax_payer_id'],
                    'created_by' => \Auth::user()->creatorId(),
                ]
            );

            if ($request->hasFile('document')) {
                foreach ($request->file('document', []) as $key => $document) {
                    if (empty($document) || !$document->isValid()) {
                        continue;
                    }

                    $image_size = $document->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                    if ($result == 1) {
                        $filenameWithExt = $document->getClientOriginalName();
                        $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension       = $document->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                        $dir             = 'uploads/document/';

                        $path = \App\Models\Utility::upload_coustom_file($request, 'document', $fileNameToStore, $dir, $key, []);

                        if ($path['flag'] == 1) {
                            $url = $path['url'];
                        } else {
                            return redirect()->back()->with('error', __($path['msg']));
                        }
                        $employee_document = EmployeeDocument::create(
                            [
                                'employee_id' => $employee['employee_id'],
                                'document_id' => $key,
                                'document_value' => $path['url'],
                                'created_by' => \Auth::user()->creatorId(),
                            ]
                        );
                        $employee_document->save();
                    }
                }
            }
            $setings = \App\Models\Utility::settings();
            if ($setings['new_employee'] == 1) {
                $department = Department::find($request['department_id']);
                $branch = Branch::find($request['branch_id']);
                $designation = Designation::find($request['designation_id']);
                $uArr = [
                    'employee_email' => $user->email,
                    'employee_password' => $request->password,
                    'employee_name' => $request['name'],
                    'employee_branch' => !empty($branch->name) ? $branch->name : '',
                    'employee_department' => !empty($department->name) ? $department->name : '',
                    'employee_designation' => !empty($designation->name) ? $designation->name : '',
                ];
                $resp = \App\Models\Utility::sendEmailTemplate('new_employee', [$user->id => $user->email], $uArr);

                return redirect()->route('employee.index')->with('success', __('Employee successfully created.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : '') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            }
            return redirect()->route('employee.index')->with('success', __('Employee successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Employee Not Found.'));
        }
        $employeeForAccess = Employee::find($id);
        $canEditAsHr = \Auth::user()->can('Edit Employee');
        $canEditOwnProfile = \Auth::user()->type === 'employee'
            && $employeeForAccess
            && (int) $employeeForAccess->user_id === (int) \Auth::id();

        if ($canEditAsHr || $canEditOwnProfile) {
            $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $managerEmployees = Employee::where('created_by', \Auth::user()->creatorId())
                ->where('id', '!=', $id)
                ->orderBy('name')
                ->pluck('name', 'id');
            $hodEmployees = Employee::where('created_by', \Auth::user()->creatorId())
                ->where('id', '!=', $id)
                ->orderBy('name')
                ->pluck('name', 'id');
            $managementEmployees = Employee::where('created_by', \Auth::user()->creatorId())
                ->where('id', '!=', $id)
                ->orderBy('name')
                ->pluck('name', 'id');
            $shifts       = collect();
            if (\Schema::hasTable('shifts')) {
                $shifts = Shift::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get()->pluck('name', 'id');
            }
            $employee     = Employee::find($id);
            $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);
            $employeeTypes = EmployeeType::where('is_active', true)->orderBy('sort_order')->get();

            return view('employee.edit', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents', 'shifts', 'managerEmployees', 'hodEmployees', 'managementEmployees', 'employeeTypes'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $canEditAsHr = \Auth::user()->can('Edit Employee');
        $canEditOwnProfile = \Auth::user()->type === 'employee'
            && (int) $employee->user_id === (int) \Auth::id();

        if (! $canEditAsHr && ! $canEditOwnProfile) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $rules = [
                'name' => 'required',
                'dob' => 'required|date|before_or_equal:' . date('Y-m-d', strtotime('-15 years')) . '|after_or_equal:' . date('Y-m-d', strtotime('-100 years')),
                'gender' => 'required',
                'phone' => 'required',
                'present_address' => 'required',
                'permanent_address' => 'required',
                'family_father_name' => 'nullable|max:120',
                'family_mother_name' => 'nullable|max:120',
                'family_spouse_name' => 'nullable|max:120',
                'family_children_count' => 'nullable|integer|min:0',
                'hobby_indoor' => 'nullable|max:120',
                'hobby_outdoor' => 'nullable|max:120',
                'hobby_other' => 'nullable|max:255',
                'education_qualification' => 'nullable|max:120',
                'education_specialization' => 'nullable|max:120',
                'education_institute' => 'nullable|max:180',
                'education_passing_year' => 'nullable|digits:4',
                'department_hierarchy' => 'nullable|in:Employee,Team Member,Manager,HOD',
                'reporting_manager_id' => 'nullable|exists:employees,id',
                'hod_id' => 'nullable|exists:employees,id',
                'management_id' => 'nullable|exists:employees,id',
                'employee_type_id' => 'nullable|exists:employee_types,id',
                'monthly_stipend' => 'nullable|numeric|min:0',
                'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'food_type' => 'nullable|in:Veg,Vegan,Non-Veg',
                'document' => 'nullable|array',
                'document.*' => 'file',
            ];
            $dobMessages = [
                'dob.required'        => __('Date of Birth is required.'),
                'dob.date'            => __('Please enter a valid Date of Birth.'),
                'dob.before_or_equal' => __('Date of Birth is not valid. Employee must be at least 15 years old.'),
                'dob.after_or_equal'  => __('Date of Birth is not valid. Please enter a realistic date of birth.'),
            ];

            if ($canEditAsHr) {
                $rules['shift_id'] = 'nullable|exists:shifts,id';
            }

            // if ($request->has('biometric_emp_id') && $employee->biometric_emp_id != $request->biometric_emp_id) {
            //     $rules['biometric_emp_id'] = [
            //         'required',
            //         Rule::unique('employees')->where(function ($query) {
            //             return $query->where('created_by', Auth::user()->creatorId());
            //         })
            //     ];
            // }

            $validator = \Validator::make(
                $request->all(),
                $rules,
                $dobMessages
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->withInput()->with('error', $messages->first());
            }

            if ($canEditAsHr && $request->hasFile('document')) {

                foreach ($request->file('document', []) as $key => $document) {
                    $employee_document = EmployeeDocument::where('employee_id', $employee->employee_id)->where('document_id', $key)->first();
                    if (!empty($document)) {

                        //storage limit
                        $dir = 'uploads/document/';
                        if (!empty($employee_document)) {
                            $file_path = $dir . $employee_document->document_value;
                        }
                        $image_size = $document->getSize();
                        $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                        if ($result == 1) {
                            if (!empty($file_path)) {
                                Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                            }

                            $filenameWithExt = $document->getClientOriginalName();
                            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                            $extension       = $document->getClientOriginalExtension();
                            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                            $dir             = 'uploads/document/';

                            $image_path      = $dir . $fileNameToStore;

                            $path = \App\Models\Utility::upload_coustom_file($request, 'document', $fileNameToStore, $dir, $key, []);
                            if (!empty($employee_document)) {
                                if ($employee_document->document_value) {
                                    \File::delete(storage_path('uploads/document/' . $employee_document->document_value));
                                }
                                $employee_document->document_value = $path['url'];
                                $employee_document->save();
                            } else {
                                $employee_document                 = new EmployeeDocument();
                                $employee_document->employee_id    = $employee->employee_id;
                                $employee_document->document_id    = $key;
                                $employee_document->document_value = $path['url'];
                                $employee_document->save();
                            }

                            if ($path['flag'] == 1) {
                                $url = $path['url'];
                            } else {
                                return redirect()->back()->withInput()->with('error', __($path['msg']));
                            }
                        }
                    }
                }
            }

            if ($canEditAsHr && !empty($request->document) && !is_null($request->document)) {
                $document_implode = implode(',', array_keys($request->document));
            } else {
                $document_implode = null;
            }

            $input = $request->all();

            // Employee self-service: only personal fields; never overwrite company / payroll / bank via crafted POST
            if ($canEditOwnProfile && ! $canEditAsHr) {
                $blocked = [
                    'salary', 'salary_type', 'account_type', 'branch_id', 'department_id', 'designation_id',
                    'employee_type_id', 'monthly_stipend',
                    'company_doj', 'reporting_manager_id', 'hod_id', 'management_id', 'department_hierarchy', 'shift_id', 'shift_type',
                    'employee_id', 'user_id', 'created_by', 'biometric_emp_id', 'documents',
                    'account_holder_name', 'account_number', 'bank_name', 'bank_identifier_code',
                    'branch_location', 'tax_payer_id', 'email', 'password',
                ];
                foreach ($blocked as $key) {
                    unset($input[$key]);
                }
            }

            $user = User::findOrFail($employee->user_id);
            $user->name = $input['name'];
            if (!empty($input['email']) && $input['email'] !== $user->email) {
                $emailExists = User::where('email', $input['email'])->where('id', '!=', $user->id)->exists();
                if ($emailExists) {
                    return redirect()->back()->withInput()->with('error', __('This email is already in use by another account.'));
                }
                $user->email = $input['email'];
            }
            $user->save();

            if ($canEditAsHr && $document_implode !== null) {
                $input['documents'] = $document_implode;
            }
            $input['address'] = $request->input('present_address');
            $input['family_details'] = $this->buildStructuredJson([
                'father_name' => $request['family_father_name'] ?? null,
                'mother_name' => $request['family_mother_name'] ?? null,
                'spouse_name' => $request['family_spouse_name'] ?? null,
                'children_count' => $request['family_children_count'] ?? null,
            ]);
            $input['hobbies'] = $this->buildStructuredJson([
                'indoor' => $request['hobby_indoor'] ?? null,
                'outdoor' => $request['hobby_outdoor'] ?? null,
                'other' => $request['hobby_other'] ?? null,
            ]);
            $input['education'] = $this->buildStructuredJson([
                'qualification' => $request['education_qualification'] ?? null,
                'specialization' => $request['education_specialization'] ?? null,
                'institute' => $request['education_institute'] ?? null,
                'passing_year' => $request['education_passing_year'] ?? null,
            ]);
            $employee->fill($input)->save();
            if ($request->salary) {
                return redirect()->route('setsalary.index')->with('success', 'Employee successfully updated.');
            }

            if (\Auth::user()->type != 'employee') {
                // return redirect()->route('employee.index')->with('success', 'Employee successfully updated.');
                return redirect()->route('employee.index')->with('success', __('Employee successfully updated.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            } else {
                return redirect()->route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))->with('success', __('Employee successfully updated.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('Delete Employee')) {
            $employee      = Employee::findOrFail($id);
            $user          = User::where('id', '=', $employee->user_id)->first();
            $emp_documents = EmployeeDocument::where('employee_id', $employee->employee_id)->get();
            $ContractEmployee = Contract::where('employee_name', '=', $employee->user_id)->get();
            $payslips = PaySlip::where('employee_id', $id)->get();
            $employee->delete();
            $user->delete();

            foreach ($ContractEmployee as $contractdelete) {
                $contractdelete->delete();
            }

            foreach ($payslips as $payslip) {
                $payslip->delete();
            }

            $dir = storage_path('uploads/document/');
            foreach ($emp_documents as $emp_document) {

                $emp_document->delete();
                // \File::delete(storage_path('uploads/document/' . $emp_document->document_value));
                if (!empty($emp_document->document_value)) {

                    $file_path = 'uploads/document/' . $emp_document->document_value;
                    $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);

                    // unlink($dir . $emp_document->document_value);
                }
            }

            return redirect()->route('employee.index')->with('success', 'Employee successfully deleted.');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        try {
            $empId = \Illuminate\Support\Facades\Crypt::decrypt($id);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', __('Employee not avaliable'));
        }

        $employee = Employee::with(['shift', 'reportingManager', 'hod', 'management'])->find($empId);
        if ($employee === null) {
            return redirect()->back()->with('error', __('Employee not avaliable'));
        }

        if (\Auth::user()->type === 'employee') {
            if ((int) $employee->user_id !== (int) \Auth::id()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } elseif (! \Auth::user()->can('Show Employee')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
        $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);
        $uploadedDocuments = $this->employeeUploadedDocuments($employee);

        return view('employee.show', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents', 'uploadedDocuments'));
    }

    /**
     * Documents from Manage Document that belong to this employee (assigned or uploaded by them).
     */
    protected function employeeUploadedDocuments(Employee $employee)
    {
        if (empty($employee->user_id)) {
            return collect();
        }

        $userId = (int) $employee->user_id;

        return DucumentUpload::where(function ($q) use ($userId) {
                $q->where('assigned_user_id', $userId)
                    ->orWhere('uploaded_by', $userId);
            })
            ->latest()
            ->get();
    }

    function employeeNumber()
    {
        $latest = Employee::where('created_by', '=', \Auth::user()->creatorId())->latest('id')->first();
        if (!$latest) {
            return 1;
        }

        return ((int) $latest->employee_id) + 1;
    }

    protected function buildStructuredJson(array $payload): ?string
    {
        $filtered = array_filter($payload, function ($value) {
            return !(is_null($value) || $value === '');
        });

        if (empty($filtered)) {
            return null;
        }

        return json_encode($filtered, JSON_UNESCAPED_UNICODE);
    }

    public function export()
    {
        $name = 'employee_' . date('Y-m-d i:h:s');
        $data = Excel::download(new EmployeesExport(), $name . '.xlsx');


        return $data;
    }

    // public function importFile()
    // {
    //     return view('employee.import');
    // }

    // public function import(Request $request)
    // {
    //     $rules = [
    //         'file' => 'required|mimes:csv,txt',
    //     ];

    //     $validator = \Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $messages = $validator->getMessageBag();

    //         return redirect()->back()->with('error', $messages->first());
    //     }

    //     try {
    //         $employees = (new EmployeesImport())->toArray(request()->file('file'))[0];
    //         $totalCustomer = count($employees) - 1;
    //         $errorArray    = [];

    //         for ($i = 1; $i <= count($employees) - 1; $i++) {

    //             $employee = $employees[$i];
    //             $employeeByEmail = Employee::where('email', $employee[5])->first();
    //             $userByEmail = User::where('email', $employee[5])->first();

    //             if (!empty($employeeByEmail) && !empty($userByEmail)) {
    //                 $employeeData = $employeeByEmail;
    //             } else {
    //                 $user = new User();
    //                 $user->name = $employee[0];
    //                 $user->email = $employee[5];
    //                 $user->password = Hash::make($employee[6]);
    //                 $user->type = 'employee';
    //                 $user->lang = 'en';
    //                 $user->created_by = \Auth::user()->creatorId();
    //                 $user->email_verified_at = date("Y-m-d H:i:s");
    //                 $user->save();
    //                 $user->assignRole('Employee');
    //                 $employeeData = new Employee();
    //                 $employeeData->employee_id      = $this->employeeNumber();
    //                 $employeeData->user_id             = $user->id;
    //             }


    //             $employeeData->name                = $employee[0];
    //             $employeeData->dob                 = $employee[1];
    //             $employeeData->gender              = $employee[2];
    //             $employeeData->phone               = $employee[3];
    //             $employeeData->address             = $employee[4];
    //             $employeeData->email               = $employee[5];
    //             $employeeData->password            = \Hash::make($employee[6]);
    //             $employeeData->employee_id         = $this->employeeNumber();
    //             $employeeData->branch_id           = $employee[8];
    //             $employeeData->department_id       = $employee[9];
    //             $employeeData->designation_id      = $employee[10];
    //             $employeeData->company_doj         = $employee[11];
    //             $employeeData->account_holder_name = $employee[12];
    //             $employeeData->account_number      = $employee[13];
    //             $employeeData->bank_name           = $employee[14];
    //             $employeeData->bank_identifier_code = $employee[15];
    //             $employeeData->branch_location     = $employee[16];
    //             $employeeData->tax_payer_id        = $employee[17];
    //             $employeeData->created_by          = \Auth::user()->creatorId();

    //             if (empty($employeeData)) {

    //                 $errorArray[] = $employeeData;
    //             } else {

    //                 $employeeData->save();
    //             }
    //         }
    //     } catch (\Throwable $th) {
    //         return redirect()->back()->with('error', __('Something went wrong please try again.'));
    //     }

    //     $errorRecord = [];

    //     if (empty($errorArray)) {
    //         $data['status'] = 'success';
    //         $data['msg']    = __('Record successfully imported');
    //     } else {
    //         $data['status'] = 'error';
    //         $data['msg']    = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalCustomer . ' ' . 'record');


    //         foreach ($errorArray as $errorData) {

    //             $errorRecord[] = implode(',', $errorData);
    //         }

    //         \Session::put('errorArray', $errorRecord);
    //     }

    //     return redirect()->back()->with($data['status'], $data['msg']);
    // }

    public function importFile()
    {
        return view('employee.import');
    }

    public function fileImport(Request $request)
    {
        session_start();

        $error = '';

        $html = '';

        if ($request->hasFile('file') && $request->file->getClientOriginalName() != '') {
            $file_array = explode(".", $request->file->getClientOriginalName());

            $extension = end($file_array);
            if ($extension == 'csv') {
                $file_data = fopen($request->file->getRealPath(), 'r');

                $file_header = fgetcsv($file_data);
                $html .= '<table class="table table-bordered"><tr>';

                for ($count = 0; $count < count($file_header); $count++) {
                    $html .= '
                            <th>
                                <select name="set_column_data" class="form-control set_column_data" data-column_number="' . $count . '">
                                <option value="">Set Count Data</option>
                                <option value="name">Name</option>
                                <option value="dob">DOB</option>
                                <option value="gender">Gender</option>
                                <option value="phone">Phone</option>
                                <option value="address">Address</option>
                                <option value="email">Email</option>
                                <option value="password">Password</option>
                                <option value="company_doj">Company Doj</option>
                                <option value="account_holder_name">Account Holder Name</option>
                                <option value="account_number">Account Number</option>
                                <option value="bank_name">Bank Name</option>
                                <option value="bank_identifier_code">Bank Identifier Code</option>
                                <option value="branch_location">Branch Location</option>
                                <option value="tax_payer_id">Tax Payer Id</option>
                                </select>
                            </th>
                            ';
                }
                $html .= '
                            <th>
                                    <select name="set_column_data branch_name" class="form-control set_column_data branch-name" data-column_number="' . $count . '">
                                        <option value="branch">Branch</option>
                                    </select>
                            </th>
                            ';
                $html .= '
                            <th>
                                    <select name="set_column_data department_name" class="form-control set_column_data department-name" data-column_number="' . $count . '">
                                        <option value="department">Department</option>
                                    </select>
                            </th>
                            ';
                $html .= '
                            <th>
                                    <select name="set_column_data designation_name" class="form-control set_column_data designation-name" data-column_number="' . $count . '">
                                        <option value="designation">Designation</option>
                                    </select>
                            </th>
                            ';
                $html .= '</tr>';
                $limit = 0;
                $temp_data = [];
                while (($row = fgetcsv($file_data)) !== false) {
                    $limit++;

                    $html .= '<tr>';

                    for ($count = 0; $count < count($row); $count++) {
                        $html .= '<td>' . $row[$count] . '</td>';
                    }

                    $html .= '<td>
                                <select name="branch_name" class="form-control branch-name-value" id="branch_name" required>;';
                    $branchs = Branch::where('created_by', \Auth::user()->id)->pluck('name', 'id');
                    foreach ($branchs as $key => $branch) {
                        $html .= ' <option value="' . $key . '">' . $branch . '</option>';
                    }
                    $html .= '  </select>
                            </td>';

                    $html .= '<td>
                                <select name="department_name" class="form-control department-name-value" id="department_name" required>;';
                    $departments = Department::where('created_by', \Auth::user()->id)->pluck('name', 'id');
                    foreach ($departments as $key => $department) {
                        $html .= ' <option value="' . $key . '">' . $department . '</option>';
                    }
                    $html .= '  </select>
                            </td>';

                    $html .= '<td>
                                <select name="designation_name" class="form-control designation-name-value" id="designation_name" required>;';
                    $designations = Designation::where('created_by', \Auth::user()->id)->pluck('name', 'id');
                    foreach ($designations as $key => $designation) {
                        $html .= ' <option value="' . $key . '">' . $designation . '</option>';
                    }
                    $html .= '  </select>
                            </td>';

                    $html .= '</tr>';

                    $temp_data[] = $row;
                }
                $_SESSION['file_data'] = $temp_data;
            } else {
                $error = 'Only <b>.csv</b> file allowed';
            }
        } else {

            $error = 'Please Select CSV File';
        }
        $output = array(
            'error' => $error,
            'output' => $html,
        );

        return json_encode($output);
    }

    public function fileImportModal()
    {
        return view('employee.import_modal');
    }

    public function employeeImportdata(Request $request)
    {
        session_start();
        $html = '<h3 class="text-danger text-center">Below data is not inserted</h3></br>';
        $flag = 0;
        $html .= '<table class="table table-bordered"><tr>';
        try {
            $file_data = $_SESSION['file_data'];

            unset($_SESSION['file_data']);
        } catch (\Throwable $th) {
            $html = '<h3 class="text-danger text-center">Something went wrong, Please try again</h3></br>';
            return response()->json([
                'html' => true,
                'response' => $html,
            ]);
        }

        $user = Auth::user();
        foreach ($file_data as $key => $row) {
            $employees = Employee::where('created_by', \Auth::user()->creatorId())->Where('email', 'like', $row[$request->email])->get();
            $branch = Branch::find($request->branch[$key]);
            $department = Department::find($request->department[$key]);
            $designation = Designation::find($request->designation[$key]);

            if ($employees->isEmpty()) {

                try {
                    $user = User::create(
                        [
                            'name' => $row[$request->name],
                            'email' => $row[$request->email],
                            'password' => Hash::make($row[$request->password]),
                            'email_verified_at' => date('Y-m-d h:i:s'),
                            'type' => 'employee',
                            'lang' => 'en',
                            'created_by' => \Auth::user()->creatorId(),
                        ]
                    );
                    $user->assignRole('Employee');
                    Employee::create([
                        'name' => $row[$request->name],
                        'user_id' => $user->id,
                        'dob' => $row[$request->dob],
                        'gender' => $row[$request->gender],
                        'phone' => $row[$request->phone],
                        'address' => $row[$request->address],
                        'email' => $row[$request->email],
                        'password' => Hash::make($row[$request->password]),
                        'employee_id' => $this->employeeNumber(),
                        'branch_id' => !empty($branch) ? $branch->id : 0,
                        'department_id' => !empty($department) ? $department->id : 0,
                        'designation_id' => !empty($designation) ? $designation->id : 0,
                        'company_doj' => $row[$request->company_doj],
                        'account_holder_name' => $row[$request->account_holder_name],
                        'account_number' => $row[$request->account_number],
                        'bank_name' => $row[$request->bank_name],
                        'bank_identifier_code' => $row[$request->bank_identifier_code],
                        'branch_location' => $row[$request->branch_location],
                        'tax_payer_id' => $row[$request->tax_payer_id],
                        'created_by' => \Auth::user()->creatorId(),
                    ]);
                } catch (\Exception $e) {
                    $flag = 1;
                    $html .= '<tr>';

                    $html .= '<td>' . (isset($row[$request->name]) ? $row[$request->name] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->dob]) ? $row[$request->dob] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->gender]) ? $row[$request->gender] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->phone]) ? $row[$request->phone] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->address]) ? $row[$request->address] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->email]) ? $row[$request->email] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->password]) ? $row[$request->password] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->company_doj]) ? $row[$request->company_doj] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->account_holder_name]) ? $row[$request->account_holder_name] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->account_number]) ? $row[$request->account_number] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->bank_name]) ? $row[$request->bank_name] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->bank_identifier_code]) ? $row[$request->bank_identifier_code] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request->account_holder_name]) ? $row[$request->account_holder_name] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$branch->id]) ? $row[$branch->id] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$department->id]) ? $row[$department->id] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$designation->id]) ? $row[$designation->id] : '-') . '</td>';

                    $html .= '</tr>';
                }
            } else {
                $flag = 1;
                $html .= '<tr>';

                $html .= '<td>' . (isset($row[$request->name]) ? $row[$request->name] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->dob]) ? $row[$request->dob] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->gender]) ? $row[$request->gender] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->phone]) ? $row[$request->phone] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->address]) ? $row[$request->address] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->email]) ? $row[$request->email] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->password]) ? $row[$request->password] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->company_doj]) ? $row[$request->company_doj] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->account_holder_name]) ? $row[$request->account_holder_name] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->account_number]) ? $row[$request->account_number] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->bank_name]) ? $row[$request->bank_name] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->bank_identifier_code]) ? $row[$request->bank_identifier_code] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request->account_holder_name]) ? $row[$request->account_holder_name] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$branch->id]) ? $row[$branch->id] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$department->id]) ? $row[$department->id] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$designation->id]) ? $row[$designation->id] : '-') . '</td>';

                $html .= '</tr>';
            }
        }

        $html .= '
                        </table>
                        <br />
                        ';
        if ($flag == 1) {

            return response()->json([
                'html' => true,
                'response' => $html,
            ]);
        } else {
            return response()->json([
                'html' => false,
                'response' => 'Data Imported Successfully',
            ]);
        }
    }

    public function profile(Request $request)
    {
        if (\Auth::user()->can('Manage Employee Profile')) {
            $employees = Employee::where('created_by', \Auth::user()->creatorId())->with(['designation', 'user']);
            if (!empty($request->branch_id)) {
                $employees->where('branch_id', $request->branch_id);
            }
            if (!empty($request->department_id)) {
                $employees->where('department_id', $request->department_id);
            }
            if (!empty($request->designation_id)) {
                $employees->where('designation_id', $request->designation_id);
            }
            $employees = $employees->get();

            $brances = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('employee.profile', compact('employees', 'departments', 'designations', 'brances'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function profileShow($id)
    {
        if (\Auth::user()->can('Show Employee Profile')) {
            $empId        = Crypt::decrypt($id);
            $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employee     = Employee::find($empId);
            if ($employee == null) {
                $employee     = Employee::where('user_id', $empId)->first();
            }

            $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);
            $uploadedDocuments = $this->employeeUploadedDocuments($employee);

            return view('employee.show', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents', 'uploadedDocuments'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function lastLogin(Request $request)
    {
        $users = User::where('created_by', \Auth::user()->creatorId())->get();

        $time = date_create($request->month);
        $firstDayofMOnth = (date_format($time, 'Y-m-d'));
        $lastDayofMonth =    \Carbon\Carbon::parse($request->month)->endOfMonth()->toDateString();
        $objUser = \Auth::user();

        $usersList = User::where('created_by', '=', $objUser->creatorId())
            ->whereNotIn('type', ['super admin', 'company'])->get()->pluck('name', 'id');
        $usersList->prepend('All', '');
        if ($request->month == null) {
            $userdetails = DB::table('login_details')
                ->join('users', 'login_details.user_id', '=', 'users.id')
                ->select(DB::raw('login_details.*, users.id as user_id , users.name as user_name , users.email as user_email ,users.type as user_type'))
                ->where(['login_details.created_by' => \Auth::user()->creatorId()])
                ->whereMonth('date', date('m'))->whereYear('date', date('Y'));
        } else {
            $userdetails = DB::table('login_details')
                ->join('users', 'login_details.user_id', '=', 'users.id')
                ->select(DB::raw('login_details.*, users.id as user_id , users.name as user_name , users.email as user_email ,users.type as user_type'))
                ->where(['login_details.created_by' => \Auth::user()->creatorId()]);
        }
        if (!empty($request->month)) {
            $userdetails->where('date', '>=', $firstDayofMOnth);
            $userdetails->where('date', '<=', $lastDayofMonth);
        }
        if (!empty($request->employee)) {
            $userdetails->where(['user_id'  => $request->employee]);
        }
        $userdetails = $userdetails->get();

        return view('employee.lastLogin', compact('users', 'usersList', 'userdetails'));
    }

    public function employeeJson(Request $request)
    {
        $employees = Employee::where('branch_id', $request->branch)->get()->pluck('name', 'id')->toArray();

        return response()->json($employees);
    }

    public function joiningletterPdf($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $joiningletter = JoiningLetter::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
        $date = date('Y-m-d');
        $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
        $settings = \App\Models\Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'app_name' => env('APP_NAME'),
            'employee_name' => $employees->name,
            'address' => !empty($employees->address) ? $employees->address : '',
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'start_date' => !empty($employees->company_doj) ? $employees->company_doj : '',
            'branch' => !empty($employees->Branch->name) ? $employees->Branch->name : '',
            'start_time' => !empty($settings['company_start_time']) ? $settings['company_start_time'] : '',
            'end_time' => !empty($settings['company_end_time']) ? $settings['company_end_time'] : '',
            'total_hours' => $result,
        ];

        $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
        return view('employee.template.joiningletterpdf', compact('joiningletter', 'employees'));
    }
    public function joiningletterDoc($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $joiningletter = JoiningLetter::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
        $date = date('Y-m-d');
        $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
        $settings = \App\Models\Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);

        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),

            'app_name' => env('APP_NAME'),
            'employee_name' => $employees->name,
            'address' => !empty($employees->address) ? $employees->address : '',
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'start_date' => !empty($employees->company_doj) ? $employees->company_doj : '',
            'branch' => !empty($employees->Branch->name) ? $employees->Branch->name : '',
            'start_time' => !empty($settings['company_start_time']) ? $settings['company_start_time'] : '',
            'end_time' => !empty($settings['company_end_time']) ? $settings['company_end_time'] : '',
            'total_hours' => $result,

        ];
        $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
        return view('employee.template.joiningletterdocx', compact('joiningletter', 'employees'));
    }

    public function ExpCertificatePdf($id)
    {
        $currantLang = \Cookie::get('LANGUAGE');
        if (!isset($currantLang)) {
            $currantLang = 'en';
        }
        $termination = Termination::where('employee_id', $id)->where('created_by', \Auth::user()->creatorId())->first();
        $experience_certificate = ExperienceCertificate::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
        $date = date('Y-m-d');
        $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
        $settings = \App\Models\Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $date1 = date_create($employees->company_doj);
        $date2 = date_create($employees->termination_date);
        $diff  = date_diff($date1, $date2);
        $duration = $diff->format("%a days");

        if (!empty($termination->termination_date)) {

            $obj = [
                'date' =>  \Auth::user()->dateFormat($date),
                'app_name' => env('APP_NAME'),
                'employee_name' => $employees->name,
                'payroll' => !empty($employees->salaryType->name) ? $employees->salaryType->name : '',
                'duration' => $duration,
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',

            ];
        } else {
            return redirect()->back()->with('error', __('Termination date is required.'));
        }


        $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
        return view('employee.template.ExpCertificatepdf', compact('experience_certificate', 'employees'));
    }
    public function ExpCertificateDoc($id)
    {
        $currantLang = \Cookie::get('LANGUAGE');
        if (!isset($currantLang)) {
            $currantLang = 'en';
        }
        $termination = Termination::where('employee_id', $id)->where('created_by', \Auth::user()->creatorId())->first();
        $experience_certificate = ExperienceCertificate::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
        $date = date('Y-m-d');
        $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();;
        $settings = \App\Models\Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $date1 = date_create($employees->company_doj);
        $date2 = date_create($employees->termination_date);
        $diff  = date_diff($date1, $date2);
        $duration = $diff->format("%a days");
        if (!empty($termination->termination_date)) {
            $obj = [
                'date' =>  \Auth::user()->dateFormat($date),
                'app_name' => env('APP_NAME'),
                'employee_name' => $employees->name,
                'payroll' => !empty($employees->salaryType->name) ? $employees->salaryType->name : '',
                'duration' => $duration,
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',

            ];
        } else {
            return redirect()->back()->with('error', __('Termination date is required.'));
        }

        $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
        return view('employee.template.ExpCertificatedocx', compact('experience_certificate', 'employees'));
    }
    public function NocPdf($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $noc_certificate = NOC::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
        $date = date('Y-m-d');
        $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
        $settings = \App\Models\Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);


        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'employee_name' => $employees->name,
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'app_name' => env('APP_NAME'),
        ];

        $noc_certificate->content = NOC::replaceVariable($noc_certificate->content, $obj);
        return view('employee.template.Nocpdf', compact('noc_certificate', 'employees'));
    }
    public function NocDoc($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $noc_certificate = NOC::where('lang', $currantLang)->where('created_by', \Auth::user()->creatorId())->first();
        $date = date('Y-m-d');
        $employees = Employee::where('id', $id)->where('created_by', \Auth::user()->creatorId())->first();
        $settings = \App\Models\Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);


        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'employee_name' => $employees->name,
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'app_name' => env('APP_NAME'),
        ];

        $noc_certificate->content = NOC::replaceVariable($noc_certificate->content, $obj);
        return view('employee.template.Nocdocx', compact('noc_certificate', 'employees'));
    }

    public function getdepartment(Request $request)
    {
        if ($request->branch_id == 0) {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
        }
        return response()->json($departments);
    }

    public function json(Request $request)
    {
        if ($request->department_id == 0) {
            $designations = Designation::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        }
        $designations = Designation::where('department_id', $request->department_id)->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();

        return response()->json($designations);
    }

    public function view($id)
    {
        $users = LoginDetail::find($id);
        return view('employee.user_log', compact('users'));
    }

    public function logindestroy($id)
    {
        $employee = LoginDetail::where('user_id', $id)->delete();

        return redirect()->back()->with('success', 'Employee successfully deleted.');
    }

    public function employeePassword($id)
    {
        $eId        = \Crypt::decrypt($id);

        $user = User::find($eId);

        $employee = User::where('id', $eId)->first();

        return view('employee.reset', compact('user', 'employee'));
    }

    public function employeePasswordReset(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'password' => 'required|confirmed|same:password_confirmation',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }


        $user                 = User::where('id', $id)->first();
        if (! $user) {
            return redirect()->back()->with('error', __('User not found.'));
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'is_login_enable' => 1,
        ])->save();

        $resp = Utility::sendLoginCredentialsEmail($user, $request->password);
        $mailNote = (! empty($resp) && ($resp['is_success'] ?? false) === false && ! empty($resp['error']))
            ? '<br> <span class="text-danger">' . $resp['error'] . '</span>'
            : '';

        return redirect()->route('employee.index')->with(
            'success',
            __('Employee Password successfully updated. Login credentials have been sent to their email.') . $mailNote
        );
    }
}
