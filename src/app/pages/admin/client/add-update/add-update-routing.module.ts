import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddUpdatePage } from './add-update.page';

const routes: Routes = [
  {
    path: '',
    component: AddUpdatePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddUpdatePageRoutingModule {}
