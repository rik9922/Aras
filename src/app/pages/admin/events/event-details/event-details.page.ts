import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { PermissionsService } from 'src/app/services/permissions.service';
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
  checkInInProgress = false;

  constructor(
    public router: Router,
    public apiService: ApiService,
    public common: CommonService,
    public storage: StorageService,
    public alertCtrl: AlertController,
    private permissionsService: PermissionsService,
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.data = this.router.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.base_url = environment.base_url;
        if (this.data) {
          this.get_pay_forecast_detail();
        }
      }
    });
  }

  get_pay_forecast_detail() {
    this.common.showLoader();
    this.apiService.getAuthData('ambassador/get_event_details?id=' + this.data?.id, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.eventDetail = res?.data;
      },
      error: (err) => {
        this.common.dismissLoading();
        console.log(err);
      },
    });
  }

  async view(url: any) {
    await Browser.open({ url: this.base_url + url });
  }

  printCurrentPosition = async () => {
    if (this.checkInInProgress || this.eventDetail?.event?.is_checked_in == 1) {
      return;
    }

    const hasPermission = await this.permissionsService.ensureLocationPermission();
    if (!hasPermission) {
      this.common.presentToast('Location permission is required to check in at the event.');
      return;
    }

    try {
      const coordinates = await Geolocation.getCurrentPosition({
        enableHighAccuracy: true,
        timeout: 15000,
      });
      await this.submitCheckIn(
        coordinates.coords.latitude,
        coordinates.coords.longitude,
        false
      );
    } catch (error) {
      this.common.presentToast('Unable to read your location. Please enable location services and try again.');
    }
  };

  async submitCheckIn(latitude: number, longitude: number, forceCheckIn: boolean) {
    if (this.checkInInProgress) {
      return;
    }

    const payload: any = {
      data_time: moment().format('YYYY-MM-DD HH:mm:ss'),
      event: this.data?.id,
      latitude,
      longitude,
    };
    if (forceCheckIn) {
      payload.force_check_in = 1;
    }

    this.checkInInProgress = true;
    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/store_GPS_tracking', payload, this.user?.token).subscribe({
      next: (res: any) => {
        this.checkInInProgress = false;
        this.common.dismissLoading();
        this.get_pay_forecast_detail();
        this.common.presentToast(res?.message || 'Checked in successfully.');
      },
      error: async (err) => {
        this.checkInInProgress = false;
        this.common.dismissLoading();
        const body = err?.error;
        const requiresConfirmation =
          body?.error_type === 'outside_event_radius' ||
          body?.data?.requires_confirmation === true;

        if (requiresConfirmation && !forceCheckIn) {
          await this.confirmRemoteCheckIn(latitude, longitude);
          return;
        }

        this.common.presentToast(body?.message || 'Check-in failed. Please try again.');
      },
    });
  }

  async confirmRemoteCheckIn(latitude: number, longitude: number) {
    const locationLabel =
      this.eventDetail?.event?.event_location_label ||
      this.eventDetail?.event?.event_address ||
      this.eventDetail?.event?.location?.address ||
      'the event destination';

    const alert = await this.alertCtrl.create({
      header: 'Are you sure?',
      message: `Are you sure you want to check in? It does not appear you are at ${locationLabel}.`,
      backdropDismiss: false,
      buttons: [
        {
          text: 'Cancel',
          role: 'cancel',
        },
        {
          text: 'Check In',
          handler: () => {
            this.submitCheckIn(latitude, longitude, true);
          },
        },
      ],
    });
    await alert.present();
  }
}
