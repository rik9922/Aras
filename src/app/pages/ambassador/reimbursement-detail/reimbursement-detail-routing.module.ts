import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ReimbursementDetailPage } from './reimbursement-detail.page';

const routes: Routes = [
  {
    path: '',
    component: ReimbursementDetailPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ReimbursementDetailPageRoutingModule {}
