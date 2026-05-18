import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ClientAccountPage } from './client-account.page';

const routes: Routes = [
  {
    path: '',
    component: ClientAccountPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ClientAccountPageRoutingModule {}
