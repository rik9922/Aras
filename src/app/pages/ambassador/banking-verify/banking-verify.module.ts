import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular';
import { SharedModule } from 'src/app/components/shared/shared.module';
import { BankingVerifyPageRoutingModule } from './banking-verify-routing.module';
import { BankingVerifyPage } from './banking-verify.page';

@NgModule({
  imports: [CommonModule, FormsModule, IonicModule, SharedModule, BankingVerifyPageRoutingModule],
  declarations: [BankingVerifyPage],
})
export class BankingVerifyPageModule {}
