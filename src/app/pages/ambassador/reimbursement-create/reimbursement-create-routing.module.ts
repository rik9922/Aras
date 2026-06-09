import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ReimbursementCreatePage } from './reimbursement-create.page';

const routes: Routes = [
  {
    path: '',
    component: ReimbursementCreatePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ReimbursementCreatePageRoutingModule {}
