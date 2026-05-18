import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { DateWiseListPage } from './date-wise-list.page';

const routes: Routes = [
  {
    path: '',
    component: DateWiseListPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class DateWiseListPageRoutingModule {}
