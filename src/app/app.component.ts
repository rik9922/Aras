import { Component } from '@angular/core';
import { MenuController, NavController, Platform } from '@ionic/angular';
import { StorageService } from './services/storage.service';
import { Router } from '@angular/router';
import { EventsService } from './services/events.service';
import { initializeApp } from 'firebase/app';
import { environment } from 'src/environments/environment';
import { Capacitor } from '@capacitor/core';
import { createUserWithEmailAndPassword, getAuth, indexedDBLocalPersistence, initializeAuth, signInWithEmailAndPassword } from 'firebase/auth';
import OneSignal from 'onesignal-cordova-plugin';
import { DeviceSyncService } from './services/device-sync.service';
import { PermissionsService } from './services/permissions.service';
import { NotificationPreferencesService } from './services/notification-preferences.service';
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
    if (!user?.token) {
      return;
    }

    await this.notificationPreferences.load(user);

    if (Capacitor.isNativePlatform()) {
      if (user.id) {
        try {
          OneSignal.login(String(user.id));
        } catch (error) {
          console.warn('OneSignal login failed', error);
        }
      }
      await this.deviceSync.syncIfAlreadySubscribed(user);
    }
  }

  async initApp() {
    const firebaseApp = initializeApp(environment.firebaseConfig);
    if (Capacitor.isNativePlatform()) {
      this.setupOneSignal();
      this.auth = initializeAuth(firebaseApp, {
        persistence: indexedDBLocalPersistence,
      });
    } else {
      this.auth = getAuth();
    }

    const val: any = await this.storage.get('apro_user');
    if (val != null) {
      this.user = val;
      this.menuCtrl.enable(true);
      setTimeout(async () => {
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

  setupOneSignal() {
    OneSignal.initialize('09f4e786-6e32-4563-a3d2-15104999baa0');

    OneSignal.Notifications.addEventListener('click', (event) => {
      console.log('Notification clicked:', event);
    });

    OneSignal.Notifications.addEventListener('foregroundWillDisplay', (payload: any) => {
      console.log('Notification received in foreground:', payload);
    });

    try {
      OneSignal.User.pushSubscription.addEventListener('change', (state) => {
        const subscriptionId = state?.current?.id;
        if (this.user?.token && subscriptionId) {
          this.deviceSync.sync(this.user, true);
        }
      });
    } catch (error) {
      console.warn('OneSignal subscription listener unavailable', error);
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
}
