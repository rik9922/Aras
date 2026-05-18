import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { BrandHomePageRoutingModule } from './brand-home-routing.module';

import { BrandHomePage } from './brand-home.page';
import { SharedModule } from 'src/app/components/shared/shared.module';
import { FullCalendarModule } from '@fullcalendar/angular';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    BrandHomePageRoutingModule,
    SharedModule,
    FullCalendarModule
  ],
  declarations: [BrandHomePage],
})
export class BrandHomePageModule {}
