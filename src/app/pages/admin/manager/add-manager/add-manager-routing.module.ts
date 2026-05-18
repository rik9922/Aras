import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddManagerPage } from './add-manager.page';

const routes: Routes = [
  {
    path: '',
    component: AddManagerPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddManagerPageRoutingModule {}
