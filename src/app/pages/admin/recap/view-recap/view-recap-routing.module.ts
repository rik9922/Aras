import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ViewRecapPage } from './view-recap.page';

const routes: Routes = [
  {
    path: '',
    component: ViewRecapPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ViewRecapPageRoutingModule {}
