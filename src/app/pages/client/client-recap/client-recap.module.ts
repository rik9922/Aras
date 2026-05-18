import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { ClientRecapPageRoutingModule } from './client-recap-routing.module';

import { ClientRecapPage } from './client-recap.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    ClientRecapPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [ClientRecapPage]
})
export class ClientRecapPageModule {}
