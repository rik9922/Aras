import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { LocationAccountPage } from './location-account.page';

const routes: Routes = [
  {
    path: '',
    component: LocationAccountPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class LocationAccountPageRoutingModule {}
