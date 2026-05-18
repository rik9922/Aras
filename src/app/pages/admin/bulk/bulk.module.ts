import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { BulkPageRoutingModule } from './bulk-routing.module';

import { BulkPage } from './bulk.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReactiveFormsModule,
    BulkPageRoutingModule
  ],
  declarations: [BulkPage]
})
export class BulkPageModule {}
