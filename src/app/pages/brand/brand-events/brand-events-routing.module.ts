import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { BrandEventsPage } from './brand-events.page';

const routes: Routes = [
  {
    path: '',
    component: BrandEventsPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class BrandEventsPageRoutingModule {}
