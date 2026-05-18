import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { CreateRecapPageRoutingModule } from './create-recap-routing.module';

import { CreateRecapPage } from './create-recap.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    CreateRecapPageRoutingModule
  ],
  declarations: [CreateRecapPage]
})
export class CreateRecapPageModule {}
