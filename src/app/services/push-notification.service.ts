import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import OneSignal from 'onesignal-cordova-plugin';
import { Capacitor, registerPlugin } from '@capacitor/core';
import { App } from '@capacitor/app';
import { Haptics } from '@capacitor/haptics';
import { AppUser, DeviceSyncService } from './device-sync.service';
import { AmbassadorNotificationsService } from './ambassador-notifications.service';

interface ForegroundNotificationFeedbackPlugin {
  playDefaultNotificationSound(): Promise<void>;
}

interface NotificationNavigationTarget {
  commands: string[];
  extras?: {
    state?: Record<string, unknown>;
  };
}

const ForegroundNotificationFeedback =
  registerPlugin<ForegroundNotificationFeedbackPlugin>('ForegroundNotificationFeedback');

const ONESIGNAL_APP_ID = '09f4e786-6e32-4563-a3d2-15104999baa0';
const NOTIFICATION_LIST_ROUTE = '__notification_list__';

@Injectable({
  providedIn: 'root',
})
export class PushNotificationService {
  private initialized = false;
  private activeUser: AppUser | null = null;
  private readyPromise: Promise<void> | null = null;
  private pendingNotificationNavigation: NotificationNavigationTarget | null = null;

  constructor(
    private readonly deviceSync: DeviceSyncService,
    private readonly router: Router,
    private readonly notifications: AmbassadorNotificationsService
  ) { }

  setActiveUser(user: AppUser | null): void {
    this.activeUser = user;
    if (user) {
      this.flushPendingNotificationNavigation();
    }
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
        void this.playForegroundNotificationFeedback();
        this.notifications.incrementUnreadCount();
      } catch (error) {
        console.warn('Failed to display foreground notification', error);
      }
    });

    OneSignal.Notifications.addEventListener('click', (event) => {
      this.refreshUnreadCount();
      const notification = event?.notification as {
        additionalData?: Record<string, unknown>;
        launchURL?: string;
      } | undefined;
      const clickUrl = event?.result?.url || notification?.launchURL;
      void this.handleNotificationClick(notification?.additionalData, clickUrl);
    });

    void App.getLaunchUrl().then((launchUrl) => {
      if (launchUrl?.url) {
        void this.handleNotificationUrl(launchUrl.url);
      }
    });

    void App.addListener('appUrlOpen', (event) => {
      void this.handleNotificationUrl(event.url);
    });

    void App.addListener('appStateChange', ({ isActive }) => {
      if (isActive) {
        this.refreshUnreadCount();
      }
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

  private async handleNotificationClick(data?: Record<string, unknown>, url?: string): Promise<void> {
    const target = this.resolveNotificationTarget(data, url);
    if (!target) {
      return;
    }

    await this.navigateFromNotification(target);
  }

  private async handleNotificationUrl(url: string): Promise<void> {
    const target = this.resolveNotificationTarget(undefined, url);
    if (target) {
      await this.navigateFromNotification(target);
    }
  }

  private resolveNotificationTarget(
    data?: Record<string, unknown>,
    url?: string
  ): NotificationNavigationTarget | null {
    const type = String(data?.['type'] ?? '');
    const objectId = Number(data?.['booking_id'] ?? this.getUrlParam(url, 'booking_id') ?? 0);

    if (['reimbursement_submitted', 'reimbursement_updated'].includes(type) && objectId > 0) {
      return {
        commands: ['/admin/reimbursements', String(objectId)],
        extras: {
          state: { id: objectId },
        },
      };
    }

    if (['reimbursement', 'reimbursement_payment_received'].includes(type) && objectId > 0) {
      return {
        commands: ['/reimbursement-detail'],
        extras: {
          state: { id: objectId },
        },
      };
    }

    if (type === 'recap_submitted' && objectId > 0) {
      return {
        commands: ['/admin-view-recap'],
        extras: {
          state: { id: objectId },
        },
      };
    }

    if (this.isEventNotificationType(type) && objectId > 0) {
      return {
        commands: ['/event-details'],
        extras: {
          state: { id: objectId, openCheckIn: type === 'event_check_in_ready' },
        },
      };
    }

    if (this.isBankingNotificationType(type)) {
      return {
        commands: ['/banking'],
      };
    }

    if (type === 'event_payout_received') {
      return {
        commands: ['/ambassador-payment'],
      };
    }

    const urlTarget = this.resolveUrlTarget(url);
    if (urlTarget) {
      return urlTarget;
    }

    if (this.isNotificationUrl(url) || data?.['notification_id']) {
      return {
        commands: [NOTIFICATION_LIST_ROUTE],
      };
    }

    return null;
  }

  private resolveUrlTarget(url?: string): NotificationNavigationTarget | null {
    const path = this.getUrlPath(url);

    const adminReimbursementMatch = path.match(/^\/admin\/reimbursements\/(\d+)$/);
    if (adminReimbursementMatch) {
      const id = Number(adminReimbursementMatch[1]);
      return {
        commands: ['/admin/reimbursements', String(id)],
        extras: {
          state: { id },
        },
      };
    }

    const recapMatch = path.match(/^\/(?:admin|manager)\/show-recap\/(\d+)$/);
    if (recapMatch) {
      return {
        commands: ['/admin-view-recap'],
        extras: {
          state: { id: Number(recapMatch[1]) },
        },
      };
    }

    const eventMatch = path.match(/^\/(?:admin|manager)\/(?:show|edit)-event\/(\d+)$/);
    if (eventMatch) {
      return {
        commands: ['/event-details'],
        extras: {
          state: { id: Number(eventMatch[1]) },
        },
      };
    }

    return null;
  }

  private async navigateFromNotification(target: NotificationNavigationTarget): Promise<void> {
    if (!this.activeUser) {
      this.pendingNotificationNavigation = target;
      return;
    }

    const commands = target.commands[0] === NOTIFICATION_LIST_ROUTE
      ? [this.notificationRouteForActiveUser()]
      : target.commands;

    await this.router.navigate(commands, target.extras);
  }

  private flushPendingNotificationNavigation(): void {
    const target = this.pendingNotificationNavigation;
    if (!target) {
      return;
    }

    this.pendingNotificationNavigation = null;
    setTimeout(() => {
      void this.navigateFromNotification(target);
    }, 500);
  }

  private notificationRouteForActiveUser(): string {
    const roleId = Number(this.activeUser?.role_id);
    if (roleId === 6) {
      return '/manager-notifications';
    }
    if (roleId === 1) {
      return '/admin-notifications';
    }
    return '/ambassador-notifications';
  }

  private isNotificationUrl(url?: string): boolean {
    const path = this.getUrlPath(url);
    return path === '/notifications'
      || path === '/ambassador-notifications'
      || path === '/admin-notifications'
      || path === '/manager-notifications';
  }

  private isEventNotificationType(type: string): boolean {
    return [
      'event_assignment',
      'event_update',
      'event_check_in',
      'event_late_check_in',
      'event_check_in_ready',
    ].includes(type);
  }

  private isBankingNotificationType(type: string): boolean {
    return [
      'banking_setup_reminder',
      'banking_setup_payment_skipped',
      'payment_setup_completed',
    ].includes(type);
  }

  private getUrlParam(url: string | undefined, name: string): string | null {
    if (!url) {
      return null;
    }

    try {
      return new URL(url).searchParams.get(name);
    } catch {
      return null;
    }
  }

  private getUrlPath(url: string | undefined): string {
    if (!url) {
      return '';
    }

    try {
      return new URL(url).pathname;
    } catch {
      return url.startsWith('/') ? url.split('?')[0] : '';
    }
  }

  private refreshUnreadCount(): void {
    if (this.activeUser?.token) {
      this.notifications.refreshUnreadCount(this.activeUser.token);
    }
  }

  private async playForegroundNotificationFeedback(): Promise<void> {
    if (Capacitor.getPlatform() === 'ios') {
      try {
        await ForegroundNotificationFeedback.playDefaultNotificationSound();
      } catch (error) {
        console.warn('Failed to play foreground notification sound', error);
      }
    }

    try {
      await Haptics.vibrate({ duration: 300 });
    } catch (error) {
      console.warn('Failed to play foreground notification haptics', error);
    }
  }
}
