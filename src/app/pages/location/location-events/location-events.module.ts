import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { LocationEventsPageRoutingModule } from './location-events-routing.module';

import { LocationEventsPage } from './location-events.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    LocationEventsPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [LocationEventsPage]
})
export class LocationEventsPageModule {}
