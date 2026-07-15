<?php

use App\Http\Controllers\AamarpayController;
use App\Models\Employee;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Storage passthrough — serves files from storage/app/public when symlink missing
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) abort(404);
    return response()->file($fullPath);
})->where('path', '.*')->name('storage.serve');

Route::get('/storage/app/public/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) abort(404);
    return response()->file($fullPath);
})->where('path', '.*');

// Bypass route — render login view directly, skip controller constructor & guest middleware
Route::get('/hrmlogin', function (\Illuminate\Http\Request $request) {
    $installed = storage_path('installed');
    if (!file_exists($installed)) {
        @file_put_contents($installed, '1');
    }
    if (Auth::check()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
    $lang = \App\Models\Utility::getValByName('default_language') ?: 'en';
    \App::setLocale($lang);
    return view('auth.login', compact('lang'));
})->name('hrmlogin');
use App\Http\Controllers\UserController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\IncomeTypeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\AttendanceEmployeeController;
use App\Http\Controllers\ChatGroupController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\AccountListController;
use App\Http\Controllers\AiTemplateController;
use App\Http\Controllers\TimeSheetController;
use App\Http\Controllers\SetSalaryController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\AwardTypeController;
use App\Http\Controllers\TerminationController;
use App\Http\Controllers\TerminationTypeController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\AllowanceController;
use App\Http\Controllers\PaySlipController;
use App\Http\Controllers\ResignationController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\WarningController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PayeesController;
use App\Http\Controllers\PayerController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\TransferBalanceController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\GrievanceController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlanRequestController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\DucumentUploadController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\AppraisalController;
use App\Http\Controllers\GoalTypeController;
use App\Http\Controllers\GoalTrackingController;
use App\Http\Controllers\CompanyPolicyController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\JobCategoryController;
use App\Http\Controllers\JobStageController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\CustomQuestionController;
use App\Http\Controllers\InterviewScheduleController;
use App\Http\Controllers\LandingPageSectionController;
use App\Http\Controllers\PaystackPaymentController;
use App\Http\Controllers\FlutterwavePaymentController;
use App\Http\Controllers\RazorpayPaymentController;
use App\Http\Controllers\PaytmPaymentController;
use App\Http\Controllers\MercadoPaymentController;
use App\Http\Controllers\MolliePaymentController;
use App\Http\Controllers\SkrillPaymentController;
use App\Http\Controllers\CoingatePaymentController;
use App\Http\Controllers\PaymentWallPaymentController;
use App\Http\Controllers\CompetenciesController;
use App\Http\Controllers\PerformanceTypeController;
use App\Http\Controllers\ZoomMeetingController;
use App\Http\Controllers\ContractTypeController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\OtherPaymentController;
use App\Http\Controllers\SaturationDeductionController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\DeductionOptionController;
use App\Http\Controllers\LoanOptionController;
use App\Http\Controllers\AllowanceOptionController;
use App\Http\Controllers\AuthorizeNetController;
use App\Http\Controllers\BankTransferController;
use App\Http\Controllers\BenefitPaymentController;
use App\Http\Controllers\BiometricAttendanceController;
use App\Http\Controllers\ScreenMonitorController;
use App\Http\Controllers\BackgroundScreenshotController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\Api\MobileApiPanelController;
use App\Http\Controllers\CashfreeController;
use App\Http\Controllers\CinetPayController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\FedapayController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\IyziPayController;
use App\Http\Controllers\KhaltiPaymentController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\NepalstePaymnetController;
use App\Http\Controllers\NotificationTemplatesController;
use App\Http\Controllers\OzowController;
use App\Http\Controllers\PaiementProController;
use App\Http\Controllers\PayfastController;
use App\Http\Controllers\PayHereController;
use App\Http\Controllers\PayslipTypeController;
use App\Http\Controllers\PaytabController;
use App\Http\Controllers\PaytrController;
use App\Http\Controllers\ReferralProgramController;
use App\Http\Controllers\SspayController;
use App\Http\Controllers\TapPaymentController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ToyyibpayPaymentController;
use App\Http\Controllers\XenditPaymentController;
use App\Http\Controllers\YooKassaController;
use App\Http\Controllers\PeopleHubController;
use Illuminate\Support\Facades\Artisan;

// use App\Http\Controllers\PlanRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('welcome');
});

// Route::get('/dashboard', function () {
//     return view('dashboard.dashboard');
// })->middleware(['auth'])->name('dashboard');


require __DIR__ . '/auth.php';
require __DIR__ . '/holiday.php';
require __DIR__ . '/holiday_settings.php';

Route::get('/check', [HomeController::class, 'check'])->middleware(
    [
        'auth',
        'XSS',
    ]
);
// Route::get('/password/resets/{lang?}', 'Auth\LoginController@showLinkRequestForm')->name('change.langPass');

Route::get('/', [HomeController::class, 'index'])->name('home')->middleware(['XSS']);

Route::get('/free-demo', [\App\Http\Controllers\FreeDemoController::class, 'show'])->name('free-demo');
Route::post('/free-demo', [\App\Http\Controllers\FreeDemoController::class, 'submit'])->name('free-demo.submit');
Route::get('/demo-inquiries', [\App\Http\Controllers\FreeDemoController::class, 'inquiries'])->name('demo-inquiries')->middleware(['auth', 'XSS']);
Route::post('/demo-inquiries/{id}/status', [\App\Http\Controllers\FreeDemoController::class, 'updateStatus'])->name('demo-inquiries.status')->middleware(['auth', 'XSS']);
Route::post('/demo-inquiries/{id}/send-credentials', [\App\Http\Controllers\FreeDemoController::class, 'sendCredentials'])->name('demo-inquiries.send-credentials')->middleware(['auth', 'XSS']);

Route::get('leave/substitute/{leave}/{token}/{action}', [LeaveController::class, 'substituteAction'])
    ->name('leave.substitute.action')
    ->middleware(['XSS']);

Route::get('career/{id}/{lang}', [JobController::class, 'career'])->name('career');
Route::get('job/requirement/{code}/{lang}', [JobController::class, 'jobRequirement'])->name('job.requirement');
Route::get('job/apply/{code}/{lang}', [JobController::class, 'jobApply'])->name('job.apply');
Route::post('job/apply/data/{code}', [JobController::class, 'jobApplyData'])->name('job.apply.data');
Route::get('terms_and_condition/{id}', [JobController::class, 'TermsAndCondition'])->name('terms-and-conditions');

// cookie consent
Route::any('/cookie-consent', [SettingsController::class, 'CookieConsent'])->name('cookie-consent');

// Public API Documentation (no auth required)
Route::get('/api-docs', [\App\Http\Controllers\Api\MobileApiPanelController::class, 'index'])->name('api-docs');
Route::get('/api-docs/postman-download', function () {
    $path = public_path('HRMS_Mobile_API.postman_collection.json');
    if (!file_exists($path)) { abort(404); }
    return response()->download($path, 'HRMS_Mobile_API.postman_collection.json', [
        'Content-Type' => 'application/json',
    ]);
})->name('api-docs.postman-download');

Route::group(['middleware' => ['verified']], function () {



    Route::get('/dashboard', [HomeController::class, 'index'])->middleware(['auth', 'XSS'])->name('dashboard');
    Route::get('/test-notifications', function() {
        return view('test-notifications');
    })->middleware(['auth', 'XSS'])->name('test.notifications');
    Route::get('/test-grievances', function() {
        return view('grievances.test');
    })->middleware(['auth', 'XSS'])->name('test.grievances');
    Route::get('/setup-grievances', function() {
        return view('migration_runner');
    })->middleware(['auth', 'XSS'])->name('migration.runner');
    Route::post('/run-migrations', [GrievanceController::class, 'runMigrations'])->name('run.migrations');
    Route::post('/api-docs/mobile-status', [MobileApiPanelController::class, 'updateStatus'])->middleware(['auth', 'XSS'])->name('api-docs.mobile-status');
    Route::post('/api-docs/generate-key', [MobileApiPanelController::class, 'generateKey'])->middleware(['auth', 'XSS'])->name('api-docs.generate-key');
    // Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware(
    //     [
    //         'auth',
    //         'XSS',
    //     ]
    // );
    Route::get('/home/getlanguvage', [HomeController::class, 'getlanguvage'])->name('home.getlanguvage');

    Route::group(
        [
            'middleware' => [
                'auth',
                'XSS',
            ],
        ],
        function () {

            Route::resource('settings', SettingsController::class);
            Route::post('email-settings', [SettingsController::class, 'saveEmailSettings'])->name('email.settings');
            Route::post('company-settings', [SettingsController::class, 'saveCompanySettings'])->name('company.settings');
            Route::post('shift-settings', [SettingsController::class, 'storeShiftSettings'])->name('settings.shift.store');
            Route::post('shift-settings/{id}', [SettingsController::class, 'updateShiftSettings'])->name('settings.shift.update');
            Route::delete('shift-settings/{id}', [SettingsController::class, 'destroyShiftSettings'])->name('settings.shift.destroy');
            Route::post('payment-settings', [SettingsController::class, 'savePaymentSettings'])->name('payment.settings');
            Route::post('system-settings', [SettingsController::class, 'saveSystemSettings'])->name('system.settings');

            // Google Calendar
            Route::post('setting/google-calender', [SettingsController::class, 'saveGoogleCalenderSettings'])->name('google.calender.settings')->middleware(['auth', 'XSS']);
            Route::any('event/get_event_data', [EventController::class, 'get_event_data'])->name('event.get_event_data')->middleware(['auth', 'XSS']);
            Route::any('event/export-event', [EventController::class, 'export_event'])->name('event.export-event')->middleware(['auth', 'XSS']);

            // SEO Settings
            Route::post('setting/seo-setting', [SettingsController::class, 'SeoSettings'])->name('seo.settings')->middleware(['auth', 'XSS']);

            // cache Settings
            Route::post('setting/cache-setting', [SettingsController::class, 'CacheSettings'])->name('clear.cache')->middleware(['auth', 'XSS']);

            // cookie consent
            Route::post('cookie-setting', [SettingsController::class, 'saveCookieSettings'])->name('cookie.setting')->middleware(['auth', 'XSS']);


            Route::get('company-setting', [SettingsController::class, 'companyIndex'])->name('company.setting');
            Route::post('company-email-setting/{name?}', [EmailTemplateController::class, 'updateStatus'])->name('company.email.setting');
            // Route::post('company-email-setting/{name}', 'EmailTemplateController@updateStatus')->name('status.email.language')->middleware(['auth']);

            Route::post('pusher-settings', [SettingsController::class, 'savePusherSettings'])->name('pusher.settings');
            Route::post('business-setting', [SettingsController::class, 'saveBusinessSettings'])->name('business.setting');

            Route::post('zoom-settings', [SettingsController::class, 'zoomSetting'])->name('zoom.settings');

            // Route::get('test-mail', [SettingsController::class, 'testMail'])->name('test.mail');
            Route::any('test-mail', [SettingsController::class, 'testMail'])->name('test.mail');
            Route::post('test-mail/send', [SettingsController::class, 'testSendMail'])->name('test.send.mail');

            Route::get('create/ip', [SettingsController::class, 'createIp'])->name('create.ip');
            Route::post('create/ip', [SettingsController::class, 'storeIp'])->name('store.ip');
            Route::get('edit/ip/{id}', [SettingsController::class, 'editIp'])->name('edit.ip');
            Route::post('edit/ip/{id}', [SettingsController::class, 'updateIp'])->name('update.ip');
            Route::delete('destroy/ip/{id}', [SettingsController::class, 'destroyIp'])->name('destroy.ip');

            Route::get('create/webhook', [SettingsController::class, 'createWebhook'])->name('create.webhook');
            Route::post('create/webhook', [SettingsController::class, 'storeWebhook'])->name('store.webhook');
            Route::get('edit/webhook/{id}', [SettingsController::class, 'editWebhook'])->name('edit.webhook');
            Route::post('edit/webhook/{id}', [SettingsController::class, 'updateWebhook'])->name('update.webhook');
            Route::delete('destroy/webhook/{id}', [SettingsController::class, 'destroyWebhook'])->name('destroy.webhook');
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                'XSS',
            ],
        ],
        function () {

            Route::get('/orders', [StripePaymentController::class, 'index'])->name('order.index');
            Route::get('/refund/{id}/{user_id}', [StripePaymentController::class, 'refund'])->name('order.refund');
            Route::get('/stripe/{code}', [StripePaymentController::class, 'stripe'])->name('stripe');
            Route::get('/stripe_request/{code}', [StripePaymentController::class, 'stripe_request'])->name('stripe_request');
            Route::post('/stripe', [StripePaymentController::class, 'stripePost'])->name('stripe.post');
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                'XSS',
            ],
        ],
        function () {
            Route::get('/banktransfer/{code}', [BankTransferController::class, 'BankTransfer'])->name('banktransfer');
            Route::post('/banktransfer', [BankTransferController::class, 'banktransferstore'])->name('banktransfer.post');
        }
    );

    Route::get('order/{id}/action', [BankTransferController::class, 'action'])->name('order.action')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('order/approve/{id}', [BankTransferController::class, 'changeaction'])->name('order.changeaction')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::delete('OrderDestroy/{id}', [PlanController::class, 'OrderDestroy'])->name('order.destroy')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    // Email Templates
    Route::get('email_template_lang/{id}/{lang?}', [EmailTemplateController::class, 'manageEmailLang'])->name('manage.email.language')->middleware(['auth', 'XSS']);
    Route::post('email_template_store/{pid}', [EmailTemplateController::class, 'storeEmailLang'])->name('store.email.language')->middleware(['auth']);
    Route::post('email_template_status/{id}', [EmailTemplateController::class, 'updateStatus'])->name('status.email.language')->middleware(['auth']);

    Route::resource('email_template', EmailTemplateController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('email_template_lang', EmailTemplateLangController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get(
        '/test',

        [SettingsController::class, 'testEmail']
    )->name('test.email')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post(
        '/test/send',
        [SettingsController::class, 'testEmailSend']

    )->name('test.email.send')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    // End

    Route::resource('user', UserController::class)
        ->middleware(
            [
                'auth',
                'XSS',
            ]
        );
    // user log
    Route::get('userlogsView/{id}', [EmployeeController::class, 'view'])->name('userlog.view')->middleware(['auth', 'XSS']);

    Route::post('employee/json', [EmployeeController::class, 'json'])->name('employee.json')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('employee/getdepartment', [EmployeeController::class, 'getdepartment'])->name('employee.getdepartment')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('branch/employee/json', [EmployeeController::class, 'employeeJson'])->name('branch.employee.json')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('employee-profile', [EmployeeController::class, 'profile'])->name('employee.profile')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('show-employee-profile/{id}', [EmployeeController::class, 'profileShow'])->name('show.employee.profile')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('lastlogin', [EmployeeController::class, 'lastLogin'])->name('lastlogin')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('employee/address/countries', [EmployeeController::class, 'getAddressCountries'])->name('employee.address.countries')->middleware(['auth', 'XSS']);
    Route::get('employee/address/states', [EmployeeController::class, 'getAddressStates'])->name('employee.address.states')->middleware(['auth', 'XSS']);
    Route::get('employee/address/cities', [EmployeeController::class, 'getAddressCities'])->name('employee.address.cities')->middleware(['auth', 'XSS']);

    Route::resource('employee', EmployeeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::delete('lastlogin/{id}', [EmployeeController::class, 'logindestroy'])->name('employee.logindestroy')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('department', DepartmentController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('designation', DesignationController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('document', DocumentController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('branch', BranchController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('awardtype', AwardTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('award', AwardController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('termination/{id}/description', [TerminationController::class, 'description'])->name('termination.description');

    Route::resource('termination', TerminationController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('terminationtype', TerminationTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('announcement/getdepartment', [AnnouncementController::class, 'getdepartment'])->name('announcement.getdepartment')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('announcement/getemployee', [AnnouncementController::class, 'getemployee'])->name('announcement.getemployee')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('announcement', AnnouncementController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::get('holiday/calender', [HolidayController::class, 'calender'])->name('holiday.calender')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('holiday', HolidayController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    // Google Calendar
    Route::any('holiday/get_holiday_data', [HolidayController::class, 'get_holiday_data'])->name('holiday.get_holiday_data')->middleware(['auth', 'XSS']);
    // Route::any('holiday/export-event', [HolidayController::class, 'export_event'])->name('holiday.export-holiday')->middleware(['auth', 'XSS']);

    Route::get('employee/salary/{eid}', [SetSalaryController::class, 'employeeBasicSalary'])->name('employee.basic.salary')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('allowances/create/{eid}', [AllowanceController::class, 'allowanceCreate'])->name('allowances.create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('commissions/create/{eid}', [CommissionController::class, 'commissionCreate'])->name('commissions.create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('loans/create/{eid}', [LoanController::class, 'loanCreate'])->name('loans.create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('saturationdeductions/create/{eid}', [SaturationDeductionController::class, 'saturationdeductionCreate'])->name('saturationdeductions.create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('otherpayments/create/{eid}', [OtherPaymentController::class, 'otherpaymentCreate'])->name('otherpayments.create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('overtimes/create/{eid}', [OvertimeController::class, 'overtimeCreate'])->name('overtimes.create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    //payslip

    Route::resource('paysliptype', PayslipTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('allowance', AllowanceController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('commission', CommissionController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('allowanceoption', AllowanceOptionController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('loanoption', LoanOptionController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('deductionoption', DeductionOptionController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('loan', LoanController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('saturationdeduction', SaturationDeductionController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('otherpayment', OtherPaymentController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('overtime', OvertimeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('event/getdepartment', [EventController::class, 'getdepartment'])->name('event.getdepartment')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('event/getemployee', [EventController::class, 'getemployee'])->name('event.getemployee')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('event/data/{id}', [EventController::class, 'showData'])->name('eventsshow');
    Route::resource('event', EventController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );



    Route::get('import/event/file', [EventController::class, 'importFile'])->name('event.file.import');
    Route::post('import/event', [EventController::class, 'import'])->name('event.import');
    Route::get('export/event', [EventController::class, 'export'])->name('event.export');

    Route::post('meeting/getdepartment', [MeetingController::class, 'getdepartment'])->name('meeting.getdepartment')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('meeting/getemployee', [MeetingController::class, 'getemployee'])->name('meeting.getemployee')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('meeting', MeetingController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('calender/meeting', [MeetingController::class, 'calender'])->name('meeting.calender')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::any('/meeting/get_meeting_data', [MeetingController::class, 'get_meeting_data'])->name('meeting.get_meeting_data')->middleware(['auth', 'XSS']);

    Route::post('employee/update/sallary/{id}', [SetSalaryController::class, 'employeeUpdateSalary'])->name('employee.salary.update')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('salary/employeeSalary', [SetSalaryController::class, 'employeeSalary'])->name('employeesalary')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('setsalary', SetSalaryController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('salary-structure', [SalaryStructureController::class, 'index'])->name('salary.structure.index')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::match(['get', 'post'], 'salary-structure/calculate', [SalaryStructureController::class, 'calculate'])->name('salary.structure.calculate')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('salary-structure/component', [SalaryStructureController::class, 'storeComponent'])->name('salary.structure.component.store')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('payroll/pay-schedule', [\App\Http\Controllers\PayrollModuleController::class, 'paySchedule'])->name('payroll.schedule')->middleware(['auth', 'XSS']);
    Route::post('payroll/pay-schedule', [\App\Http\Controllers\PayrollModuleController::class, 'savePaySchedule'])->name('payroll.schedule.save')->middleware(['auth', 'XSS']);
    Route::get('payroll/components', [\App\Http\Controllers\PayrollModuleController::class, 'components'])->name('payroll.components')->middleware(['auth', 'XSS']);
    Route::post('payroll/components', [\App\Http\Controllers\PayrollModuleController::class, 'storeComponent'])->name('payroll.components.store')->middleware(['auth', 'XSS']);
    Route::post('payroll/components/bulk-action', [\App\Http\Controllers\PayrollModuleController::class, 'bulkActionComponents'])->name('payroll.components.bulk')->middleware(['auth', 'XSS']);
    Route::post('payroll/components/seed-defaults', [\App\Http\Controllers\PayrollModuleController::class, 'seedDefaultComponents'])->name('payroll.components.seed')->middleware(['auth', 'XSS']);
    Route::get('payroll/employee-salary', [\App\Http\Controllers\PayrollModuleController::class, 'employeeSalary'])->name('payroll.employee.salary')->middleware(['auth', 'XSS']);
    Route::post('payroll/employee-salary', [\App\Http\Controllers\PayrollModuleController::class, 'saveEmployeeSalary'])->name('payroll.employee.salary.save')->middleware(['auth', 'XSS']);
    Route::get('payroll/employee-salary/{id}/view', [\App\Http\Controllers\PayrollModuleController::class, 'viewSalaryStructure'])->name('payroll.employee.salary.view')->middleware(['auth', 'XSS']);
    Route::post('payroll/employee-salary/{id}/special-allowance', [\App\Http\Controllers\PayrollModuleController::class, 'storeSpecialAllowance'])->name('payroll.employee.salary.special.allowance.store')->middleware(['auth', 'XSS']);
    Route::delete('payroll/employee-salary/{id}/special-allowance/{allowanceId}', [\App\Http\Controllers\PayrollModuleController::class, 'deleteSpecialAllowance'])->name('payroll.employee.salary.special.allowance.delete')->middleware(['auth', 'XSS']);
    Route::post('payroll/employee-salary/{id}/special-deduction', [\App\Http\Controllers\PayrollModuleController::class, 'storeSpecialDeduction'])->name('payroll.employee.salary.special.deduction.store')->middleware(['auth', 'XSS']);
    Route::delete('payroll/employee-salary/{id}/special-deduction/{deductionId}', [\App\Http\Controllers\PayrollModuleController::class, 'deleteSpecialDeduction'])->name('payroll.employee.salary.special.deduction.delete')->middleware(['auth', 'XSS']);

    // Salary Increment
    Route::get('payroll/salary-increment', [\App\Http\Controllers\PayrollModuleController::class, 'salaryIncrement'])->name('payroll.salary.increment')->middleware(['auth', 'XSS']);
    Route::post('payroll/salary-increment', [\App\Http\Controllers\PayrollModuleController::class, 'storeSalaryIncrement'])->name('payroll.salary.increment.store')->middleware(['auth', 'XSS']);
    Route::delete('payroll/salary-increment/{id}', [\App\Http\Controllers\PayrollModuleController::class, 'deleteSalaryIncrement'])->name('payroll.salary.increment.delete')->middleware(['auth', 'XSS']);

    Route::get('payroll/process', [\App\Http\Controllers\PayrollModuleController::class, 'processPayroll'])->name('payroll.process')->middleware(['auth', 'XSS']);
    Route::get('payroll/process/export', [\App\Http\Controllers\PayrollModuleController::class, 'exportProcessPayroll'])->name('payroll.process.export')->middleware(['auth', 'XSS']);
    Route::get('payroll/process/pdf', [\App\Http\Controllers\PayrollModuleController::class, 'pdfSalaryStatement'])->name('payroll.process.pdf')->middleware(['auth', 'XSS']);
    Route::post('payroll/process', [\App\Http\Controllers\PayrollModuleController::class, 'runPayroll'])->name('payroll.process.run')->middleware(['auth', 'XSS']);
    Route::delete('payroll/process/{id}', [\App\Http\Controllers\PayrollModuleController::class, 'deletePayroll'])->name('payroll.process.delete')->middleware(['auth', 'XSS']);
    Route::delete('payroll/process-filtered', [\App\Http\Controllers\PayrollModuleController::class, 'deletePayrollFiltered'])->name('payroll.process.delete.filtered')->middleware(['auth', 'XSS']);
    Route::get('payroll/reimbursements', [\App\Http\Controllers\PayrollModuleController::class, 'reimbursements'])->name('payroll.reimbursements')->middleware(['auth', 'XSS']);
    Route::post('payroll/reimbursements', [\App\Http\Controllers\PayrollModuleController::class, 'storeReimbursement'])->name('payroll.reimbursements.store')->middleware(['auth', 'XSS']);
    Route::post('payroll/reimbursements/{id}/status', [\App\Http\Controllers\PayrollModuleController::class, 'updateReimbursementStatus'])->name('payroll.reimbursements.status')->middleware(['auth', 'XSS']);
    Route::get('payroll/supplementary', [\App\Http\Controllers\PayrollModuleController::class, 'supplementary'])->name('payroll.supplementary')->middleware(['auth', 'XSS']);
    Route::post('payroll/supplementary', [\App\Http\Controllers\PayrollModuleController::class, 'storeSupplementary'])->name('payroll.supplementary.store')->middleware(['auth', 'XSS']);
    Route::delete('payroll/supplementary/{id}', [\App\Http\Controllers\PayrollModuleController::class, 'deleteSupplementary'])->name('payroll.supplementary.delete')->middleware(['auth', 'XSS']);

    Route::get('statutory/dashboard', [\App\Http\Controllers\StatutoryComplianceController::class, 'dashboard'])->name('statutory.dashboard')->middleware(['auth', 'XSS']);
    Route::get('statutory/{code}', [\App\Http\Controllers\StatutoryComplianceController::class, 'componentSettings'])->name('statutory.component.settings')->middleware(['auth', 'XSS']);
    Route::post('statutory/{code}', [\App\Http\Controllers\StatutoryComplianceController::class, 'saveComponentSettings'])->name('statutory.component.save')->middleware(['auth', 'XSS']);
    Route::get('statutory-state-configuration', [\App\Http\Controllers\StatutoryComplianceController::class, 'stateConfiguration'])->name('statutory.states')->middleware(['auth', 'XSS']);
    Route::post('statutory-state-configuration', [\App\Http\Controllers\StatutoryComplianceController::class, 'saveState'])->name('statutory.states.save')->middleware(['auth', 'XSS']);
    Route::get('statutory-employee-config', [\App\Http\Controllers\StatutoryComplianceController::class, 'employeeConfig'])->name('statutory.employee.config')->middleware(['auth', 'XSS']);
    Route::post('statutory-employee-config', [\App\Http\Controllers\StatutoryComplianceController::class, 'saveEmployeeConfig'])->name('statutory.employee.config.save')->middleware(['auth', 'XSS']);

    Route::get('it-declaration', [\App\Http\Controllers\ItDeclarationController::class, 'employeeIndex'])->name('it.declaration.index')->middleware(['auth', 'XSS']);
    Route::get('it-declaration/create', [\App\Http\Controllers\ItDeclarationController::class, 'employeeForm'])->name('it.declaration.create')->middleware(['auth', 'XSS']);
    Route::get('it-declaration/{id}/edit', [\App\Http\Controllers\ItDeclarationController::class, 'employeeForm'])->name('it.declaration.edit')->middleware(['auth', 'XSS']);
    Route::post('it-declaration/store', [\App\Http\Controllers\ItDeclarationController::class, 'saveEmployee'])->name('it.declaration.store')->middleware(['auth', 'XSS']);
    Route::post('it-declaration/{id}/update', [\App\Http\Controllers\ItDeclarationController::class, 'saveEmployee'])->name('it.declaration.update')->middleware(['auth', 'XSS']);
    Route::delete('it-declaration/{id}/delete', [\App\Http\Controllers\ItDeclarationController::class, 'deleteDeclaration'])->name('it.declaration.delete')->middleware(['auth', 'XSS']);
    Route::get('it-declaration-review', [\App\Http\Controllers\ItDeclarationController::class, 'adminIndex'])->name('it.declaration.review.index')->middleware(['auth', 'XSS']);
    Route::get('it-declaration-review/{id}', [\App\Http\Controllers\ItDeclarationController::class, 'adminShow'])->name('it.declaration.review.show')->middleware(['auth', 'XSS']);
    Route::post('it-declaration-review/{id}/action', [\App\Http\Controllers\ItDeclarationController::class, 'adminAction'])->name('it.declaration.review.action')->middleware(['auth', 'XSS']);

    Route::get('payslip/paysalary/{id}/{date}', [PaySlipController::class, 'paysalary'])->name('payslip.paysalary')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('payslip/bulk_pay_create/{date}', [PaySlipController::class, 'bulk_pay_create'])->name('payslip.bulk_pay_create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('payslip/bulkpayment/{date}', [PaySlipController::class, 'bulkpayment'])->name('payslip.bulkpayment')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('payslip/search_json', [PaySlipController::class, 'search_json'])->name('payslip.search_json')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('payslip/employeepayslip', [PaySlipController::class, 'employeepayslip'])->name('payslip.employeepayslip')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('payslip/showemployee/{id}', [PaySlipController::class, 'showemployee'])->name('payslip.showemployee')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('payslip/editemployee/{id}', [PaySlipController::class, 'editemployee'])->name('payslip.editemployee')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('payslip/editemployee/{id}/{month}', [PaySlipController::class, 'updateEmployee'])->name('payslip.updateemployee')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('payslip/pdf/{id}/{m}', [PaySlipController::class, 'pdf'])->name('payslip.pdf')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('payslip/payslipPdf/{id}', [PaySlipController::class, 'payslipPdf'])->name('payslip.payslipPdf')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('payslip/send/{id}/{m}', [PaySlipController::class, 'send'])->name('payslip.send')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('payslip/delete/{id}', [PaySlipController::class, 'destroy'])->name('payslip.delete')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('payslip', PaySlipController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('resignation', ResignationController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('travel', TravelController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('promotion', PromotionController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('transfer', TransferController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('complaint', ComplaintController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    
    // Grievance Management Routes
    // Public anonymous tracking — must be declared before the {id} route so
    // the literal "track" segment doesn't get captured as an id.
    Route::get ('grievances/track',  [GrievanceController::class, 'trackForm'])->name('grievances.track');
    Route::post('grievances/track',  [GrievanceController::class, 'trackLookup'])->name('grievances.track.lookup');

    Route::prefix('grievances')->middleware(['auth', 'XSS'])->group(function () {
        Route::get('/', [GrievanceController::class, 'index'])->name('grievances.index');
        Route::get('/create', [GrievanceController::class, 'create'])->name('grievances.create');
        Route::post('/', [GrievanceController::class, 'store'])->name('grievances.store');
        Route::get('/{id}', [GrievanceController::class, 'show'])->name('grievances.show')->whereNumber('id');
        Route::post('/{id}/status', [GrievanceController::class, 'updateStatus'])->name('grievances.update.status')->whereNumber('id');
        Route::post('/{id}/response', [GrievanceController::class, 'addResponse'])->name('grievances.add.response')->whereNumber('id');
        Route::delete('/{id}', [GrievanceController::class, 'destroy'])->name('grievances.destroy')->whereNumber('id');
        Route::get('/stats', [GrievanceController::class, 'getStats'])->name('grievances.stats');
    });
    Route::resource('warning', WarningController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('profile', [UserController::class, 'profile'])->name('profile')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('edit-profile', [UserController::class, 'editprofile'])->name('update.account')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('accountlist', AccountListController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('accountbalance', [AccountListController::class, 'account_balance'])->name('accountbalance')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::get('leave/{id}/action', [LeaveController::class, 'action'])->name('leave.action')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('leave/changeaction', [LeaveController::class, 'changeaction'])->name('leave.changeaction')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    // Manager leave notification API routes
    Route::get('leave/pending-subordinates', [LeaveController::class, 'getPendingSubordinateLeaves'])->name('leave.pending-subordinates')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('leave/approve-ajax', [LeaveController::class, 'approveLeaveAjax'])->name('leave.approve-ajax')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('leave/substitute/respond', [LeaveController::class, 'substituteRespond'])->name('leave.substitute.respond')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('leave/substitutes', [LeaveController::class, 'substituteEmployees'])->name('leave.substitutes')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('leave/jsoncount', [LeaveController::class, 'jsoncount'])->name('leave.jsoncount')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('leave', LeaveController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('calender/leave', [LeaveController::class, 'calender'])->name('leave.calender')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::any('leave/get_leave_data', [LeaveController::class, 'get_leave_data'])->name('leave.get_leave_data')->middleware(['auth', 'XSS']);

    // Compensatory Leave Routes
    Route::get('leave/claim-compensatory', [LeaveController::class, 'claimCompensatoryLeaveView'])->name('leave.claim.compensatory')->middleware(['auth', 'XSS']);
    Route::post('leave/claim-compensatory', [LeaveController::class, 'storeCompensatoryLeaveClaim'])->name('leave.claim.compensatory.store')->middleware(['auth', 'XSS']);
    Route::get('leave/award-compensatory', [LeaveController::class, 'awardCompensatoryLeaveView'])->name('leave.award.compensatory')->middleware(['auth', 'XSS', 'permission:Manage Leave']);
    Route::post('leave/award-compensatory', [LeaveController::class, 'storeAwardCompensatoryLeave'])->name('leave.award.compensatory.store')->middleware(['auth', 'XSS', 'permission:Manage Leave']);

    Route::get('ticket/{id}/reply', [TicketController::class, 'reply'])->name('ticket.reply')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('ticket/changereply', [TicketController::class, 'changereply'])->name('ticket.changereply')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('ticket', TicketController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('attendanceemployee/bulkattendance', [AttendanceEmployeeController::class, 'bulkAttendance'])->name('attendanceemployee.bulkattendance')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('attendanceemployee/bulkattendance/export', [AttendanceEmployeeController::class, 'bulkAttendanceExport'])->name('attendanceemployee.bulkattendance.export')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('attendanceemployee/bulkattendance/template', [AttendanceEmployeeController::class, 'bulkAttendanceTemplate'])->name('attendanceemployee.bulkattendance.template')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('attendanceemployee/bulkattendance/import', [AttendanceEmployeeController::class, 'bulkAttendanceImport'])->name('attendanceemployee.bulkattendance.import')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('attendanceemployee/bulkattendance', [AttendanceEmployeeController::class, 'bulkAttendanceData'])->name('attendanceemployee.bulkattendances')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('attendanceemployee/attendance', [AttendanceEmployeeController::class, 'attendance'])->name('attendanceemployee.attendance')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('attendanceemployee/swipe-history', [AttendanceEmployeeController::class, 'swipeHistory'])->name('attendanceemployee.swipe-history')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('attendanceemployee/swipe-request', [AttendanceEmployeeController::class, 'submitSwipeRequest'])->name('attendanceemployee.swipe-request')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('attendanceemployee/swipe-request/{id}/update', [AttendanceEmployeeController::class, 'updateSwipeRequest'])->name('attendanceemployee.swipe-request.update')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('attendanceemployee/swipe-request/{id}/process', [AttendanceEmployeeController::class, 'processSwipeRequest'])->name('attendanceemployee.swipe-request.process')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('chat-groups/{groupId?}', [ChatGroupController::class, 'index'])->name('chat-groups.index')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('chat-groups', [ChatGroupController::class, 'store'])->name('chat-groups.store')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('chat-groups/{groupId}/members', [ChatGroupController::class, 'addMembers'])->name('chat-groups.members')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('chat-groups/{groupId}/messages', [ChatGroupController::class, 'sendMessage'])->name('chat-groups.messages')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('chat-groups/{groupId}/voice', [ChatGroupController::class, 'sendVoice'])->name('chat-groups.voice')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('chat-groups/{groupId}/messages-json', [ChatGroupController::class, 'getMessages'])->name('chat-groups.messages-json')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('chat-groups-header-notifications', [ChatGroupController::class, 'headerNotifications'])->name('chat-groups.header-notifications')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('chat-direct-contacts', [ChatGroupController::class, 'directContacts'])->name('chat-direct-contacts')->middleware(
        ['auth', 'XSS']
    );
    Route::get('chat-inline-messages/{userId}', [ChatGroupController::class, 'inlineMessages'])->name('chat-inline-messages')->middleware(['auth', 'XSS']);
    Route::post('chat-inline-send/{userId}', [ChatGroupController::class, 'inlineSend'])->name('chat-inline-send')->middleware(['auth', 'XSS']);
    Route::get('chat-group-inline-messages/{groupId}', [ChatGroupController::class, 'inlineGroupMessages'])->name('chat-group-inline-messages')->middleware(['auth', 'XSS']);
    Route::post('chat-group-inline-send/{groupId}', [ChatGroupController::class, 'inlineGroupSend'])->name('chat-group-inline-send')->middleware(['auth', 'XSS']);

    Route::get('chat-groups-chatbox-favorites', [ChatGroupController::class, 'chatboxFavorites'])->name('chat-groups.chatbox-favorites')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('attendanceemployee', AttendanceEmployeeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('attendance/sync-for-payroll', [AttendanceEmployeeController::class, 'syncForPayroll'])->name('attendance.sync-for-payroll')->middleware(['auth', 'XSS']);
    Route::get('attendance/export-monthly-excel', [AttendanceEmployeeController::class, 'exportMonthlyExcel'])->name('attendance.export-monthly-excel')->middleware(['auth', 'XSS']);
    Route::post('attendance/reapply-policy', [AttendanceEmployeeController::class, 'reapplyAttendancePolicy'])->name('attendance.reapply-policy')->middleware(['auth', 'XSS']);
    // Used by Mark Attendance page (Excel/CSV upload form in attendance/index.blade.php)
    Route::post('attendance/upload-excel', [AttendanceEmployeeController::class, 'uploadExcelAttendance'])->name('attendance.upload-excel')->middleware(['auth', 'XSS']);

    //import attendance
    // Route::get('import/attendance/file', [AttendanceEmployeeController::class, 'importFile'])->name('attendance.file.import');
    // Route::post('import/attendance', [AttendanceEmployeeController::class, 'import'])->name('attendance.import');
    Route::get('import/attendance/file', [AttendanceEmployeeController::class, 'importFile'])->name('attendance.file.import');
    Route::post('attendance/import', [ImportController::class, 'fileImport'])->name('attendance.import');
    Route::get('import/attendance/modal/', [ImportController::class, 'fileImportModal'])->name('attendance.import.modal');
    Route::post('import/attendance', [AttendanceEmployeeController::class, 'attendanceImportdata'])->name('attendance.import.data');

    Route::resource('timesheet', TimeSheetController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('expensetype', ExpenseTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('incometype', IncomeTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('paymenttype', PaymentTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('leavetype', LeaveTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('payees', PayeesController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('payer', PayerController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('deposit', DepositController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('expense', ExpenseController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('transferbalance', TransferBalanceController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::group(
        [
            'middleware' => [
                'auth',
                'XSS',
            ],
        ],
        function () {
            Route::get('change-language/{lang}', [LanguageController::class, 'changeLanquage'])->name('change.language');
            Route::get('manage-language/{lang}', [LanguageController::class, 'manageLanguage'])->name('manage.language');
            Route::post('store-language-data/{lang}', [LanguageController::class, 'storeLanguageData'])->name('store.language.data');
            Route::get('create-language', [LanguageController::class, 'createLanguage'])->name('create.language');
            Route::post('store-language', [LanguageController::class, 'storeLanguage'])->name('store.language');
            Route::delete('/lang/{id}', [LanguageController::class, 'destroyLang'])->name('lang.destroy');
            Route::post('disable-language', [LanguageController::class, 'disableLang'])->name('disablelanguage');
        }
    );

    Route::resource('roles', RoleController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('permissions', PermissionController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('user/{id}/plan', [UserController::class, 'upgradePlan'])->name('plan.upgrade')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('user/{id}/plan/{pid}', [UserController::class, 'activePlan'])->name('plan.active')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('plans', PlanController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('plans/plans-trial/{id}', [PlanController::class, 'PlanTrial'])->name('plans.trial');
    Route::post('plan-disable', [PlanController::class, 'planDisable'])->name('plan.disable')->middleware(['auth', 'XSS']);
    // Route::get('/plan_request/{code}', 'PlanController@plan_request')->name('plan_request')->middleware(
    //     [
    //         'auth',
    //         'XSS',
    //     ]
    // );


    // Route::resource('plan_requests', 'PlanRequestController');

    // Route::get('/plan_requests/update/{id}', 'PlanRequestController@update')->name('plan_request.update')->middleware(
    //     [
    //         'auth',
    //         'XSS',
    //     ]
    // );

    Route::group(
        [
            'middleware' => [
                'auth',
                'XSS',

            ],
        ],
        function () {
            Route::resource('plan_request', PlanRequestController::class);
        }
    );



    // Plan Request Module
    Route::get('plan_request', [PlanRequestController::class, 'index'])->name('plan_request.index')->middleware(['auth', 'XSS',]);
    Route::get('request_frequency/{id}', [PlanRequestController::class, 'requestView'])->name('request.view')->middleware(['auth', 'XSS',]);
    Route::get('request_send/{id}', [PlanRequestController::class, 'userRequest'])->name('send.request')->middleware(['auth', 'XSS',]);
    Route::get('request_response/{id}/{response}', [PlanRequestController::class, 'acceptRequest'])->name('response.request')->middleware(['auth', 'XSS',]);
    Route::get('request_cancel/{id}', [PlanRequestController::class, 'cancelRequest'])->name('request.cancel')->middleware(['auth', 'XSS',]);
    // End Plan Request Module



    Route::post('change-password', [UserController::class, 'updatePassword'])->name('update.password');

    Route::resource('coupons', CouponController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('account-assets', AssetController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('document-upload', DucumentUploadController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('indicator', IndicatorController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('appraisal', AppraisalController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('/check-branch-indicator', [AppraisalController::class, 'checkBranchIndicator'])->name('checkBranchIndicator');
    Route::resource('goaltype', GoalTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('goaltracking', GoalTrackingController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('company-policy', CompanyPolicyController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('notification-templates', NotificationTemplatesController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('notification-templates/{id?}/{lang?}/', [NotificationTemplatesController::class, 'index'])->name('notifications-templates.index')->middleware(['auth', 'XSS']);
    Route::get('notification-templates-lang/{id}/{lang?}', [NotificationTemplatesController::class, 'manageNotificationLang'])->name('manage.notification.language')->middleware(['auth', 'XSS']);

    Route::resource('trainingtype', TrainingTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('trainer', TrainerController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );



    Route::post('training/status', [TrainingController::class, 'updateStatus'])->name('training.status')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::resource('training', TrainingController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('training/getemployee', [TrainingController::class, 'getemployee'])->name('training.getemployee')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('plan-pay-with-paypal', [PaypalController::class, 'planPayWithPaypal'])->name('plan.pay.with.paypal')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('{id}/plan-get-payment-status', [PaypalController::class, 'planGetPaymentStatus'])->name('plan.get.payment.status')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get(
        '/apply-coupon',
        [CouponController::class, 'applyCoupon'],
    )->name('apply.coupon')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::get('report/income-expense', [ReportController::class, 'incomeVsExpense'])->name('report.income-expense')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('report/leave', [ReportController::class, 'leave'])->name('report.leave')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('report/leave/export', [ReportController::class, 'leaveExport'])->name('report.leave.export')->middleware(['auth', 'XSS']);
    Route::get('employee/{id}/leave/{status}/{type}/{month}/{year}', [ReportController::class, 'employeeLeave'])->name('report.employee.leave')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('report/account-statement', [ReportController::class, 'accountStatement'])->name('report.account.statement')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('report/payroll', [ReportController::class, 'payroll'])->name('report.payroll')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('report/monthly/attendance', [ReportController::class, 'monthlyAttendance'])->name('report.monthly.attendance')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('monthly/getdepartment', [ReportController::class, 'getdepartment'])->name('monthly.getdepartment')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('monthly/getemployee', [ReportController::class, 'getemployee'])->name('monthly.getemployee')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('report/attendance/{month}/{branch}/{department}/{employee}', [ReportController::class, 'exportCsv'])->name('report.attendance')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('report/timesheet', [ReportController::class, 'timesheet'])->name('report.timesheet')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    //------------------------------------  Recurtment --------------------------------

    Route::resource('job-category', JobCategoryController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('job-stage', JobStageController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('job-stage/order', [JobStageController::class, 'order'])->name('job.stage.order')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('job', JobController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    // Route::get('career/{id}/{lang}', [JobController::class, 'career'])->name('career');
    // Route::get('job/requirement/{code}/{lang}', [JobController::class, 'jobRequirement'])->name('job.requirement');
    // Route::get('job/apply/{code}/{lang}', [JobController::class, 'jobApply'])->name('job.apply');
    // Route::post('job/apply/data/{code}', [JobController::class, 'jobApplyData'])->name('job.apply.data');


    Route::get('candidates-job-applications', [JobApplicationController::class, 'candidate'])->name('job.application.candidate')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('job-application', JobApplicationController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('job-application/order', [JobApplicationController::class, 'order'])->name('job.application.order')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('job-application/{id}/rating', [JobApplicationController::class, 'rating'])->name('job.application.rating')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::delete('job-application/{id}/archive', [JobApplicationController::class, 'archive'])->name('job.application.archive')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::post('job-application/{id}/skill/store', [JobApplicationController::class, 'addSkill'])->name('job.application.skill.store')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('job-application/{id}/note/store', [JobApplicationController::class, 'addNote'])->name('job.application.note.store')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::delete('job-application/{id}/note/destroy', [JobApplicationController::class, 'destroyNote'])->name('job.application.note.destroy')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('job-application/getByJob', [JobApplicationController::class, 'getByJob'])->name('get.job.application')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::get('job-onboard', [JobApplicationController::class, 'jobOnBoard'])->name('job.on.board')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('job-onboard/create/{id}', [JobApplicationController::class, 'jobBoardCreate'])->name('job.on.board.create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('job-onboard/store/{id}', [JobApplicationController::class, 'jobBoardStore'])->name('job.on.board.store')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('job-onboard/edit/{id}', [JobApplicationController::class, 'jobBoardEdit'])->name('job.on.board.edit')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('job-onboard/update/{id}', [JobApplicationController::class, 'jobBoardUpdate'])->name('job.on.board.update')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::delete('job-onboard/delete/{id}', [JobApplicationController::class, 'jobBoardDelete'])->name('job.on.board.delete')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::get('job-onboard/convert/{id}', [JobApplicationController::class, 'jobBoardConvert'])->name('job.on.board.convert')->middleware(
        [
            'auth',
            'XSS',
        ]
    );
    Route::post('job-onboard/convert/{id}', [JobApplicationController::class, 'jobBoardConvertData'])->name('job.on.board.converts')->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::post('job-application/stage/change', [JobApplicationController::class, 'stageChange'])->name('job.application.stage.change')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('custom-question', CustomQuestionController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );


    Route::resource('interview-schedule', InterviewScheduleController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::get('interview-schedule/create/{id?}', [InterviewScheduleController::class, 'create'])->name('interview-schedules.create')->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::any('/interview-schedule/get_interview-schedule_data', [InterviewScheduleController::class, 'get_interview_schedule_data'])->name('interview-schedule.get_interview-schedule_data')->middleware(['auth', 'XSS']);

    // Post-interview feedback / status / rating
    Route::post('/interview-schedule/{interviewSchedule}/feedback', [InterviewScheduleController::class, 'recordFeedback'])->name('interview-schedule.feedback')->middleware(['auth', 'XSS']);


    //================================= Custom Landing Page ====================================//

    // Route::get('/landingpage', 'LandingPageSectionController@index')->name('custom_landing_page.index')->middleware(['auth', 'XSS']);
    Route::get('/LandingPage/show/{id}', [LandingPageSectionController::class, 'show']);
    Route::post('/LandingPage/setConetent', [LandingPageSectionController::class, 'setConetent'])->middleware(['auth', 'XSS']);
    Route::get('/get_landing_page_section/{name}', function ($name) {
        $plans = \DB::table('plans')->get();

        return view('custom_landing_page.' . $name, compact('plans'));
    });
    Route::post('/LandingPage/removeSection/{id}', [LandingPageSectionController::class, 'removeSection'])->middleware(['auth', 'XSS']);
    Route::post('/LandingPage/setOrder', [LandingPageSectionController::class, 'setOrder'])->middleware(['auth', 'XSS']);
    Route::post('/LandingPage/copySection', [LandingPageSectionController::class, 'copySection'])->middleware(['auth', 'XSS']);


    //================================= Payment Gateways  ====================================//

    Route::post('/plan-pay-with-paystack', [PaystackPaymentController::class, 'planPayWithPaystack'])->name('plan.pay.with.paystack')->middleware(['auth', 'XSS']);
    Route::get('/plan/paystack/{pay_id}/{plan_id}', [PaystackPaymentController::class, 'getPaymentStatus'])->name('plan.paystack');

    Route::post('/plan-pay-with-flaterwave', [FlutterwavePaymentController::class, 'planPayWithFlutterwave'])->name('plan.pay.with.flaterwave')->middleware(['auth', 'XSS']);
    Route::get('/plan/flaterwave/{txref}/{plan_id}', [FlutterwavePaymentController::class, 'getPaymentStatus'])->name('plan.flaterwave');

    Route::post('/plan-pay-with-razorpay',  [RazorpayPaymentController::class, 'planPayWithRazorpay'])->name('plan.pay.with.razorpay')->middleware(['auth', 'XSS']);
    Route::get('/plan/razorpay/{txref}/{plan_id}', [RazorpayPaymentController::class, 'getPaymentStatus'])->name('plan.razorpay');

    Route::post('/plan-pay-with-paytm',  [PaytmPaymentController::class, 'planPayWithPaytm'])->name('plan.pay.with.paytm')->middleware(['auth', 'XSS']);
    Route::post('/plan/paytm/{plan}', [PaytmPaymentController::class, 'getPaymentStatus'])->name('plan.paytm');

    Route::post('/plan-pay-with-mercado',  [MercadoPaymentController::class, 'planPayWithMercado'])->name('plan.pay.with.mercado')->middleware(['auth', 'XSS']);
    Route::get('/plan/mercado/{plan}',  [MercadoPaymentController::class, 'getPaymentStatus'])->name('plan.mercado');

    Route::post('/plan-pay-with-mollie',  [MolliePaymentController::class, 'planPayWithMollie'])->name('plan.pay.with.mollie')->middleware(['auth', 'XSS']);
    Route::get('/plan/mollie/{plan}', [MolliePaymentController::class, 'getPaymentStatus'])->name('plan.mollie');

    Route::post('/plan-pay-with-skrill', [SkrillPaymentController::class, 'planPayWithSkrill'])->name('plan.pay.with.skrill')->middleware(['auth', 'XSS']);
    Route::get('/plan/skrill/{plan}',  [SkrillPaymentController::class, 'getPaymentStatus'])->name('plan.skrill');

    Route::post('/plan-pay-with-coingate', [CoingatePaymentController::class, 'planPayWithCoingate'])->name('plan.pay.with.coingate')->middleware(['auth', 'XSS']);
    Route::get('/plan/coingate/{plan}', [CoingatePaymentController::class, 'getPaymentStatus'])->name('plan.coingate');

    Route::post('paymentwall', [PaymentWallPaymentController::class, 'paymentwall'])->name('paymentwall');
    Route::post('plan-pay-with-paymentwall/{plan}', [PaymentWallPaymentController::class, 'planPayWithPaymentwall'])->name('plan.pay.with.paymentwall');
    Route::any('/plan/{flag}', [PaymentWallPaymentController::class, 'paymenterror'])->name('callback.error');
    // Route::get('/plans/{flag}', ['as' => 'error.plan.show','uses' => 'PaymentWallPaymentController@planeerror']);

    Route::Post('plan-pay-with-toyyibpay', [ToyyibpayPaymentController::class, 'charge'])->name('plan.pay.with.toyyibpay')->middleware(['auth', 'XSS']);
    Route::get('/plan/toyyibpay/{plan}/{coupon}/{amount}', [ToyyibpayPaymentController::class, 'status'])->name('plan.toyyibpay');

    Route::post('payfast-plan', [PayfastController::class, 'index'])->name('payfast.payment')->middleware(['auth']);
    Route::get('payfast-plan/{success}', [PayfastController::class, 'success'])->name('payfast.payment.success')->middleware(['auth']);

    Route::post('iyzipay/prepare', [IyziPayController::class, 'initiatePayment'])->name('iyzipay.payment.init');
    Route::post('iyzipay/callback/plan/{id}/{amount}/{coupan_code?}', [IyzipayController::class, 'iyzipayCallback'])->name('iyzipay.payment.callback');

    Route::post('/sspay', [SspayController::class, 'SspayPaymentPrepare'])->name('plan.sspaypayment');
    Route::get('sspay-payment-plan/{plan_id}/{amount}/{couponCode}', [SspayController::class, 'SspayPlanGetPayment'])->middleware(['auth'])->name('plan.sspay.callback');

    Route::post('plan-pay-with-paytab', [PaytabController::class, 'planPayWithpaytab'])->middleware(['auth'])->name('plan.pay.with.paytab');
    Route::any('paytab-success/plan', [PaytabController::class, 'PaytabGetPayment'])->middleware(['auth'])->name('plan.paytab.success');

    Route::any('/payment/initiate', [BenefitPaymentController::class, 'initiatePayment'])->name('benefit.initiate');
    Route::any('call_back', [BenefitPaymentController::class, 'call_back'])->name('benefit.call_back');

    Route::post('cashfree/payments/store', [CashfreeController::class, 'cashfreePaymentStore'])->name('cashfree.payment');
    Route::any('cashfree/payments/success', [CashfreeController::class, 'cashfreePaymentSuccess'])->name('cashfreePayment.success');

    Route::post('/aamarpay/payment', [AamarpayController::class, 'pay'])->name('pay.aamarpay.payment')->middleware('auth');
    Route::any('/aamarpay/success/{data}', [AamarpayController::class, 'aamarpaysuccess'])->name('pay.aamarpay.success')->middleware('auth');

    Route::post('/paytr/payment/{plan_id}', [PaytrController::class, 'PlanpayWithPaytr'])->name('plan.pay.with.paytr');
    Route::get('/paytr/sussess/', [PaytrController::class, 'paytrsuccess'])->name('pay.paytr.success');

    Route::post('/plan/yookassa/payment', [YooKassaController::class, 'planPayWithYooKassa'])->name('plan.pay.with.yookassa');
    Route::get('/plan/yookassa/{plan}', [YooKassaController::class, 'planGetYooKassaStatus'])->name('plan.get.yookassa.status');

    Route::any('/midtrans', [MidtransController::class, 'planPayWithMidtrans'])->name('plan.get.midtrans');
    Route::any('/midtrans/callback', [MidtransController::class, 'planGetMidtransStatus'])->name('plan.get.midtrans.status');

    Route::any('/xendit/payment', [XenditPaymentController::class, 'planPayWithXendit'])->name('plan.xendit.payment');
    Route::any('/xendit/payment/status', [XenditPaymentController::class, 'planGetXenditStatus'])->name('plan.xendit.status');

    Route::post('/nepalste/payment', [NepalstePaymnetController::class, 'planPayWithnepalste'])->name('plan.pay.with.nepalste');
    Route::get('nepalste/status/', [NepalstePaymnetController::class, 'planGetNepalsteStatus'])->name('nepalste.status');
    Route::get('nepalste/cancel/', [NepalstePaymnetController::class, 'planGetNepalsteCancel'])->name('nepalste.cancel');

    Route::post('paiementpro/payment', [PaiementProController::class, 'planPayWithpaiementpro'])->name('plan.pay.with.paiementpro');
    Route::get('paiementpro/status', [PaiementProController::class, 'planGetpaiementproStatus'])->name('paiementpro.status');

    Route::post('fedapay/payment', [FedapayController::class, 'planPayWithFedapay'])->name('plan.pay.with.fedapay');
    Route::get('fedapay/status', [FedapayController::class, 'planGetFedapayStatus'])->name('fedapay.status');

    Route::post('payhere/payment', [PayHereController::class, 'planPayWithPayHere'])->name('plan.pay.with.payhere');
    Route::any('payhere/status', [PayHereController::class, 'planGetPayHereStatus'])->name('payhere.status');

    Route::post('plan-pay-with-khalti', [KhaltiPaymentController::class, 'planPayWithKhalti'])->name('plan.pay.with.khalti');
    Route::any('khalti/status', [KhaltiPaymentController::class, 'planGetKhaltiStatus'])->name('plan.get.khalti.success');

    Route::post('plan-pay-with-ozow', [OzowController::class, 'planPayWithOzow'])->name('plan.pay.with.ozow')->middleware(['auth']);
    Route::any('plan-get-ozow-status/{plan_id}', [OzowController::class, 'planGetOzowStatus'])->name('plan.get.ozow.status');

    Route::post('plan-pay-with-authorizenet', [AuthorizeNetController::class, 'planPayWithAuthorizeNet'])->name('plan.pay.with.authorizenet')->middleware(['auth']);
    Route::any('plan-get-authorizenet-status', [AuthorizeNetController::class, 'planGetAuthorizeNetStatus'])->name('plan.get.authorizenet.status');

    Route::post('plan-pay-with-tap', [TapPaymentController::class, 'planPayWithTap'])->name('plan.pay.with.tap');
    Route::any('plan-get-tap-status/{plan_id}', [TapPaymentController::class, 'planGetTapStatus'])->name('plan.get.tap.status');

    Route::post('/plan/company/payment', [CinetPayController::class, 'planPayWithCinetPay'])->name('plan.pay.with.cinetpay');
    Route::post('/plan/company/payment/return', [CinetPayController::class, 'planCinetPayReturn'])->name('plan.cinetpay.return');
    Route::post('/plan/company/payment/notify/', [CinetPayController::class, 'planCinetPayNotify'])->name('plan.cinetpay.notify');

    Route::resource('competencies', CompetenciesController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    Route::resource('performanceType', PerformanceTypeController::class)->middleware(
        [
            'auth',
            'XSS',
        ]
    );

    //employee Import & Export
    // Route::get('import/employee/file', [EmployeeController::class, 'importFile'])->name('employee.file.import');
    // Route::post('import/employee', [EmployeeController::class, 'import'])->name('employee.import');
    Route::get('import/employee/file', [EmployeeController::class, 'importFile'])->name('employee.file.import');
    Route::post('employee/import', [EmployeeController::class, 'fileImport'])->name('employee.import');
    Route::get('import/employee/modal', [EmployeeController::class, 'fileImportModal'])->name('employee.import.modal');
    Route::post('import/employee', [EmployeeController::class, 'employeeImportdata'])->name('employee.import.data');
    Route::get('export/employee', [EmployeeController::class, 'export'])->name('employee.export');

    // Timesheet Import & Export

    // Route::get('import/timesheet/file', [TimeSheetController::class, 'importFile'])->name('timesheet.file.import');
    // Route::post('import/timesheet', [TimeSheetController::class, 'import'])->name('timesheet.import');
    Route::get('import/timesheet/file', [TimeSheetController::class, 'importFile'])->name('timesheet.file.import');
    Route::post('timesheet/import', [ImportController::class, 'fileImport'])->name('timesheet.import');
    Route::get('import/timesheet/modal/', [ImportController::class, 'fileImportModal'])->name('timesheet.import.modal');
    Route::post('import/timesheet', [TimeSheetController::class, 'timesheetImportdata'])->name('timesheet.import.data');
    Route::get('export/timesheet', [TimeSheetController::class, 'export'])->name('timesheet.export');
    Route::get('export/timesheet/excel', [TimeSheetController::class, 'exportExcel'])->name('timesheet.export.excel');
    Route::get('export/timesheet/export', [ReportController::class, 'exportTimeshhetReport'])->name('timesheet.report.export');

    //leave export
    Route::get('export/leave', [LeaveController::class, 'export'])->name('leave.export');
    Route::get('export/leave/report', [ReportController::class, 'LeaveReportExport'])->name('leave.report.export');

    //deposite Export
    Route::get('export/deposite', [DepositController::class, 'export'])->name('deposite.export');

    //expense Export
    Route::get('export/expense', [ExpenseController::class, 'export'])->name('expense.export');

    //Transfer Balance Export
    Route::get('export/transfer-balance', [TransferBalanceController::class, 'export'])->name('transfer_balance.export');

    //Training Import & Export
    Route::get('export/training', [TrainingController::class, 'export'])->name('training.export');

    //Payroll Export
    Route::get('export/payroll/{month}/{branch}/{department}', [ReportController::class, 'PayrollReportExport'])->name('payroll.report.export');

    // payslip export
    Route::post('export/payslip', [PaySlipController::class, 'PayslipExport'])->name('payslip.export');

    //Account Statement Export
    Route::get('export/accountstatement/report', [ReportController::class, 'AccountStatementReportExport'])->name('accountstatement.report.export');

    //Trainer Export
    // Route::get('import/training/file', [TrainerController::class, 'importFile'])->name('trainer.file.import');
    // Route::post('import/training', [TrainerController::class, 'import'])->name('trainer.import');
    Route::get('import/trainer/file', [TrainerController::class, 'importFile'])->name('trainer.file.import');
    Route::post('trainer/import', [TrainerController::class, 'fileImport'])->name('trainer.import');
    Route::get('import/trainer/modal', [TrainerController::class, 'fileImportModal'])->name('trainer.import.modal');
    Route::post('import/trainer', [TrainerController::class, 'trainerImportdata'])->name('trainer.import.data');

    Route::get('export/trainer', [TrainerController::class, 'export'])->name('trainer.export');

    //Holiday Export & Import
    // Route::get('import/holidays/file', [HolidayController::class, 'importFile'])->name('holidays.file.import');
    // Route::post('import/holidays', [HolidayController::class, 'import'])->name('holidays.import');
    Route::get('import/holidays/file', [HolidayController::class, 'importFile'])->name('holidays.file.import');
    Route::post('holidays/import', [ImportController::class, 'fileImport'])->name('holidays.import');
    Route::get('import/holidays/modal/', [ImportController::class, 'fileImportModal'])->name('holidays.import.modal');
    Route::post('import/holidays', [HolidayController::class, 'holidaysImportdata'])->name('holidays.import.data');
    Route::get('export/holidays', [HolidayController::class, 'export'])->name('holidays.export');

    //Asset Import & Export
    // Route::get('import/assets/file', [AssetController::class, 'importFile'])->name('assets.file.import');
    // Route::post('import/assets', [AssetController::class, 'import'])->name('assets.import');
    Route::get('import/assets/file', [AssetController::class, 'importFile'])->name('assets.file.import');
    Route::post('assets/import', [ImportController::class, 'fileImport'])->name('assets.import');
    Route::get('import/assets/modal/', [ImportController::class, 'fileImportModal'])->name('assets.import.modal');
    Route::post('import/assets', [AssetController::class, 'assetsImportdata'])->name('assets.import.data');
    Route::get('export/assets', [AssetController::class, 'export'])->name('assets.export');

    //zoom meeting
    Route::any('zoommeeting/calendar', [ZoomMeetingController::class, 'calender'])->name('zoom_meeting.calender')->middleware(['auth', 'XSS']);
    Route::resource('zoom-meeting', ZoomMeetingController::class)->middleware(['auth', 'XSS']);
    Route::any('zoom-meeting/get_zoom_meeting_data', [ZoomMeetingController::class, 'get_zoom_meeting_data'])->name('zoommeeting.get_zoom_meeting_data')->middleware(['auth', 'XSS']);
    // Route::any('zoom-meeting/export-zoom_meeting', [ZoomMeetingController::class, 'export_zoom_meeting'])->name('zoommeeting.export-zoom_meeting')->middleware(['auth', 'XSS']);

    //slack
    Route::post('setting/slack', [SettingsController::class, 'slack'])->name('slack.setting');

    //telegram
    Route::post('setting/telegram', [SettingsController::class, 'telegram'])->name('telegram.setting');

    //twilio
    Route::post('setting/twilio', [SettingsController::class, 'twilio'])->name('twilio.setting');

    // recaptcha
    Route::post('/recaptcha-settings', [SettingsController::class, 'recaptchaSettingStore'])->name('recaptcha.settings.store')->middleware(['auth', 'XSS']);

    // user reset password
    Route::get('user-login/{id}', [UserController::class, 'LoginManage'])->name('user.login');
    Route::any('user-reset-password/{id}', [UserController::class, 'userPassword'])->name('user.reset');
    Route::post('user-reset-password/{id}', [UserController::class, 'userPasswordReset'])->name('user.password.update');

    // Employee reset password
    Route::any('employee-reset-password/{id}', [EmployeeController::class, 'employeePassword'])->name('employee.reset');
    Route::post('employee-reset-password/{id}', [EmployeeController::class, 'employeePasswordReset'])->name('employee.password.update');

    //contract
    Route::resource('contract_type', ContractTypeController::class)->middleware(['auth', 'XSS']);
    Route::resource('contract', ContractController::class)->middleware(['auth', 'XSS']);
    Route::post('/contract_status_edit/{id}', [ContractController::class, 'contract_status_edit'])->name('contract.status')->middleware(['auth', 'XSS']);
    Route::post('/contract/{id}/file', [ContractController::class, 'fileUpload'])->name('contracts.file.upload')->middleware(['auth', 'XSS']);
    Route::get('/contract/{id}/file/{fid}',  [ContractController::class, 'fileDownload'])->name('contracts.file.download')->middleware(['auth', 'XSS']);
    Route::get('/contract/{id}/file/delete/{fid}', [ContractController::class, 'fileDelete'])->name('contracts.file.delete')->middleware(['auth', 'XSS']);
    Route::post('/contract/{id}/notestore', [ContractController::class, 'noteStore'])->name('contracts.note.store')->middleware(['auth']);
    Route::get('/contract/{id}/note', [ContractController::class, 'noteDestroy'])->name('contracts.note.destroy')->middleware(['auth']);

    Route::post('contract/{id}/description', [ContractController::class, 'descriptionStore'])->name('contracts.description.store')->middleware(['auth']);


    Route::post('/contract/{id}/commentstore', [ContractController::class, 'commentStore'])->name('comment.store');
    Route::get('/contract/{id}/comment', [ContractController::class, 'commentDestroy'])->name('comment.destroy');


    Route::get('/contract/copy/{id}', [ContractController::class, 'copycontract'])->name('contracts.copy')->middleware(['auth', 'XSS']);
    Route::post('/contract/copy/store/{id}', [ContractController::class, 'copycontractstore'])->name('contracts.copystore')->middleware(['auth', 'XSS']);

    Route::get('contract/{id}/get_contract', [ContractController::class, 'printContract'])->name('get.contract');
    Route::get('contract/pdf/{id}', [ContractController::class, 'pdffromcontract'])->name('contract.download.pdf');

    // Route::get('/signature/{id}', 'ContractController@signature')->name('signature')->middleware(['auth','XSS']);
    // Route::post('/signaturestore', 'ContractController@signatureStore')->name('signaturestore')->middleware(['auth','XSS']);

    Route::get('/contract/{id}/mail', [ContractController::class, 'sendmailContract'])->name('send.mail.contract');
    Route::get('/signature/{id}', [ContractController::class, 'signature'])->name('signature')->middleware(['auth', 'XSS']);
    Route::post('/signaturestore', [ContractController::class, 'signatureStore'])->name('signaturestore')->middleware(['auth', 'XSS']);

    //offer Letter
    Route::post('setting/offerlatter/{lang?}', [SettingsController::class, 'offerletterupdate'])->name('offerlatter.update');
    Route::get('setting/offerlatter', [SettingsController::class, 'index'])->name('get.offerlatter.language');
    Route::get('job-onboard/pdf/{id}', [JobApplicationController::class, 'offerletterPdf'])->name('offerlatter.download.pdf');
    Route::get('job-onboard/doc/{id}', [JobApplicationController::class, 'offerletterDoc'])->name('offerlatter.download.doc');

    //joining Letter
    Route::post('setting/joiningletter/{lang?}', [SettingsController::class, 'joiningletterupdate'])->name('joiningletter.update');
    Route::get('setting/joiningletter/', [SettingsController::class, 'index'])->name('get.joiningletter.language');
    Route::get('employee/pdf/{id}', [EmployeeController::class, 'joiningletterPdf'])->name('joiningletter.download.pdf');
    Route::get('employee/doc/{id}', [EmployeeController::class, 'joiningletterDoc'])->name('joininglatter.download.doc');

    //Experience Certificate
    Route::post('setting/exp/{lang?}', [SettingsController::class, 'experienceCertificateupdate'])->name('experiencecertificate.update');
    Route::get('setting/exp', [SettingsController::class, 'index'])->name('get.experiencecertificate.language');
    Route::get('employee/exppdf/{id}', [EmployeeController::class, 'ExpCertificatePdf'])->name('exp.download.pdf');
    Route::get('employee/expdoc/{id}', [EmployeeController::class, 'ExpCertificateDoc'])->name('exp.download.doc');

    //NOC
    Route::post('setting/noc/{lang?}', [SettingsController::class, 'NOCupdate'])->name('noc.update');
    Route::get('setting/noc', [SettingsController::class, 'index'])->name('get.noc.language');
    Route::get('employee/nocpdf/{id}', [EmployeeController::class, 'NocPdf'])->name('noc.download.pdf');
    Route::get('employee/nocdoc/{id}', [EmployeeController::class, 'NocDoc'])->name('noc.download.doc');

    //appricalStar
    Route::post('/appraisals', [AppraisalController::class, 'empByStar'])->name('empByStar')->middleware(['auth', 'XSS']);
    Route::post('/appraisals1', [AppraisalController::class, 'empByStar1'])->name('empByStar1')->middleware(['auth', 'XSS']);
    Route::post('/getemployee', [AppraisalController::class, 'getemployee'])->name('getemployee');

    //storage Setting
    Route::post('storage-settings', [SettingsController::class, 'storageSettingStore'])->name('storage.setting.store')->middleware(['auth', 'XSS']);

    // ChatGT Settings
    Route::post('chatgptkey', [SettingsController::class, 'chatgptkey'])->name('settings.chatgptkey')->middleware(['auth', 'XSS']);
    Route::get('generate/{template_name}', [AiTemplateController::class, 'create'])->name('generate')->middleware(['auth', 'XSS']);
    Route::post('generate/keywords/{id}', [AiTemplateController::class, 'getKeywords'])->name('generate.keywords')->middleware(['auth', 'XSS']);
    Route::post('generate/response', [AiTemplateController::class, 'AiGenerate'])->name('generate.response')->middleware(['auth', 'XSS']);

    // Grammer Check With AI
    Route::get('grammar/{template}', [AiTemplateController::class, 'grammar'])->name('grammar')->middleware(['auth', 'XSS']);
    Route::post('grammar/response', [AiTemplateController::class, 'grammarProcess'])->name('grammar.response')->middleware(['auth', 'XSS']);

    // Login As Company
    Route::get('users/{id}/login-with-company', [UserController::class, 'LoginWithCompany'])->name('login.with.company');
    Route::get('login-with-company/exit', [UserController::class, 'ExitCompany'])->name('exit.company');

    // Adminhub
    Route::get('company-info/{id}', [UserController::class, 'CompnayInfo'])->name('company.info');
    Route::post('user-unable', [UserController::class, 'UserUnable'])->name('user.unable');

    Route::get('referral-program/company', [ReferralProgramController::class, 'companyIndex'])->name('referral-program.company');
    Route::resource('referral-program', ReferralProgramController::class);
    Route::get('request-amount-sent/{id}', [ReferralProgramController::class, 'requestedAmountSent'])->name('request.amount.sent');
    Route::get('request-amount-cancel/{id}', [ReferralProgramController::class, 'requestCancel'])->name('request.amount.cancel');
    Route::post('request-amount-store/{id}', [ReferralProgramController::class, 'requestedAmountStore'])->name('request.amount.store');
    Route::get('request-amount/{id}/{status}', [ReferralProgramController::class, 'requestedAmount'])->name('amount.request');

    // remove biometric code
    // BiometricAttendance
    // Route::post('biometric-setting', [SettingsController::class, 'BiometricSetting'])->name('biometric-settings.store')->middleware(['auth', 'XSS']);
    // Route::resource('/biometric-attendance', BiometricAttendanceController::class)->middleware(
    //     [
    //         'auth',
    //         'XSS',
    //     ]
    // );
    // Route::post('/biometric-attendance/sync/{start_date?}/{end_date?}', [BiometricAttendanceController::class, 'AllSync'])->middleware(['auth'])->name('biometric-attendance.allsync');

    // cache
    Route::get('/config-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');
        return redirect()->back()->with('success', 'Cache Clear Successfully');
    })->name('config.cache');
});

/* ── Screen Monitor (outside verified group – auth handled per-route) ── */
Route::post('screen-monitor/capture', [ScreenMonitorController::class, 'capture'])->name('screen-monitor.capture')->middleware(['auth', 'XSS']);
Route::get('screen-monitor', [ScreenMonitorController::class, 'index'])->name('screen-monitor.index')->middleware(['auth', 'XSS']);
Route::get('screen-monitor/{userId}', [ScreenMonitorController::class, 'show'])->name('screen-monitor.show')->middleware(['auth', 'XSS']);
Route::delete('screen-monitor/{id}', [ScreenMonitorController::class, 'destroy'])->name('screen-monitor.destroy')->middleware(['auth', 'XSS']);

/* ── Background Screenshot Capture (silent, no permission needed) ── */
// XSS middleware required so captured_at uses the company's timezone, matching the index/show pages.
Route::post('bg-screenshot/capture', [BackgroundScreenshotController::class, 'capture'])->name('bg-screenshot.capture')->middleware(['auth', 'XSS']);
Route::post('bg-screenshot/interval', [BackgroundScreenshotController::class, 'updateInterval'])->name('bg-screenshot.interval')->middleware(['auth', 'XSS']);

/* ── Page-visit tracking (which page, how long, which tab) ── */
Route::post('bg-screenshot/visit/start',     [BackgroundScreenshotController::class, 'visitStart'])    ->name('bg-screenshot.visit.start')    ->middleware(['auth', 'XSS']);
Route::post('bg-screenshot/visit/heartbeat', [BackgroundScreenshotController::class, 'visitHeartbeat'])->name('bg-screenshot.visit.heartbeat')->middleware(['auth', 'XSS']);
Route::post('bg-screenshot/visit/end',       [BackgroundScreenshotController::class, 'visitEnd'])      ->name('bg-screenshot.visit.end')      ->middleware(['auth', 'XSS']);
Route::get('payroll/slip/{id}', [\App\Http\Controllers\PayrollModuleController::class, 'salarySlip'])->name('payroll.slip')->middleware(['auth', 'XSS']);
Route::get('payroll/breakdown/{id}', [\App\Http\Controllers\PayrollModuleController::class, 'breakdown'])->name('payroll.breakdown')->middleware(['auth', 'XSS']);
Route::get('payroll/tax-computation/{employeeId}/{fy?}', [\App\Http\Controllers\PayrollModuleController::class, 'taxComputation'])->name('payroll.tax-computation')->middleware(['auth', 'XSS']);
Route::get('payroll/my-payslips', [\App\Http\Controllers\PayrollModuleController::class, 'myPayslips'])->name('payroll.my-payslips')->middleware(['auth', 'XSS']);

Route::get('bg-screenshot', [BackgroundScreenshotController::class, 'index'])->name('bg-screenshot.index')->middleware(['auth', 'XSS']);
Route::get('bg-screenshot/{userId}', [BackgroundScreenshotController::class, 'show'])->name('bg-screenshot.show')->middleware(['auth', 'XSS']);
Route::delete('bg-screenshot/{id}', [BackgroundScreenshotController::class, 'destroy'])->name('bg-screenshot.destroy')->middleware(['auth', 'XSS']);

// ── Recruitment / Manpower Requisition ──────────────────────────────────────
Route::prefix('recruitment')->middleware(['auth', 'XSS'])->group(function () {
    $mr = \App\Http\Controllers\ManpowerRequisitionController::class;

    Route::get('/',                                  [$mr, 'dashboard'])->name('recruitment.dashboard');

    Route::get('requisitions',                       [$mr, 'index'])->name('recruitment.requisitions.index');
    Route::get('requisitions/create',                [$mr, 'create'])->name('recruitment.requisitions.create');
    Route::post('requisitions',                      [$mr, 'store'])->name('recruitment.requisitions.store');
    Route::get('requisitions/{id}',                  [$mr, 'show'])->name('recruitment.requisitions.show');
    Route::delete('requisitions/{id}',               [$mr, 'destroy'])->name('recruitment.requisitions.destroy');

    // Approval flow (HR / company / super-admin only — controller enforces)
    Route::post('requisitions/{id}/approve',         [$mr, 'approve'])->name('recruitment.requisitions.approve');
    Route::post('requisitions/{id}/reject',          [$mr, 'reject'])->name('recruitment.requisitions.reject');

    // AI JD generator
    Route::post('requisitions/{id}/generate-jd',     [$mr, 'generateJd'])->name('recruitment.requisitions.generate-jd');
    Route::put('requisitions/{id}/jd',               [$mr, 'updateJd'])->name('recruitment.requisitions.update-jd');

    // Handoff to existing Job module
    Route::post('requisitions/{id}/create-job',      [$mr, 'createJob'])->name('recruitment.requisitions.create-job');

    // ── Background Verification (BGV) ─────────────────────────────
    $rl = \App\Http\Controllers\RecruitmentLifecycleController::class;
    Route::get('bgv',                              [$rl, 'bgvIndex'])     ->name('recruitment.bgv.index');
    Route::get('bgv/{candidate}',                  [$rl, 'bgvShow'])      ->name('recruitment.bgv.show');
    Route::post('bgv/{candidate}/initiate',        [$rl, 'bgvInitiate'])  ->name('recruitment.bgv.initiate');
    Route::post('bgv/{candidate}/checks',          [$rl, 'bgvAddCustom']) ->name('recruitment.bgv.add');
    Route::post('bgv/checks/{check}',              [$rl, 'bgvUpdate'])    ->name('recruitment.bgv.update');
    Route::delete('bgv/checks/{check}',            [$rl, 'bgvDelete'])    ->name('recruitment.bgv.delete');

    // ── Pre-Onboarding ────────────────────────────────────────────
    Route::get('preonboarding',                    [$rl, 'preonIndex'])   ->name('recruitment.preonboarding.index');
    Route::get('preonboarding/{candidate}',        [$rl, 'preonShow'])    ->name('recruitment.preonboarding.show');
    Route::post('preonboarding/{candidate}/initiate', [$rl, 'preonInitiate'])->name('recruitment.preonboarding.initiate');
    Route::post('preonboarding/{candidate}/items', [$rl, 'preonAddCustom'])->name('recruitment.preonboarding.add');
    Route::post('preonboarding/items/{item}',      [$rl, 'preonUpdate'])  ->name('recruitment.preonboarding.update');
    Route::delete('preonboarding/items/{item}',    [$rl, 'preonDelete'])  ->name('recruitment.preonboarding.delete');

    // ── Probation Reviews ─────────────────────────────────────────
    Route::get('probation',                        [$rl, 'probationIndex'])->name('recruitment.probation.index');
    Route::get('probation/{employee}',             [$rl, 'probationShow']) ->name('recruitment.probation.show');
    Route::post('probation/reviews/{review}',      [$rl, 'probationUpdate'])->name('recruitment.probation.update');

    // ── Assessments / Test Scorecards ─────────────────────────────
    Route::get('assessments',                              [$rl, 'assessmentIndex']) ->name('recruitment.assessments.index');
    Route::get('assessments/{candidate}',                  [$rl, 'assessmentShow'])  ->name('recruitment.assessments.show');
    Route::post('assessments/{candidate}',                 [$rl, 'assessmentStore']) ->name('recruitment.assessments.store');
    Route::post('assessments/edit/{assessment}',           [$rl, 'assessmentUpdate'])->name('recruitment.assessments.update');
    Route::delete('assessments/{assessment}',              [$rl, 'assessmentDelete'])->name('recruitment.assessments.delete');

    // ── Candidate Compare ─────────────────────────────────────────
    Route::get('compare',                                  [$rl, 'compareForm'])     ->name('recruitment.compare');
    Route::post('compare/offer-request',                   [\App\Http\Controllers\OfferManagementController::class, 'requestApprovalFromCompare'])
        ->name('recruitment.compare.offer_request');

    // ── Final Evaluation & Decision (Stage 7) ─────────────────────
    Route::get('decisions',                                [$rl, 'decisionsIndex'])    ->name('recruitment.decisions.index');
    Route::post('decisions/{candidate}',                   [$rl, 'markDecision'])      ->name('recruitment.decisions.mark');
    Route::post('decisions/{candidate}/notes',             [$rl, 'postDecisionNote'])  ->name('recruitment.decisions.notes.post');
    Route::delete('decisions/notes/{note}',                [$rl, 'deleteDecisionNote'])->name('recruitment.decisions.notes.delete');

    // ── Offer Management (Stage 8) ────────────────────────────────
    $om = \App\Http\Controllers\OfferManagementController::class;
    Route::get('offers',                                   [$om, 'index'])             ->name('recruitment.offers.index');
    Route::get('offers/{id}',                              [$om, 'show'])              ->name('recruitment.offers.show');
    Route::post('offers/{id}/compensation',                [$om, 'saveCompensation'])  ->name('recruitment.offers.compensation');
    Route::post('offers/{id}/release',                     [$om, 'release'])           ->name('recruitment.offers.release');
    Route::post('offers/{id}/approve',                     [$om, 'approve'])           ->name('recruitment.offers.approve');
    Route::post('offers/{id}/negotiation',                 [$om, 'negotiation'])       ->name('recruitment.offers.negotiation');
    Route::post('offers/{id}/accept',                      [$om, 'accept'])            ->name('recruitment.offers.accept');
    Route::post('offers/{id}/decline',                     [$om, 'decline'])           ->name('recruitment.offers.decline');
    Route::post('offers/{id}/letter',                      [$om, 'uploadOfferLetter']) ->name('recruitment.offers.letter');

    // ── Analytics (source of hire, funnel, recruiters) ────────────
    Route::get('analytics',                                [$rl, 'analytics'])       ->name('recruitment.analytics');

    // ── Talent Pool (Advanced) ────────────────────────────────────
    $tp = \App\Http\Controllers\TalentPoolController::class;
    Route::get('talent-pool',                              [$tp, 'index'])            ->name('recruitment.talent-pool.index');
    Route::get('talent-pool/match',                        [$tp, 'matchForJob'])      ->name('recruitment.talent-pool.match');
    Route::get('talent-pool/create',                       [$tp, 'create'])           ->name('recruitment.talent-pool.create');
    Route::post('talent-pool',                             [$tp, 'store'])            ->name('recruitment.talent-pool.store');
    Route::get('talent-pool/{id}',                         [$tp, 'show'])             ->name('recruitment.talent-pool.show');
    Route::post('talent-pool/{id}',                        [$tp, 'update'])           ->name('recruitment.talent-pool.update');
    Route::post('talent-pool/{id}/status',                 [$tp, 'updateStatus'])     ->name('recruitment.talent-pool.status');
    Route::delete('talent-pool/{id}',                      [$tp, 'destroy'])          ->name('recruitment.talent-pool.delete');
    Route::post('talent-pool/import/{application}',        [$tp, 'importFromApplication'])->name('recruitment.talent-pool.import');
});

// ── Policy Management Module ────────────────────────────────────────────────
Route::middleware(['auth', 'XSS'])->group(function () {
    $pc = \App\Http\Controllers\PolicyController::class;

    Route::get('policies',                   [$pc, 'index'])->name('policies.index');
    Route::get('policies/create',            [$pc, 'create'])->name('policies.create');
    Route::post('policies',                  [$pc, 'store'])->name('policies.store');
    Route::get('policies/{id}/edit',         [$pc, 'edit'])->name('policies.edit')->whereNumber('id');
    Route::put('policies/{id}',              [$pc, 'update'])->name('policies.update')->whereNumber('id');
    Route::delete('policies/{id}',           [$pc, 'destroy'])->name('policies.destroy')->whereNumber('id');

    // Detail / file / acknowledge
    Route::get('policies/{id}',              [$pc, 'show'])->name('policies.show')->whereNumber('id');
    Route::get('policies/{id}/file',         [$pc, 'file'])->name('policies.file')->whereNumber('id');
    Route::post('policies/{id}/acknowledge', [$pc, 'acknowledge'])->name('policies.acknowledge')->whereNumber('id');
});

// ── Exit Management Module ──────────────────────────────────────────────────
Route::middleware(['auth', 'XSS'])->group(function () {
    $em = \App\Http\Controllers\ExitManagementController::class;

    // List + apply
    Route::get ('exit-management',                       [$em, 'index'])->name('exit-management.index');
    Route::get ('exit-management/create',                [$em, 'create'])->name('exit-management.create');
    Route::post('exit-management',                       [$em, 'store'])->name('exit-management.store');
    Route::get ('exit-management/{id}',                  [$em, 'show'])->name('exit-management.show')->whereNumber('id');
    Route::delete('exit-management/{id}',                [$em, 'destroy'])->name('exit-management.destroy')->whereNumber('id');

    // Manager actions
    Route::post('exit-management/{id}/manager/approve',  [$em, 'managerApprove'])->name('exit-management.manager.approve')->whereNumber('id');
    Route::post('exit-management/{id}/manager/reject',   [$em, 'managerReject'])->name('exit-management.manager.reject')->whereNumber('id');

    // HR actions
    Route::post('exit-management/{id}/hr/approve',       [$em, 'hrApprove'])->name('exit-management.hr.approve')->whereNumber('id');
    Route::post('exit-management/{id}/hr/reject',        [$em, 'hrReject'])->name('exit-management.hr.reject')->whereNumber('id');
    Route::post('exit-management/{id}/complete',         [$em, 'complete'])->name('exit-management.complete')->whereNumber('id');

    // Checklist
    Route::post  ('exit-management/{id}/checklist',                  [$em, 'checklistAdd'])->name('exit-management.checklist.add')->whereNumber('id');
    Route::post  ('exit-management/{id}/checklist/{itemId}/toggle',  [$em, 'checklistToggle'])->name('exit-management.checklist.toggle')->whereNumber('id')->whereNumber('itemId');
    Route::delete('exit-management/{id}/checklist/{itemId}',         [$em, 'checklistDelete'])->name('exit-management.checklist.delete')->whereNumber('id')->whereNumber('itemId');

    // FNF
    Route::post('exit-management/{id}/fnf', [$em, 'fnfSave'])->name('exit-management.fnf.save')->whereNumber('id');
});

// ── Survey Module ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'XSS'])->group(function () {
    $sc = \App\Http\Controllers\SurveyController::class;

    // HR/Admin: survey CRUD
    Route::get('surveys',                   [$sc, 'index'])->name('surveys.index');
    Route::get('surveys/create',            [$sc, 'create'])->name('surveys.create');
    Route::post('surveys',                  [$sc, 'store'])->name('surveys.store');
    Route::get('surveys/{id}/edit',         [$sc, 'edit'])->name('surveys.edit');
    Route::put('surveys/{id}',              [$sc, 'update'])->name('surveys.update');
    Route::delete('surveys/{id}',           [$sc, 'destroy'])->name('surveys.destroy');
    Route::post('surveys/{id}/activate',    [$sc, 'activate'])->name('surveys.activate');
    Route::post('surveys/{id}/close',       [$sc, 'close'])->name('surveys.close');

    // Analytics
    Route::get('surveys/enps',              [$sc, 'enpsReport'])->name('surveys.enps');
    Route::get('surveys/pulse',             [$sc, 'pulseReport'])->name('surveys.pulse');
    Route::get('surveys/team-pulse',        [$sc, 'teamPulse'])->name('surveys.team-pulse');
    Route::get('surveys/alerts',            [$sc, 'alerts'])->name('surveys.alerts');
    Route::post('surveys/alerts/{id}/resolve', [$sc, 'alertResolve'])->name('surveys.alerts.resolve');
    Route::get('surveys/reports/departments', [$sc, 'reportDepartments'])->name('surveys.reports.departments');
    Route::get('surveys/reports/managers',    [$sc, 'reportManagers'])->name('surveys.reports.managers');
    Route::get('surveys/reports/sentiment',   [$sc, 'reportSentiment'])->name('surveys.reports.sentiment');
    Route::post('surveys/sentiment/reanalyze',[$sc, 'sentimentReanalyze'])->name('surveys.sentiment.reanalyze');
    Route::get('surveys/{id}/analytics',    [$sc, 'analytics'])->name('surveys.analytics');
    Route::get('surveys/{id}/export',       [$sc, 'export'])->name('surveys.export');
    Route::get('surveys/{id}/export-pdf',   [$sc, 'exportPdf'])->name('surveys.export.pdf');

    // Question Builder (nested under a survey)
    Route::get('surveys/{id}/questions',          [$sc, 'questions'])->name('surveys.questions');
    Route::post('surveys/{id}/questions',         [$sc, 'questionStore'])->name('surveys.questions.store');
    Route::put('surveys/{id}/questions/{qid}',    [$sc, 'questionUpdate'])->name('surveys.questions.update');
    Route::delete('surveys/{id}/questions/{qid}', [$sc, 'questionDestroy'])->name('surveys.questions.destroy');
    Route::post('surveys/{id}/questions/reorder', [$sc, 'questionReorder'])->name('surveys.questions.reorder');

    // Employee: my surveys (list + fill + history)
    // IMPORTANT: 'my-surveys/history' MUST be declared before 'my-surveys/{id}'
    // — otherwise the literal path "history" matches the {id} placeholder and
    // sends the request to myFill('history') instead of myHistory().
    Route::get('my-surveys',                [$sc, 'mySurveys'])->name('surveys.my');
    Route::get('my-surveys/history',        [$sc, 'myHistory'])->name('surveys.my.history');
    Route::get('my-surveys/{id}',           [$sc, 'myFill'])->name('surveys.my.fill')->whereNumber('id');
    Route::post('my-surveys/{id}',          [$sc, 'mySubmit'])->name('surveys.my.submit')->whereNumber('id');
});

// ── Growth Review Module ────────────────────────────────────────────────────
Route::prefix('growth-review')->middleware(['auth', 'XSS'])->group(function () {
    $c = \App\Http\Controllers\GrowthReviewController::class;

    Route::get('/', [$c, 'dashboard'])->name('growth-review.dashboard');

    // Performance Cycles
    Route::get('cycles', [$c, 'cycles'])->name('growth-review.cycles');
    Route::get('cycles/create', [$c, 'cycleCreate'])->name('growth-review.cycles.create');
    Route::post('cycles', [$c, 'cycleStore'])->name('growth-review.cycles.store');
    Route::get('cycles/{id}/edit', [$c, 'cycleEdit'])->name('growth-review.cycles.edit');
    Route::put('cycles/{id}', [$c, 'cycleUpdate'])->name('growth-review.cycles.update');
    Route::delete('cycles/{id}', [$c, 'cycleDelete'])->name('growth-review.cycles.delete');
    Route::get('cycles/{id}', [$c, 'cycleShow'])->name('growth-review.cycles.show');
    Route::post('cycles/{id}/assign', [$c, 'cycleAssign'])->name('growth-review.cycles.assign');
    Route::delete('cycles/{id}/unassign/{empId}', [$c, 'cycleUnassign'])->name('growth-review.cycles.unassign');
    Route::post('cycles/{id}/activate', [$c, 'cycleActivate'])->name('growth-review.cycles.activate');

    // Missions
    Route::get('missions', [$c, 'missions'])->name('growth-review.missions');
    Route::post('missions', [$c, 'missionStore'])->name('growth-review.missions.store');
    Route::put('missions/{id}', [$c, 'missionUpdate'])->name('growth-review.missions.update');
    Route::post('missions/{id}/approve', [$c, 'missionApprove'])->name('growth-review.missions.approve');
    Route::delete('missions/{id}', [$c, 'missionDelete'])->name('growth-review.missions.delete');
    Route::post('missions/{id}/rate', [$c, 'missionRate'])->name('growth-review.missions.rate');
    Route::post('missions/{id}/upload', [$c, 'missionUpload'])->name('growth-review.missions.upload');
    Route::delete('missions/{id}/document', [$c, 'missionDocDelete'])->name('growth-review.missions.doc-delete');

    // Shoutouts
    Route::get('shoutouts', [$c, 'shoutouts'])->name('growth-review.shoutouts');
    Route::post('shoutouts', [$c, 'shoutoutStore'])->name('growth-review.shoutouts.store');
    Route::delete('shoutouts/{id}', [$c, 'shoutoutDelete'])->name('growth-review.shoutouts.delete');

    // Sync Ups
    Route::get('sync-ups', [$c, 'syncUps'])->name('growth-review.sync-ups');
    Route::post('sync-ups', [$c, 'syncUpStore'])->name('growth-review.sync-ups.store');
    Route::put('sync-ups/{id}', [$c, 'syncUpUpdate'])->name('growth-review.sync-ups.update');
    Route::delete('sync-ups/{id}', [$c, 'syncUpDelete'])->name('growth-review.sync-ups.delete');

    // Comeback Plans
    Route::get('comeback-plans', [$c, 'comebackPlans'])->name('growth-review.comeback');
    Route::post('comeback-plans', [$c, 'comebackStore'])->name('growth-review.comeback.store');
    Route::put('comeback-plans/{id}', [$c, 'comebackUpdate'])->name('growth-review.comeback.update');
    Route::post('comeback-plans/{id}/reviews', [$c, 'comebackReviewStore'])->name('growth-review.comeback.reviews.store');
    Route::delete('comeback-plans/{id}', [$c, 'comebackDelete'])->name('growth-review.comeback.delete');

    // Reviews
    Route::get('reviews', [$c, 'reviews'])->name('growth-review.reviews');
    Route::get('reviews/{cycleId}/{employeeId}/{type}', [$c, 'reviewForm'])->name('growth-review.reviews.form');
    Route::post('reviews', [$c, 'reviewStore'])->name('growth-review.reviews.store');

    // Calibration
    Route::get('calibration', [$c, 'calibration'])->name('growth-review.calibration');
    Route::post('calibration', [$c, 'calibrationUpdate'])->name('growth-review.calibration.update');
    Route::post('calibration/freeze', [$c, 'freezeRatings'])->name('growth-review.calibration.freeze');
    Route::post('calibration/apply-bell-curve', [$c, 'applyBellCurve'])->name('growth-review.calibration.bell-curve');

    // Increments
    Route::get('increments', [$c, 'increments'])->name('growth-review.increments');
    Route::post('increments/generate', [$c, 'generateIncrements'])->name('growth-review.increments.generate');
    Route::post('increments/goal-seek', [$c, 'incrementsGoalSeek'])->name('growth-review.increments.goal-seek');
    Route::post('increments/store-proposal', [$c, 'storeProposal'])->name('growth-review.increments.store-proposal');
    Route::get('increments/export', [$c, 'incrementsExport'])->name('growth-review.increments.export');
    Route::post('increments/{id}/approve', [$c, 'incrementApprove'])->name('growth-review.increments.approve');
    Route::post('increments/{id}/sync-payroll', [$c, 'incrementSyncPayroll'])->name('growth-review.increments.sync-payroll');
    Route::put('increments/{id}', [$c, 'incrementUpdate'])->name('growth-review.increments.update');
    Route::post('increments/{id}/propose', [$c, 'incrementPropose'])->name('growth-review.increments.propose');
    Route::get('increments/{id}/letter', [$c, 'incrementLetter'])->name('growth-review.increments.letter');

    // ── KPI Generator ────────────────────────────────────────────
    $kg = \App\Http\Controllers\GrKpiGeneratorController::class;
    Route::get('kpi-generator',                              [$kg, 'index'])->name('growth-review.kpi-generator.index');
    Route::get('kpi-generator/my-assigned',                  [$kg, 'myAssigned'])->name('growth-review.kpi-generator.my-assigned');
    Route::post('kpi-generator/generate',                    [$kg, 'generate'])->name('growth-review.kpi-generator.generate');
    Route::get('kpi-generator/{id}',                         [$kg, 'show'])->name('growth-review.kpi-generator.show');
    Route::get('kpi-generator/{id}/pdf',                     [$kg, 'pdf'])->name('growth-review.kpi-generator.pdf');
    Route::post('kpi-generator/{id}/target',                 [$kg, 'updateTarget'])->name('growth-review.kpi-generator.update-target');
    Route::post('kpi-generator/{id}/kpi',                    [$kg, 'addKpi'])->name('growth-review.kpi-generator.add-kpi');
    Route::delete('kpi-generator/{id}/kpi',                  [$kg, 'deleteKpi'])->name('growth-review.kpi-generator.delete-kpi');
    Route::post('kpi-generator/{id}/kra',                    [$kg, 'addKra'])->name('growth-review.kpi-generator.add-kra');
    Route::delete('kpi-generator/{id}/kra',                  [$kg, 'deleteKra'])->name('growth-review.kpi-generator.delete-kra');
    Route::post('kpi-generator/{id}/assign',                 [$kg, 'assign'])->name('growth-review.kpi-generator.assign');
    Route::delete('kpi-generator/{id}/assign/{assignmentId}',[$kg, 'unassign'])->name('growth-review.kpi-generator.unassign');
    Route::post('kpi-generator/{id}/kpi-document',           [$kg, 'uploadKpiDocument'])->name('growth-review.kpi-generator.kpi-document.upload');
    Route::delete('kpi-generator/{id}/kpi-document',         [$kg, 'deleteKpiDocument'])->name('growth-review.kpi-generator.kpi-document.delete');
    Route::post('kpi-generator/{id}/submit',                 [$kg, 'submit'])->name('growth-review.kpi-generator.submit');
    Route::post('kpi-generator/{id}/manager-finalize',       [$kg, 'managerFinalize'])->name('growth-review.kpi-generator.manager-finalize');
    Route::post('kpi-generator/{id}/hod-finalize',           [$kg, 'hodFinalize'])->name('growth-review.kpi-generator.hod-finalize');
    Route::delete('kpi-generator/{id}',                      [$kg, 'destroy'])->name('growth-review.kpi-generator.destroy');

    // ── KPI Sub-masters CRUD ─────────────────────────────────────
    $km = \App\Http\Controllers\GrKpiMasterController::class;
    Route::get('masters/{master}',                  [$km, 'index'])->name('growth-review.masters.index');
    Route::post('masters/{master}',                 [$km, 'store'])->name('growth-review.masters.store');
    Route::put('masters/{master}/{id}',             [$km, 'update'])->name('growth-review.masters.update');
    Route::delete('masters/{master}/{id}',          [$km, 'destroy'])->name('growth-review.masters.destroy');
});

// ── People Hub ────────────────────────────────────────────────
Route::prefix('people-hub')->middleware(['auth', 'XSS'])->group(function () {
    Route::get('crew',           [PeopleHubController::class, 'crew'])->name('people-hub.crew');
    Route::get('squad',          [PeopleHubController::class, 'squad'])->name('people-hub.squad');
    Route::get('mentor',         [PeopleHubController::class, 'mentor'])->name('people-hub.mentor');
    Route::post('mentor/assign', [PeopleHubController::class, 'mentorAssign'])->name('people-hub.mentor.assign');
    Route::get('search',         [PeopleHubController::class, 'search'])->name('people-hub.search');
    Route::get('detail/{id}',    [PeopleHubController::class, 'detail'])->name('people-hub.detail');
});

// ══════════════════════════════════════════════════════════════
// Activity Tracker module (Laptop/Desktop monitoring — admin dashboard)
// Module folder: modules/activity-tracker/
// ══════════════════════════════════════════════════════════════
$activityTrackerWebRoutes = base_path('Modules/activity-tracker/laravel/routes/routes.php');
if (!file_exists($activityTrackerWebRoutes)) {
    // Backward compat for older deployments on case-insensitive filesystems.
    $activityTrackerWebRoutes = base_path('modules/activity-tracker/laravel/routes/routes.php');
}
if (file_exists($activityTrackerWebRoutes)) {
    require $activityTrackerWebRoutes;
}
