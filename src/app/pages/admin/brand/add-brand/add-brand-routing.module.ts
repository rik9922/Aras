import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddBrandPage } from './add-brand.page';

const routes: Routes = [
  {
    path: '',
    component: AddBrandPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddBrandPageRoutingModule {}
