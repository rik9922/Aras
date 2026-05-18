import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AmbassadorEditRecapPage } from './ambassador-edit-recap.page';

const routes: Routes = [
  {
    path: '',
    component: AmbassadorEditRecapPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AmbassadorEditRecapPageRoutingModule {}
