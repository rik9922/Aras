import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AdminPaymentPageRoutingModule } from './admin-payment-routing.module';

import { AdminPaymentPage } from './admin-payment.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReactiveFormsModule,
    AdminPaymentPageRoutingModule
  ],
  declarations: [AdminPaymentPage]
})
export class AdminPaymentPageModule {}
