import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ReimbursementEditPage } from './reimbursement-edit.page';

const routes: Routes = [
  {
    path: '',
    component: ReimbursementEditPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ReimbursementEditPageRoutingModule {}
