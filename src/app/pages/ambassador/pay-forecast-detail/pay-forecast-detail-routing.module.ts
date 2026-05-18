import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { PayForecastDetailPage } from './pay-forecast-detail.page';

const routes: Routes = [
  {
    path: '',
    component: PayForecastDetailPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class PayForecastDetailPageRoutingModule {}
