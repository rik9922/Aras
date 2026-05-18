import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ClientRecapPage } from './client-recap.page';

const routes: Routes = [
  {
    path: '',
    component: ClientRecapPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ClientRecapPageRoutingModule {}
