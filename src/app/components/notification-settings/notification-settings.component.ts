import { Component, Input, OnInit } from '@angular/core';
import { AppUser, DeviceSyncService } from 'src/app/services/device-sync.service';
import { NotificationPreferencesService } from 'src/app/services/notification-preferences.service';
import { CommonService } from 'src/app/services/common.service';
import { ApiService } from 'src/app/services/api.service';

@Component({
  selector: 'app-notification-settings',
  templateUrl: './notification-settings.component.html',
  styleUrls: ['./notification-settings.component.scss'],
  standalone: false,
})
export class NotificationSettingsComponent implements OnInit {
  @Input() user: AppUser | null = null;

  pushEnabled = true;
  emailEnabled = true;
  loading = true;
  saving = false;
  testing = false;
  isNative = false;
  private readyForToggle = false;

  constructor(
    private readonly deviceSync: DeviceSyncService,
    private readonly preferences: NotificationPreferencesService,
    private readonly common: CommonService,
    private readonly apiService: ApiService
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
        this.emailEnabled = prefs.email_enabled;
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

  async onEmailToggle(event: CustomEvent) {
    if (!this.readyForToggle) {
      return;
    }

    const enabled = !!event.detail.checked;
    if (!this.user?.token || this.saving) {
      return;
    }

    this.saving = true;
    const saved = await this.preferences.updateEmailEnabled(this.user, enabled);
    this.emailEnabled = saved;
    if (saved !== enabled) {
      this.common.presentToast('Unable to update email notification preference.');
    }
    this.saving = false;
  }

  sendTestPush() {
    if (!this.user?.token || this.testing) {
      return;
    }

    this.testing = true;
    this.apiService
      .sendAuthData('test_push', {}, this.user.token)
      .subscribe({
        next: (res: any) => {
          this.common.presentToast(res?.message || 'Test push notification sent.');
          this.testing = false;
        },
        error: (err) => {
          this.common.presentToast(err?.error?.message || 'Unable to send test push notification.');
          this.testing = false;
        },
      });
  }
}
