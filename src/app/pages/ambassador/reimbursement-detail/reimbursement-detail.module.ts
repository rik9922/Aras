import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { ReimbursementDetailPageRoutingModule } from './reimbursement-detail-routing.module';

import { ReimbursementDetailPage } from './reimbursement-detail.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReimbursementDetailPageRoutingModule
  ],
  declarations: [ReimbursementDetailPage]
})
export class ReimbursementDetailPageModule {}
