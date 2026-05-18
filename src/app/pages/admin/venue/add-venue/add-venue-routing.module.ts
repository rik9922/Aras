import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddVenuePage } from './add-venue.page';

const routes: Routes = [
  {
    path: '',
    component: AddVenuePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddVenuePageRoutingModule {}
