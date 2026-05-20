import { NgModule, CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeaderComponent } from '../header/header.component';
import { CheckInModalComponent } from '../check-in-modal/check-in-modal.component';
import { NotificationSettingsComponent } from '../notification-settings/notification-settings.component';
import { IonicModule } from '@ionic/angular';
import { FormsModule } from '@angular/forms';



@NgModule({
  declarations: [HeaderComponent, CheckInModalComponent, NotificationSettingsComponent],
  imports: [
    CommonModule,
    IonicModule,
    FormsModule
  ],
  exports: [HeaderComponent, CheckInModalComponent, NotificationSettingsComponent],
  schemas: [CUSTOM_ELEMENTS_SCHEMA]

})
export class SharedModule { }

