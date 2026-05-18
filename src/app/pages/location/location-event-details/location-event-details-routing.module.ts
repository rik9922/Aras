import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { LocationEventDetailsPage } from './location-event-details.page';

const routes: Routes = [
  {
    path: '',
    component: LocationEventDetailsPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class LocationEventDetailsPageRoutingModule {}
