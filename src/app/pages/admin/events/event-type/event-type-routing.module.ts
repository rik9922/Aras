import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { EventTypePage } from './event-type.page';

const routes: Routes = [
  {
    path: '',
    component: EventTypePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class EventTypePageRoutingModule {}
