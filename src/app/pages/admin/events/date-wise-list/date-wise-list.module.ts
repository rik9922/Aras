import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { DateWiseListPageRoutingModule } from './date-wise-list-routing.module';

import { DateWiseListPage } from './date-wise-list.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    DateWiseListPageRoutingModule
  ],
  declarations: [DateWiseListPage]
})
export class DateWiseListPageModule {}
