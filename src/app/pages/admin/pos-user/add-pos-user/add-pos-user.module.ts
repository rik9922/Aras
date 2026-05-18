import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AddPosUserPageRoutingModule } from './add-pos-user-routing.module';

import { AddPosUserPage } from './add-pos-user.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AddPosUserPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AddPosUserPage]
})
export class AddPosUserPageModule {}
