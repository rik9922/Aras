import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddUserLocationPageRoutingModule } from './add-user-location-routing.module';

import { AddUserLocationPage } from './add-user-location.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AddUserLocationPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AddUserLocationPage]
})
export class AddUserLocationPageModule {}
