import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { BulkPage } from './bulk.page';

const routes: Routes = [
  {
    path: '',
    component: BulkPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class BulkPageRoutingModule {}
