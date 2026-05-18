import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AdminPayForecastPage } from './admin-pay-forecast.page';

const routes: Routes = [
  {
    path: '',
    component: AdminPayForecastPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AdminPayForecastPageRoutingModule {}
