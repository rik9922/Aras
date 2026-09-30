import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
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

  constructor(
    private readonly deviceSync: DeviceSyncService,
    private readonly router: Router,
    private readonly toastCtrl: ToastController
  ) { }

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
        event.preventDefault(false);
        const notification = event.getNotification();
        console.log('OneSignal foreground notification received', {
          id: notification.notificationId,
          title: notification.title,
        });
        notification.display();
        void this.showForegroundBanner(notification);
      } catch (error) {
        console.warn('Failed to display foreground notification', error);
      }
    });

    OneSignal.Notifications.addEventListener('click', (event) => {
      const data = (event?.notification as { additionalData?: Record<string, unknown> } | undefined)
        ?.additionalData;
      void this.handleNotificationClick(data);
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

  private async handleNotificationClick(data?: Record<string, unknown>): Promise<void> {
    if (!data) {
      return;
    }

    const type = String(data['type'] ?? '');
    const eventId = Number(data['booking_id'] ?? 0);

    if (type === 'event_check_in_ready' && eventId > 0) {
      await this.router.navigate(['/event-details'], {
        state: { id: eventId, openCheckIn: true },
      });
    }
  }

  private async showForegroundBanner(notification: {
    title?: string;
    body?: string;
    additionalData?: object;
  }): Promise<void> {
    const toast = await this.toastCtrl.create({
      header: notification.title || 'Notification',
      message: notification.body || '',
      position: 'top',
      duration: 5000,
      cssClass: 'ios-foreground-notification',
      translucent: true,
      buttons: [
        {
          text: 'View',
          handler: () => {
            void this.handleNotificationClick(
              notification.additionalData as Record<string, unknown> | undefined
            );
          },
        },
        {
          text: 'Dismiss',
          role: 'cancel',
        },
      ],
    });

    await toast.present();
  }
}
