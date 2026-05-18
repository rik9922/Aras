import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-client-event-details',
  templateUrl: './client-event-details.page.html',
  styleUrls: ['./client-event-details.page.scss'],
  standalone: false
})
export class ClientEventDetailsPage implements OnInit {

  params: any = { title: 'Event Details', is_back: true };
  event: any;
  eventId: any;
  user: any;
  constructor(
    public router: Router,
    public storage: StorageService,
    public nav: NavController,
    public common: CommonService,
    public apiService: ApiService,

  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.eventId = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getEventData();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }

  getEventData() {
    this.common.showLoader();
    this.apiService.getAuthData(`client/get_event_details?id=${this.eventId}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      console.log('res', res);
      this.event = res?.data;
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
    })
  }
  routeTo(url: any, data: any) {
    this.router.navigate([url], { state: { id: data } });
  }
  calculateTimeDifference(start_date: any, end_date: any) {
    const startDate = new Date(start_date);
    const endDate = new Date(end_date);
    const differenceInMilliseconds = endDate.getTime() - startDate.getTime();
    const totalMinutes = differenceInMilliseconds / (1000 * 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = Math.floor(totalMinutes % 60);
    const formattedTime = `${this.padZero(hours)}:${this.padZero(minutes)} Hrs`;
    return formattedTime;
  }
  padZero(value: number): string {
    return value < 10 ? `0${value}` : `${value}`;
  }
}
