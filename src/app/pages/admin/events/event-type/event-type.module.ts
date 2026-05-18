import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { EventTypePageRoutingModule } from './event-type-routing.module';

import { EventTypePage } from './event-type.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReactiveFormsModule,
    EventTypePageRoutingModule
  ],
  declarations: [EventTypePage]
})
export class EventTypePageModule {}
