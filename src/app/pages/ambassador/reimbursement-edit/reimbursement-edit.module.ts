import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { ReimbursementEditPageRoutingModule } from './reimbursement-edit-routing.module';

import { ReimbursementEditPage } from './reimbursement-edit.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReimbursementEditPageRoutingModule
  ],
  declarations: [ReimbursementEditPage]
})
export class ReimbursementEditPageModule {}
