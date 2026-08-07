<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\CityController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoomController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RetentionController;
use App\Http\Controllers\PermissionController;
use App\Http\Middleware\AdminAccessMiddleware;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ZoomCallLogsController;
use App\Http\Controllers\ZoomMeetingsController;
use App\Http\Middleware\ManagerAccessMiddleware;
use App\Http\Middleware\AdminManagerAccessMiddleware;
use App\Http\Middleware\TeamLeadManagerAccessMiddleware;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });
// Route::get('/', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified']);

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/', [DashboardController::class, 'index'])->name('')->middleware(['auth', 'verified']);

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware(['auth', 'verified']);
Route::get('/dashboard/getreportdata', [DashboardController::class, 'getReportData'])->name('dashboard.getreportdata')->middleware(['auth']);
Route::get('/dashboard/getreportdatamanager', [DashboardController::class, 'getReportDataManager'])->name('dashboard.getreportdatamanager')->middleware(['auth']);
Route::get('/dashboard/getreportdataforadminmanager', [DashboardController::class, 'getReportDataForAdminManager'])->name('dashboard.getreportdataforadminmanager')->middleware(['auth']);
Route::get('/dashboard/getreportdatamanagers', [DashboardController::class, 'getReportDatamanagers'])->name('dashboard.getreportdatamanagers')->middleware(['auth']);
Route::get('/dashboard/gettotalsalesdetails', [DashboardController::class, 'getTotalSalesDetails'])->name('dashboard.gettotalsalesdetails')->middleware(['auth']);
Route::get('/dashboard/getsalesdetailsbydaterange', [DashboardController::class, 'getSalesDetailsByDateRange'])->name('dashboard.getSalesDetailsByDateRange')->middleware(['auth']);
Route::get('/dashboard/get-disposition-call-details', [DashboardController::class, 'getDispositionCallDetails'])->name('dashboard.getDispositionCallDetails')->middleware(['auth']);
Route::get('/dashboard/get-total-calls-details', [DashboardController::class, 'getTotalCallsDetails'])->name('dashboard.gettotalcallsdetails')->middleware(['auth']);

Route::get('/dashboard/get-disposition-details-sales-executives', [DashboardController::class, 'getSalesExecutiveDispositionDetails'])->name('dashboard.getSalesExecutiveDispositionDetails')->middleware(['auth']);
Route::get('/dashboard/get-disposition-call-details-sales-executives', [DashboardController::class, 'getDispositionCallDetailsForSalesExec'])->name('dashboard.getDispositionCallDetailsForSalesExec')->middleware(['auth']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/user', [UserController::class, 'index'])->name('user')
        ->middleware([AdminManagerAccessMiddleware::class]);
    Route::get('/user/create', [UserController::class, 'create'])->name('user.create')->middleware([AdminAccessMiddleware::class]);
    Route::get('/users/role/get', [UserController::class, 'getsome'])->name('user.getsome')->middleware([AdminAccessMiddleware::class]);
    Route::get('/refreshzoomtoken', [ZoomController::class, 'refreshZoomToken'])->name('zoomtoken.refresh');
    Route::get('/getzoomusercredentials', [ZoomController::class, 'getZoomCredentials'])->name('zoom.getusercredentials');
    Route::get('/user/edit/{id}', [UserController::class, 'edit'])->name('user.edit')->middleware([AdminAccessMiddleware::class]);
    Route::get('/user/report/{id}', [UserController::class, 'report'])->name('user.report');
    Route::get('/user/customreport/{id}', [UserController::class, 'customreport'])->name('user.report.customdate');
    Route::get('/getusersbyauthorityid', [UserController::class, 'getUsersByAuthorityId'])->name('user.getusersbyauthorityid');

    Route::post('/users/stores', [UserController::class, 'store'])->name('user.stores')->middleware([AdminAccessMiddleware::class]);
    Route::post('getuserdetails', [UserController::class, 'getuserdetails'])->name('user.getuserdetails');
    Route::post('/user/getuserbyrole', [UserController::class, 'getUserByRole'])->name('user.getuserbyrole');
    Route::post('toggleActiveStatus', [UserController::class, 'toggleActiveStatus'])->name('user.toggleActiveStatus')->middleware([AdminAccessMiddleware::class]);

    Route::patch('updateuserdetails', [UserController::class, 'update'])->name('user.update')->middleware([AdminAccessMiddleware::class]);
    Route::patch('/updateuserpassword', [UserController::class, 'updateUserPassword'])->name('user.updateuserpassword');
    Route::patch('/updatezoomdetails', [ZoomController::class, 'updateZoomDetails'])->name('zoomdetails.update');
    Route::patch('/updatezoommeeting', [ZoomController::class, 'updateZoomMeeting'])->name('zoommeeting.update');
    Route::patch('/updatezoomusercredentials', [ZoomController::class, 'updateUserZoomCredentials'])->name('zoom.updateusercredentials');
    Route::patch('/unassingallcompaniesofuser', [UserController::class, 'unassignAllCompaniesOfUser'])->name('user.unassignallcompaniesofuser');

    Route::delete('deleteuser', [UserController::class, 'deleteuser'])->name('user.deleteuser')->middleware([AdminAccessMiddleware::class]);
    Route::delete('/deletezoomdetails', [ZoomController::class, 'deleteZoomMeeting'])->name('zoommeeting.delete');

});

Route::middleware('auth')->group(function () {

    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/account/create', [AccountController::class, 'create'])->name('account.create')->middleware([AdminManagerAccessMiddleware::class]);
    Route::get('/account/edit/{id}', [AccountController::class, 'edit'])->name('account.edit');
    Route::get('/account/view/{id}', [AccountController::class, 'view'])->name('account.view');
    Route::get('/account/getcompanieslist', [AccountController::class, 'getCompaniesList'])->name('companies.list');

    Route::post('/api-data1', [AccountController::class, 'apiData'])->name('account.api-data1')->middleware([VerifyCsrfToken::class]);
    Route::post('/submitdisposition', [AccountController::class, 'submitDisposition'])->name('account.submitdisposition');
    Route::post('/getcallhistory', [AccountController::class, 'getCallHistory'])->name('account.getcallhistory')->middleware([VerifyCsrfToken::class]);
    Route::post('/account/update', [AccountController::class, 'update'])->name('account.update');
    Route::post('/store', [AccountController::class, 'store'])->name('account.store')->middleware([AdminManagerAccessMiddleware::class]);
    Route::post('/account/assign', [AccountController::class, 'assignCompany'])->name('account.assign');
    Route::post('/account/addremark', [AccountController::class, 'addRemark'])->name('account.addremark');
    Route::post('/account/checkCompanyAssigned', [AccountController::class, 'checkCompanyAssigned'])->name('account.checkcompanyassigned');

    Route::put('/account/unassigncompany', [AccountController::class, 'unassignCompany'])->name('account.unassigncompany');

    Route::delete('/account/delete-phone', [AccountController::class, 'deleteCompanyPhone'])->name('account.deletephone');
    Route::delete('/account/delete-email', [AccountController::class, 'deleteCompanyEmail'])->name('account.deleteemail');
    Route::patch(('/account/toggle-blacklist'), [AccountController::class, 'toggleBlacklist'])->name('account.toggleblacklist');

});

Route::middleware('auth')->group(function () {
    Route::get('/lead', [LeadController::class, 'index'])->name('lead.index');
    Route::get('/lead/create', [LeadController::class, 'create'])->name('lead.create');
    Route::get('/lead/edit/{id}', [LeadController::class, 'edit'])->name('lead.edit');
    Route::get('/lead/view/{id}', [LeadController::class, 'view'])->name('lead.view');

    Route::post('/lead/getleadcallhistory', [LeadController::class, 'getLeadsCallHistory'])->name('lead.getleadcallhistory');
    Route::post('/lead/store', [LeadController::class, 'store'])->name('lead.store');
    Route::post('/lead/windowrefreshdisposition', [LeadController::class, 'windowRefreshDisposition'])->name('lead.windowrefreshdisposition');
    Route::post('/lead/submitleaddisposition', [LeadController::class, 'submitLeadDisposition'])->name('lead.submitleaddisposition');
    Route::post('/lead/addremark', [LeadController::class, 'addRemark'])->name('lead.addremark');
    Route::post('/lead/checkLeadAssigned', [LeadController::class, 'checkLeadAssigned'])->name('lead.checkleadassigned');
    Route::post('/lead/assign', [LeadController::class, 'assignLead'])->name('lead.assign');

    Route::patch('/lead/update', [LeadController::class, 'update'])->name('lead.update');
    Route::patch('/lead/unassignlead', [LeadController::class, 'unassignLead'])->name('lead.unassignlead');

});

Route::group(['prefix' => 'country'], function () {
    Route::get('/', [CountryController::class, 'index'])->name('country.index');
});

Route::group(['prefix' => 'states'], function () {
    Route::get('{country_id}', [StateController::class, 'index'])->name('states.index');
});

Route::group(['prefix' => 'cities'], function () {
    Route::get('{state_id}', [CityController::class, 'index'])->name('cities.index');
});

Route::middleware('auth')->group(function () {
    Route::post('getstates', [AddressController::class, 'getstates'])->name('address.getstates');
    Route::post('getcities', [AddressController::class, 'getcities'])->name('address.getcities');
});

Route::middleware('auth')->group(function () {

    Route::get('/zoom-calllogs', [ZoomCallLogsController::class, 'index'])->name('zoom.calllogs');
    Route::get('/zoom-calllogs/transcript', [ZoomCallLogsController::class, 'getCallTranscript'])->name('zoom.calltranscript');
    Route::get('/zoom/getrecordings', [ZoomController::class, 'getZoomRecordings'])->name('zoom.getrecordings');
    Route::get('/zoom/getrecordingurl', [ZoomCallLogsController::class, 'getDownloadUrl'])->name('zoom.getrecordingurl');
    Route::get('/zoom-meetings', [ZoomMeetingsController::class, 'index'])->name('zoom.meetings');
});

Route::middleware('auth')->group(function () {
    Route::get('events', [EventController::class, 'index'])->name('event.index');
    Route::get('eventsv2', [CalendarController::class, 'index'])->name('event.version2');
    Route::get('eventsv3', [CalendarController::class, 'v3'])->name('event.version3');
});

Route::middleware('auth')->group(function () {
    Route::get('calendar/get', [CalendarController::class, 'getData'])->name('calendar.get');
    Route::get('calendar/geteventbyrange', [CalendarController::class, 'getEventByRange'])->name('calendar.geteventbyrange');
    Route::get('/api/todayevents', [CalendarController::class, 'getTodaysEvents'])->name('calendar.todaysevents');
    Route::get('/api/companies', [CalendarController::class, 'searchCompany'])->name('calendar.searchcompanies');
    Route::get('/api/users', [CalendarController::class, 'searchUser'])->name('calendar.searchusers');
    Route::get('/zoom/token', [ZoomController::class, 'tokenGenerate'])->name('zoom.token');

    Route::post('calendar/create', [CalendarController::class, 'create'])->name('calendar.create');
    Route::post('/zoom', [ZoomController::class, 'createMeeting'])->name('zoom.index');

    Route::put('calendar/update', [CalendarController::class, 'update'])->name('calendar.update');

    Route::delete('calendar/del', [CalendarController::class, 'delete'])->name('calendar.delete');

});

Route::group(['prefix' => 'client'], function () {
    Route::get('/', [ClientsController::class, 'index'])->name('client.index');
    Route::get('/edit/{id}', [ClientsController::class, 'edit'])->name('client.edit')->middleware([AdminManagerAccessMiddleware::class]);

    Route::post('/store', [ClientsController::class, 'store'])->name('client.store')->middleware([AdminManagerAccessMiddleware::class]);
    Route::post('/update', [ClientsController::class, 'update'])->name('client.update')->middleware([AdminManagerAccessMiddleware::class]);

    Route::patch('/toggle-blacklist', [ClientsController::class, 'toggleBlacklist'])->name('client.toggleblacklist');

    Route::delete('/delete/delete-phone', [ClientsController::class, 'deleteClientPhone'])->name('client.deletephone');
    Route::delete('/delete/delete-email', [ClientsController::class, 'deleteClientEmail'])->name('client.deleteemail');
    Route::delete('/delete', [ClientsController::class, 'deleteClient'])->name('client.delete');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/permission', [PermissionController::class, 'index'])->name('permission.index');
    Route::get('/permission/create', [PermissionController::class, 'create'])->name('permission.create');
    Route::get('/permission/getpermissions', [PermissionController::class, 'getPermissions'])->name('permission.getpermissions');
    Route::get('/permission/getPermissionsByRole/{role_id}', [PermissionController::class, 'getPermissionsByRole'])->name('permission.getPermissionsByRole');

    Route::post('/permission/store', [PermissionController::class, 'store'])->name('permission.store');
    Route::post('/permission/edit', [PermissionController::class, 'edit'])->name('permission.edit');
    Route::post('/permission/update', [PermissionController::class, 'update'])->name('permission.update');

});

Route::group(['prefix' => 'usercache'], function () {
    Route::get('/user', [PermissionController::class, 'cachePermissions'])->name('user.cache');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/role', [RoleController::class, 'index'])->name('role.index');
    Route::get('/role/edit', [RoleController::class, 'edit'])->name('role.edit');
    Route::get('/role/getRoleDetails/{id}', [RoleController::class, 'getRoleDetails'])->name('role.getRoleDetails');

    Route::get('/role/create', [RoleController::class, 'createView'])->name('role.createView');
    Route::post('/role/store', [RoleController::class, 'store'])->name('role.store');
    Route::post('/role/addPermissionsToRole', [RoleController::class, 'addPermissionsToRole'])->name('role.addPermissionsToRole');
    Route::post('/role/update', [RoleController::class, 'update'])->name('role.update');

    Route::delete('/role/deletePermissionsFromRole', [RoleController::class, 'deletePermissionsFromRole'])->name('role.deletePermissionsFromRole');
});

Route::group(['prefix' => 'notification'], function () {
    Route::get('/', [NotificationController::class, 'index'])->name('notification.index')->middleware([AdminManagerAccessMiddleware::class]);
    Route::get('getNotifications', [NotificationController::class, 'getNotifications'])->name('notification.getNotifications');
    Route::get('getNotificationCount', [NotificationController::class, 'getNotificationCount'])->name('notification.getCount');

    Route::post('updateNotification', [NotificationController::class, 'updateNotification'])->name('notification.updateNotification');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/group', [GroupController::class, 'index'])->name('group.index');
    Route::get('/group/create', [GroupController::class, 'create'])->name('group.create');

    Route::post('/group/store', [GroupController::class, 'store'])->name('group.store');
    Route::post('/group/update', [GroupController::class, 'update'])->name('group.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/email', [EmailController::class, 'index'])->name('email.index');
});

Route::group(['prefix' => 'logs'], function () {
    Route::get('/', [LogsController::class, 'index'])->name('logs.index')->middleware(TeamLeadManagerAccessMiddleware::class);
})->middleware('auth');


Route::group(['prefix' => 'targets'], function () {
    Route::get('/getmonthdata', [TargetController::class, 'getMonthData'])->name('target.getmonthdata')->middleware(AdminManagerAccessMiddleware::class);
    Route::get('/getuserswithtargets', [TargetController::class, 'getUsersWithTargets'])->name('target.getuserswithtargets')->middleware(AdminManagerAccessMiddleware::class);

    Route::post('/updatetarget', [TargetController::class, 'updateTarget'])->name('target.updatetarget')->middleware(AdminManagerAccessMiddleware::class);

})->middleware('auth');


Route::middleware('auth')->group(function () {
    Route::get('/retention/import', [RetentionController::class, 'import'])->name('retention.import');

    Route::get('/retention', [RetentionController::class, 'index'])->name('retention.index');
    Route::get('/retention/create', [RetentionController::class, 'create'])->name('retention.create');
    Route::get('/retention/edit/{id}', [RetentionController::class, 'edit'])->name('retention.edit');
    Route::get('/retention/view/{id}', [RetentionController::class, 'view'])->name('retention.view');
    Route::post('/retention/getleadcallhistory', [RetentionController::class, 'getLeadsCallHistory'])->name('retention.getleadcallhistory');

    Route::post('/retention/store', [RetentionController::class, 'store'])->name('retention.store');
    Route::post('/retention/windowrefreshdisposition', [RetentionController::class, 'windowRefreshDisposition'])->name('retention.windowrefreshdisposition');
    Route::post('/retention/submitleaddisposition', [RetentionController::class, 'submitLeadDisposition'])->name('retention.submitleaddisposition');

    Route::patch('/retention/update', [RetentionController::class, 'update'])->name('retention.update');

    Route::post('/retention/addremark', [RetentionController::class, 'addRemark'])->name('retention.addremark');

    Route::post('/retention/checkLeadAssigned', [RetentionController::class, 'checkLeadAssigned'])->name('retention.checkleadassigned');

    Route::post('/retention/assign', [RetentionController::class, 'assignLead'])->name('retention.assign');

    Route::patch('/retention/unassignlead', [RetentionController::class, 'unassignLead'])->name('retention.unassignlead');
});

Route::middleware(['auth'])->prefix('report')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('report.index');
    Route::get('/disposition-list-by-company-id/{id}', [ReportController::class, 'dispositionListByCompanyId'])->name('report.disposition-list-by-company-id');
});


Route::get('/matchnumber', [DashboardController::class, 'matchNumber'])->name('zoomcallmatch');
Route::view('/access-denied', 'access-denied')->name('access-denied');


require __DIR__ . '/auth.php';
