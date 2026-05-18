import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddRecapPage } from './add-recap.page';

const routes: Routes = [
  {
    path: '',
    component: AddRecapPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddRecapPageRoutingModule {}
