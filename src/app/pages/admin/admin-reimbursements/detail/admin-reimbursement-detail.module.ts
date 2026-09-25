import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { SharedModule } from 'src/app/components/shared/shared.module';
import { AdminReimbursementDetailPageRoutingModule } from './admin-reimbursement-detail-routing.module';
import { AdminReimbursementDetailPage } from './admin-reimbursement-detail.page';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    AdminReimbursementDetailPageRoutingModule
  ],
  declarations: [AdminReimbursementDetailPage]
})
export class AdminReimbursementDetailPageModule {}
