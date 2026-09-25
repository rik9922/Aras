import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-admin-pay-forecast',
  templateUrl: './admin-pay-forecast.page.html',
  styleUrls: ['./admin-pay-forecast.page.scss'],
  standalone: false,
})
export class AdminPayForecastPage implements OnInit {
  params: any = { title: 'Pay Forecast', is_back: true };
  user: any;
  data: any = {};
  rows: any[] = [];
  searchPayForecast!: FormGroup;
  isSearch = false;
  isLoading = false;
  hasMore = true;
  total = 0;
  page = 1;
  perPage = 20;
  years: number[] = [];
  months: any[] = [];

  readonly paymentTypes = ['Event Pay', 'Event Pay (Expedited)', 'Reimbursement'];

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
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
      year: [''],
      month: [''],
      start_day: [''],
      end_day: [''],
      no_show: [false],
      only_events_with_recaps: [false],
      only_events_with_no_recaps: [false],
      exclude_unpaid_events: [false],
      pinata_pay: [false],
    });

    const start = parseInt(moment().subtract(5, 'years').format('YYYY'), 10);
    const end = parseInt(moment().add(5, 'years').format('YYYY'), 10);
    for (let year = start; year <= end; year++) {
      this.years.push(year);
    }

    this.months = moment.months().map((name, index) => ({ id: index + 1, name }));
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.resetAndLoad();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  resetAndLoad(event?: any) {
    this.page = 1;
    this.rows = [];
    this.hasMore = true;
    this.loadForecast(event);
  }

  loadForecast(event?: any) {
    if (this.isLoading || !this.hasMore) {
      event?.target?.complete();
      return;
    }

    this.isLoading = true;
    const endpoint = `admin/get_pay_forecast?${this.buildQueryString()}`;

    this.apiService.getAuthData(endpoint, this.user?.token).subscribe((res: any) => {
      const payload = res?.data || {};
      const newRows = payload?.rows || payload?.data || [];
      this.data = {
        locations: payload?.locations || this.data?.locations || [],
        markets: payload?.markets || this.data?.markets || [],
        brands: payload?.brands || this.data?.brands || [],
        managers: payload?.managers || this.data?.managers || [],
        ambassadors: payload?.ambassadors || this.data?.ambassadors || [],
      };
      this.total = payload?.total ?? payload?.recordsFiltered ?? newRows.length;
      this.rows = this.page === 1 ? newRows : [...this.rows, ...newRows];
      this.hasMore = this.rows.length < this.total;
      this.page++;
      this.isLoading = false;
      event?.target?.complete();
    }, err => {
      this.isLoading = false;
      event?.target?.complete();
      this.common.presentAlert(err?.error?.message || 'Unable to load payment forecast.');
    });
  }

  searchClicked() {
    this.isSearch = !this.isSearch;
  }

  applyFilters() {
    this.isSearch = false;
    this.resetAndLoad();
  }

  resetForm() {
    this.searchPayForecast.reset({
      no_show: false,
      only_events_with_recaps: false,
      only_events_with_no_recaps: false,
      exclude_unpaid_events: false,
      pinata_pay: false,
    });
    this.isSearch = false;
    this.resetAndLoad();
  }

  viewRow(row: any) {
    if (row?.source_type === 'recap' && row?.source_id) {
      this.router.navigate(['/admin-view-recap'], { state: { id: row.source_id } });
      return;
    }

    if (row?.event_id) {
      this.nav.navigateForward('event-details', { state: { id: row.event_id } });
    }
  }

  getMoney(value: any): string {
    const amount = Number(value || 0);
    return `$${amount.toFixed(2)}`;
  }

  getDate(value: any, format = 'MMM DD, YYYY'): string {
    return value ? moment(value).format(format) : '-';
  }

  getEventMeta(row: any): string {
    return [row?.brand_name, row?.location, this.getDate(row?.event_date)]
      .filter((value) => value && value !== '-')
      .join(' | ');
  }

  getStatusClass(row: any): string {
    return String(row?.payroll_status || 'Pending').toLowerCase() === 'pending'
      ? 'status-badge--pending'
      : 'status-badge--approved';
  }

  isApproved(row: any): boolean {
    return String(row?.payroll_status || '').toLowerCase() === 'approved' && !!row?.approval_date;
  }

  private buildQueryString(): string {
    const params = new URLSearchParams();
    params.set('page', String(this.page));
    params.set('per_page', String(this.perPage));

    Object.entries(this.searchPayForecast.value || {}).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '' || value === false) {
        return;
      }

      params.set(key, value === true ? '1' : String(value));
    });

    return params.toString();
  }
}
