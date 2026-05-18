import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { AddAmbassadorPage } from './add-ambassador.page';

const routes: Routes = [
  {
    path: '',
    component: AddAmbassadorPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AddAmbassadorPageRoutingModule {}
