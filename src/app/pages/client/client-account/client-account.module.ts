import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { ClientAccountPageRoutingModule } from './client-account-routing.module';

import { ClientAccountPage } from './client-account.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    ClientAccountPageRoutingModule,
    SharedModule,
    ReactiveFormsModule
  ],
  declarations: [ClientAccountPage]
})
export class ClientAccountPageModule {}
