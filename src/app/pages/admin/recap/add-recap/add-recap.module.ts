import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddRecapPageRoutingModule } from './add-recap-routing.module';

import { AddRecapPage } from './add-recap.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AddRecapPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AddRecapPage]
})
export class AddRecapPageModule {}
