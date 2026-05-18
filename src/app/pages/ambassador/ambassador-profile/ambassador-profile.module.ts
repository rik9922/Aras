import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { AmbassadorProfilePageRoutingModule } from './ambassador-profile-routing.module';

import { AmbassadorProfilePage } from './ambassador-profile.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    AmbassadorProfilePageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [AmbassadorProfilePage]
})
export class AmbassadorProfilePageModule {}
