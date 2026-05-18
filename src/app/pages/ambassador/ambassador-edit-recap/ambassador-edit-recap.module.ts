import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AmbassadorEditRecapPageRoutingModule } from './ambassador-edit-recap-routing.module';

import { AmbassadorEditRecapPage } from './ambassador-edit-recap.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReactiveFormsModule,
    AmbassadorEditRecapPageRoutingModule
  ],
  declarations: [AmbassadorEditRecapPage]
})
export class AmbassadorEditRecapPageModule {}
