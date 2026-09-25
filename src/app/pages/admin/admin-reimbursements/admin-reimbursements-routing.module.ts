import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AdminReimbursementsPage } from './admin-reimbursements.page';

const routes: Routes = [
  {
    path: '',
    component: AdminReimbursementsPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AdminReimbursementsPageRoutingModule {}
