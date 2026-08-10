<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\AdminAccessMiddleware;
use App\Http\Middleware\ManagerAccessMiddleware;
use App\Http\Middleware\AdminManagerAccessMiddleware;
use App\Http\Middleware\TeamLeadManagerAccessMiddleware;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoomController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ZoomCallLogsController;
use App\Http\Controllers\ZoomMeetingsController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\RetentionController;
use App\Http\Controllers\ReportController;

use App\Http\Controllers\RetentionImportController;

Route::middleware(['web', 'auth', 'can:Can Import Retention'])->group(function () {
    Route::post('/retention-import',      [RetentionImportController::class, 'trigger']);
    Route::get('/retention-import/{id}',  [RetentionImportController::class, 'status']);
});

use App\Http\Controllers\ZoomSyncController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/zoom-sync',      [ZoomSyncController::class, 'trigger']);
    Route::get('/zoom-sync/{id}',  [ZoomSyncController::class, 'status']);
});

/*
Route::controller(DashboardController::class)
    ->middleware(['web', 'auth', 'verified'])
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/dashboard', 'index')->name('dashboard');
    });

Route::controller(DashboardController::class)
    ->middleware(['web', 'auth'])
    ->prefix('dashboard')
    ->as('dashboard.')
    ->group(function () {
        //JSON API
        Route::get('/getreportdata', 'getReportData')->name('getreportdata');
        //JSON API
        Route::get('/getreportdatamanager', 'getReportDataManager')->name('getreportdatamanager');
        //JSON API
        Route::get('/getreportdataforadminmanager', 'getReportDataForAdminManager')->name('getreportdataforadminmanager');
        //JSON API
        Route::get('/getreportdatamanagers', 'getReportDatamanagers')->name('getreportdatamanagers');
        //JSON API
        Route::get('/gettotalsalesdetails', 'getTotalSalesDetails')->name('gettotalsalesdetails');
        //JSON API
        Route::get('/getsalesdetailsbydaterange', 'getSalesDetailsByDateRange')->name('getSalesDetailsByDateRange');
        //JSON API
        Route::get('/get-disposition-call-details', 'getDispositionCallDetails')->name('getDispositionCallDetails');
        //JSON API
        Route::get('/get-total-calls-details', 'getTotalCallsDetails')->name('gettotalcallsdetails');
        //JSON API
        Route::get('/get-disposition-details-sales-executives', 'getSalesExecutiveDispositionDetails')->name('getSalesExecutiveDispositionDetails');
        //JSON API
        Route::get('/get-disposition-call-details-sales-executives', 'getDispositionCallDetailsForSalesExec')->name('getDispositionCallDetailsForSalesExec');
    });

Route::controller(ProfileController::class)
    ->middleware(['web', 'auth'])
    ->prefix('profile')
    ->as('profile.')
    ->group(function () {
        Route::get('/', 'edit')->name('edit');
        //JSON API
        Route::patch('/', 'update')->name('update');
        Route::delete('/', 'destroy')->name('destroy');
    });

Route::controller(UserController::class)
    ->middleware(['web', 'auth'])
    ->prefix('user')
    ->group(function () {
        Route::get('/', 'index')->name('user')->middleware([AdminManagerAccessMiddleware::class]);
    });

Route::controller(UserController::class)
    ->middleware(['web', 'auth'])
    ->prefix('user')
    ->as('user.')
    ->group(function () {
        Route::get('/create', 'create')->name('create')->middleware([AdminAccessMiddleware::class]);
        //No Method Available
        Route::get('/role/get', 'getsome')->name('getsome')->middleware([AdminAccessMiddleware::class]);
        Route::get('/edit/{id}', 'edit')->name('edit')->middleware([AdminAccessMiddleware::class]);
        Route::get('/report/{id}', 'report')->name('report');
        //JSON API
        Route::get('/customreport/{id}', 'customreport')->name('report.customdate');
        //JSON API
        Route::post('/stores', 'store')->name('stores')->middleware([AdminAccessMiddleware::class]);
        //JSON API
        Route::post('/getuserbyrole', 'getUserByRole')->name('getuserbyrole');
    });

Route::controller(UserController::class)
    ->middleware(['web', 'auth'])
    ->as('user.')
    ->group(function () {
        //JSON API
        Route::get('/getusersbyauthorityid', 'getUsersByAuthorityId')->name('getusersbyauthorityid');
        //JSON API
        Route::post('getuserdetails', 'getuserdetails')->name('getuserdetails');
        //JSON API
        Route::post('toggleActiveStatus', 'toggleActiveStatus')->name('toggleActiveStatus')->middleware([AdminAccessMiddleware::class]);
        Route::patch('updateuserdetails', 'update')->name('update')->middleware([AdminAccessMiddleware::class]);
        //JSON API
        Route::patch('/updateuserpassword', 'updateUserPassword')->name('updateuserpassword');
        //JSON API
        Route::patch('/unassingallcompaniesofuser', 'unassignAllCompaniesOfUser')->name('unassignallcompaniesofuser');
        //JSON API
        Route::delete('deleteuser', 'deleteuser')->name('deleteuser')->middleware([AdminAccessMiddleware::class]);
    });

Route::controller(ZoomController::class)
    ->middleware(['web', 'auth'])
    ->prefix('zoom')
    ->as('zoom.')
    ->group(function () {
        Route::post('/', 'createMeeting')->name('index');
        //JSON API
        Route::get('/token', 'tokenGenerate')->name('token');
        //JSON API
        Route::get('/getrecordings', 'getZoomRecordings')->name('getrecordings');
    });

Route::controller(ZoomController::class)
    ->middleware(['web', 'auth'])
    ->group(function () {
        //JSON API
        Route::get('/refreshzoomtoken', 'refreshZoomToken')->name('zoomtoken.refresh');
        //JSON API
        Route::get('/getzoomusercredentials', 'getZoomCredentials')->name('zoom.getusercredentials');
        //JSON API
        Route::patch('/updatezoomdetails', 'updateZoomDetails')->name('zoomdetails.update');
        //JSON API
        Route::patch('/updatezoommeeting', 'updateZoomMeeting')->name('zoommeeting.update');
        Route::patch('/updatezoomusercredentials', 'updateUserZoomCredentials')->name('zoom.updateusercredentials');
        //JSON API
        Route::delete('/deletezoomdetails', 'deleteZoomMeeting')->name('zoommeeting.delete');
    });

Route::controller(AccountController::class)
    ->middleware(['web', 'auth'])
    ->prefix('account')
    ->group(function () {
        //JSON API
        Route::get('/getcompanieslist', 'getCompaniesList')->name('companies.list');
    });

Route::controller(AccountController::class)
    ->middleware(['web', 'auth'])
    ->prefix('account')
    ->as('account.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create')->middleware([AdminManagerAccessMiddleware::class]);
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/view/{id}', 'view')->name('view');
        //JSON API
        Route::post('/update', 'update')->name('update');
        //JSON API
        Route::post('/assign', 'assignCompany')->name('assign');
        //JSON API
        Route::post('/addremark', 'addRemark')->name('addremark');
        //JSON API
        Route::post('/checkCompanyAssigned', 'checkCompanyAssigned')->name('checkcompanyassigned');
        //JSON API
        Route::put('/unassigncompany', 'unassignCompany')->name('unassigncompany');
        //JSON API
        Route::delete('/delete-phone', 'deleteCompanyPhone')->name('deletephone');
        //JSON API
        Route::delete('/delete-email', 'deleteCompanyEmail')->name('deleteemail');
        Route::patch(('/toggle-blacklist'), 'toggleBlacklist')->name('toggleblacklist');
    });

Route::controller(AccountController::class)
    ->middleware(['web', 'auth'])
    ->as('account.')
    ->group(function () {
        //JSON API
        Route::post('/api-data1', 'apiData')->name('api-data1')->middleware([VerifyCsrfToken::class]);
        //JSON API
        Route::post('/submitdisposition', 'submitDisposition')->name('submitdisposition');
        //JSON API
        Route::post('/getcallhistory', 'getCallHistory')->name('getcallhistory')->middleware([VerifyCsrfToken::class]);
        //JSON API
        Route::post('/store', 'store')->name('store')->middleware([AdminManagerAccessMiddleware::class]);
    });

Route::controller(LeadController::class)
    ->middleware(['web', 'auth'])
    ->prefix('lead')
    ->as('lead.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/view/{id}', 'view')->name('view');
        //JSON API
        Route::post('/getleadcallhistory', 'getLeadsCallHistory')->name('getleadcallhistory');
        //JSON API
        Route::post('/store', 'store')->name('store');
        //JSON API
        Route::post('/windowrefreshdisposition', 'windowRefreshDisposition')->name('windowrefreshdisposition');
        //JSON API
        Route::post('/submitleaddisposition', 'submitLeadDisposition')->name('submitleaddisposition');
        //JSON API
        Route::post('/addremark', 'addRemark')->name('lead.addremark');
        //JSON API
        Route::post('/checkLeadAssigned', 'checkLeadAssigned')->name('checkleadassigned');
        //JSON API
        Route::post('/assign', 'assignLead')->name('assign');
        //JSON API
        Route::patch('/update', 'update')->name('update');
        //JSON API
        Route::patch('/unassignlead', 'unassignLead')->name('unassignlead');
    });

Route::controller(CountryController::class)
    ->middleware(['web', 'auth'])
    ->prefix('country')
    ->as('country.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });

Route::controller(StateController::class)
    ->middleware(['web', 'auth'])
    ->prefix('states')
    ->as('states.')
    ->group(function () {
        Route::get('/{country_id}', 'index')->name('index');
    });

Route::controller(CityController::class)
    ->middleware(['web', 'auth'])
    ->prefix('cities')
    ->as('cities.')
    ->group(function () {
        Route::get('/{state_id}', 'index')->name('index');
    });

Route::controller(AddressController::class)
    ->middleware(['web', 'auth'])
    ->as('address.')
    ->group(function () {
        //JSON API
        Route::post('getstates', 'getstates')->name('getstates');
        //JSON API
        Route::post('getcities', 'getcities')->name('getcities');
    });

Route::controller(ZoomCallLogsController::class)
    ->middleware(['web', 'auth'])
    ->as('zoom.')
    ->group(function () {
        Route::get('/zoom-calllogs', 'index')->name('calllogs');
        //JSON API
        Route::get('/zoom-calllogs/transcript', 'getCallTranscript')->name('calltranscript');
        //JSON API
        Route::get('/zoom/getrecordingurl', 'getDownloadUrl')->name('getrecordingurl');
    });

Route::controller(ZoomMeetingsController::class)
    ->middleware(['web', 'auth'])
    ->as('zoom.')
    ->group(function () {
        Route::get('/zoom-meetings', 'index')->name('meetings');
    });

Route::controller(EventController::class)
    ->middleware(['web', 'auth'])
    ->as('event.')
    ->group(function () {
        Route::get('events', 'index')->name('index');
    });

Route::controller(CalendarController::class)
    ->middleware(['web', 'auth'])
    ->as('event.')
    ->group(function () {
        Route::get('eventsv2', 'index')->name('version2');
        Route::get('eventsv3', 'v3')->name('version3');
    });

Route::controller(CalendarController::class)
    ->middleware(['web', 'auth'])
    ->prefix('calendar')
    ->as('calendar.')
    ->group(function () {
        //JSON API
        Route::get('/get', 'getData')->name('get');
        //JSON API
        Route::get('/geteventbyrange', 'getEventByRange')->name('geteventbyrange');
        Route::post('/create', 'create')->name('create');
        Route::put('/update', 'update')->name('update');
        Route::delete('/del', 'delete')->name('delete');
    });

Route::controller(CalendarController::class)
    ->middleware(['web', 'auth'])
    ->prefix('api')
    ->as('calendar.')
    ->group(function () {
        //JSON API
        Route::get('/todayevents', 'getTodaysEvents')->name('todaysevents');
        //JSON API
        Route::get('/companies', 'searchCompany')->name('searchcompanies');
        //JSON API
        Route::get('/users', 'searchUser')->name('searchusers');
    });

Route::controller(ClientsController::class)
    ->middleware(['web', 'auth'])
    ->prefix('client')
    ->as('client.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/edit/{id}', 'edit')->name('edit')->middleware([AdminManagerAccessMiddleware::class]);
        //JSON API
        Route::post('/store', 'store')->name('store')->middleware([AdminManagerAccessMiddleware::class]);
        //JSON API
        Route::post('/update', 'update')->name('update')->middleware([AdminManagerAccessMiddleware::class]);
        //JSON API
        Route::delete('/delete/delete-phone', 'deleteClientPhone')->name('deletephone');
        //JSON API
        Route::delete('/delete/delete-email', 'deleteClientEmail')->name('deleteemail');
        Route::patch('/toggle-blacklist', 'toggleBlacklist')->name('toggleblacklist');
    });

Route::controller(PermissionController::class)
    ->middleware(['web', 'auth'])
    ->prefix('permission')
    ->as('permission.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        //JSON API
        Route::get('/create', 'create')->name('create');
        //JSON API
        Route::get('/getpermissions', 'getPermissions')->name('getpermissions');
        //JSON API
        Route::get('/getPermissionsByRole/{role_id}', 'getPermissionsByRole')->name('getPermissionsByRole');
        Route::post('/store', 'store')->name('store');
        Route::post('/edit', 'edit')->name('edit');
        Route::post('/update', 'update')->name('update');
    });

Route::controller(RoleController::class)
    ->middleware(['web', 'auth'])
    ->prefix('role')
    ->as('role.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/edit', 'edit')->name('edit');
        Route::get('/getRoleDetails/{id}', 'getRoleDetails')->name('getRoleDetails');
        Route::post('/create', 'create')->name('create');
        //JSON API
        Route::post('/addPermissionsToRole', 'addPermissionsToRole')->name('addPermissionsToRole');
        Route::post('/update', 'update')->name('update');
        //JSON API
        Route::delete('/deletePermissionsFromRole', 'deletePermissionsFromRole')->name('deletePermissionsFromRole');
    });

Route::controller(NotificationController::class)
    ->middleware(['web', 'auth'])
    ->prefix('notification')
    ->as('notification.')
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware([AdminManagerAccessMiddleware::class]);
        //JSON API
        Route::get('getNotifications', 'getNotifications')->name('getNotifications');
        //JSON API
        Route::get('getNotificationCount', 'getNotificationCount')->name('getCount');
        //JSON API
        Route::post('updateNotification', 'updateNotification')->name('updateNotification');
    });

Route::controller(GroupController::class)
    ->middleware(['web', 'auth'])
    ->prefix('group')
    ->as('group.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        //JSON API
        Route::post('/store', 'store')->name('store');
        //JSON API
        Route::post('/update', 'update')->name('update');
    });

Route::controller(EmailController::class)
    ->middleware(['web', 'auth'])
    ->prefix('email')
    ->as('email.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });

Route::controller(LogsController::class)
    ->middleware(['web', 'auth'])
    ->prefix('logs')
    ->as('logs.')
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware(TeamLeadManagerAccessMiddleware::class);
    });

Route::controller(TargetController::class)
    ->middleware(['web', 'auth'])
    ->prefix('targets')
    ->as('target.')
    ->group(function () {
        //JSON API
        Route::get('/getmonthdata', 'getMonthData')->name('getmonthdata')->middleware(AdminManagerAccessMiddleware::class);
        //JSON API
        Route::get('/getuserswithtargets', 'getUsersWithTargets')->name('getuserswithtargets')->middleware(AdminManagerAccessMiddleware::class);
        //JSON API
        Route::post('/updatetarget', 'updateTarget')->name('updatetarget')->middleware(AdminManagerAccessMiddleware::class);
    });

Route::controller(RetentionController::class)
    ->middleware(['web', 'auth'])
    ->prefix('retention')
    ->as('retention.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        //JSON API
        Route::get('/import', 'import')->name('import');
        Route::get('/create', 'create')->name('create');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/view/{id}', 'view')->name('view');
        //JSON API
        Route::post('/getleadcallhistory', 'getLeadsCallHistory')->name('getleadcallhistory');
        //JSON API
        Route::post('/store', 'store')->name('store');
        //JSON API
        Route::post('/windowrefreshdisposition', 'windowRefreshDisposition')->name('windowrefreshdisposition');
        //JSON API
        Route::post('/submitleaddisposition', 'submitLeadDisposition')->name('submitleaddisposition');
        //JSON API
        Route::patch('/update', 'update')->name('update');
        //JSON API
        Route::post('/addremark', 'addRemark')->name('addremark');
        //JSON API
        Route::post('/checkLeadAssigned', 'checkLeadAssigned')->name('checkleadassigned');
        //JSON API
        Route::post('/assign', 'assignLead')->name('assign');
        //JSON API
        Route::patch('/unassignlead', 'unassignLead')->name('unassignlead');
    });

Route::controller(DashboardController::class)
    ->group(function () {
        //JSON API
        Route::get('/matchnumber', 'matchNumber')->name('zoomcallmatch');
    });

Route::view('/access-denied', 'access-denied')->name('access-denied');

Route::controller(ReportController::class)
    ->middleware(['web', 'auth'])
    ->prefix('report')
    ->as('report.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        //JSON API
        Route::get('/disposition-list-by-company-id/{id}', 'dispositionListByCompanyId')->name('dispositionListByCompanyId');
    });
*/
