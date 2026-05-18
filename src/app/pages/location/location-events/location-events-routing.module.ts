import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { LocationEventsPage } from './location-events.page';

const routes: Routes = [
  {
    path: '',
    component: LocationEventsPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class LocationEventsPageRoutingModule {}
