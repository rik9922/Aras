import { Component, OnInit } from '@angular/core';
import { AlertController, NavController } from '@ionic/angular';
import {
  AmbassadorNotification,
  AmbassadorNotificationsService,
} from 'src/app/services/ambassador-notifications.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-notifications',
  templateUrl: './notifications.page.html',
  styleUrls: ['./notifications.page.scss'],
})
export class NotificationsPage implements OnInit {
  params: any = { title: 'Notifications', is_back: true };
  user: any;
  notifications: AmbassadorNotification[] = [];
  unreadCount = 0;
  loading = false;
  actionLoading = false;
  page = 1;
  hasMore = false;

  constructor(
    private readonly storage: StorageService,
    private readonly nav: NavController,
    private readonly notificationsService: AmbassadorNotificationsService,
    private readonly common: CommonService,
    private readonly alertCtrl: AlertController,
  ) {}

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((user: any) => {
      if (!user?.token) {
        this.nav.navigateRoot('/login');
        return;
      }

      this.user = user;
      this.loadNotifications();
    });
  }

  loadNotifications(event?: any, append = false) {
    if (!this.user?.token) {
      event?.target?.complete();
      return;
    }

    const nextPage = append ? this.page + 1 : 1;
    this.loading = !event && !append;
    this.notificationsService.loadNotifications(this.user.token, nextPage).subscribe({
      next: ({ notifications, unreadCount, pagination }) => {
        this.notifications = append ? [...this.notifications, ...notifications] : notifications;
        this.unreadCount = unreadCount;
        this.page = pagination.currentPage;
        this.hasMore = pagination.hasMore;
        this.loading = false;
        event?.target?.complete();
      },
      error: () => {
        this.loading = false;
        event?.target?.complete();
        this.common.presentToast('Unable to load notifications.');
      },
    });
  }

  loadMore(event: any) {
    if (!this.hasMore || this.actionLoading) {
      event?.target?.complete();
      return;
    }

    this.loadNotifications(event, true);
  }

  markAsRead(notification: AmbassadorNotification) {
    if (notification.is_seen || this.actionLoading) {
      return;
    }

    this.actionLoading = true;
    this.notificationsService.markAsRead(this.user.token, notification.id).subscribe({
      next: () => {
        notification.is_seen = true;
        this.actionLoading = false;
        this.refreshUnreadCount();
      },
      error: () => {
        this.actionLoading = false;
        this.common.presentToast('Unable to mark notification as read.');
      },
    });
  }

  async deleteNotification(notification: AmbassadorNotification) {
    const confirmed = await this.confirm('Delete notification?', 'This notification will be removed from your list.');
    if (!confirmed || this.actionLoading) {
      return;
    }

    this.actionLoading = true;
    this.notificationsService.deleteNotification(this.user.token, notification.id).subscribe({
      next: () => {
        this.notifications = this.notifications.filter((item) => item.id !== notification.id);
        this.actionLoading = false;
        this.refreshUnreadCount();
      },
      error: () => {
        this.actionLoading = false;
        this.common.presentToast('Unable to delete notification.');
      },
    });
  }

  markAllAsRead() {
    if (this.unreadCount === 0 || this.actionLoading) {
      return;
    }

    this.actionLoading = true;
    this.notificationsService.markAllAsRead(this.user.token).subscribe({
      next: () => {
        this.notifications = this.notifications.map((item) => ({ ...item, is_seen: true }));
        this.actionLoading = false;
        this.refreshUnreadCount();
      },
      error: () => {
        this.actionLoading = false;
        this.common.presentToast('Unable to mark notifications as read.');
      },
    });
  }

  async deleteAll() {
    if (this.notifications.length === 0) {
      return;
    }

    const confirmed = await this.confirm('Delete all notifications?', 'Your notification list will be cleared.');
    if (!confirmed || this.actionLoading) {
      return;
    }

    this.actionLoading = true;
    this.notificationsService.deleteAll(this.user.token).subscribe({
      next: () => {
        this.notifications = [];
        this.hasMore = false;
        this.actionLoading = false;
        this.refreshUnreadCount();
      },
      error: () => {
        this.actionLoading = false;
        this.common.presentToast('Unable to delete notifications.');
      },
    });
  }

  private refreshUnreadCount() {
    this.notificationsService.loadUnreadCount(this.user.token).subscribe({
      next: (count) => {
        this.unreadCount = count;
      },
      error: () => {
        this.unreadCount = this.notifications.filter((item) => !item.is_seen).length;
        this.notificationsService.setUnreadCount(this.unreadCount);
      },
    });
  }

  private async confirm(header: string, message: string): Promise<boolean> {
    const alert = await this.alertCtrl.create({
      header,
      message,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Delete', role: 'destructive' },
      ],
    });

    await alert.present();
    const result = await alert.onDidDismiss();
    return result.role === 'destructive';
  }
}
