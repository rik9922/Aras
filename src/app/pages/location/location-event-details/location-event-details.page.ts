import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-location-event-details',
  templateUrl: './location-event-details.page.html',
  styleUrls: ['./location-event-details.page.scss'],
  standalone: false
})
export class LocationEventDetailsPage implements OnInit {
  params: any = { title: 'Event Details', is_back: true };
  event: any;
  user: any;
  constructor(
    public router: Router,
    public storage: StorageService,
    public nav: NavController,
    public common:CommonService,
    public apiService:ApiService
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.event = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if(this.event?.id){
          this.getSingleEventData(this.event?.id);
        }
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }
  getSingleEventData(event_id:any) {
    this.common.showLoader();
    this.apiService.getAuthData('admin/get_event_details?id=' + event_id, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      if (res?.data?.event) {
        res.data.event.venue_name = parseInt(res.data.event.venue_name);
        res.data.event.client_id = parseInt(res.data.event.client_id);
        this.event = res?.data?.event;
      }
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
    })
  }

  getStatusLabel(status: string | number): string {
    return status == '1' ? 'Completed' : 'Pending';
  }

  getStatusClass(status: string | number): string {
    return status == '1' ? 'status-pill--success' : 'status-pill--warning';
  }

  formatEventTime(): string {
    if (this.event?.datetime) {
      return this.event.datetime;
    }
    const start = this.event?.start_time;
    const end = this.event?.end_time;
    if (start && end) {
      return `From: ${start} To: ${end}`;
    }
    if (start) {
      return `From: ${start}`;
    }
    return '-';
  }

}
