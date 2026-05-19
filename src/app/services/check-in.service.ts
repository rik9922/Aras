import { Injectable } from '@angular/core';
import { AlertController } from '@ionic/angular';
import { Geolocation } from '@capacitor/geolocation';
import * as moment from 'moment';
import { ApiService } from './api.service';
import { CommonService } from './common.service';
import { PermissionsService } from './permissions.service';

export interface CheckInResult {
  success: boolean;
  message?: string;
}

@Injectable({
  providedIn: 'root',
})
export class CheckInService {
  constructor(
    private readonly apiService: ApiService,
    private readonly common: CommonService,
    private readonly permissionsService: PermissionsService,
    private readonly alertCtrl: AlertController
  ) {}

  loadEventDetails(eventId: number, token: string) {
    return this.apiService.getAuthData(
      'ambassador/get_event_details?id=' + eventId,
      token
    );
  }

  async performCheckIn(
    eventId: number,
    token: string,
    eventDetail: any,
    forceCheckIn = false
  ): Promise<CheckInResult> {
    const hasPermission = await this.permissionsService.ensureLocationPermission();
    if (!hasPermission) {
      return {
        success: false,
        message: 'Location permission is required to check in at the event.',
      };
    }

    let latitude: number;
    let longitude: number;

    try {
      const coordinates = await Geolocation.getCurrentPosition({
        enableHighAccuracy: true,
        timeout: 15000,
      });
      latitude = coordinates.coords.latitude;
      longitude = coordinates.coords.longitude;
    } catch {
      return {
        success: false,
        message: 'Unable to read your location. Please enable location services and try again.',
      };
    }

    const payload: any = {
      data_time: moment().format('YYYY-MM-DD HH:mm:ss'),
      event: eventId,
      latitude,
      longitude,
    };
    if (forceCheckIn) {
      payload.force_check_in = 1;
    }

    return new Promise<CheckInResult>((resolve) => {
      this.common.showLoader('Checking in...');
      this.apiService.sendAuthData('ambassador/store_GPS_tracking', payload, token).subscribe({
        next: (res: any) => {
          this.common.dismissLoading();
          resolve({
            success: true,
            message: res?.message || 'Checked in successfully.',
          });
        },
        error: async (err) => {
          this.common.dismissLoading();
          const body = err?.error;
          const requiresConfirmation =
            body?.error_type === 'outside_event_radius' ||
            body?.data?.requires_confirmation === true;

          if (requiresConfirmation && !forceCheckIn) {
            const confirmed = await this.confirmRemoteCheckIn(eventDetail, latitude, longitude);
            if (confirmed) {
              const retry = await this.performCheckIn(eventId, token, eventDetail, true);
              resolve(retry);
              return;
            }
            resolve({ success: false, message: 'Check-in cancelled.' });
            return;
          }

          resolve({
            success: false,
            message: body?.message || 'Check-in failed. Please try again.',
          });
        },
      });
    });
  }

  private async confirmRemoteCheckIn(
    eventDetail: any,
    latitude: number,
    longitude: number
  ): Promise<boolean> {
    const alert = await this.alertCtrl.create({
      header: 'Are you sure?',
      message:
        'Are you sure you want to check in? It does not appear you are at the event destination.',
      backdropDismiss: false,
      buttons: [
        { text: 'No', role: 'cancel' },
        { text: 'Yes', role: 'confirm' },
      ],
    });

    await alert.present();
    const { role } = await alert.onDidDismiss();
    return role === 'confirm';
  }

  getLocationLabel(eventDetail: any): string {
    const event = eventDetail?.event ?? eventDetail;
    return (
      event?.event_location_label ||
      event?.event_address ||
      'Event location'
    );
  }
}
