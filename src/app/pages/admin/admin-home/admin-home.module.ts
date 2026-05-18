import { CUSTOM_ELEMENTS_SCHEMA, NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AdminHomePageRoutingModule } from './admin-home-routing.module';

import { AdminHomePage } from './admin-home.page';
import { FullCalendarModule } from '@fullcalendar/angular';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AdminHomePageRoutingModule,
    FullCalendarModule,
    SharedModule
  ],
  declarations: [AdminHomePage],
  schemas:[CUSTOM_ELEMENTS_SCHEMA]
})
export class AdminHomePageModule {}
