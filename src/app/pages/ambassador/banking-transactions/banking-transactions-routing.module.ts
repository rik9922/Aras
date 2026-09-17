import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { BankingTransactionsPage } from './banking-transactions.page';

const routes: Routes = [{ path: '', component: BankingTransactionsPage }];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class BankingTransactionsPageRoutingModule {}
