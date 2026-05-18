import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { RecapPageRoutingModule } from './recap-routing.module';

import { RecapPage } from './recap.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RecapPageRoutingModule,
    SharedModule
  ],
  declarations: [RecapPage]
})
export class RecapPageModule {}
