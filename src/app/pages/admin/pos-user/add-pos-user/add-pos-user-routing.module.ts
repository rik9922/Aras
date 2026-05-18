import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddPosUserPage } from './add-pos-user.page';

const routes: Routes = [
  {
    path: '',
    component: AddPosUserPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddPosUserPageRoutingModule {}
