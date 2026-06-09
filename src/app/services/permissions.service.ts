import { Injectable } from '@angular/core';
import { AlertController } from '@ionic/angular';
import { Geolocation } from '@capacitor/geolocation';
import OneSignal from 'onesignal-cordova-plugin';
import { AppUser, DeviceSyncService } from './device-sync.service';
import { NotificationPreferencesService } from './notification-preferences.service';
import { PushNotificationService } from './push-notification.service';

@Injectable({
  providedIn: 'root',
})
export class PermissionsService {
  constructor(
    private readonly alertCtrl: AlertController,
    private readonly deviceSync: DeviceSyncService,
    private readonly preferences: NotificationPreferencesService,
    private readonly pushNotifications: PushNotificationService
  ) {}

  /**
   * After native app login: request notification and location permissions.
   */
  async requestPostLoginPermissions(user: AppUser | null): Promise<void> {
    if (!user?.token) {
      return;
    }

    if (!this.deviceSync.isNativeApp()) {
      return;
    }

    await this.pushNotifications.whenReady();
    await this.preferences.load(user);
    await this.promptNotificationPermission(user);

    if (user.role_id === 5) {
      await this.promptLocationPermission();
    }
  }

  /** @deprecated Use requestPostLoginPermissions */
  async requestBaPostLoginPermissions(user: AppUser | null): Promise<void> {
    return this.requestPostLoginPermissions(user);
  }

  private async hasFullNotificationPermission(): Promise<boolean> {
    try {
      return await OneSignal.Notifications.getPermissionAsync();
    } catch (error) {
      console.warn('Unable to read native notification permission', error);
      return false;
    }
  }

  private async promptNotificationPermission(user: AppUser): Promise<void> {
    const fullyAuthorized = await this.hasFullNotificationPermission();
    if (fullyAuthorized) {
      await this.deviceSync.syncIfAlreadySubscribed(user);
      return;
    }

    const alert = await this.alertCtrl.create({
      header: 'Notifications',
      message: 'Allow notifications for latest updates',
      backdropDismiss: false,
      cssClass: 'permission-alert',
      buttons: [
        { text: 'Not Now', role: 'cancel' },
        { text: 'Allow', role: 'allow' },
      ],
    });

    await alert.present();
    const { role } = await alert.onDidDismiss();

    if (role === 'allow') {
      await this.deviceSync.registerPushNotifications(user);
    } else {
      await this.preferences.updatePushEnabled(user, false);
    }
  }

  async ensureLocationPermission(): Promise<boolean> {
    try {
      let status = await Geolocation.checkPermissions();
      if (status.location === 'granted') {
        return true;
      }
      if (status.location === 'denied') {
        return false;
      }
      status = await Geolocation.requestPermissions();
      return status.location === 'granted';
    } catch (error) {
      console.warn('Location permission request failed', error);
      return false;
    }
  }

  private async promptLocationPermission(): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Location',
      message: 'Location access is required to verify check-in at event destinations.',
      backdropDismiss: false,
      buttons: [
        { text: 'Not Now', role: 'cancel' },
        { text: 'Allow', role: 'allow' },
      ],
    });

    await alert.present();
    const { role } = await alert.onDidDismiss();

    if (role === 'allow') {
      await this.ensureLocationPermission();
    }
  }
}
