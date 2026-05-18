import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { PayForecastDetailPageRoutingModule } from './pay-forecast-detail-routing.module';

import { PayForecastDetailPage } from './pay-forecast-detail.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    PayForecastDetailPageRoutingModule
  ],
  declarations: [PayForecastDetailPage]
})
export class PayForecastDetailPageModule {}
