import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { BankingVerifyPage } from './banking-verify.page';

const routes: Routes = [{ path: '', component: BankingVerifyPage }];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class BankingVerifyPageRoutingModule {}
