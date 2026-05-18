import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ClientEventsPage } from './client-events.page';

const routes: Routes = [
  {
    path: '',
    component: ClientEventsPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ClientEventsPageRoutingModule {}
