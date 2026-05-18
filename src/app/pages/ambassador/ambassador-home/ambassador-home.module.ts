import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AmbassadorHomePageRoutingModule } from './ambassador-home-routing.module';

import { AmbassadorHomePage } from './ambassador-home.page';
import { SharedModule } from 'src/app/components/shared/shared.module';
import { FullCalendarModule } from '@fullcalendar/angular';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AmbassadorHomePageRoutingModule,
    SharedModule,
    FullCalendarModule
  ],
  declarations: [AmbassadorHomePage]
})
export class AmbassadorHomePageModule {}
