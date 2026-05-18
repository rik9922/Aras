import { CUSTOM_ELEMENTS_SCHEMA, NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddUpdatePageRoutingModule } from './add-update-routing.module';

import { AddUpdatePage } from './add-update.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ReactiveFormsModule,
    AddUpdatePageRoutingModule
  ],
  declarations: [AddUpdatePage],
  schemas:[CUSTOM_ELEMENTS_SCHEMA]
})
export class AddUpdatePageModule {}
