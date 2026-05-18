import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { Browser } from '@capacitor/browser';
import { Geolocation } from '@capacitor/geolocation';
import { AlertController } from '@ionic/angular';
import * as moment from 'moment';

@Component({
  standalone: false,
  selector: 'app-event-details',
  templateUrl: './event-details.page.html',
  styleUrls: ['./event-details.page.scss'],
})
export class EventDetailsPage implements OnInit {
  params: any = { title: 'Event Detail', is_back: true };
  data: any;
  user: any;
  eventDetail: any;
  base_url: any;
  constructor(
    public router: Router,
    public apiService: ApiService,
    public common: CommonService,
    public storage: StorageService,
    public alertCtrl: AlertController,
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.data = this.router.getCurrentNavigation()?.extras?.state;
      console.log(this.data)
    }
  }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        console.log(this.user?.role_id)
        this.base_url = environment.base_url;
        if (this.data) {
          this.get_pay_forecast_detail();
        }
      }
    })
  }

  get_pay_forecast_detail() {
    this.common.showLoader()
    this.apiService.getAuthData('ambassador/get_event_details?id=' + this.data?.id, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading()
      this.eventDetail = res?.data;
    }, err => {
      this.common.dismissLoading()
      console.log(err)
    })
  }

  async view(url: any) {
    await Browser.open({ url: this.base_url + url });
  }


  printCurrentPosition = async () => {
    const coordinates = await Geolocation.getCurrentPosition();
    console.log('Current position:', coordinates);
    if(this.data?.event?.location?.latitude===coordinates?.coords?.latitude && this.data?.event?.location?.longitude===coordinates?.coords?.longitude){
      this.storeTracking({
        data_time:moment().format('YYYY-MM-DD HH:mm:ss'),
        event:this.data?.id
      })
    } else {
      this.completeIt(coordinates?.coords)
    }
  }

  async completeIt(temp:any) {
    let alert = await this.alertCtrl.create({
      header: 'Are you sure?',
      message: 'It doesnt look like you are at the correct location, are you sure you want to check in?',
      backdropDismiss: false,
      buttons: [
        {
          text: 'No',
          handler: () => {
            this.storeTracking({
              data_time:moment().format('YYYY-MM-DD HH:mm:ss'),
              event:this.data?.id
            })
          } 
        },
        {
          text: 'Yes',
          handler: () => {
            this.storeTracking({
              data_time:moment().format('YYYY-MM-DD HH:mm:ss'),
              latitude:temp?.latitude,
              longitude:temp?.longitude,
              event:this.data?.id
            })
          }
        }]
    });
    await alert.present();
  }

  storeTracking(temp:any){
    console.log(temp)
    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/store_GPS_tracking',temp,this.user?.token).subscribe((res:any)=>{
      console.log(res);
      this.get_pay_forecast_detail()
      this.common.dismissLoading()
    },err=>{
      console.log(err);
      this.common.dismissLoading()
    })
  }

}
