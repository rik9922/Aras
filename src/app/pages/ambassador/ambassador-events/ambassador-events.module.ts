import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AmbassadorEventsPageRoutingModule } from './ambassador-events-routing.module';

import { AmbassadorEventsPage } from './ambassador-events.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AmbassadorEventsPageRoutingModule,
    SharedModule
  ],
  declarations: [AmbassadorEventsPage]
})
export class AmbassadorEventsPageModule {}
