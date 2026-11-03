<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AmbassadorController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\DeviceDetailController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TestPushNotificationController;
use App\Http\Controllers\Webhooks\DwollaWebhookController;
use App\Http\Controllers\Webhooks\QuickBooksWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('send_otp', [AuthController::class, 'send_otp']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::get('get-roles', [AuthController::class, 'getRoles']);

Route::post('userInformationForDeleteAC', [AuthController::class, 'userInformationForDeleteAC']);

Route::post('test-push', [TestPushNotificationController::class, 'send']);

// Route::post('webhooks/dwolla', [DwollaWebhookController::class, 'handle']);
Route::post('/webhooks/dwolla', [DwollaWebhookController::class, 'handle'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
    ]);

Route::post('/webhooks/quickbooks', [QuickBooksWebhookController::class, 'handle'])
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
    ]);

Route::group(['middleware' => ['jwt.verify']], function () {

    // auth apis
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('get-profile', [AuthController::class, 'getProfile']);
    Route::post('update-profile', [AuthController::class, 'updateProfile']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('delete-profile', [AuthController::class, 'deleteProfile']);
    Route::get('get-profile-by-id/{id}', [AuthController::class, 'getProfileDetailsById']);

    // contact us
    Route::post('contact-us', [AuthController::class, 'contactUs']);
});

Route::group(['prefix' => 'auth/v1/admin', 'middleware' => ['jwt.auth', 'active.manager']], function () {
    Route::get('get_events', [AdminController::class, 'getEvents']);
    Route::get('get_event_list', [AdminController::class, 'getEventlist']);
    Route::get('get_event_types', [AdminController::class, 'getEventTypes']);
    Route::get('get_clients', [AdminController::class, 'getClients']);
    Route::get('get_client_parent_options', [AdminController::class, 'getClientParentOptions']);
    Route::post('bulk_update_clients', [AdminController::class, 'bulkUpdateClients']);
    Route::post('update_client_parent', [AdminController::class, 'updateClientParent']);
    Route::post('sync_client_quickbooks', [AdminController::class, 'syncClientQuickBooks']);
    Route::post('sync_all_clients_quickbooks', [AdminController::class, 'syncAllClientsQuickBooks']);
    Route::get('export_clients', [AdminController::class, 'exportClients']);
    Route::get('client_monthly_stats', [AdminController::class, 'getClientMonthlyStats']);
    Route::get('get_client_details', [AdminController::class, 'getClientDetails']);
    Route::get('get_client_transactions', [AdminController::class, 'getClientTransactions']);
    Route::post('sync_client_transactions', [AdminController::class, 'syncClientTransactions']);
    Route::get('get_brands', [AdminController::class, 'getBrands']);
    Route::get('get_brand_categories', [AdminController::class, 'getBrandCategories']);
    Route::get('get_recap', [AdminController::class, 'getRecap']);
    Route::get('search_recap_filters', [AdminController::class, 'searchRecapFilters']);
    Route::get('get_brands_distributions_data', [AdminController::class, 'getBrandsAndDistributions']);
    Route::get('get_ambassador', [AdminController::class, 'getAmbassador']);
    Route::get('get_event_department', [AdminController::class, 'getEventDepartment']);

    Route::post('save_event_type', [AdminController::class, 'saveEventType']);
    Route::post('delete_event_type', [AdminController::class, 'deleteEventType']);
    Route::post('save_brand_category', [AdminController::class, 'saveBrandCategory']);
    Route::post('delete_brand_category', [AdminController::class, 'deleteBrandCategory']);
    Route::get('get_brand_category', [AdminController::class, 'getBrandCategory']);
    Route::get('get_brand_by_id', [AdminController::class, 'getBrandById']);
    Route::post('add_update_brand', [AdminController::class, 'addUpdateBrand']);
    Route::post('delete_brand', [AdminController::class, 'deleteBrand']);
    Route::get('get_department_by_id', [AdminController::class, 'getDepartmentById']);
    Route::post('add_update_department', [AdminController::class, 'addUpdateDepartment']);
    Route::post('delete_department', [AdminController::class, 'deleteDepartment']);
    Route::get('get_locations', [AdminController::class, 'getLocations']);
    Route::get('get_location_by_id', [AdminController::class, 'getLocationById']);
    Route::post('add_update_location', [AdminController::class, 'addUpdateLocation']);
    Route::post('delete_location', [AdminController::class, 'deleteLocation']);
    Route::get('get_venues', [AdminController::class, 'getVenues']);
    Route::get('get_venue_by_id', [AdminController::class, 'getVenueById']);
    Route::post('add_update_venue', [AdminController::class, 'addUpdateVenue']);
    Route::get('get_user_locations', [AdminController::class, 'getUserLocations']);
    Route::get('get_user_by_id', [AdminController::class, 'getUserById']);
    Route::get('get_user_location_data', [AdminController::class, 'getUserLocationData']);
    Route::post('add_update_user_location', [AdminController::class, 'addUpdateUserLocation']);
    Route::get('get_market_data', [AdminController::class, 'getMarketData']);
    Route::get('get_market_by_id', [AdminController::class, 'getMarketById']);
    Route::post('add_update_market', [AdminController::class, 'addUpdateMarket']);
    Route::post('delete_market', [AdminController::class, 'deleteMarket']);
    Route::get('get_distributors', [AdminController::class, 'getDistributors']);
    Route::get('get_distributors_by_id', [AdminController::class, 'getDistributorsById']);
    Route::post('add_update_distributor', [AdminController::class, 'addUpdateDistributor']);
    Route::get('get_managers', [AdminController::class, 'getManagers']);
    Route::get('get_manager_by_id', [AdminController::class, 'getManagerById']);
    Route::post('add_update_manager', [AdminController::class, 'addUpdateManager']);
    Route::post('delete_manager', [AdminController::class, 'deleteManager']);
    Route::get('get_pos_users', [AdminController::class, 'getPosUsers']);
    Route::post('add_update_pos_user', [AdminController::class, 'addUpdatePosUser']);
    Route::get('get_settings', [AdminController::class, 'getSettings']);
    Route::post('add_update_settings', [AdminController::class, 'addUpdateSettings']);
    Route::get('quickbooks_setup', [AdminController::class, 'quickbooksSetup']);
    Route::post('quickbooks_setup_action', [AdminController::class, 'quickbooksSetupAction']);
    Route::get('get_unapprove_ambassador_list', [AdminController::class, 'getUnapproveAmbassadorList']);
    Route::get('get_events_data', [AdminController::class, 'getEventsData']);
    Route::get('search_venues',      [AdminController::class, 'searchVenues']);
    Route::get('search_brands',      [AdminController::class, 'searchBrands']);
    Route::get('search_distributors',[AdminController::class, 'searchDistributors']);
    Route::get('search_markets',     [AdminController::class, 'searchMarkets']);
    Route::get('get_form_data',           [AdminController::class, 'getEventFormData']);
    Route::get('client_billing_defaults',  [AdminController::class, 'getClientBillingDefaults']);
    Route::get('event_billing_parties',    [AdminController::class, 'getEventBillingParties']);
    Route::post('searchAmbassador', [AdminController::class, 'searchAmbassador']);
    Route::post('add_event',    [AdminController::class, 'addEvent']);
    Route::post('delete_event', [AdminController::class, 'deleteEvent']);
    Route::post('assign_ambassabor_event', [AdminController::class, 'assignAmbassaborEvent']);
    Route::post('reject_ambassabor_event', [AdminController::class, 'rejectAmbassaborEvent']);
    Route::get('get_pay_forecast', [AdminController::class, 'getPayForecast']);
    Route::get('get_payments', [AdminController::class, 'getPayments']);
    Route::get('get_reimbursements', [AdminController::class, 'getReimbursements']);
    Route::get('get_reimbursement_by_id', [AdminController::class, 'getReimbursementById']);
    Route::post('approve_reimbursement', [AdminController::class, 'approveReimbursement']);
    Route::post('reject_reimbursement', [AdminController::class, 'rejectReimbursement']);
    Route::post('generate_payroll', [AdminController::class, 'generatePayroll']);
    Route::post('process_payroll', [AdminController::class, 'processPayroll']);
    Route::get('get_bulk_preview', [AdminController::class, 'getBulkPreview']);
    Route::get('get_markets', [AdminController::class, 'getMarkets']);
    Route::get('get_event_details', [AdminController::class, 'getEventDetails']);
    Route::post('delete_ambassador', [AdminController::class, 'deleteAmbassador']);
    Route::post('add_update_ambassdor', [AdminController::class, 'addUpdateAmbassdor']);
    Route::get('get_market_state', [AdminController::class, 'getMarketState']);
    Route::get('get_recap_by_id', [AdminController::class, 'getRecapById']);
    Route::get('export_recaps', [AdminController::class, 'exportRecaps']);
    Route::post('export_recap_email', [AdminController::class, 'emailRecapExport']);
    Route::post('recap/{id}/export_email', [AdminController::class, 'emailRecapExport']);
    Route::post('add_update_recap', [AdminController::class, 'addUpdateRecap']);
    Route::post('update_client_profile', [AdminController::class, 'updateClientProfile']);
    Route::post('send_message', [AdminController::class, 'sendMessage']);
    Route::post('upload_aras_document', [AdminController::class, 'uploadArasDocument']);
    Route::get('get_aras_documents', [AdminController::class, 'getArasDocuments']);
    Route::post('delete_aras_document', [AdminController::class, 'deleteArasDocument']);

});

Route::group(['prefix' => 'auth/v1/location', 'middleware' => ['jwt.auth', 'active.manager']], function () {
    Route::get('get_events', [LocationController::class, 'getEvents']);
    Route::get('get_state_venue_data', [LocationController::class, 'getStateVenueData']);
    Route::get('get_events_calendar_data', [LocationController::class, 'getEventsCalendarData']);
    Route::post('location-filter-events', [LocationController::class, 'locationFilterEvents']);
    Route::get('get_profile_data_by_id', [LocationController::class, 'getProfileById']);
    Route::post('update_client_profile', [LocationController::class, 'updateClientProfile']);
    Route::get('get_event_list', [LocationController::class, 'getEventlist']);
    Route::get('get_event_details', [LocationController::class, 'getEventDetails']);
});

Route::group(['prefix' => 'auth/v1/brand', 'middleware' => ['jwt.auth', 'active.manager']], function () {
    Route::get('get_events', [BrandController::class, 'getEvents']);
    Route::get('get_events_calender_data', [BrandController::class, 'getEventsCalenderData']);
    Route::get('get_event_list', [BrandController::class, 'getEventlist']);
});

Route::group(['prefix' => 'auth/v1/client', 'middleware' => ['jwt.auth', 'active.manager']], function () {
    Route::get('get_events', [ClientController::class, 'getEvents']);
    Route::get('get_events_calendar_data', [ClientController::class, 'getEventsCalendarData']);
    Route::get('get_recap_calendar_data', [ClientController::class, 'getRecapCalendarData']);
    Route::get('get_event_list', [ClientController::class, 'getEventlist']);
    Route::post('client-filter-events', [ClientController::class, 'clientFilterEvents']);
    Route::post('recap-filter-events', [ClientController::class, 'recapFilterEvents']);
    Route::get('get_event_details', [ClientController::class, 'getEventDetails']);

});

Route::group(['prefix' => 'auth/v1/ambassador', 'middleware' => ['jwt.auth', 'active.manager']], function () {
    Route::get('get_events', [AmbassadorController::class, 'getEvents']);
    Route::get('get_state_market', [AmbassadorController::class, 'getStateMarket']);
    Route::post('update_profile', [AmbassadorController::class, 'updateProfile']);
    Route::get('get_events_data', [AmbassadorController::class, 'getEventsData']);
    Route::get('get_recap_data', [AmbassadorController::class, 'getRecapData']);
    Route::get('get_created_recap', [AmbassadorController::class, 'getCreatedRecap']);
    Route::get('get_submitted_recap', [AmbassadorController::class, 'getSubmittedRecap']);
    Route::get('get_single_submitted_recap', [AmbassadorController::class, 'getSingleSubmittedRecap']);
    Route::get('get_pay_forecast', [AmbassadorController::class, 'getPayForecast']);
    Route::get('get_event_list', [AmbassadorController::class, 'getEventlist']);
    Route::get('get_pay_forecast_detail', [AmbassadorController::class, 'getPayForecastDetail']);
    Route::get('get_payments', [AmbassadorController::class, 'getPayments']);
    Route::get('get_event_details', [AmbassadorController::class, 'getEventDetails']);
    Route::get('get_ready_check_in_events', [AmbassadorController::class, 'getReadyCheckInEvents']);

    Route::post('apply_event', [AmbassadorController::class, 'applyEvent']);
    Route::get('get_recap_by_id', [AmbassadorController::class, 'getRecapById']);
    Route::get('get_recap_draft', [AmbassadorController::class, 'getRecapDraft']);
    Route::post('save_recap_draft', [AmbassadorController::class, 'saveRecapDraft']);
    Route::delete('delete_recap_draft', [AmbassadorController::class, 'deleteRecapDraft']);
    Route::post('add_update_recap', [AmbassadorController::class, 'addUpdateRecap']);
    Route::post('store_GPS_tracking', [AmbassadorController::class, 'storeGPSTracking']);

    Route::get('get_reimbursements', [AmbassadorController::class, 'getReimbursements']);
    Route::get('get_reimbursement_form_data', [AmbassadorController::class, 'getReimbursementFormData']);
    Route::get('get_reimbursement_by_id', [AmbassadorController::class, 'getReimbursementById']);
    Route::post('store_reimbursement', [AmbassadorController::class, 'storeReimbursement']);
    Route::post('update_reimbursement', [AmbassadorController::class, 'updateReimbursement']);

    // Direct Deposit / Banking (Dwolla)
    Route::get('banking/status',            [\App\Http\Controllers\Api\BankingController::class, 'status']);
    Route::post('banking/setup',            [\App\Http\Controllers\Api\BankingController::class, 'setup']);
    Route::post('banking/add',              [\App\Http\Controllers\Api\BankingController::class, 'add']);
    Route::post('banking/verify',           [\App\Http\Controllers\Api\BankingController::class, 'verify']);
    Route::post('banking/set-default/{id}', [\App\Http\Controllers\Api\BankingController::class, 'setDefault']);
    Route::delete('banking/remove/{id}',    [\App\Http\Controllers\Api\BankingController::class, 'remove']);
    Route::get('banking/transactions',      [\App\Http\Controllers\Api\BankingController::class, 'transactions']);

});

Route::group(['prefix' => 'auth/v1/common', 'middleware' => ['jwt.auth', 'active.manager']], function () {
    Route::get('get_users', [CommonController::class, 'getUsers']);
});

Route::group(['prefix' => 'auth/v1/', 'middleware' => ['jwt.auth', 'active.manager']], function () {
    Route::get('get_users', [CommonController::class, 'getUsers']);
    Route::post('chatFileUpload', [CommonController::class, 'chatFileUpload']);
    Route::post('save_device_details', [DeviceDetailController::class, 'saveDeviceDetails']);
    Route::get('notification_preferences', [\App\Http\Controllers\Api\NotificationPreferenceController::class, 'show']);
    Route::post('notification_preferences', [\App\Http\Controllers\Api\NotificationPreferenceController::class, 'update']);
    Route::post('test_push', [TestPushNotificationController::class, 'sendToAuthenticatedUser']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{notification}/delete', [NotificationController::class, 'destroy']);
    Route::post('notifications/delete-all', [NotificationController::class, 'destroyAll']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::delete('notifications', [NotificationController::class, 'destroyAll']);
});
