import { Injectable } from "@angular/core";
import { map } from "rxjs/operators";
import { HttpClient, HttpHeaders } from "@angular/common/http";
import { environment } from "src/environments/environment";
import { Platform, ModalController, MenuController } from "@ionic/angular";
import { Network } from "@capacitor/network";
import { StorageService } from "./storage.service";
import { EventsService } from "./events.service";
import { CommonService } from "./common.service";
import { Router } from "@angular/router";

@Injectable({
  providedIn: "root",
})
export class ApiService {
  status: any;

  base_url = environment.base_url;
  loginUrl = environment.login_url;
  authUrl = environment.auth_url;
  isAlertOpen: boolean = false;
  constructor(
    public http: HttpClient,
    public platform: Platform,
    public modal: ModalController,
    public storage: StorageService,
    public event: EventsService,
    public common: CommonService,
    public router: Router,
    public menuCtrl: MenuController
  ) { }

  async getNetworkStatus() {
    return new Promise(async (resolve, reject) => {
      await Network.getStatus().then(
        (res: any) => {
          resolve(res);
        },
        (err: any) => {
          reject(err);
        }
      );
    });
  }

  sendAuthData(endPoint: any, data: any, Token: any) {
    // if (endPoint !== "save_device_details") {
    //   this.checkUserStatus(Token);
    // }
    this.authUrl = environment.auth_url;
    if (endPoint == 'update-profile') {
      this.authUrl = this.authUrl.split('auth')[0];
      console.log('this.authUrl', this.authUrl);
    }
    const httpOptions = {
      headers: new HttpHeaders({
        // 'Content-Type': 'application/json',
        Authorization: "Bearer " + Token,
      }),
    };
    return this.http.post(this.authUrl + endPoint, data, httpOptions).pipe(
      map((data) => {
        return data;
      })
    );
  }

  getAuthData(endpoint: any, token: any) {
    // this.checkUserStatus(token);
    this.authUrl = environment.auth_url;
    const httpOptions = {
      headers: new HttpHeaders({
        "Content-Type": "application/json",
        Authorization: "Bearer " + token,
      }),
    };
    return this.http.get(this.authUrl + endpoint, httpOptions).pipe(
      map((data) => {
        return data;
      })
    );
  }

  sendData(endpoint: any, parameter: any) {
    return this.http.post(this.loginUrl + endpoint, parameter).pipe(
      map((data) => {
        return data;
      })
    );
  }

  getData(endPoint: any) {
    return this.http.get(this.loginUrl + endPoint).pipe(
      map((data) => {
        return data;
      })
    );
  }

  checkUserStatus(token: any) {
    this.authUrl = environment.auth_url;
    const httpOptions = {
      headers: new HttpHeaders({
        "Content-Type": "application/json",
        Authorization: "Bearer " + token,
      }),
    };
    if (token) {
      this.http.get(this.authUrl + "checkUserStatus", httpOptions).pipe().subscribe((res: any) => {
        console.log(res);
        this.event.publish("updateNotifications", res?.users?.notification_count);
      },
        async (err: any) => {
          let temp: any = { token: token };
          if (err?.error?.data) {
            temp = err?.error?.data;
            temp.token = token;
          }
          if (err?.status === 0) {
            if (!navigator.onLine) {
              if (!this.isAlertOpen) {
                this.isAlertOpen = true;
                let check: any = await this.common.presentAlertWithPromise('Alert', "No internet connection. Please check your network and try again.");
                if (check) {
                  this.isAlertOpen = false;
                }
              }
            } else {
              if (!this.isAlertOpen) {
                this.isAlertOpen = true;
                let check = await this.common.presentAlertWithPromise("Alert", "An error occurred while trying to reach the server.");
                if (check) {
                  this.isAlertOpen = false;
                }
              }
            }
          } else {
            if (!this.isAlertOpen) {
              this.isAlertOpen = true;
              let check: any = await this.common.presentAlertWithPromise('Alert', err?.error?.message || "An error occurred. Please try again.");
              if (check) {
                this.isAlertOpen = false;
              }
            }
          }
          this.event.publish("model", false);
          this.storage.remove("fuw_user");
          this.menuCtrl.enable(false);
          this.router.navigate(["/login"]);
          // window.location.reload()
          // this.common.presentAlert(err?.error?.message);
        }
      );
    }
  }
}
