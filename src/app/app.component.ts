import { Component } from '@angular/core';
import { MenuController, NavController, Platform } from '@ionic/angular';
import { StorageService } from './services/storage.service';
import { Router } from '@angular/router';
import { EventsService } from './services/events.service';
import { initializeApp } from 'firebase/app';
import { environment } from 'src/environments/environment';
import { Capacitor } from '@capacitor/core';
import { createUserWithEmailAndPassword, getAuth, indexedDBLocalPersistence, initializeAuth, signInWithEmailAndPassword } from 'firebase/auth';
import { DeviceSyncService } from './services/device-sync.service';
import { PermissionsService } from './services/permissions.service';
import { NotificationPreferencesService } from './services/notification-preferences.service';
import { PushNotificationService } from './services/push-notification.service';
import { homeRouteForRole } from './utils/role-routing';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent {
  user: any;
  logout_modal: boolean = false;
  auth: any;
  is_more: boolean = false;
  is_more_pay: boolean = false;

  closeMenu() {
    this.menuCtrl.close('main-menu');
  }

  navigate(route: string) {
    this.router.navigate([route]);
    this.closeMenu();
  }

  /** Route to the logged-in user's own home, derived from their role. */
  goHome() {
    const route = homeRouteForRole(this.user?.role_id);
    if (route) {
      this.router.navigate([route]);
    }
    this.closeMenu();
  }

  constructor(
    public platform: Platform,
    public menuCtrl: MenuController,
    public storage: StorageService,
    public router: Router,
    public event: EventsService,
    public navCtrl: NavController,
    private deviceSync: DeviceSyncService,
    private permissionsService: PermissionsService,
    private notificationPreferences: NotificationPreferencesService,
    private pushNotifications: PushNotificationService,
  ) {
    this.initApp();
    this.event.subscribe('logoutUser', () => {
      this.user = '';
    });

    this.event.subscribe('logout', (res) => {
      this.user = res;
      this.logout(true);
    });

    this.event.subscribe('firebase_login', (res) => {
      this.fireAuth(res?.email, 'sR123456');
    });

    this.event.subscribe('user_login', (res) => {
      void this.onUserLogin(res);
    });

    // Run from app root so permission alerts survive navigation away from login
    this.event.subscribe('app_request_permissions', (res) => {
      this.user = res;
      setTimeout(() => {
        this.permissionsService.requestPostLoginPermissions(res);
      }, 400);
    });

    this.event.subscribe('ba_request_permissions', (res) => {
      this.event.publish('app_request_permissions', res);
    });
  }

  private async onUserLogin(user: any): Promise<void> {
    this.user = user;
    this.pushNotifications.setActiveUser(user);
    if (!user?.token) {
      return;
    }

    await this.notificationPreferences.load(user);

    if (Capacitor.isNativePlatform()) {
      await this.pushNotifications.whenReady();
      await this.pushNotifications.linkUser(user);
      await this.deviceSync.syncIfAlreadySubscribed(user);
    }
  }

  async initApp() {
    const firebaseApp = initializeApp(environment.firebaseConfig);
    if (Capacitor.isNativePlatform()) {
      this.pushNotifications.initialize();
      await this.pushNotifications.whenReady();
      this.auth = initializeAuth(firebaseApp, {
        persistence: indexedDBLocalPersistence,
      });
    } else {
      this.auth = getAuth();
    }

    const val: any = await this.storage.get('apro_user');
    if (val != null) {
      this.user = val;
      this.pushNotifications.setActiveUser(val);
      this.menuCtrl.enable(true, 'main-menu');
      setTimeout(async () => {
        if (!Capacitor.isNativePlatform()) {
          return;
        }
        await this.pushNotifications.whenReady();
        await this.pushNotifications.linkUser(val);
        const prefs = await this.notificationPreferences.load(val);
        if (prefs?.push_enabled) {
          await this.deviceSync.syncIfAlreadySubscribed(val);
        }
      }, 800);
      this.fireAuth(val?.email, 'sR123456');
      setTimeout(() => {
        const homeRoute = homeRouteForRole(val?.role_id);
        if (homeRoute) {
          this.router.navigate([homeRoute]);
        }
      }, 0);
    } else {
      this.router.navigate(['/login']);
    }
  }

  logout(type: any) {
    this.logout_modal = type;
  }

  async confirmUserLogout() {
    const user = this.user;
    this.notificationPreferences.reset();
    await this.deviceSync.logout(user);
    this.event.publish('model', false);
    this.logout_modal = false;
    this.storage.remove('apro_user');
    this.navCtrl.navigateRoot(['/login']);
    this.event.publish('logoutUser', true);
  }

  fireAuth(email: any, password: any) {
    signInWithEmailAndPassword(this.auth, email, password)
      .then(() => {})
      .catch(() => {
        this.CreateFireAuth(email, password);
      });
  }

  CreateFireAuth(email: any, password: any) {
    const auth = getAuth();
    createUserWithEmailAndPassword(auth, email, password)
      .then(() => {})
      .catch(() => {});
  }

  more() {
    this.is_more = !this.is_more;
  }

  pay() {
    this.is_more_pay = !this.is_more_pay;
  }
}
