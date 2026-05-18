import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddEventsDepartmentPage } from './add-events-department.page';

const routes: Routes = [
  {
    path: '',
    component: AddEventsDepartmentPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddEventsDepartmentPageRoutingModule {}
