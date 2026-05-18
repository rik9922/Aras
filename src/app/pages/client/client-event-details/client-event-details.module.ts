import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { ClientEventDetailsPageRoutingModule } from './client-event-details-routing.module';

import { ClientEventDetailsPage } from './client-event-details.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    ClientEventDetailsPageRoutingModule,
    SharedModule
  ],
  declarations: [ClientEventDetailsPage]
})
export class ClientEventDetailsPageModule {}
