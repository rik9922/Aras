import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddMarketPage } from './add-market.page';

const routes: Routes = [
  {
    path: '',
    component: AddMarketPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddMarketPageRoutingModule {}
