import { Component } from '@angular/core';
import { MenuController, NavController, Platform } from '@ionic/angular';
import { StorageService } from './services/storage.service';
import { Router } from '@angular/router';
import { EventsService } from './services/events.service';
import { initializeApp } from 'firebase/app';
import { environment } from 'src/environments/environment';
import { Capacitor } from '@capacitor/core';
import { createUserWithEmailAndPassword, getAuth, indexedDBLocalPersistence, initializeAuth, signInWithEmailAndPassword } from 'firebase/auth';
import OneSignal from "onesignal-cordova-plugin";
import { Device } from "@capacitor/device";
import { ApiService } from './services/api.service';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent {
  user: any;
  logout_modal: boolean = false;
  auth:any;
  constructor(
    public platform: Platform,
    public menuCtrl: MenuController,
    public storage: StorageService,
    public router: Router,
    public event: EventsService,
    public navCtrl: NavController,
    public apiService: ApiService
  ) {
    this.initApp();
    this.event.subscribe('logoutUser', res => {
      this.user = "";
      this.saveDeviceDetails(res,0);
    });

    this.event.subscribe("logout", (res) => {
      this.user = res;
      this.saveDeviceDetails(res,0);
      this.logout(true);
    });

    this.event.subscribe("firebase_login", (res) => {
      this.fireAuth(res?.email, 'sR123456')
    });

    this.event.subscribe("user_login", (res) => {
      this.user = res;
      this.saveDeviceDetails(res,1);
    });
  }

  async initApp() {
    const firebaseApp = initializeApp(environment.firebaseConfig);
    if (Capacitor.isNativePlatform()) {
      this.setupOneSignal();
      this.auth = initializeAuth(firebaseApp, {
        persistence: indexedDBLocalPersistence
      })
    } else {
      this.auth = getAuth()
    }

    const val: any = await this.storage.get("apro_user");
    if (val != null) {
      this.user = val;
      this.menuCtrl.enable(true);
      this.fireAuth(val?.email, 'sR123456')
      setTimeout(() => {
        if (val?.role_id === 1) {
          this.router.navigate(["/tabs/admin-home"]);
        } else if (val?.role_id === 2) {
          this.router.navigate(["/tabs/brand-home"]);
        } else if (val?.role_id === 3) {
          this.router.navigate(["/tabs/client-home"]);
        } else if (val?.role_id === 4) {
          this.router.navigate(["/tabs/location-home"]);
        } else if (val?.role_id === 5) {
          this.router.navigate(["/tabs/ambassador-home"]);
        }
      }, 0);
    } else {
      this.router.navigate(["/login"]);
    }
  }

    setupOneSignal() {
    // Initialize OneSignal
    OneSignal.initialize("09f4e786-6e32-4563-a3d2-15104999baa0");
    // Request permission for iOS (not needed on Android, but safe to call)
    OneSignal.Notifications.requestPermission(true).then(
      async (accepted: boolean) => {
        console.log("User accepted notifications: " + accepted);
      }
    );

    OneSignal.Notifications.registerForProvisionalAuthorization(function (accepted) {
      console.log(accepted)
    });

    // When a notification is clicked/opened
    OneSignal.Notifications.addEventListener('click', (event) => {
      console.log('Notification clicked:', event);
      const actionId = event.result.actionId;
      if (actionId === 'YES_ACTION') {
        console.log('YES clicked');
      } else if (actionId === 'NO_ACTION') {
        console.log('NO clicked');
      } else {
        console.log('Notification body clicked');
      }
    });

    // Listen for notifications received while app is open
    OneSignal.Notifications.addEventListener("foregroundWillDisplay", (payload: any) => {
      console.log("Notification received in foreground:", payload);
    });
    


  }

  async saveDeviceDetails(user: any, isLogin: any = 1) {
    // console.log('saveDeviceDetails called');
    if (this.platform.is("cordova")) {
      const deviceId = await Device.getId();
      OneSignal.User.pushSubscription.getIdAsync().then((res: any) => {
        let data: any = {
          device_token: res,
          platform: this.platform.is("ios") ? "ios" : "android",
          is_user_loggedin: isLogin,
          time_zone: Intl.DateTimeFormat().resolvedOptions().timeZone,
          // device_id: deviceId?.identifier,
          // status: 1,
        };
        this.apiService.sendAuthData("save_device_details", data, user.token).subscribe((res: any) => {
          console.log("response", res);
        },
          (err) => {
            console.log("error", err);
          }
        );
      });
    }
  }


  logout(type: any) {
    console.log(type);
    this.logout_modal = type;
  }

  confirmUserLogout() {
    // this.setUpNotification(this.user, 0)
    this.saveDeviceDetails(this.user,0);
    this.event.publish("model", false);
    this.logout_modal = false;
    this.storage.remove("apro_user");
    this.navCtrl.navigateRoot(["/login"]);
    this.event.publish("logoutUser", true);
  }

  fireAuth(email: any, password: any) {
    signInWithEmailAndPassword(this.auth, email, password)
      .then((userCredential) => {
        const user = userCredential.user;
      })
      .catch((error) => {
        this.CreateFireAuth(email, password);
      });
  }

  CreateFireAuth(email: any, password: any) {
    const auth = getAuth();
    createUserWithEmailAndPassword(auth, email, password)
      .then((userCredential) => {
        const user = userCredential.user;
      })
      .catch((error) => {
      });
  }
}
