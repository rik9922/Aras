import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { SubmittedRecapPageRoutingModule } from './submitted-recap-routing.module';

import { SubmittedRecapPage } from './submitted-recap.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    SubmittedRecapPageRoutingModule
  ],
  declarations: [SubmittedRecapPage]
})
export class SubmittedRecapPageModule {}
