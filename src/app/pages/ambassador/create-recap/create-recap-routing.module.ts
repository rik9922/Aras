import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { CreateRecapPage } from './create-recap.page';

const routes: Routes = [
  {
    path: '',
    component: CreateRecapPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class CreateRecapPageRoutingModule {}
