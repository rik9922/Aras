import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';

@Component({
  standalone: false,
  selector: 'app-pay-forecast-detail',
  templateUrl: './pay-forecast-detail.page.html',
  styleUrls: ['./pay-forecast-detail.page.scss'],
})
export class PayForecastDetailPage implements OnInit {
  params: any = { title: 'Pay Forecast Detail', is_back: true };
  data: any;
  user: any;
  eventDetail: any;
  base_url: any;
  constructor(
    public router: Router,
    public apiService: ApiService,
    public common: CommonService,
    public storage: StorageService
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
    })
  }

  get_pay_forecast_detail() {
    this.common.showLoader()
    this.apiService.getAuthData('ambassador/get_pay_forecast_detail?id=' + this.data?.id, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading()
      this.eventDetail = res?.data;
    }, err => {
      this.common.dismissLoading()
      console.log(err)
    })
  }

}
