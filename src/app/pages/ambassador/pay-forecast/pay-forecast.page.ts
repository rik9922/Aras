import { Component, OnInit } from '@angular/core';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { StorageService } from 'src/app/services/storage.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  standalone: false,
  selector: 'app-pay-forecast',
  templateUrl: './pay-forecast.page.html',
  styleUrls: ['./pay-forecast.page.scss'],
})
export class PayForecastPage implements OnInit {
  params: any = { title: 'Pay Forecast', is_back: true };
  user: any;
  data: any = {};
  rows: any[] = [];
  visibleRows: any[] = [];
  isLoading = false;
  isSearch = false;
  errorMessage = '';
  hasMore = false;
  total = 0;
  page = 1;
  perPage = 10;
  start_date: any = '';
  end_date: any = '';

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public nav: NavController,
  ) { }

  ngOnInit() {
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
    this.visibleRows = [];
    this.hasMore = false;
    this.loadForecast(event);
  }

  loadForecast(event?: any) {
    if (this.isLoading) {
      event?.target?.complete();
      return;
    }

    this.isLoading = true;
    this.errorMessage = '';

    this.apiService.getAuthData(`ambassador/get_pay_forecast?${this.buildQueryString()}`, this.user?.token).subscribe((res: any) => {
      const { payload, rows } = resolveDataTableResponse(res);
      this.data = payload || {};
      this.rows = rows.map((row: any) => ({
        ...row,
        referenceId: row?.event_id || row?.id || this.extractReferenceId(row?.action),
      }));
      this.total = payload?.recordsFiltered ?? payload?.recordsTotal ?? this.rows.length;
      this.visibleRows = this.rows.slice(0, this.perPage);
      this.page = 2;
      this.hasMore = this.visibleRows.length < this.rows.length;
      this.isLoading = false;
      event?.target?.complete();
    }, err => {
      this.isLoading = false;
      this.errorMessage = err?.error?.message || 'Unable to load pay forecast.';
      event?.target?.complete();
    });
  }

  loadMore(event?: any) {
    const nextLength = this.page * this.perPage;
    this.visibleRows = this.rows.slice(0, nextLength);
    this.page++;
    this.hasMore = this.visibleRows.length < this.rows.length;
    event?.target?.complete();
  }

  searchClicked() {
    this.isSearch = !this.isSearch;
  }

  applyFilters() {
    this.isSearch = false;
    this.resetAndLoad();
  }

  reset() {
    this.start_date = '';
    this.end_date = '';
    this.isSearch = false;
    this.resetAndLoad();
  }

  viewDetails(row: any) {
    if (row?.referenceId) {
      this.nav.navigateForward('pay-forecast-detail', { state: { id: row.referenceId } });
    }
  }

  getForecastAmount(row: any): string {
    return row?.totalPay || this.getTotalPay();
  }

  getTotalPay(): string {
    const value = this.data?.totalPay;
    if (value === null || value === undefined || value === '') {
      return '$0.00';
    }

    return typeof value === 'number' ? `$${value.toFixed(2)}` : String(value).startsWith('$') ? String(value) : `$${value}`;
  }

  getDateRangeLabel(): string {
    if (this.start_date && this.end_date) {
      return `${this.start_date} to ${this.end_date}`;
    }

    return 'All assigned events';
  }

  private buildQueryString(): string {
    const params = new URLSearchParams();
    params.set('draw', '1');

    if (this.start_date && this.end_date) {
      params.set('start_date', this.start_date);
      params.set('end_date', this.end_date);
    }

    return params.toString();
  }

  private extractReferenceId(action: any): string {
    if (!action || typeof action !== 'string') {
      return '';
    }

    const match = action.match(/id="([^"]+)"/);
    return match?.[1] || '';
  }
}
