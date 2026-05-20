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

  constructor(private readonly deviceSync: DeviceSyncService) {}

  setActiveUser(user: AppUser | null): void {
    this.activeUser = user;
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
          this.deviceSync.sync(user, true);
        }
      });
    } catch (error) {
      console.warn('OneSignal subscription listener unavailable', error);
    }

    this.initialized = true;
  }

  async linkUser(user: AppUser | null): Promise<void> {
    this.activeUser = user;

    if (!Capacitor.isNativePlatform() || !user?.id) {
      return;
    }

    try {
      OneSignal.login(String(user.id));
    } catch (error) {
      console.warn('OneSignal login failed', error);
    }
  }
}
