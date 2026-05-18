import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { LocationEventDetailsPageRoutingModule } from './location-event-details-routing.module';

import { LocationEventDetailsPage } from './location-event-details.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    LocationEventDetailsPageRoutingModule,
    SharedModule
  ],
  declarations: [LocationEventDetailsPage]
})
export class LocationEventDetailsPageModule {}
