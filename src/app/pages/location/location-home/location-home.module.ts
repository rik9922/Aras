import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { LocationHomePageRoutingModule } from './location-home-routing.module';

import { LocationHomePage } from './location-home.page';
import { FullCalendarModule } from '@fullcalendar/angular';
import { SharedModule } from 'src/app/components/shared/shared.module';


@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    LocationHomePageRoutingModule,
    SharedModule,
    FullCalendarModule
  ],
  declarations: [LocationHomePage]
})
export class LocationHomePageModule {}
