import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { BankingAddPage } from './banking-add.page';

const routes: Routes = [{ path: '', component: BankingAddPage }];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class BankingAddPageRoutingModule {}
