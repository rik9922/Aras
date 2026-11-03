import { Injectable } from '@angular/core';
import { ActionSheetController, AlertController, LoadingController, NavController, ToastController } from '@ionic/angular';
import { EventsService } from './events.service';

@Injectable({
  providedIn: 'root'
})
export class CommonService {
  loading: any;
  private loadingRequests = 0;
  private loadingTask: Promise<void> = Promise.resolve();
  state: any;
  constructor(
    public loadingCtrl: LoadingController,
    public alertCtrl: AlertController,
    public toastCtrl: ToastController,
    public nav: NavController,
    public event: EventsService,
    public actionSheetCtrl: ActionSheetController
  ) { }

  dismissLoading(): Promise<void> {
    this.loadingRequests = Math.max(0, this.loadingRequests - 1);
    this.loadingTask = this.loadingTask.then(async () => {
      if (this.loadingRequests === 0 && this.loading) {
        const overlay = this.loading;
        this.loading = null;
        await overlay.dismiss();
      }
    });
    return this.loadingTask;
  }

  showLoader(msg: string = ''): Promise<void> {
    this.loadingRequests++;
    this.loadingTask = this.loadingTask.then(async () => {
      if (this.loadingRequests === 0 || this.loading) return;
      this.loading = await this.loadingCtrl.create({
        message: msg || 'Please wait...',
        backdropDismiss: false,
      });
      await this.loading.present();
    });
    return this.loadingTask;
  }

  async presentAlert(msg: any) {
    let alert = await this.alertCtrl.create({
      header: 'Alert',
      message: msg,
      backdropDismiss: false,
      buttons: [{
        text: 'OK',
        handler: () => {

        }
      }]
    });
    await alert.present();
  }

  async presentAlertWithPromise(title: any = 'Alert', msg: any = '') {
    return new Promise(async (resolve, reject) => {
      let alert = await this.alertCtrl.create({
        header: title,
        message: msg,
        backdropDismiss: false,
        buttons: [{
          text: 'OK',
          handler: () => {
            resolve(true);
          }
        }]
      });
      await alert.present();
    })
  }
  async presentConfirmationAlert(title: any = 'Alert', msg: any = '') {
    return new Promise(async (resolve, reject) => {
      let alert = await this.alertCtrl.create({
        header: title,
        message: msg,
        backdropDismiss: false,
        buttons: [
          {
            text: 'No',
            handler: () => {
              resolve(false);
            }
          },
          {
            text: 'Yes',
            handler: () => {
              resolve(true);
            }
          }
        ]
      });
      await alert.present();
    })
  }

  async redirectConfirmationAlert(title: any = 'Alert', msg: any = '', text: any = '') {
    return new Promise(async (resolve, reject) => {
      let alert = await this.alertCtrl.create({
        header: title,
        message: msg,
        backdropDismiss: false,
        buttons: [
          {
            text: 'Skip',
            handler: () => {
              resolve(false);
            }
          },
          {
            text: text,
            handler: () => {
              resolve(true);
            }
          }
        ]
      });
      await alert.present();
    })
  }

  async presentToast(msg: any, postion: any = 'bottom') {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2000,
      position: postion
    });
    toast.present();
  }


  public goToRoot(page: any, navigationExtras: any) {
    this.nav.navigateRoot(page, navigationExtras);
  }
  getEmergencyRequests(request: any) {
    var emergencyReqNumber = request?.filter((x: any) => x.request_type == 'emergency' && x.status == 1)
    this.event.publish('emergencyReqUpdate', emergencyReqNumber?.length);
    return emergencyReqNumber?.length;
  }

  setState(data: any) {
    this.state = data;
  }
  getState() {
    return this.state;
  }
  
  confirmationAlert(msg = 'Are you sure?', heading = 'Alert', btn1: any = 'Cancel', btn2: any = 'Okay') {
    return new Promise(async (reslove, reject) => {
      const alert = await this.alertCtrl.create({
        header: heading,
        message: msg,
        buttons: [
          {
            text: btn1,
            role: 'cancel',
            handler: () => {
              console.log('Alert canceled');
              reject(false);
            },
          },
          {
            text: btn2,
            role: 'confirm',
            handler: () => {
              console.log('Alert confirmed');
              reslove(true);
            }
          },
        ],
        backdropDismiss: false
      });
      await alert.present();
    })
  }
}

