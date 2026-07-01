import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { Router } from '@angular/router';
import { AlertController, NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter, resolveDataTableResponse } from 'src/app/utils/api-response.util';

declare var jQuery: any;

@Component({
  selector: 'app-admin-pay-forecast',
  templateUrl: './admin-pay-forecast.page.html',
  styleUrls: ['./admin-pay-forecast.page.scss'],
  standalone: false,
})
export class AdminPayForecastPage implements OnInit {
  params: any = { title: 'Pay Forecast', is_back: true };
  user: any;
  data: any;
  searchPayForecast!: FormGroup;
  isSearch: boolean = false;
  years: any = [];
  months: any = [];
  days: any = [];
  extraParams: any = '';
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
    public fb: FormBuilder
  ) { }

  ngOnInit() {
    this.searchPayForecast = this.fb.group({
      location: [''],
      market_id: [''],
      brand_id: [''],
      manager_id: [''],
      ambassador_name: [''],
      ambassador_email: [''],
      years: [''],
      start_date: [''],
      end_date: [''],
      noShow: [false],
      only_events_with_recaps: [false],
      only_events_with_no_recaps: [false],
      exclude_unpaid_events: [false],
      pinata_pay: [false],
    });
  }

  ionViewWillEnter() {
    let count = 0;
    let start = parseInt(moment().subtract(5, 'years').format('YYYY'));
    for (let index = start; index <= parseInt(moment().add(5, 'years').format('YYYY')); index++) {
      this.years.push(start + count)
      count++;
    }

    let months = moment.months();
    for (let index = 0; index < months?.length; index++) {
      this.months.push({
        id: index + 1,
        name: months[index]
      })
    }

    this.data = [];
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
    var self = this;
    var table = jQuery('#admin_pay_forecast').DataTable({
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
        url: environment.auth_url + "admin/get_pay_forecast?" + this.extraParams,
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        data: function (d: any) {
          return d
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          const { payload, rows } = resolveDataTableResponse(a);
          table.processing(false);
          self.data = payload;
          return rows},
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "first_name" },
        { data: "brand" },
        // { data: "status" },
        { data: "action" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="eye"]').on('click', function (e: any) {
          self.nav.navigateForward('event-details', { state: { id: e.target.id } })
        });
      }
    });
  }


  ionViewWillLeave() {
    if (jQuery('#admin_')) {
      jQuery('#admin_pay_forecast').DataTable().destroy();
    }
  }

  searchClicked() {
    this.isSearch = !this.isSearch;
  }

  resetForm() {
    this.extraParams = '';
    jQuery('#admin_pay_forecast').DataTable().destroy();
    this.searchPayForecast.reset();
    this.isSearch = !this.isSearch;
    this.ionViewWillEnter();
  }

  onChange(event: any, key: any) {
    jQuery('#admin_pay_forecast').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value
    this.ionViewWillEnter();
    this.searchClicked()
  }

  onChangeCheckbox(event: any, key: any) {
    jQuery('#admin_pay_forecast').DataTable().destroy();
    var value = event?.detail?.checked ? 1 : 0;
    this.extraParams = '&' + key + '=' + value
    this.ionViewWillEnter();
    this.searchClicked()
  }

  onChangeMonth(event: any, type: any) {
    console.log(event?.detail?.value)
    let current = this.searchPayForecast.value?.years + '-' + event?.detail?.value + '-' + moment().format('D')
    const startOfMonth = moment(current).startOf('month').format('D');
    const endOfMonth = moment(current).endOf('month').format('D');
    for (let index = parseInt(startOfMonth); index <= parseInt(endOfMonth); index++) {
      this.days.push(index);
    }
  }

  onSubmit() {

  }
}
