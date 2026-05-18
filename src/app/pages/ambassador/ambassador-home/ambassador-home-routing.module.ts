import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AmbassadorHomePage } from './ambassador-home.page';

const routes: Routes = [
  {
    path: '',
    component: AmbassadorHomePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AmbassadorHomePageRoutingModule {}
