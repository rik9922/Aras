import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddBrandPageRoutingModule } from './add-brand-routing.module';

import { AddBrandPage } from './add-brand.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AddBrandPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AddBrandPage]
})
export class AddBrandPageModule {}
