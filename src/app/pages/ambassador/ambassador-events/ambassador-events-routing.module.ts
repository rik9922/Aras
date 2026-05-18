import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AmbassadorEventsPage } from './ambassador-events.page';

const routes: Routes = [
  {
    path: '',
    component: AmbassadorEventsPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AmbassadorEventsPageRoutingModule {}
