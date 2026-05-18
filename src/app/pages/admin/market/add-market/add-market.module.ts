import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddMarketPageRoutingModule } from './add-market-routing.module';

import { AddMarketPage } from './add-market.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AddMarketPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AddMarketPage]
})
export class AddMarketPageModule {}
