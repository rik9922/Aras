import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { IonicModule } from '@ionic/angular';

import { ArasDocumentPageRoutingModule } from './aras-document-routing.module';

import { ArasDocumentPage } from './aras-document.page';
import { SharedModule } from 'src/app/components/shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    SharedModule,
    ArasDocumentPageRoutingModule
  ],
  declarations: [ArasDocumentPage]
})
export class ArasDocumentPageModule {}
