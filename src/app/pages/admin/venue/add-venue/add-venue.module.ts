import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddVenuePageRoutingModule } from './add-venue-routing.module';

import { AddVenuePage } from './add-venue.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AddVenuePageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AddVenuePage]
})
export class AddVenuePageModule {}
