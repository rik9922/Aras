import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { BrandEventsPageRoutingModule } from './brand-events-routing.module';

import { BrandEventsPage } from './brand-events.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    BrandEventsPageRoutingModule,
    SharedModule
  ],
  declarations: [BrandEventsPage]
})
export class BrandEventsPageModule {}
