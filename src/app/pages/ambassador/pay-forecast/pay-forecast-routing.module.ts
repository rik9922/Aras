import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { PayForecastPage } from './pay-forecast.page';

const routes: Routes = [
  {
    path: '',
    component: PayForecastPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class PayForecastPageRoutingModule {}
