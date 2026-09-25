import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { SharedModule } from 'src/app/components/shared/shared.module';
import { AdminReimbursementsPageRoutingModule } from './admin-reimbursements-routing.module';
import { AdminReimbursementsPage } from './admin-reimbursements.page';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReactiveFormsModule,
    AdminReimbursementsPageRoutingModule
  ],
  declarations: [AdminReimbursementsPage]
})
export class AdminReimbursementsPageModule {}
