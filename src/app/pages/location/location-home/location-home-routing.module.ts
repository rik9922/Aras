import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { LocationHomePage } from './location-home.page';

const routes: Routes = [
  {
    path: '',
    component: LocationHomePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class LocationHomePageRoutingModule {}
