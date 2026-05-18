import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { LocationAccountPageRoutingModule } from './location-account-routing.module';

import { LocationAccountPage } from './location-account.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    LocationAccountPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [LocationAccountPage]
})
export class LocationAccountPageModule {}
