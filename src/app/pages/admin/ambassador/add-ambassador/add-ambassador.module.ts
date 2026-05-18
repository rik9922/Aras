import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddAmbassadorPageRoutingModule } from './add-ambassador-routing.module';

import { AddAmbassadorPage } from './add-ambassador.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AddAmbassadorPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AddAmbassadorPage]
})
export class AddAmbassadorPageModule {}
