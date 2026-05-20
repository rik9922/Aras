import { Component, Input, OnInit } from '@angular/core';
import { AppUser, DeviceSyncService } from 'src/app/services/device-sync.service';
import { NotificationPreferencesService } from 'src/app/services/notification-preferences.service';
import { CommonService } from 'src/app/services/common.service';

@Component({
  selector: 'app-notification-settings',
  templateUrl: './notification-settings.component.html',
  styleUrls: ['./notification-settings.component.scss'],
  standalone: false,
})
export class NotificationSettingsComponent implements OnInit {
  @Input() user: AppUser | null = null;

  pushEnabled = true;
  loading = true;
  saving = false;
  isNative = false;
  private readyForToggle = false;

  constructor(
    private readonly deviceSync: DeviceSyncService,
    private readonly preferences: NotificationPreferencesService,
    private readonly common: CommonService
  ) {}

  ngOnInit() {
    this.isNative = this.deviceSync.isNativeApp();
    this.init();
  }

  private async init() {
    this.loading = true;
    if (this.user?.token) {
      const prefs = await this.preferences.load(this.user);
      if (prefs) {
        this.pushEnabled = prefs.push_enabled;
      }
    }
    this.loading = false;
    this.readyForToggle = true;
  }

  async onPushToggle(event: CustomEvent) {
    if (!this.readyForToggle) {
      return;
    }

    const enabled = !!event.detail.checked;
    if (!this.user?.token || this.saving) {
      return;
    }

    this.saving = true;

    if (enabled) {
      const registered = await this.deviceSync.enablePushNotifications(this.user);
      if (!registered) {
        this.pushEnabled = false;
        await this.preferences.updatePushEnabled(this.user, false);
        this.common.presentToast(
          'Enable notifications in system settings to receive push alerts.'
        );
      } else {
        this.pushEnabled = true;
      }
    } else {
      await this.deviceSync.disablePushNotifications(this.user);
      this.pushEnabled = false;
    }

    this.saving = false;
  }
}
