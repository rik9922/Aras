import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AdminPayForecastPageRoutingModule } from './admin-pay-forecast-routing.module';

import { AdminPayForecastPage } from './admin-pay-forecast.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    SharedModule,
    ReactiveFormsModule,
    IonicModule,
    AdminPayForecastPageRoutingModule
  ],
  declarations: [AdminPayForecastPage]
})
export class AdminPayForecastPageModule {}
