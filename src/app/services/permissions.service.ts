import { Injectable } from '@angular/core';
import { AlertController } from '@ionic/angular';
import { Geolocation } from '@capacitor/geolocation';
import OneSignal from 'onesignal-cordova-plugin';
import { AppUser, DeviceSyncService } from './device-sync.service';

/** OneSignal.OSNotificationPermission.Authorized */
const OS_NOTIFICATION_AUTHORIZED = 2;

@Injectable({
  providedIn: 'root',
})
export class PermissionsService {
  constructor(
    private readonly alertCtrl: AlertController,
    private readonly deviceSync: DeviceSyncService
  ) {}

  /**
   * After BA login: show notification prompt, then register with OneSignal + backend.
   */
  async requestBaPostLoginPermissions(user: AppUser | null): Promise<void> {
    if (!user?.token || Number(user.role_id) !== 5) {
      return;
    }

    if (!this.deviceSync.isNativeApp()) {
      return;
    }

    await this.promptNotificationPermission(user);
    await this.promptLocationPermission();
  }

  private async hasFullNotificationPermission(): Promise<boolean> {
    try {
      const nativePermission = await OneSignal.Notifications.permissionNative();
      return nativePermission === OS_NOTIFICATION_AUTHORIZED;
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
