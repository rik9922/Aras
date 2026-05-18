import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AmbassadorProfilePage } from './ambassador-profile.page';

const routes: Routes = [
  {
    path: '',
    component: AmbassadorProfilePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AmbassadorProfilePageRoutingModule {}
