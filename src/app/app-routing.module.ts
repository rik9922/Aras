import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';

const routes: Routes = [
  {
    path: '',
    loadChildren: () => import('./tabs/tabs.module').then(m => m.TabsPageModule)
  },
  {
    path: 'login',
    loadChildren: () => import('./beforeAuth/login/login.module').then(m => m.LoginPageModule)
  },
  {
    path: 'forget-password',
    loadChildren: () => import('./beforeAuth/forget-password/forget-password.module').then(m => m.ForgetPasswordPageModule)
  },
  {
    path: 'verify-otp',
    loadChildren: () => import('./beforeAuth/verify-otp/verify-otp.module').then(m => m.VerifyOtpPageModule)
  },
  {
    path: 'reset-password',
    loadChildren: () => import('./beforeAuth/reset-password/reset-password.module').then(m => m.ResetPasswordPageModule)
  },




  //  Admin role pages 
  {
    path: 'admin-home',
    loadChildren: () => import('./pages/admin/admin-home/admin-home.module').then(m => m.AdminHomePageModule)
  },
  {
    path: 'admin-event-list',
    loadChildren: () => import('./pages/admin/events/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-event-type',
    loadChildren: () => import('./pages/admin/events/event-type/event-type.module').then(m => m.EventTypePageModule)
  },
  {
    path: 'admin-client-list',
    loadChildren: () => import('./pages/admin/client/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-client-add-update',
    loadChildren: () => import('./pages/admin/client/add-update/add-update.module').then(m => m.AddUpdatePageModule)
  },
  {
    path: 'admin-client-view',
    loadChildren: () => import('./pages/admin/client/view/view.module').then(m => m.ViewPageModule)
  },
  {
    path: 'admin-client-transactions',
    loadChildren: () => import('./pages/admin/client/transactions/transactions.module').then(m => m.TransactionsPageModule)
  },
  {
    path: 'admin-brand-list',
    loadChildren: () => import('./pages/admin/brand/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-brand-category',
    loadChildren: () => import('./pages/admin/brand/category/category.module').then(m => m.CategoryPageModule)
  },
  {
    path: 'admin-recap-list',
    loadChildren: () => import('./pages/admin/recap/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-ambassador-list',
    loadChildren: () => import('./pages/admin/ambassador/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-department-list',
    loadChildren: () => import('./pages/admin/department/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-add-location',
    loadChildren: () => import('./pages/admin/location/add-location/add-location.module').then(m => m.AddLocationPageModule)
  },
  {
    path: 'admin-location-list',
    loadChildren: () => import('./pages/admin/location/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-venue-list',
    loadChildren: () => import('./pages/admin/venue/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-add-venue',
    loadChildren: () => import('./pages/admin/venue/add-venue/add-venue.module').then(m => m.AddVenuePageModule)
  },
  {
    path: 'admin-event-date-wise-list',
    loadChildren: () => import('./pages/admin/events/date-wise-list/date-wise-list.module').then(m => m.DateWiseListPageModule)
  },
  {
    path: 'admin-user-location-list',
    loadChildren: () => import('./pages/admin/user-location/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-add-user-location',
    loadChildren: () => import('./pages/admin/user-location/add-user-location/add-user-location.module').then(m => m.AddUserLocationPageModule)
  },
  {
    path: 'admin-market-list',
    loadChildren: () => import('./pages/admin/market/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-add-market',
    loadChildren: () => import('./pages/admin/market/add-market/add-market.module').then(m => m.AddMarketPageModule)
  },
  {
    path: 'admin-distributor-list',
    loadChildren: () => import('./pages/admin/distributor/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-add-distributor',
    loadChildren: () => import('./pages/admin/distributor/add-distributor/add-distributor.module').then(m => m.AddDistributorPageModule)
  },
  {
    path: 'admin-manager-list',
    loadChildren: () => import('./pages/admin/manager/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-add-manager',
    loadChildren: () => import('./pages/admin/manager/add-manager/add-manager.module').then(m => m.AddManagerPageModule)
  },
  {
    path: 'admin-pos-user-list',
    loadChildren: () => import('./pages/admin/pos-user/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-add-pos-user',
    loadChildren: () => import('./pages/admin/pos-user/add-pos-user/add-pos-user.module').then(m => m.AddPosUserPageModule)
  },
  {
    path: 'admin-settings',
    loadChildren: () => import('./pages/admin/settings/settings.module').then(m => m.SettingsPageModule)
  },
  {
    path: 'admin-my-account',
    loadChildren: () => import('./pages/admin/my-account/my-account.module').then(m => m.MyAccountPageModule)
  },
  {
    path: 'admin-unapprove-ambassador-list',
    loadChildren: () => import('./pages/admin/unapprove-ambassador/list/list.module').then(m => m.ListPageModule)
  },
  {
    path: 'admin-add-ambassador',
    loadChildren: () => import('./pages/admin/ambassador/add-ambassador/add-ambassador.module').then(m => m.AddAmbassadorPageModule)
  },
  {
    path: 'admin-add-recap',
    loadChildren: () => import('./pages/admin/recap/add-recap/add-recap.module').then(m => m.AddRecapPageModule)
  },
  {
    path: 'admin-view-recap',
    loadChildren: () => import('./pages/admin/recap/view-recap/view-recap.module').then(m => m.ViewRecapPageModule)
  },




  {
    path: 'brand-home',
    loadChildren: () => import('./pages/brand/brand-home/brand-home.module').then(m => m.BrandHomePageModule)
  },
  {
    path: 'client-home',
    loadChildren: () => import('./pages/client/client-home/client-home.module').then(m => m.ClientHomePageModule)
  },

  {
    path: 'ambassador-home',
    loadChildren: () => import('./pages/ambassador/ambassador-home/ambassador-home.module').then(m => m.AmbassadorHomePageModule)
  },


  //  Location user role 
  {
    path: 'location-home',
    loadChildren: () => import('./pages/location/location-home/location-home.module').then(m => m.LocationHomePageModule)
  },
  {
    path: 'location-events',
    loadChildren: () => import('./pages/location/location-events/location-events.module').then(m => m.LocationEventsPageModule)
  },
  {
    path: 'location-account',
    loadChildren: () => import('./pages/location/location-account/location-account.module').then(m => m.LocationAccountPageModule)
  },
  {
    path: 'location-event-details',
    loadChildren: () => import('./pages/location/location-event-details/location-event-details.module').then(m => m.LocationEventDetailsPageModule)
  },
  {
    path: 'brand-events',
    loadChildren: () => import('./pages/brand/brand-events/brand-events.module').then(m => m.BrandEventsPageModule)
  },
  {
    path: 'client-events',
    loadChildren: () => import('./pages/client/client-events/client-events.module').then(m => m.ClientEventsPageModule)
  },
  {
    path: 'client-account',
    loadChildren: () => import('./pages/client/client-account/client-account.module').then(m => m.ClientAccountPageModule)
  },
  {
    path: 'client-recap',
    loadChildren: () => import('./pages/client/client-recap/client-recap.module').then(m => m.ClientRecapPageModule)
  },
  {
    path: 'client-event-details',
    loadChildren: () => import('./pages/client/client-event-details/client-event-details.module').then(m => m.ClientEventDetailsPageModule)
  },
  {
    path: 'add-events',
    loadChildren: () => import('./pages/admin/events/add-events/add-events.module').then(m => m.AddEventsPageModule)
  },
  {
    path: 'add-brand',
    loadChildren: () => import('./pages/admin/brand/add-brand/add-brand.module').then(m => m.AddBrandPageModule)
  },
  {
    path: 'add-events-department',
    loadChildren: () => import('./pages/admin/department/add-events-department/add-events-department.module').then(m => m.AddEventsDepartmentPageModule)
  },
  {
    path: 'update-events-department',
    loadChildren: () => import('./pages/admin/department/add-events-department/add-events-department.module').then(m => m.AddEventsDepartmentPageModule)
  },
  {
    path: 'ambassador-events',
    loadChildren: () => import('./pages/ambassador/ambassador-events/ambassador-events.module').then(m => m.AmbassadorEventsPageModule)
  },
  {
    path: 'ambassador-profile',
    loadChildren: () => import('./pages/ambassador/ambassador-profile/ambassador-profile.module').then(m => m.AmbassadorProfilePageModule)
  },
  {
    path: 'ambassador-recap',
    loadChildren: () => import('./pages/ambassador/recap/recap.module').then(m => m.RecapPageModule)
  },
  {
    path: 'create-recap',
    loadChildren: () => import('./pages/ambassador/create-recap/create-recap.module').then(m => m.CreateRecapPageModule)
  },
  {
    path: 'submitted-recap',
    loadChildren: () => import('./pages/ambassador/submitted-recap/submitted-recap.module').then(m => m.SubmittedRecapPageModule)
  },
  {
    path: 'pay-forecast',
    loadChildren: () => import('./pages/ambassador/pay-forecast/pay-forecast.module').then(m => m.PayForecastPageModule)
  },
  {
    path: 'aras-document',
    loadChildren: () => import('./pages/ambassador/aras-document/aras-document.module').then(m => m.ArasDocumentPageModule)
  },
  {
    path: 'pay-forecast-detail',
    loadChildren: () => import('./pages/ambassador/pay-forecast-detail/pay-forecast-detail.module').then(m => m.PayForecastDetailPageModule)
  },
  {
    path: 'ambassador-payment',
    loadChildren: () => import('./pages/ambassador/payment/payment.module').then(m => m.PaymentPageModule)
  },
  {
    path: 'banking',
    loadChildren: () => import('./pages/ambassador/banking/banking.module').then(m => m.BankingPageModule)
  },
  {
    path: 'banking-transactions',
    loadChildren: () => import('./pages/ambassador/banking-transactions/banking-transactions.module').then(m => m.BankingTransactionsPageModule)
  },
  {
    path: 'banking-add',
    loadChildren: () => import('./pages/ambassador/banking-add/banking-add.module').then(m => m.BankingAddPageModule)
  },
  {
    path: 'banking-verify',
    loadChildren: () => import('./pages/ambassador/banking-verify/banking-verify.module').then(m => m.BankingVerifyPageModule)
  },
  {
    path: 'reimbursements',
    loadChildren: () => import('./pages/ambassador/reimbursement/reimbursement.module').then(m => m.ReimbursementPageModule)
  },
  {
    path: 'reimbursement-create',
    loadChildren: () => import('./pages/ambassador/reimbursement-create/reimbursement-create.module').then(m => m.ReimbursementCreatePageModule)
  },
  {
    path: 'reimbursement-detail',
    loadChildren: () => import('./pages/ambassador/reimbursement-detail/reimbursement-detail.module').then(m => m.ReimbursementDetailPageModule)
  },
  {
    path: 'reimbursement-edit',
    loadChildren: () => import('./pages/ambassador/reimbursement-edit/reimbursement-edit.module').then(m => m.ReimbursementEditPageModule)
  },
  {
    path: 'event-details',
    loadChildren: () => import('./pages/admin/events/event-details/event-details.module').then(m => m.EventDetailsPageModule)
  },
  {
    path: 'ambassador-edit-recap',
    loadChildren: () => import('./pages/ambassador/ambassador-edit-recap/ambassador-edit-recap.module').then(m => m.AmbassadorEditRecapPageModule)
  },
  {
    path: 'admin-pay-forecast',
    loadChildren: () => import('./pages/admin/admin-pay-forecast/admin-pay-forecast.module').then(m => m.AdminPayForecastPageModule)
  },
  {
    path: 'admin-payment',
    loadChildren: () => import('./pages/admin/admin-payment/admin-payment.module').then(m => m.AdminPaymentPageModule)
  },
  {
    path: 'admin/reimbursements/:id',
    loadChildren: () => import('./pages/admin/admin-reimbursements/detail/admin-reimbursement-detail.module').then(m => m.AdminReimbursementDetailPageModule)
  },
  {
    path: 'admin/reimbursements',
    loadChildren: () => import('./pages/admin/admin-reimbursements/admin-reimbursements.module').then(m => m.AdminReimbursementsPageModule)
  },
  {
    path: 'bill',
    loadChildren: () => import('./pages/admin/bill/bill.module').then(m => m.BillPageModule)
  },
  {
    path: 'bulk',
    loadChildren: () => import('./pages/admin/bulk/bulk.module').then(m => m.BulkPageModule)
  },
  {
    path: 'invoice',
    loadChildren: () => import('./pages/admin/invoice/invoice.module').then(m => m.InvoicePageModule)
  },
  {
    path: 'messaging',
    loadChildren: () => import('./pages/admin/messaging/messaging.module').then(m => m.MessagingPageModule)
  },
  {
    path: 'message',
    loadChildren: () => import('./commonPages/message/message.module').then( m => m.MessagePageModule)
  },
  {
    path: 'chat',
    loadChildren: () => import('./commonPages/chat/chat.module').then( m => m.ChatPageModule)
  },



];
@NgModule({
  imports: [
    RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules })
  ],
  exports: [RouterModule]
})
export class AppRoutingModule { }
