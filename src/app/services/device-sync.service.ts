import { Injectable } from '@angular/core';
import { Capacitor } from '@capacitor/core';
import { Platform } from '@ionic/angular';
import OneSignal from 'onesignal-cordova-plugin';
import { ApiService } from './api.service';
import { NotificationPreferencesService } from './notification-preferences.service';

export interface AppUser {
  id?: number;
  token?: string;
  role_id?: number;
}

@Injectable({
  providedIn: 'root',
})
export class DeviceSyncService {
  constructor(
    private readonly apiService: ApiService,
    private readonly platform: Platform,
    private readonly preferences: NotificationPreferencesService
  ) {}

  isNativeApp(): boolean {
    return Capacitor.isNativePlatform();
  }

  /**
   * Full push registration: link OneSignal user, request permission, opt in, save token to API.
   */
  async registerPushNotifications(user: AppUser | null): Promise<boolean> {
    if (!user?.token || !this.isNativeApp()) {
      return false;
    }

    try {
      await this.linkOneSignalUser(user);

      const granted = await OneSignal.Notifications.requestPermission(true);
      if (!granted) {
        return false;
      }

      OneSignal.User.pushSubscription.optIn();

      const subscriptionId = await this.waitForSubscriptionId();
      if (!subscriptionId) {
        console.warn('OneSignal subscription id not available after permission granted');
        return false;
      }

      await this.saveTokenToBackend(user, subscriptionId, true, true);
      await this.preferences.updatePushEnabled(user, true);
      return true;
    } catch (error) {
      console.warn('Push notification registration failed', error);
      return false;
    }
  }

  async enablePushNotifications(user: AppUser | null): Promise<boolean> {
    if (!user?.token || !this.isNativeApp()) {
      return false;
    }

    const alreadyGranted = await this.hasNotificationPermission();
    const registered = alreadyGranted
      ? await this.syncIfAlreadySubscribed(user)
      : await this.registerPushNotifications(user);

    if (registered) {
      await this.preferences.updatePushEnabled(user, true);
    }

    return registered;
  }

  async disablePushNotifications(user: AppUser | null): Promise<void> {
    if (!user?.token) {
      return;
    }

    if (this.isNativeApp()) {
      try {
        OneSignal.User.pushSubscription.optOut();
      } catch (error) {
        console.warn('OneSignal opt-out failed', error);
      }
    }

    await this.preferences.updatePushEnabled(user, false);

    let deviceToken = '';
    if (this.isNativeApp()) {
      try {
        deviceToken = (await OneSignal.User.pushSubscription.getIdAsync()) ?? '';
      } catch (error) {
        console.warn('Unable to read push subscription while disabling', error);
      }
    }

    await this.saveTokenToBackend(user, deviceToken, true, false);
  }

  /**
   * Sync when permission was already granted (app resume / returning user).
   */
  async syncIfAlreadySubscribed(user: AppUser | null): Promise<boolean> {
    if (!user?.token || !this.isNativeApp()) {
      return false;
    }

    try {
      if (!(await this.hasNotificationPermission())) {
        return false;
      }

      await this.linkOneSignalUser(user);

      const subscriptionId = await this.waitForSubscriptionId();
      if (!subscriptionId) {
        return false;
      }

      await this.saveTokenToBackend(user, subscriptionId, true, true);
      await this.preferences.updatePushEnabled(user, true);
      return true;
    } catch (error) {
      console.warn('Push sync failed', error);
      return false;
    }
  }

  /**
   * Called from the OneSignal subscription change listener. Persists the token
   * whenever the SDK reports a subscription id — not gated on push preferences
   * so registration cannot be skipped after the user opts in at the OS level.
   */
  async persistSubscriptionFromListener(user: AppUser | null): Promise<void> {
    if (!user?.token || !this.isNativeApp()) {
      return;
    }

    try {
      const optedIn = await OneSignal.User.pushSubscription.getOptedInAsync();
      if (!optedIn) {
        return;
      }

      const subscriptionId = await OneSignal.User.pushSubscription.getIdAsync();
      if (!subscriptionId) {
        return;
      }

      await this.saveTokenToBackend(user, subscriptionId, true, true);
      await this.preferences.updatePushEnabled(user, true);
    } catch (error) {
      console.warn('Subscription listener persist failed', error);
    }
  }

  async sync(user: AppUser | null, isLoggedIn: boolean): Promise<void> {
    if (!user?.token || !this.isNativeApp() || !this.preferences.pushEnabled) {
      return;
    }

    try {
      const subscriptionId = await OneSignal.User.pushSubscription.getIdAsync();
      if (!subscriptionId) {
        return;
      }

      await this.saveTokenToBackend(user, subscriptionId, isLoggedIn, true);
    } catch (error) {
      console.warn('Device sync failed', error);
    }
  }

  async linkOneSignalUser(user: AppUser | null): Promise<void> {
    if (!user?.id || !this.isNativeApp()) {
      return;
    }

    try {
      OneSignal.login(String(user.id));
      // OneSignal v5 may need a moment to attach the subscription after login().
      await new Promise((resolve) => setTimeout(resolve, 300));
    } catch (error) {
      console.warn('OneSignal login failed', error);
    }
  }

  async logout(user: AppUser | null): Promise<void> {
    if (!user?.token) {
      return;
    }

    let deviceToken: string | null = null;
    if (this.isNativeApp()) {
      try {
        deviceToken = await OneSignal.User.pushSubscription.getIdAsync();
      } catch (error) {
        console.warn('Unable to read push subscription on logout', error);
      }
    }

    if (this.isNativeApp()) {
      try {
        OneSignal.User.pushSubscription.optOut();
      } catch (error) {
        console.warn('OneSignal opt-out failed', error);
      }
    }

    await this.clearDeviceOnBackend(user, deviceToken);

    if (this.isNativeApp()) {
      try {
        OneSignal.logout();
      } catch (error) {
        console.warn('OneSignal logout failed', error);
      }
    }

    await new Promise<void>((resolve) => {
      this.apiService.logout(user.token!, deviceToken).subscribe({
        next: () => resolve(),
        error: () => resolve(),
      });
    });
  }

  private clearDeviceOnBackend(user: AppUser, deviceToken: string | null): Promise<void> {
    const data = {
      device_token: deviceToken ?? '',
      platform: this.platform.is('ios') ? 'ios' : 'android',
      is_user_loggedin: 0,
      push_opt_in: 0,
    };

    return new Promise<void>((resolve) => {
      this.apiService.sendAuthData('save_device_details', data, user.token!).subscribe({
        next: () => resolve(),
        error: () => resolve(),
      });
    });
  }

  private async waitForSubscriptionId(
    maxAttempts = 20,
    delayMs = 500
  ): Promise<string | null> {
    for (let attempt = 0; attempt < maxAttempts; attempt++) {
      const id = await OneSignal.User.pushSubscription.getIdAsync();
      if (id) {
        return id;
      }
      await new Promise((resolve) => setTimeout(resolve, delayMs));
    }
    return null;
  }

  private async hasNotificationPermission(): Promise<boolean> {
    try {
      return await OneSignal.Notifications.getPermissionAsync();
    } catch {
      return false;
    }
  }

  private saveTokenToBackend(
    user: AppUser,
    subscriptionId: string,
    isLoggedIn: boolean,
    pushOptIn: boolean
  ): Promise<void> {
    const data = {
      device_token: subscriptionId,
      platform: this.platform.is('ios') ? 'ios' : 'android',
      is_user_loggedin: isLoggedIn ? 1 : 0,
      push_opt_in: pushOptIn ? 1 : 0,
      time_zone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    };

    return new Promise<void>((resolve, reject) => {
      this.apiService.sendAuthData('save_device_details', data, user.token!).subscribe({
        next: (res: any) => {
          if (res?.data?.token_stored === false) {
            console.warn('Device token was not stored on the server', res);
          }
          resolve();
        },
        error: (err) => {
          console.warn('save_device_details failed', err);
          reject(err);
        },
      });
    });
  }
}
