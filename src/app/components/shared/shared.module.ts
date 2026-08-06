import { NgModule, CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeaderComponent } from '../header/header.component';
import { CheckInModalComponent } from '../check-in-modal/check-in-modal.component';
import { NotificationSettingsComponent } from '../notification-settings/notification-settings.component';
import { SearchableSelectComponent } from '../searchable-select/searchable-select.component';
import { TypeaheadSelectComponent } from '../typeahead-select/typeahead-select.component';
import { IonicModule } from '@ionic/angular';
import { FormsModule } from '@angular/forms';



@NgModule({
  declarations: [
    HeaderComponent,
    CheckInModalComponent,
    NotificationSettingsComponent,
    SearchableSelectComponent,
    TypeaheadSelectComponent,
  ],
  imports: [
    CommonModule,
    IonicModule,
    FormsModule
  ],
  exports: [
    HeaderComponent,
    CheckInModalComponent,
    NotificationSettingsComponent,
    SearchableSelectComponent,
    TypeaheadSelectComponent,
  ],
  schemas: [CUSTOM_ELEMENTS_SCHEMA]

})
export class SharedModule { }

