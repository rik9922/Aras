import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddUserLocationPage } from './add-user-location.page';

const routes: Routes = [
  {
    path: '',
    component: AddUserLocationPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddUserLocationPageRoutingModule {}
