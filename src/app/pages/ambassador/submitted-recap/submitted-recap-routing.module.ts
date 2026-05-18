import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { SubmittedRecapPage } from './submitted-recap.page';

const routes: Routes = [
  {
    path: '',
    component: SubmittedRecapPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class SubmittedRecapPageRoutingModule {}
