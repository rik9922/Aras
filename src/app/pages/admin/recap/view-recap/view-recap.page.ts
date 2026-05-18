import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-view-recap',
  templateUrl: './view-recap.page.html',
  styleUrls: ['./view-recap.page.scss'],
  standalone: false
})
export class ViewRecapPage implements OnInit {
  params: any = { title: 'View Recap', is_back: true };
  data: any;
  user: any;
  recapData: any = {};


  constructor(
    public common: CommonService,
    public router: Router,
    public storage: StorageService,
    public apiService: ApiService,
    public event: EventsService,
    public nav: NavController
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.getRecapById(this.data?.id);
        }
      } else {
        this.nav.navigateRoot(['/login']);
      }
    })
  }
  getRecapById(recapId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_recap_by_id?id=${recapId}`, this.user?.token).subscribe((res: any) => {
      this.recapData = res?.data.recap;
      this.common.dismissLoading();
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }

}