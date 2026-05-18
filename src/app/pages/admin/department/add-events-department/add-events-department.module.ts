import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddEventsDepartmentPageRoutingModule } from './add-events-department-routing.module';

import { AddEventsDepartmentPage } from './add-events-department.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AddEventsDepartmentPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AddEventsDepartmentPage]
})
export class AddEventsDepartmentPageModule {}
