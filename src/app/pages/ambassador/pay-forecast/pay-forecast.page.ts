import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter, resolveDataTableResponse } from 'src/app/utils/api-response.util';

declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-pay-forecast',
  templateUrl: './pay-forecast.page.html',
  styleUrls: ['./pay-forecast.page.scss'],
})
export class PayForecastPage implements OnInit {
  params: any = { title: 'Pay Forecast', is_back: true };
  user: any;
  isSpinner: boolean = false;
  data: any;
  filterEvents: any = [];
  brands: any = [];
  type: any = '';
  start_date: any = '';
  end_date: any = '';

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
  ) { }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.data = [];
    this.filterEvents = [];
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getEventsData();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  getEventsData() {
    this.isSpinner = true;
    var self = this;
    var table = jQuery('#ambassador_pay_forecast').DataTable({
      dom: 'rtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      bDestroy: true,
      bAutoWidth: false,
      bJQueryUI: true,
      sDom: '<"H"ilfr><"applicationsList"t><"F"p>',

      ajax: {
        url: environment.auth_url + "ambassador/get_pay_forecast",
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        data: function (d: any) {
          d.start_date = self.start_date
          d.end_date = self.end_date
          return d
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          const { payload, rows } = resolveDataTableResponse(a);
          if (payload?.is_searched === null || payload?.is_searched === '') {
            self.brands = payload.brands;
          }
          table.processing(false);
          self.data = payload;
          return rows},
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "location" },
        { data: "brand" },
        { data: "totalPay" },
        { data: "action" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="eye"]').on('click', function (e: any) {
          self.nav.navigateForward('pay-forecast-detail', { state: { id: e.target.id } })
        })
      }
    });
  }


  ionViewWillLeave() {
    if (jQuery('#ambassador_submitted_recap')) {
      jQuery('#ambassador_submitted_recap').DataTable().destroy();
    }
  }

  getStatusWise(event: any) {
    jQuery('#ambassador_submitted_recap').DataTable().destroy();
    this.ionViewWillEnter()
  }

  reset() {
    jQuery('#ambassador_submitted_recap').DataTable().destroy();
    this.start_date = '';
    this.end_date = '';
    this.ionViewWillEnter()
  }
}
