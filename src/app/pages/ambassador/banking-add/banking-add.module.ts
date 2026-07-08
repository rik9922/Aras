import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular';
import { SharedModule } from 'src/app/components/shared/shared.module';
import { BankingAddPageRoutingModule } from './banking-add-routing.module';
import { BankingAddPage } from './banking-add.page';

@NgModule({
  imports: [CommonModule, FormsModule, IonicModule, SharedModule, BankingAddPageRoutingModule],
  declarations: [BankingAddPage],
})
export class BankingAddPageModule {}
