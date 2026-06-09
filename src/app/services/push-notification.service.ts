import { Injectable } from '@angular/core';
import OneSignal from 'onesignal-cordova-plugin';
import { Capacitor } from '@capacitor/core';
import { AppUser, DeviceSyncService } from './device-sync.service';

const ONESIGNAL_APP_ID = '09f4e786-6e32-4563-a3d2-15104999baa0';

@Injectable({
  providedIn: 'root',
})
export class PushNotificationService {
  private initialized = false;
  private activeUser: AppUser | null = null;
  private readyPromise: Promise<void> | null = null;

  constructor(private readonly deviceSync: DeviceSyncService) {}

  setActiveUser(user: AppUser | null): void {
    this.activeUser = user;
  }

  whenReady(): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      return Promise.resolve();
    }

    if (this.initialized) {
      return Promise.resolve();
    }

    if (!this.readyPromise) {
      this.readyPromise = Promise.resolve(this.initialize());
    }

    return this.readyPromise;
  }

  /**
   * OneSignal v5 suppresses foreground notifications until the app calls
   * event.getNotification().display() inside foregroundWillDisplay.
   */
  initialize(): void {
    if (!Capacitor.isNativePlatform() || this.initialized) {
      return;
    }

    OneSignal.initialize(ONESIGNAL_APP_ID);

    OneSignal.Notifications.addEventListener('foregroundWillDisplay', (event) => {
      try {
        event.getNotification().display();
      } catch (error) {
        console.warn('Failed to display foreground notification', error);
      }
    });

    OneSignal.Notifications.addEventListener('click', (event) => {
      console.log('Notification clicked:', event);
    });

    try {
      OneSignal.User.pushSubscription.addEventListener('change', (state) => {
        const subscriptionId = state?.current?.id;
        const user = this.activeUser;
        if (user?.token && subscriptionId) {
          void this.deviceSync.persistSubscriptionFromListener(user);
        }
      });
    } catch (error) {
      console.warn('OneSignal subscription listener unavailable', error);
    }

    this.initialized = true;
  }

  async linkUser(user: AppUser | null): Promise<void> {
    this.activeUser = user;
    await this.whenReady();
    await this.deviceSync.linkOneSignalUser(user);
  }
}
