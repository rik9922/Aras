import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AdminReimbursementDetailPage } from './admin-reimbursement-detail.page';

const routes: Routes = [
  {
    path: '',
    component: AdminReimbursementDetailPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AdminReimbursementDetailPageRoutingModule {}
