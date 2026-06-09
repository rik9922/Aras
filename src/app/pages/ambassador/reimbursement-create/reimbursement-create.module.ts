import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { ReimbursementCreatePageRoutingModule } from './reimbursement-create-routing.module';

import { ReimbursementCreatePage } from './reimbursement-create.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReimbursementCreatePageRoutingModule
  ],
  declarations: [ReimbursementCreatePage]
})
export class ReimbursementCreatePageModule {}
