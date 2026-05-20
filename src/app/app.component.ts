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
      this.user = res;
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
        if (val?.role_id === 1) {
          this.router.navigate(['/tabs/admin-home']);
        } else if (val?.role_id === 2) {
          this.router.navigate(['/tabs/brand-home']);
        } else if (val?.role_id === 3) {
          this.router.navigate(['/tabs/client-home']);
        } else if (val?.role_id === 4) {
          this.router.navigate(['/tabs/location-home']);
        } else if (val?.role_id === 5) {
          this.router.navigate(['/tabs/ambassador-home']);
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
