import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AdminPaymentPage } from './admin-payment.page';

const routes: Routes = [
  {
    path: '',
    component: AdminPaymentPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AdminPaymentPageRoutingModule {}
