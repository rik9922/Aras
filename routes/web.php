<?php

use App\Http\Controllers\Admin\AmbassadorController;
use App\Http\Controllers\Admin\ArasDocumentController;
use App\Http\Controllers\Admin\BillController;
use App\Http\Controllers\Admin\BrandCategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BulkController;
use App\Http\Controllers\Admin\ImportDataController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DwollaBalanceAlertController;
use App\Http\Controllers\Admin\DwollaWebhookSubscriptionController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventDepartmentController;
use App\Http\Controllers\Admin\EventDistributorController;
use App\Http\Controllers\Admin\EventImportController;
use App\Http\Controllers\Admin\EventLocationController;
use App\Http\Controllers\Admin\EventTypeController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ManagerCotroller;
use App\Http\Controllers\Admin\MarketController;
use App\Http\Controllers\Admin\MessagingController;
use App\Http\Controllers\Admin\ModelController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\POSUserController;
use App\Http\Controllers\Admin\PushEventToBestController;
use App\Http\Controllers\Admin\QuickBooksController;
use App\Http\Controllers\Admin\RecapController;
use App\Http\Controllers\Admin\ReceiptOcrController;
use App\Http\Controllers\Admin\ReimbursementController as AdminReimbursementController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ButtonShowcaseController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\Vendor1099ReportController;
use App\Http\Controllers\Admin\VenueController;
use App\Http\Controllers\Ambassador\AmbassadorEventController;
use App\Http\Controllers\Ambassador\DwollaController;
use App\Http\Controllers\Ambassador\PayForecastController;
use App\Http\Controllers\Ambassador\PaymentController;
use App\Http\Controllers\Ambassador\RecapController as AmbassadorRecapController;
use App\Http\Controllers\Ambassador\ReceiptOcrController as AmbassadorReceiptOcrController;
use App\Http\Controllers\Ambassador\ReimbursementController as AmbassadorReimbursementController;
use App\Http\Controllers\Ambassador\SubmitRecapController;
use App\Http\Controllers\Ambassador\W9FormController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BrandEventController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientEventController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\Location\LocationEventController;
use App\Http\Controllers\NotificationSettingsController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationsController;

// Route::redirect('/','login');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(getUserDashboard());  // Redirect to the correct dashboard
    }

    return redirect()->route('login'); // If not logged in, go to the login page
});

Route::get('/download-recap/{id}', [App\Http\Controllers\PDFController::class, 'downloadRecap'])->name('download-recap');
Route::get('login', [LoginController::class, 'loginForm'])->name('login');
Route::get('forgot-password', [ForgotPasswordController::class, 'viewForgetPassword']);
Route::get('verification-code', [ForgotPasswordController::class, 'viewVerificationCode']);
Route::get('reset-password', [ForgotPasswordController::class, 'viewResetPassword']);

Auth::routes();
Route::middleware(['web'])->group(function () {
    Route::post('login-user', [LoginController::class, 'loginUser'])->name('login-user');
    Route::middleware(['auth'])->group(function () {

        // ── Shared QuickBooks callback ─────────────────────────────────────────
        // Accessible to both Admins and Managers with a single registered redirect
        // URI in the Intuit Developer Portal: APP_URL/quickbooks/callback
        // No role middleware here — the controller resolves the correct post-auth
        // redirect based on the authenticated user's role.
        Route::get('/quickbooks/callback', [QuickBooksController::class, 'callback'])
            ->name('quickbooks.callback.shared');
        // ──────────────────────────────────────────────────────────────────────

        // Admin Routes
        Route::middleware(['role:Administrator'])->group(function () {

            Route::prefix('admin')->group(function () {

                // calender
                Route::get('calendar', [CalendarController::class, 'adminCalendar'])->name('admin.calendar');

                // Events route
                Route::get('/events', [EventController::class, 'index'])->name('admin.events');
                Route::get('/event/create', [EventController::class, 'create'])->name('admin.event.create');
                Route::get('duplicate-event/{id}', [EventController::class, 'duplicate'])->name('admin.duplicate.event');
                Route::post('store-event', [EventController::class, 'store'])->name('admin.store.event');
                Route::get('event/resolve-timezone', [EventController::class, 'resolveTimezone'])->name('admin.event.resolve-timezone');
                Route::get('edit-event/{id}', [EventController::class, 'edit'])->name('admin.edit.event');
                Route::post('update-event', [EventController::class, 'updateEvent'])->name('admin.update.event');
                Route::post('events/{event}/notify-ambassadors', [EventController::class, 'notifyBrandAmbassadors'])
                    ->name('admin.events.notify-ambassadors');
                Route::post('delete-event/{id}', [EventController::class, 'destroy'])->name('admin.delete.event');
                Route::post('search-ambassador', [EventController::class, 'searchAmbassador'])->name('admin.search.ambassador');
                Route::post('filter-events', [EventController::class, 'filterEvents'])->name('admin.filter.events');
                Route::post('update-event-status', [EventController::class, 'updateEventStatus'])->name('admin.update-event-status.event');
                Route::post('events/{event}/budget-approve', [EventController::class, 'approveBudget'])->name('admin.events.budget.approve');
                Route::post('events/{event}/budget-deny', [EventController::class, 'denyBudget'])->name('admin.events.budget.deny');
                Route::get('unapprove-ambassador-for-events', [EventController::class, 'unApproveAmbassadorEvents'])->name('admin.unapprove-ambassador-for-events');
                Route::post('assign-amb-event', [EventController::class, 'assignAmbEvent'])->name('ambassador.assign-amb-event');
                Route::post('remove-ambassador-assignment', [EventController::class, 'removeAmbassadorAssignment'])->name('admin.remove-ambassador-assignment');
                Route::post('events/{id}/toggle-kit-delivered', [EventController::class, 'toggleKitDelivered']);
                Route::post('events/best/send-selected', [EventController::class, 'sendSelectedToBest'])->name('admin.events.best.send-selected');
                Route::post('events/{event}/best/retry', [EventController::class, 'retryBest'])->name('admin.events.best.retry');

                // BEST lookup APIs (for event-creation modal)
                Route::controller(PushEventToBestController::class)->group(function () {
                    Route::get('best-get-account', 'getAccount')->name('admin.best.get-account');
                    Route::get('best-get-program', 'getProgram')->name('admin.best.get-program');
                    Route::get('best-get-consumer-events', 'getConsumerEvents')->name('admin.best.get-consumer-events');
                    Route::get('best-get-tags', 'getTags')->name('admin.best.get-tags');
                    Route::get('best-get-brand-featured', 'getBrandFeatured')->name('admin.best.get-brand-featured');
                });

                // fetch near by ambassador
                Route::get('fetch-nearby-ambassadors', [EventController::class, 'fetchNearByAmbassador'])->name('admin.fetch-nearby-ambassadors');

                // export Events
                Route::get('export-events', [ExportController::class, 'exportEvents'])->name('admin.export.events');
                Route::get('export-recap-events', [ExportController::class, 'exportRecapEvent'])->name('admin.export-recap-events');
                Route::post('recap/{id}/export-email', [ExportController::class, 'emailSingleRecapExport'])->name('admin.recap.export-email');
                Route::get('export-monthly-events', [ExportController::class, 'exportMonthlyEvents'])->name('admin.export-monthly-events');
                Route::get('export-event-invoicing-month', [ExportController::class, 'exportEventInvoicingMonth'])->name('admin.export-event-invoicing-month');
                Route::get('export-event-invoicing-event', [ExportController::class, 'exportEventInvoicingEvent'])->name('admin.export-event-invoicing-event');
                Route::get('export-event-invoicing-client', [ExportController::class, 'exportEventInvoicingClient'])->name('admin.export-event-invoicing-client');
                Route::get('export-brands', [ExportController::class, 'exportBrands'])->name('admin.export.brands');

                // Other admin routes
                Route::get('recaps', [RecapController::class, 'index'])->name('admin.recaps');
                Route::post('recaps-filter-events', [RecapController::class, 'recapFilter'])->name('admin.filter.recap-events');
                Route::get('show-event/{id}', [CommonController::class, 'viewEvent'])->name('admin.show-event');
                Route::get('view-recap/{id}', [CommonController::class, 'viewRecap'])->name('admin.view-recap');
                Route::patch('approve-recap/{id}', [CommonController::class, 'approveRecap'])->name('admin.recap.approve');
                Route::patch('deny-recap/{id}', [CommonController::class, 'denyRecap'])->name('admin.recap.deny');

                Route::patch('recap/{id}/manager-fields', [CommonController::class, 'updateRecapManagerFields'])
                    ->name('admin.update-recap-manager-fields');

                // its update
                Route::get('show-recap/{id}', [CommonController::class, 'showRecap'])->name('admin.show-recap');
                Route::get('/recap/{id}/edit', [AmbassadorRecapController::class, 'editRecap'])->name('admin.edit-recap');
                Route::post('update-recap', [AmbassadorRecapController::class, 'updateRecap'])->name('admin.update-recap');

                // Client Routes
                Route::get('clients', [ClientController::class, 'index'])->name('admin.clients');
                Route::get('clients/create', [ClientController::class, 'create'])->name('admin.clients.create');
                Route::get('clients/next-qb-customer-id', [ClientController::class, 'nextQuickBooksCustomerId'])->name('admin.clients.next-qb-customer-id');
                Route::get('clients/{client}/billing-defaults', [ClientController::class, 'billingDefaults'])->name('admin.clients.billing-defaults');
                Route::post('store-client', [ClientController::class, 'store'])->name('admin.store.client');
                Route::post('clients/check-similar-billings', [ClientController::class, 'checkSimilarBillings'])->name('admin.clients.check-similar-billings');
                Route::get('edit-client/{id}', [ClientController::class, 'edit'])->name('admin.edit.client');
                Route::get('show-client/{client}', [ClientController::class, 'show'])->name('admin.show.client');
                Route::get('client/{client}/transactions', [ClientController::class, 'transactions'])->name('admin.client.transactions');
                Route::post('client/{client}/transactions/sync', [ClientController::class, 'syncTransactions'])->name('admin.client.transactions.sync');
                Route::post('update-client', [ClientController::class, 'updateClient'])->name('admin.update.client');

                // Brand Route
                Route::get('brands', [BrandController::class, 'index'])->name('admin.brands');
                Route::get('brand/create', [BrandController::class, 'create'])->name('admin.brands.create');
                Route::post('store-brand', [BrandController::class, 'store'])->name('admin.store.brand');
                Route::get('edit-brand/{id}', [BrandController::class, 'edit'])->name('admin.edit.brand');
                Route::post('update-brand', [BrandController::class, 'updateBrand'])->name('admin.update.brand');
                Route::delete('delete-brand/{id}', [BrandController::class, 'destroy'])->name('admin.delete.brand');
                Route::get('brands/{brand}/bill-to-options', [BrandController::class, 'billToOptions'])->name('admin.brands.bill-to-options');

                // Brand Category Route
                Route::get('brand-category', [BrandCategoryController::class, 'index'])->name('admin.brand-category');
                Route::get('brand-category/create', [BrandCategoryController::class, 'create'])->name('admin.brand-category.create');
                Route::post('store-brand-category', [BrandCategoryController::class, 'store'])->name('admin.store.brand-category');
                Route::get('brand-category/edit/{id}', [BrandCategoryController::class, 'edit'])->name('admin.edit.brand-category');
                Route::post('update-brand-category', [BrandCategoryController::class, 'updateBrandCategory'])->name('admin.update.brand-category');
                Route::delete('delete-brand-category/{id}', [BrandCategoryController::class, 'destroy'])->name('admin.delete.brand-category');

                // Event Distributor
                Route::get('event-destributor', [EventDistributorController::class, 'index'])->name('admin.event-destributor');
                Route::get('event-destributor/create', [EventDistributorController::class, 'create'])->name('admin.event-destributor.create');
                Route::post('store-event-destributor', [EventDistributorController::class, 'store'])->name('admin.store.event-destributor');
                Route::get('edit-event-destributor/{id}', [EventDistributorController::class, 'edit'])->name('admin.edit.event-destributor');
                Route::post('update-event-destributor', [EventDistributorController::class, 'updateEventDistributor'])->name('admin.update.event-destributor');
                Route::delete('delete-event-destributor/{id}', [EventDistributorController::class, 'destroy'])->name('admin.delete.event-destributor');

                // Event Department
                Route::get('event-department', [EventDepartmentController::class, 'index'])->name('admin.event-department');
                Route::get('event-department/create', [EventDepartmentController::class, 'create'])->name('admin.event-department.create');
                Route::post('store-event-department', [EventDepartmentController::class, 'store'])->name('admin.store.event-department');
                Route::get('edit-event-department/{id}', [EventDepartmentController::class, 'edit'])->name('admin.edit.event-department');
                Route::post('update-event-department', [EventDepartmentController::class, 'updateEventDepartment'])->name('admin.update.event-department');
                Route::delete('delete-event-department/{id}', [EventDepartmentController::class, 'destroy'])->name('admin.delete.event-department');

                // Event markets
                Route::get('markets', [MarketController::class, 'index'])->name('admin.markets');
                Route::get('markets/create', [MarketController::class, 'create'])->name('admin.market.create');
                Route::post('store-markets', [MarketController::class, 'store'])->name('admin.store.market');
                Route::get('edit-markets/{id}', [MarketController::class, 'edit'])->name('admin.edit.markets');
                Route::post('update-markets', [MarketController::class, 'updateMarket'])->name('admin.update.market');
                Route::delete('delete-markets', [MarketController::class, 'bulkDestroy'])->name('admin.delete.markets.bulk');
                Route::delete('delete-markets/{market}', [MarketController::class, 'destroy'])->name('admin.delete.markets');

                // / Event ambassadorType
                Route::get('eventtypes', [EventTypeController::class, 'index'])->name('admin.eventtypes');
                Route::get('eventtypes/create', [EventTypeController::class, 'create'])->name('admin.eventtypes.create');
                Route::post('store-eventtypes', [EventTypeController::class, 'store'])->name('admin.store.eventtypes');
                Route::get('edit-eventtypes/{id}', [EventTypeController::class, 'edit'])->name('admin.edit.eventtypes');
                Route::post('update-eventtypes', [EventTypeController::class, 'updateAmbassadorType'])->name('admin.update.eventtypes');

                // locations route
                Route::get('locations', [LocationController::class, 'index'])->name('admin.locations');
                Route::get('location/create', [LocationController::class, 'create'])->name('admin.location.create');
                Route::post('store-location', [LocationController::class, 'store'])->name('admin.store.location');
                Route::get('edit-location/{id}', [LocationController::class, 'edit'])->name('admin.edit.location');
                Route::post('update-location', [LocationController::class, 'updateLocation'])->name('admin.update.location');

                // Event Location
                Route::get('event-locations', [EventLocationController::class, 'index'])->name('admin.event.locations');
                Route::get('event-location/create', [EventLocationController::class, 'create'])->name('admin.event.location.create');
                Route::post('store-event-location', [EventLocationController::class, 'store'])->name('admin.store.event.location');
                Route::get('edit-event-location/{id}', [EventLocationController::class, 'edit'])->name('admin.edit.event.location');
                Route::post('update-event-location', [EventLocationController::class, 'updateLocation'])->name('admin.update.event.location');
                Route::post('event-locations/sync-lat-lng', [EventLocationController::class, 'syncLatLng'])->name('admin.event.locations.sync-lat-lng');

                // Event Location
                Route::get('pos-user', [POSUserController::class, 'index'])->name('admin.pos.user');
                Route::get('pos-user-create', [POSUserController::class, 'create'])->name('admin.create.pos.user');
                Route::post('pos-user-store', [POSUserController::class, 'store'])->name('admin.store.pos.user');
                Route::get('edit-pos-user/{id}', [POSUserController::class, 'edit'])->name('admin.edit.pos.user');
                Route::post('update-pos-user', [POSUserController::class, 'updateLocation'])->name('admin.update.pos.user');

                // ambassadors route
                Route::get('ambassadors', [AmbassadorController::class, 'index'])->name('admin.ambassadors');
                Route::get('ambassador/create', [AmbassadorController::class, 'create'])->name('admin.ambassador.create');
                Route::post('store-ambassador', [AmbassadorController::class, 'store'])->name('admin.store.ambassador');
                Route::get('ambassadors/{ambassador}', [AmbassadorController::class, 'show'])->name('admin.ambassadors.show');
                Route::get('edit-ambassador/{id}', [AmbassadorController::class, 'edit'])->name('admin.edit.ambassador');
                Route::post('update-ambassador', [AmbassadorController::class, 'updateAmbassador'])->name('admin.update.ambassador');
                Route::post('delete-ambassador/{id}', [AmbassadorController::class, 'destroy'])->name('admin.delete.ambassador');
                Route::post('filter-ambassador', [AmbassadorController::class, 'filterAmbassador'])->name('admin.filter-ambassador');
                Route::post('ambassadors/{ambassador}/remove-markets', [AmbassadorController::class, 'removeMarkets'])->name('admin.ambassadors.remove-markets');
                Route::post('ambassadors/{ambassador}/send-banking-reminder', [AmbassadorController::class, 'sendBankingReminder'])->name('admin.send-banking-reminder');
                Route::post('ambassadors/send-bulk-banking-reminder', [AmbassadorController::class, 'sendBulkBankingReminder'])->name('admin.send-bulk-banking-reminder');

                // Managers route
                Route::get('managers', [ManagerCotroller::class, 'index'])->name('admin.managers');
                Route::get('manager/create', [ManagerCotroller::class, 'create'])->name('admin.manager.create');
                Route::post('store-manager', [ManagerCotroller::class, 'store'])->name('admin.store.manager');
                Route::get('edit-manager/{id}', [ManagerCotroller::class, 'edit'])->name('admin.edit.manager');
                Route::post('update-manager', [ManagerCotroller::class, 'updateManager'])->name('admin.update.manager');

                // Venue Route
                Route::get('venue-type', [VenueController::class, 'index'])->name('admin.venues');
                Route::get('venue/create', [VenueController::class, 'create'])->name('admin.venue.create');
                Route::post('store-venue', [VenueController::class, 'store'])->name('admin.store.venue');
                Route::get('venue/edit/{id}', [VenueController::class, 'edit'])->name('admin.edit.venue');
                Route::post('update-venue', [VenueController::class, 'updateVenue'])->name('admin.update.venue');

                // settings
                Route::get('setting', [SettingController::class, 'setting'])->name('admin.setting');

                // Button Design System Showcase
                Route::get('buttons', [ButtonShowcaseController::class, 'index'])->name('admin.buttons');
                Route::post('update-setting', [SettingController::class, 'updateSetting'])->name('admin.settings.updateSetting');

                Route::get('bill-to', [BillController::class, 'index'])->name('admin.bills');
                Route::get('bill-to/export', [BillController::class, 'export'])->name('admin.bills.export');
                Route::post('bill-to/sync-all', [BillController::class, 'syncAllClients'])->name('admin.bills.sync-all');
                Route::post('bill-to/bulk-update', [BillController::class, 'bulkUpdate'])->name('admin.bills.bulk-update');
                Route::post('bill-to/{client}/parent', [BillController::class, 'updateParent'])->name('admin.bills.parent');
                Route::post('bill-to/{client}/sync', [BillController::class, 'syncClient'])->name('admin.bills.sync-client');
                Route::get('bill-to/{client}/monthly-stats', [BillController::class, 'monthlyStats'])->name('admin.bills.monthly-stats');
                Route::get('bulks', [BulkController::class, 'index'])->name('admin.bulks');
                Route::get('import-data', [ImportDataController::class, 'index'])->name('admin.import-data');
                Route::post('import-data/brands', [ImportDataController::class, 'importBrands'])->name('admin.import-data.brands');
                Route::post('import-data/distributors', [ImportDataController::class, 'importDistributors'])->name('admin.import-data.distributors');
                Route::post('import-data/locations', [ImportDataController::class, 'importLocations'])->name('admin.import-data.locations');
                Route::post('import-data/markets', [ImportDataController::class, 'importMarkets'])->name('admin.import-data.markets');
                Route::get('receipt-ocr', [ReceiptOcrController::class, 'index'])->name('admin.receipt-ocr.index');
                Route::post('receipt-ocr', [ReceiptOcrController::class, 'store'])->name('admin.receipt-ocr.store');
                Route::post('bulk/preview', [BulkController::class, 'preview'])->name('admin.bulk.preview');
                Route::post('bulk/cancel', [BulkController::class, 'cancel'])->name('admin.bulk.cancel');
                Route::post('bulk/update', [BulkController::class, 'update'])->name('admin.bulk.update');
                Route::get('models', [ModelController::class, 'index'])->name('admin.models');
                Route::get('invoices', [InvoiceController::class, 'index'])->name('admin.invoices');
                Route::get('event-invoicing', [InvoiceController::class, 'eventInvoicing'])->name('admin.event-invoicing');
                Route::get('event-invoicing/{client}', [InvoiceController::class, 'eventInvoicingShow'])->name('admin.event-invoicing.show');
                Route::post('event-invoicing/{client}/create-month-invoice', [InvoiceController::class, 'createMonthInvoice'])->name('admin.event-invoicing.create-month-invoice');
                Route::get('expenses', [ExpenseController::class, 'index'])->name('admin.expenses');
                Route::post('expenses/sync', [ExpenseController::class, 'sync'])->name('admin.expenses.sync');
                Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('admin.expense-categories.index');
                Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('admin.expense-categories.store');
                Route::patch('expense-categories/{expenseCategory}/default', [ExpenseCategoryController::class, 'setDefault'])->name('admin.expense-categories.default');
                Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('admin.payment-methods.index');
                Route::post('payment-methods/sync', [PaymentMethodController::class, 'sync'])->name('admin.payment-methods.sync');
                Route::patch('payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault'])->name('admin.payment-methods.set-default');
                Route::get('event/{event}/generate-invoice', [InvoiceController::class, 'generateInvoice'])->name('admin.event.generate-invoice');
                Route::get('event/{event}/invoice-review', [InvoiceController::class, 'invoiceReview'])->name('admin.event.invoice-review');
                Route::post('event/{event}/invoice-review/save-draft', [InvoiceController::class, 'saveDraft'])->name('admin.event.invoice-review.save-draft');
                Route::post('event/{event}/invoice-review/publish-drafts', [InvoiceController::class, 'publishDrafts'])->name('admin.event.invoice-review.publish-drafts');
                Route::post('event/{event}/transfer-to-quickbooks', [InvoiceController::class, 'transferEventToQuickBooks'])->name('admin.event.transfer-to-quickbooks');
                Route::post('store-invoice', [InvoiceController::class, 'storeInvoice'])->name('admin.invoice.store');

                Route::resource('services', ServiceController::class)->names('admin.services');
                Route::post('services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('admin.services.toggle-status');
                Route::post('services/sync-to-quickbooks', [ServiceController::class, 'syncToQuickBooks'])->name('admin.services.sync-to-quickbooks');

                Route::post('invoices/sync', [InvoiceController::class, 'sync'])->name('admin.invoices.sync');
                Route::get('invoices/{id}', [InvoiceController::class, 'show'])->name('admin.invoices.show');
                Route::get('invoices/{id}/print', [InvoiceController::class, 'printView'])->name('admin.invoices.print');
                Route::post('invoices/{id}/send-email', [InvoiceController::class, 'sendEmail'])->name('admin.invoices.send-email');

                Route::get('messaging', [MessagingController::class, 'index'])->name('admin.messaging');
                Route::post('send-message', [MessagingController::class, 'sendMessage'])->name('admin.send-message');

                Route::get('/profile', [CommonController::class, 'adminProfile'])->name('admin.profile');
                Route::post('/update-profile', [CommonController::class, 'adminUpdateProfile'])->name('admin.update-profile');

                // payment Route
                Route::get('payment-forecast', [AdminPaymentController::class, 'paymentForcast'])->name('admin.payment-forecast');
                Route::get('payment-forecast/show/{id}', [AdminPaymentController::class, 'paymentForcastShow'])->name('admin.payment-forecast.show');
                Route::get('payments', [AdminPaymentController::class, 'payment'])->name('admin.payments');
                Route::post('payments/generate-payroll', [AdminPaymentController::class, 'generatePayroll'])->name('admin.payments.generate-payroll');
                Route::post('payments/process-payroll', [AdminPaymentController::class, 'processPayroll'])->name('admin.payments.process-payroll');
                Route::get('vendor-1099-report', [Vendor1099ReportController::class, 'index'])->name('admin.vendor-1099.index');
                Route::get('vendor-1099-report/{user}/expenses', [Vendor1099ReportController::class, 'expenses'])->name('admin.vendor-1099.expenses');
                Route::get('vendor-1099-report/{user}/expenses/export', [Vendor1099ReportController::class, 'exportExpenses'])->name('admin.vendor-1099.expenses.export');
                Route::patch('vendor-1099-report/{user}/toggle', [Vendor1099ReportController::class, 'toggle1099Tracked'])->name('admin.vendor-1099.toggle');

                // Dwolla webhooks (admin CRUD)
                Route::get('reimbursements', [AdminReimbursementController::class, 'index'])->name('admin.reimbursements.index');
                Route::get('reimbursements/{reimbursement}', [AdminReimbursementController::class, 'show'])->name('admin.reimbursements.show');
                Route::patch('reimbursements/{reimbursement}/approve', [AdminReimbursementController::class, 'approve'])->name('admin.reimbursements.approve');
                Route::patch('reimbursements/{reimbursement}/reject', [AdminReimbursementController::class, 'reject'])->name('admin.reimbursements.reject');

                Route::get('webhooks', [DwollaWebhookSubscriptionController::class, 'index'])->name('admin.webhooks.index');
                Route::get('webhooks/create', [DwollaWebhookSubscriptionController::class, 'create'])->name('admin.webhooks.create');
                Route::post('webhooks', [DwollaWebhookSubscriptionController::class, 'store'])->name('admin.webhooks.store');
                Route::post('webhooks/{id}/toggle-paused', [DwollaWebhookSubscriptionController::class, 'updatePaused'])->name('admin.webhooks.toggle-paused');
                Route::post('webhooks/{id}/delete', [DwollaWebhookSubscriptionController::class, 'destroy'])->name('admin.webhooks.destroy');
                Route::get('dwolla/balance-alerts', [DwollaBalanceAlertController::class, 'index'])->name('admin.dwolla.balance-alerts');

                // quickbooks
                Route::get('/quickbooks/auth', [QuickBooksController::class, 'auth'])->name('admin.quickbooks.auth');
                Route::get('/quickbooks/callback', [QuickBooksController::class, 'callback']);
                Route::post('/quickbooks/sync-vendors', [QuickBooksController::class, 'syncVendors'])->name('admin.quickbooks.sync-vendors');
                Route::post('/quickbooks/sync-accounts', [QuickBooksController::class, 'syncAccounts'])->name('admin.quickbooks.sync-accounts');
                Route::post('/quickbooks/sync-terms', [QuickBooksController::class, 'syncTerms'])->name('admin.quickbooks.sync-terms');
                Route::post('/quickbooks/sync-payment-methods', [QuickBooksController::class, 'syncPaymentMethods'])->name('admin.quickbooks.sync-payment-methods');
                Route::post('/quickbooks/sync-expense-categories', [QuickBooksController::class, 'syncExpenseCategories'])->name('admin.quickbooks.sync-expense-categories');

                // sync clients with quickbooks
                Route::post('sync-clients-quickbooks', [ClientController::class, 'syncWithQuickBooks'])->name('admin.sync-clients-quickbooks');
                Route::post('clients/{client}/sync-quickbooks', [ClientController::class, 'syncClientWithQuickBooks'])->name('admin.client.sync-quickbooks');

                Route::get('/docuemnts', [ArasDocumentController::class, 'index'])->name('admin.documents');
                Route::post('/uploadDocument', [ArasDocumentController::class, 'uploadDocument']);
                Route::get('/getDocuments', [ArasDocumentController::class, 'getDocuments']);
                Route::put('/documents/{id}', [ArasDocumentController::class, 'update'])->name('admin.documents.update');
                Route::delete('/documents/{id}', [ArasDocumentController::class, 'destroy'])->name('admin.documents.destroy');

            });
        });

        // Manager Routes
        Route::middleware(['role:Manager'])->group(function () {

            Route::prefix('manager')->group(function () {

                // calender
                Route::get('calendar', [CalendarController::class, 'managerCalendar'])->name('manager.calendar');

                // Events route
                Route::get('/events', [EventController::class, 'index'])->name('manager.events');
                Route::get('/event/create', [EventController::class, 'create'])->name('manager.event.create');
                Route::get('duplicate-event/{id}', [EventController::class, 'duplicate'])->name('manager.duplicate.event');
                Route::post('store-event', [EventController::class, 'store'])->name('manager.store.event');
                Route::get('event/resolve-timezone', [EventController::class, 'resolveTimezone'])->name('manager.event.resolve-timezone');
                Route::get('edit-event/{id}', [EventController::class, 'edit'])->name('manager.edit.event');
                Route::post('update-event', [EventController::class, 'updateEvent'])->name('manager.update.event');
                Route::post('events/{event}/notify-ambassadors', [EventController::class, 'notifyBrandAmbassadors'])
                    ->name('manager.events.notify-ambassadors');
                Route::post('delete-event/{id}', [EventController::class, 'destroy'])->name('manager.delete.event');
                Route::post('search-ambassador', [EventController::class, 'searchAmbassador'])->name('manager.search.ambassador');
                Route::post('filter-events', [EventController::class, 'filterEvents'])->name('manager.filter.events');
                Route::post('update-event-status', [EventController::class, 'updateEventStatus'])->name('manager.update-event-status.event');
                Route::post('events/{event}/budget-approve', [EventController::class, 'approveBudget'])->name('manager.events.budget.approve');
                Route::post('events/{event}/budget-deny', [EventController::class, 'denyBudget'])->name('manager.events.budget.deny');
                Route::get('unapprove-ambassador-for-events', [EventController::class, 'unApproveAmbassadorEvents'])->name('manager.unapprove-ambassador-for-events');
                Route::post('assign-amb-event', [EventController::class, 'assignAmbEvent'])->name('manager.assign-amb-event');
                Route::post('remove-ambassador-assignment', [EventController::class, 'removeAmbassadorAssignment'])->name('manager.remove-ambassador-assignment');
                Route::post('events/{id}/toggle-kit-delivered', [EventController::class, 'toggleKitDelivered']);
                Route::post('events/best/send-selected', [EventController::class, 'sendSelectedToBest'])->name('manager.events.best.send-selected');
                Route::post('events/{event}/best/retry', [EventController::class, 'retryBest'])->name('manager.events.best.retry');

                // BEST lookup APIs (for event-creation modal)
                Route::controller(PushEventToBestController::class)->group(function () {
                    Route::get('best-get-account', 'getAccount')->name('manager.best.get-account');
                    Route::get('best-get-program', 'getProgram')->name('manager.best.get-program');
                    Route::get('best-get-consumer-events', 'getConsumerEvents')->name('manager.best.get-consumer-events');
                    Route::get('best-get-tags', 'getTags')->name('manager.best.get-tags');
                    Route::get('best-get-brand-featured', 'getBrandFeatured')->name('manager.best.get-brand-featured');
                });

                // fetch near by ambassador
                Route::get('fetch-nearby-ambassadors', [EventController::class, 'fetchNearByAmbassador'])->name('manager.fetch-nearby-ambassadors');

                // export Events
                Route::get('export-events', [ExportController::class, 'exportEvents'])->name('manager.export.events');
                Route::get('export-recap-events', [ExportController::class, 'exportRecapEvent'])->name('manager.export-recap-events');
                Route::post('recap/{id}/export-email', [ExportController::class, 'emailSingleRecapExport'])->name('manager.recap.export-email');
                Route::get('export-monthly-events', [ExportController::class, 'exportMonthlyEvents'])->name('manager.export-monthly-events');
                Route::get('export-event-invoicing-month', [ExportController::class, 'exportEventInvoicingMonth'])->name('manager.export-event-invoicing-month');
                Route::get('export-event-invoicing-event', [ExportController::class, 'exportEventInvoicingEvent'])->name('manager.export-event-invoicing-event');
                Route::get('export-event-invoicing-client', [ExportController::class, 'exportEventInvoicingClient'])->name('manager.export-event-invoicing-client');
                Route::get('export-brands', [ExportController::class, 'exportBrands'])->name('manager.export.brands');

                // Other admin routes
                Route::get('recaps', [RecapController::class, 'index'])->name('manager.recaps');
                Route::post('recaps-filter-events', [RecapController::class, 'recapFilter'])->name('manager.filter.recap-events');
                Route::get('show-event/{id}', [CommonController::class, 'viewEvent'])->name('manager.show-event');
                Route::get('view-recap/{id}', [CommonController::class, 'viewRecap'])->name('manager.view-recap');

                Route::patch('recap/{id}/manager-fields', [CommonController::class, 'updateRecapManagerFields'])
                    ->name('manager.update-recap-manager-fields');

                // Recap management (same authority as admin)
                Route::get('show-recap/{id}', [CommonController::class, 'showRecap'])->name('manager.show-recap');
                Route::get('/recap/{id}/edit', [AmbassadorRecapController::class, 'editRecap'])->name('manager.edit-recap');
                Route::post('update-recap', [AmbassadorRecapController::class, 'updateRecap'])->name('manager.update-recap');
                Route::patch('approve-recap/{id}', [CommonController::class, 'approveRecap'])->name('manager.recap.approve');
                Route::patch('deny-recap/{id}', [CommonController::class, 'denyRecap'])->name('manager.recap.deny');

                // Client Routes
                Route::get('clients/next-qb-customer-id', [ClientController::class, 'nextQuickBooksCustomerId'])->name('manager.clients.next-qb-customer-id');
                Route::get('clients/{client}/billing-defaults', [ClientController::class, 'billingDefaults'])->name('manager.clients.billing-defaults');
                Route::get('clients', [ClientController::class, 'index'])->name('manager.clients');
                Route::get('clients/create', [ClientController::class, 'create'])->name('manager.clients.create');
                Route::post('store-client', [ClientController::class, 'store'])->name('manager.store.client');
                Route::post('clients/check-similar-billings', [ClientController::class, 'checkSimilarBillings'])->name('manager.clients.check-similar-billings');
                Route::get('edit-client/{id}', [ClientController::class, 'edit'])->name('manager.edit.client');
                Route::get('show-client/{client}', [ClientController::class, 'show'])->name('manager.show.client');
                Route::get('client/{client}/transactions', [ClientController::class, 'transactions'])->name('manager.client.transactions');
                Route::post('client/{client}/transactions/sync', [ClientController::class, 'syncTransactions'])->name('manager.client.transactions.sync');
                Route::post('update-client', [ClientController::class, 'updateClient'])->name('manager.update.client');
                Route::post('sync-clients-quickbooks', [ClientController::class, 'syncWithQuickBooks'])->name('manager.sync-clients-quickbooks');
                Route::post('clients/{client}/sync-quickbooks', [ClientController::class, 'syncClientWithQuickBooks'])->name('manager.client.sync-quickbooks');

                // Brand Route
                Route::get('brands', [BrandController::class, 'index'])->name('manager.brands');
                Route::get('brand/create', [BrandController::class, 'create'])->name('manager.brands.create');
                Route::post('store-brand', [BrandController::class, 'store'])->name('manager.store.brand');
                Route::get('edit-brand/{id}', [BrandController::class, 'edit'])->name('manager.edit.brand');
                Route::post('update-brand', [BrandController::class, 'updateBrand'])->name('manager.update.brand');
                Route::delete('delete-brand/{id}', [BrandController::class, 'destroy'])->name('manager.delete.brand');
                Route::get('brands/{brand}/bill-to-options', [BrandController::class, 'billToOptions'])->name('manager.brands.bill-to-options');

                // Brand Category Route
                Route::get('brand-category', [BrandCategoryController::class, 'index'])->name('manager.brand-category');
                Route::get('brand-category/create', [BrandCategoryController::class, 'create'])->name('manager.brand-category.create');
                Route::post('store-brand-category', [BrandCategoryController::class, 'store'])->name('manager.store.brand-category');
                Route::get('brand-category/edit/{id}', [BrandCategoryController::class, 'edit'])->name('manager.edit.brand-category');
                Route::post('update-brand-category', [BrandCategoryController::class, 'updateBrandCategory'])->name('manager.update.brand-category');
                Route::delete('delete-brand-category/{id}', [BrandCategoryController::class, 'destroy'])->name('manager.delete.brand-category');

                // Event Distributor
                Route::get('event-destributor', [EventDistributorController::class, 'index'])->name('manager.event-destributor');
                Route::get('event-destributor/create', [EventDistributorController::class, 'create'])->name('manager.event-destributor.create');
                Route::post('store-event-destributor', [EventDistributorController::class, 'store'])->name('manager.store.event-destributor');
                Route::get('edit-event-destributor/{id}', [EventDistributorController::class, 'edit'])->name('manager.edit.event-destributor');
                Route::post('update-event-destributor', [EventDistributorController::class, 'updateEventDistributor'])->name('manager.update.event-destributor');
                Route::delete('delete-event-destributor/{id}', [EventDistributorController::class, 'destroy'])->name('manager.delete.event-destributor');

                // Event Department
                Route::get('event-department', [EventDepartmentController::class, 'index'])->name('manager.event-department');
                Route::get('event-department/create', [EventDepartmentController::class, 'create'])->name('manager.event-department.create');
                Route::post('store-event-department', [EventDepartmentController::class, 'store'])->name('manager.store.event-department');
                Route::get('edit-event-department/{id}', [EventDepartmentController::class, 'edit'])->name('manager.edit.event-department');
                Route::post('update-event-department', [EventDepartmentController::class, 'updateEventDepartment'])->name('manager.update.event-department');
                Route::delete('delete-event-department/{id}', [EventDepartmentController::class, 'destroy'])->name('manager.delete.event-department');

                // Event markets
                Route::get('markets', [MarketController::class, 'index'])->name('manager.markets');
                Route::get('markets/create', [MarketController::class, 'create'])->name('manager.market.create');
                Route::post('store-markets', [MarketController::class, 'store'])->name('manager.store.market');
                Route::get('edit-markets/{id}', [MarketController::class, 'edit'])->name('manager.edit.markets');
                Route::post('update-markets', [MarketController::class, 'updateMarket'])->name('manager.update.market');
                Route::delete('delete-markets', [MarketController::class, 'bulkDestroy'])->name('manager.delete.markets.bulk');
                Route::delete('delete-markets/{market}', [MarketController::class, 'destroy'])->name('manager.delete.markets');

                // / Event ambassadorType
                Route::get('eventtypes', [EventTypeController::class, 'index'])->name('manager.eventtypes');
                Route::get('eventtypes/create', [EventTypeController::class, 'create'])->name('manager.eventtypes.create');
                Route::post('store-eventtypes', [EventTypeController::class, 'store'])->name('manager.store.eventtypes');
                Route::get('edit-eventtypes/{id}', [EventTypeController::class, 'edit'])->name('manager.edit.eventtypes');
                Route::post('update-eventtypes', [EventTypeController::class, 'updateAmbassadorType'])->name('manager.update.eventtypes');

                // locations route
                Route::get('locations', [LocationController::class, 'index'])->name('manager.locations');
                Route::get('location/create', [LocationController::class, 'create'])->name('manager.location.create');
                Route::post('store-location', [LocationController::class, 'store'])->name('manager.store.location');
                Route::get('edit-location/{id}', [LocationController::class, 'edit'])->name('manager.edit.location');
                Route::post('update-location', [LocationController::class, 'updateLocation'])->name('manager.update.location');

                // Event Location
                Route::get('event-locations', [EventLocationController::class, 'index'])->name('manager.event.locations');
                Route::get('event-location/create', [EventLocationController::class, 'create'])->name('manager.event.location.create');
                Route::post('store-event-location', [EventLocationController::class, 'store'])->name('manager.store.event.location');
                Route::get('edit-event-location/{id}', [EventLocationController::class, 'edit'])->name('manager.edit.event.location');
                Route::post('update-event-location', [EventLocationController::class, 'updateLocation'])->name('manager.update.event.location');
                Route::post('event-locations/sync-lat-lng', [EventLocationController::class, 'syncLatLng'])->name('manager.event.locations.sync-lat-lng');

                // Event Location
                Route::get('pos-user', [POSUserController::class, 'index'])->name('manager.pos.user');
                Route::get('pos-user-create', [POSUserController::class, 'create'])->name('manager.create.pos.user');
                Route::post('pos-user-store', [POSUserController::class, 'store'])->name('manager.store.pos.user');
                Route::get('edit-pos-user/{id}', [POSUserController::class, 'edit'])->name('manager.edit.pos.user');
                Route::post('update-pos-user', [POSUserController::class, 'updateLocation'])->name('manager.update.pos.user');

                // ambassadors route
                Route::get('ambassadors', [AmbassadorController::class, 'index'])->name('manager.ambassadors');
                Route::get('ambassador/create', [AmbassadorController::class, 'create'])->name('manager.ambassador.create');
                Route::post('store-ambassador', [AmbassadorController::class, 'store'])->name('manager.store.ambassador');
                Route::get('ambassadors/{ambassador}', [AmbassadorController::class, 'show'])->name('manager.ambassadors.show');
                Route::get('edit-ambassador/{id}', [AmbassadorController::class, 'edit'])->name('manager.edit.ambassador');
                Route::post('update-ambassador', [AmbassadorController::class, 'updateAmbassador'])->name('manager.update.ambassador');
                Route::post('delete-ambassador/{id}', [AmbassadorController::class, 'destroy'])->name('manager.delete.ambassador');
                Route::post('filter-ambassador', [AmbassadorController::class, 'filterAmbassador'])->name('manager.filter-ambassador');
                Route::post('ambassadors/{ambassador}/remove-markets', [AmbassadorController::class, 'removeMarkets'])->name('manager.ambassadors.remove-markets');
                Route::post('ambassadors/{ambassador}/send-banking-reminder', [AmbassadorController::class, 'sendBankingReminder'])->name('manager.send-banking-reminder');
                Route::post('ambassadors/send-bulk-banking-reminder', [AmbassadorController::class, 'sendBulkBankingReminder'])->name('manager.send-bulk-banking-reminder');

                // Managers route
                Route::get('managers', [ManagerCotroller::class, 'index'])->name('manager.managers');
                Route::get('manager/create', [ManagerCotroller::class, 'create'])->name('manager.manager.create');
                Route::post('store-manager', [ManagerCotroller::class, 'store'])->name('manager.store.manager');
                Route::get('edit-manager/{id}', [ManagerCotroller::class, 'edit'])->name('manager.edit.manager');
                Route::post('update-manager', [ManagerCotroller::class, 'updateManager'])->name('manager.update.manager');
                Route::post('documents', [ManagerCotroller::class, 'documents'])->name('manager.documents');

                // Aras Document routes (mirrors admin group so Managers can use file picker)
                Route::get('/getDocuments', [ArasDocumentController::class, 'getDocuments']);
                Route::post('/uploadDocument', [ArasDocumentController::class, 'uploadDocument']);
                Route::get('/docuemnts', [ArasDocumentController::class, 'index'])->name('manager.aras-documents');
                Route::put('/documents/{id}', [ArasDocumentController::class, 'update'])->name('manager.aras-documents.update');
                Route::delete('/documents/{id}', [ArasDocumentController::class, 'destroy'])->name('manager.aras-documents.destroy');

                // Venue Route
                Route::get('venue-type', [VenueController::class, 'index'])->name('manager.venues');
                Route::get('venue/create', [VenueController::class, 'create'])->name('manager.venue.create');
                Route::post('store-venue', [VenueController::class, 'store'])->name('manager.store.venue');
                Route::get('venue/edit/{id}', [VenueController::class, 'edit'])->name('manager.edit.venue');
                Route::post('update-venue', [VenueController::class, 'updateVenue'])->name('manager.update.venue');

                // settings
                Route::get('setting', [SettingController::class, 'setting'])->name('manager.setting');
                Route::post('update-setting', [SettingController::class, 'updateSetting'])->name('manager.settings.updateSetting');

                Route::get('bill-to', [BillController::class, 'index'])->name('manager.bills');
                Route::get('bill-to/export', [BillController::class, 'export'])->name('manager.bills.export');
                Route::post('bill-to/sync-all', [BillController::class, 'syncAllClients'])->name('manager.bills.sync-all');
                Route::post('bill-to/bulk-update', [BillController::class, 'bulkUpdate'])->name('manager.bills.bulk-update');
                Route::post('bill-to/{client}/parent', [BillController::class, 'updateParent'])->name('manager.bills.parent');
                Route::post('bill-to/{client}/sync', [BillController::class, 'syncClient'])->name('manager.bills.sync-client');
                Route::get('bill-to/{client}/monthly-stats', [BillController::class, 'monthlyStats'])->name('manager.bills.monthly-stats');
                Route::get('bulks', [BulkController::class, 'index'])->name('manager.bulks');
                Route::get('models', [ModelController::class, 'index'])->name('manager.models');
                Route::get('invoices', [InvoiceController::class, 'index'])->name('manager.invoices');
                Route::get('event-invoicing', [InvoiceController::class, 'eventInvoicing'])->name('manager.event-invoicing');
                Route::get('event-invoicing/{client}', [InvoiceController::class, 'eventInvoicingShow'])->name('manager.event-invoicing.show');
                Route::post('event-invoicing/{client}/create-month-invoice', [InvoiceController::class, 'createMonthInvoice'])->name('manager.event-invoicing.create-month-invoice');
                Route::get('event/{event}/generate-invoice', [InvoiceController::class, 'generateInvoice'])->name('manager.event.generate-invoice');
                Route::get('event/{event}/invoice-review', [InvoiceController::class, 'invoiceReview'])->name('manager.event.invoice-review');
                Route::post('event/{event}/invoice-review/save-draft', [InvoiceController::class, 'saveDraft'])->name('manager.event.invoice-review.save-draft');
                Route::post('event/{event}/invoice-review/publish-drafts', [InvoiceController::class, 'publishDrafts'])->name('manager.event.invoice-review.publish-drafts');
                Route::post('store-invoice', [InvoiceController::class, 'storeInvoice'])->name('manager.invoice.store');
                Route::post('event/{event}/transfer-to-quickbooks', [InvoiceController::class, 'transferEventToQuickBooks'])->name('manager.event.transfer-to-quickbooks');

                // QuickBooks (manager-facing routes mapping to same controllers)
                Route::get('/quickbooks/auth', [QuickBooksController::class, 'auth'])->name('manager.quickbooks.auth');
                Route::get('/quickbooks/callback', [QuickBooksController::class, 'callback']);
                Route::post('/quickbooks/sync-vendors', [QuickBooksController::class, 'syncVendors'])->name('manager.quickbooks.sync-vendors');
                Route::post('/quickbooks/sync-accounts', [QuickBooksController::class, 'syncAccounts'])->name('manager.quickbooks.sync-accounts');
                Route::post('/quickbooks/sync-terms', [QuickBooksController::class, 'syncTerms'])->name('manager.quickbooks.sync-terms');
                Route::post('/quickbooks/sync-payment-methods', [QuickBooksController::class, 'syncPaymentMethods'])->name('manager.quickbooks.sync-payment-methods');
                Route::post('/quickbooks/sync-expense-categories', [QuickBooksController::class, 'syncExpenseCategories'])->name('manager.quickbooks.sync-expense-categories');

                Route::get('expenses', [ExpenseController::class, 'index'])->name('manager.expenses');
                Route::post('expenses/sync', [ExpenseController::class, 'sync'])->name('manager.expenses.sync');
                Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('manager.expense-categories.index');
                Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('manager.expense-categories.store');
                Route::patch('expense-categories/{expenseCategory}/default', [ExpenseCategoryController::class, 'setDefault'])->name('manager.expense-categories.default');

                Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('manager.payment-methods.index');
                Route::post('payment-methods/sync', [PaymentMethodController::class, 'sync'])->name('manager.payment-methods.sync');
                Route::patch('payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault'])->name('manager.payment-methods.set-default');

                Route::get('vendor-1099-report', [Vendor1099ReportController::class, 'index'])->name('manager.vendor-1099.index');
                Route::get('vendor-1099-report/{user}/expenses', [Vendor1099ReportController::class, 'expenses'])->name('manager.vendor-1099.expenses');
                Route::get('vendor-1099-report/{user}/expenses/export', [Vendor1099ReportController::class, 'exportExpenses'])->name('manager.vendor-1099.expenses.export');
                Route::patch('vendor-1099-report/{user}/toggle', [Vendor1099ReportController::class, 'toggle1099Tracked'])->name('manager.vendor-1099.toggle');

                Route::resource('services', ServiceController::class)->names('manager.services');
                Route::post('services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('manager.services.toggle-status');
                Route::post('services/sync-to-quickbooks', [ServiceController::class, 'syncToQuickBooks'])->name('manager.services.sync-to-quickbooks');

                Route::post('invoices/sync', [InvoiceController::class, 'sync'])->name('manager.invoices.sync');
                Route::get('invoices/{id}', [InvoiceController::class, 'show'])->name('manager.invoices.show');
                Route::get('invoices/{id}/print', [InvoiceController::class, 'printView'])->name('manager.invoices.print');
                Route::post('invoices/{id}/send-email', [InvoiceController::class, 'sendEmail'])->name('manager.invoices.send-email');

                Route::get('messaging', [MessagingController::class, 'index'])->name('manager.messaging');
                Route::post('send-message', [MessagingController::class, 'sendMessage'])->name('manager.send-message');

                Route::get('reimbursements', [AdminReimbursementController::class, 'index'])->name('manager.reimbursements.index');
                Route::get('reimbursements/create', [AmbassadorReimbursementController::class, 'create'])->name('manager.reimbursements.create');
                Route::post('reimbursements', [AmbassadorReimbursementController::class, 'store'])->name('manager.reimbursements.store');
                Route::get('reimbursements/{reimbursement}', [AdminReimbursementController::class, 'show'])->name('manager.reimbursements.show');
                Route::patch('reimbursements/{reimbursement}/approve', [AdminReimbursementController::class, 'approve'])->name('manager.reimbursements.approve');
                Route::patch('reimbursements/{reimbursement}/reject', [AdminReimbursementController::class, 'reject'])->name('manager.reimbursements.reject');
                Route::get('/profile', [CommonController::class, 'adminProfile'])->name('manager.profile');
                Route::post('/update-profile', [CommonController::class, 'adminUpdateProfile'])->name('manager.update-profile');

                // payment Route
                Route::get('payment-forecast', [AdminPaymentController::class, 'paymentForcast'])->name('manager.payment-forecast');
                Route::get('payments', [AdminPaymentController::class, 'payment'])->name('manager.payments');
                Route::post('payments/generate-payroll', [AdminPaymentController::class, 'generatePayroll'])->name('manager.payments.generate-payroll');
                Route::post('payments/process-payroll', [AdminPaymentController::class, 'processPayroll'])->name('manager.payments.process-payroll');

            });
        });

        // Client Routes
        Route::middleware(['role:Client'])->group(function () {
            Route::prefix('client')->group(function () {

                Route::get('calendar', [CalendarController::class, 'calendar'])->name('client.calendar');

                Route::get('/profile', [CommonController::class, 'clientProfile'])->name('client.profile');
                Route::post('/update-profile', [CommonController::class, 'updateClientProfile'])->name('client.update-profile');

                Route::get('/events', [ClientEventController::class, 'index'])->name('client.events');
                Route::get('show-event/{id}', [CommonController::class, 'viewEvent'])->name('client.show-event');
                Route::get('view-recap/{id}', [CommonController::class, 'viewRecap'])->name('client.view-recap');
                Route::post('location-filter-events', [ClientEventController::class, 'clientFilterEvents'])->name('client.filter.events');

                Route::get('recaps', [RecapController::class, 'index'])->name('client.recaps');
                Route::post('recaps-filter-events', [RecapController::class, 'recapFilter'])->name('client.filter.recap-events');

                Route::get('client-export-events', [ExportController::class, 'clientExportEvent'])->name('client.export.events');
                Route::get('client-export-recap-events', [ExportController::class, 'exportRecapEvent'])->name('client.export.recap-events');
                Route::post('recap/{id}/export-email', [ExportController::class, 'emailSingleRecapExport'])->name('client.recap.export-email');

                Route::get('bank-account', [ClientController::class, 'showForm'])->name('client.bank-account');
            });
        });

        // Location Routes
        Route::middleware(['role:Location'])->group(function () {
            Route::prefix('location')->group(function () {

                Route::get('calendar', [CalendarController::class, 'calendar'])->name('location.calendar');

                Route::get('/events', [LocationEventController::class, 'index'])->name('location.events');
                Route::get('show-event/{id}', [CommonController::class, 'viewEvent'])->name('location.show-event');
                Route::get('view-recap/{id}', [CommonController::class, 'viewRecap'])->name('location.view-recap');
                Route::post('location-filter-events', [LocationEventController::class, 'locationFilterEvents'])->name('location.filter.events');

                Route::get('/profile', [CommonController::class, 'locationProfile'])->name('location.profile');
                Route::post('/update-profile', [CommonController::class, 'updateLocationProfile'])->name('location.update-profile');

                Route::get('export-events', [ExportController::class, 'exportEvents'])->name('location.export.events');
            });
        });

        // Brand Routes
        Route::middleware(['role:Brand'])->group(function () {
            Route::prefix('brand')->group(function () {

                Route::get('calendar', [CalendarController::class, 'calendar'])->name('brand.calendar');
                Route::get('events', [BrandEventController::class, 'index'])->name('brand.events');
                Route::post('getEvents', [BrandEventController::class, 'getEvents'])->name('events.getEvents');

                Route::get('/profile', function () {
                    return view('profile');
                })->name('brand.profile');

                Route::get('recaps', [RecapController::class, 'index'])->name('brand.recaps');
                Route::get('clients', [ClientController::class, 'index'])->name('brand.clients');
                Route::get('brands', [BrandController::class, 'index'])->name('brand.brands');
                Route::get('bill-to', [BillController::class, 'index'])->name('brand.bills');
                Route::get('bulks', [BulkController::class, 'index'])->name('brand.bulks');
                Route::get('models', [ModelController::class, 'index'])->name('brand.models');
                Route::get('invoices', [InvoiceController::class, 'index'])->name('brand.invoices');
            });
        });

        // Ambassador Routes
        Route::prefix('ambassador')->name('ambassador.')->middleware(['role:Ambassador'])->group(function () {

            Route::get('calendar', [CalendarController::class, 'calendar'])->name('calendar');

            Route::controller(CommonController::class)->group(function () {
                Route::get('/profile', 'profile')->name('profile');
                Route::post('/update-profile', 'updateProfile')->name('update-profile');
                Route::get('show-event/{id}', 'viewEvent')->name('show-event');
                Route::get('view-recap/{id}', 'viewRecap')->name('view-recap');
            });

            Route::get('/events', [AmbassadorEventController::class, 'index'])->name('events');
            Route::post('/apply-event', [AmbassadorEventController::class, 'applyEvent'])->name('apply-event');
            Route::post('/withdraw-event', [AmbassadorEventController::class, 'withdrawEvent'])->name('withdraw-event');
            Route::post('/check-in-event', [AmbassadorEventController::class, 'checkInEvent'])->name('check-in-event');

            // Best APi
            Route::controller(PushEventToBestController::class)->group(function () {
                Route::get('best-get-account', 'getAccount');
                Route::get('best-get-program', 'getProgram');
                Route::get('best-get-consumer-events', 'getConsumerEvents');
                Route::get('best-get-tags', 'getTags');
                Route::get('best-get-brand-featured', 'getBrandFeatured');
            });

            // Recaps
            Route::controller(AmbassadorRecapController::class)->group(function () {
                Route::get('recaps', 'index')->name('recaps');
                Route::get('recap-drafts', 'drafts')->name('recap-drafts');
                Route::get('create-recap/{id}', 'create')->name('create-recap');
                Route::post('store-recap', 'store')->name('store-recap');
                Route::get('/recap/{id}/edit', 'editRecap')->name('edit-recap');
                Route::post('update-recap', 'updateRecap')->name('update-recap');
            });

            Route::get('submitted-recap', [SubmitRecapController::class, 'index'])->name('submitted-recap');
            Route::post('receipt-ocr', AmbassadorReceiptOcrController::class)->name('receipt-ocr');
            Route::get('pay-forecast', [PayForecastController::class, 'index'])->name('pay-forecast');
            Route::get('payment', [PaymentController::class, 'index'])->name('payment');

            Route::get('reimbursements', [AmbassadorReimbursementController::class, 'index'])->name('reimbursements.index');
            Route::get('reimbursements/create', [AmbassadorReimbursementController::class, 'create'])->name('reimbursements.create');
            Route::post('reimbursements', [AmbassadorReimbursementController::class, 'store'])->name('reimbursements.store');
            Route::get('reimbursements/{reimbursement}', [AmbassadorReimbursementController::class, 'show'])->name('reimbursements.show');
            Route::get('reimbursements/{reimbursement}/edit', [AmbassadorReimbursementController::class, 'edit'])->name('reimbursements.edit');
            Route::put('reimbursements/{reimbursement}', [AmbassadorReimbursementController::class, 'update'])->name('reimbursements.update');

            Route::get('clients', [ClientController::class, 'index'])->name('clients');
            Route::get('brands', [BrandController::class, 'index'])->name('brands');
            Route::get('bill-to', [BillController::class, 'index'])->name('bills');
            Route::get('bulks', [BulkController::class, 'index'])->name('bulks');
            Route::get('models', [ModelController::class, 'index'])->name('models');
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices');

            // dwolla routes

            Route::controller(DwollaController::class)
                ->prefix('dwolla')
                ->name('dwolla.')
                ->group(function () {

                    Route::get('/payment-setup', 'paymentSetupForm')->name('payment-setup');
                    Route::post('/payment-setup', 'completePaymentSetup')->name('payment-setup.complete');

                    Route::get('/bank', 'bank')->name('bank-account');
                    Route::post('/bank', 'addBank')->name('bank');
                    Route::delete('/bank/{fundingSource}', 'deleteBank')->name('bank.delete');
                    Route::get('/bank/verify/{source}', 'verifyForm')->name('bank.verify.form');
                    Route::post('/bank/verify', 'verify')->name('bank.verify');
                    Route::post('bank/{bank}/set-default', 'setDefault')->name('bank.setDefault');

                    // transactions
                    Route::get('/bank/transactions', 'transactions')->name('bank.transactions');
                });

            // W9 Form routes
            Route::get('/w9-form', [W9FormController::class, 'show'])->name('w9.show');
            Route::post('/w9-form', [W9FormController::class, 'store'])->name('w9.store');
        });

        // Notifications (common to all authenticated roles)
        Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/seen', [NotificationsController::class, 'markSeen'])
            ->name('notifications.seen');

        Route::get('/notifications/settings', [NotificationSettingsController::class, 'edit'])
            ->name('notifications.settings');
        Route::post('/notifications/settings', [NotificationSettingsController::class, 'update'])
            ->name('notifications.settings.update');

        Route::post('/notifications/push-token', [NotificationSettingsController::class, 'storePushToken'])
            ->name('notifications.push-token');
    });
});

Route::get('test', [UserController::class, 'createDwollaCustomer']);

Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return 'Cleared!';
});

// One-time admin route to create + seed the `states` table on production.
// Bug 2 (MC cmon1a7u4005d3r1h5cdct2vh): no states migration/seeder existed,
// so the State dropdown on the recap form was empty in prod.
// Idempotent: hitting this endpoint repeatedly only adds missing states.
Route::get('/admin/seed-states', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = trim(\Illuminate\Support\Facades\Artisan::output());

    \Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\StateSeeder',
        '--force' => true,
    ]);
    $seedOutput = trim(\Illuminate\Support\Facades\Artisan::output());

    $count = \App\Models\State::count();
    return response()->json([
        'ok' => true,
        'states_in_table' => $count,
        'migrate_output' => $migrateOutput,
        'seed_output' => $seedOutput,
    ]);
});

// Bug 2 (MC cmon1a7u4005d3r1h5cdct2vh): one-time setup endpoint to create
// + seed the `states` table on prod. Idempotent. Deliberately does NOT call
// `migrate --force` because the legacy backend was server-only before
// 2026-04-28 and its migration history may not match the repo — a bulk
// migrate could run unrelated migrations against production.
Route::get('/admin/seed-states', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('states')) {
        \Illuminate\Support\Facades\Schema::create('states', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 8)->nullable();
            $table->timestamps();
        });
    } elseif (! \Illuminate\Support\Facades\Schema::hasColumn('states', 'short_name')) {
        \Illuminate\Support\Facades\Schema::table('states', function ($table) {
            $table->string('short_name', 8)->nullable()->after('name');
        });
        if (\Illuminate\Support\Facades\Schema::hasColumn('states', 'abbreviation')) {
            \Illuminate\Support\Facades\DB::table('states')
                ->whereNull('short_name')
                ->update(['short_name' => \Illuminate\Support\Facades\DB::raw('abbreviation')]);
        }
    }

    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\StateSeeder',
        '--force' => true,
    ]);

    return response()->json([
        'ok'         => true,
        'message'    => 'States table ensured and seeded successfully.',
        'seed_output'=> Artisan::output(),
        'count'      => \Illuminate\Support\Facades\DB::table('states')->count(),
    ]);
});

// PWA Routes
Route::get('/all-routes', function () {
    $routes = collect(Route::getRoutes())->map(function ($route) {
        return $route->uri();
    })->toArray();

    return response()->json($routes);
});

Route::get('/offline', function () {
    return view('vendor.laravelpwa.offline');
});

Route::post('/events/import', [EventImportController::class, 'importExcel'])->name('admin.events.import.process');
