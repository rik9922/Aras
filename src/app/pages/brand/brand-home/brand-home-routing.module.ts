import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { BrandHomePage } from './brand-home.page';

const routes: Routes = [
  {
    path: '',
    component: BrandHomePage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class BrandHomePageRoutingModule {}
