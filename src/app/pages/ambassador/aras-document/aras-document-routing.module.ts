import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ArasDocumentPage } from './aras-document.page';

const routes: Routes = [
  {
    path: '',
    component: ArasDocumentPage
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ArasDocumentPageRoutingModule {}
