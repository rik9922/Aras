import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular';
import { SharedModule } from 'src/app/components/shared/shared.module';
import { BankingTransactionsPageRoutingModule } from './banking-transactions-routing.module';
import { BankingTransactionsPage } from './banking-transactions.page';

@NgModule({
  imports: [CommonModule, FormsModule, IonicModule, SharedModule, BankingTransactionsPageRoutingModule],
  declarations: [BankingTransactionsPage],
})
export class BankingTransactionsPageModule {}
