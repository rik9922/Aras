import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-admin-payment',
  templateUrl: './admin-payment.page.html',
  styleUrls: ['./admin-payment.page.scss'],
})
export class AdminPaymentPage implements OnInit {
  params: any = { title: 'Payments', is_back: true };
  user: any;
  data: any = {};
  rows: any[] = [];
  filterForm!: FormGroup;
  payrollForm!: FormGroup;
  isSearch = false;
  isLoading = false;
  isGenerating = false;
  isProcessing = false;
  hasMore = true;
  total = 0;
  page = 1;
  perPage = 20;

  readonly statusOptions = [
    { value: 'all', label: 'All' },
    { value: 'created', label: 'Created' },
    { value: 'processing', label: 'Processing' },
    { value: 'completed', label: 'Completed' },
    { value: 'failed', label: 'Error' },
    { value: 'removed', label: 'Removed' },
  ];

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public fb: FormBuilder
  ) { }

  ngOnInit() {
    const today = moment();
    const searchStart = today.clone().subtract(30, 'days').format('YYYY-MM-DD');
    const searchEnd = today.format('YYYY-MM-DD');
    const payrollEnd = today.clone().subtract(2, 'days');
    const payrollStart = payrollEnd.clone().subtract(15, 'days');

    this.filterForm = this.fb.group({
      startDate: [searchStart],
      endDate: [searchEnd],
      ambassador_id: [''],
      statuses: [[]],
    });

    this.payrollForm = this.fb.group({
      generate_start_date: [payrollStart.format('YYYY-MM-DD')],
      generate_end_date: [payrollEnd.format('YYYY-MM-DD')],
    });
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
    this.loadPayments(event);
  }

  loadPayments(event?: any) {
    if (this.isLoading || !this.hasMore) {
      event?.target?.complete();
      return;
    }

    this.isLoading = true;
    this.apiService.getAuthData(`admin/get_payments?${this.buildQueryString()}`, this.user?.token).subscribe((res: any) => {
      const payload = res?.data || {};
      const newRows = payload?.rows || payload?.data || [];
      this.data = {
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
      this.common.presentAlert(err?.error?.message || 'Unable to load payments.');
    });
  }

  searchClicked() {
    this.isSearch = !this.isSearch;
  }

  applyFilters() {
    this.isSearch = false;
    this.resetAndLoad();
  }

  resetFilters() {
    const today = moment();
    this.filterForm.reset({
      startDate: today.clone().subtract(30, 'days').format('YYYY-MM-DD'),
      endDate: today.format('YYYY-MM-DD'),
      ambassador_id: '',
      statuses: [],
    });
    this.isSearch = false;
    this.resetAndLoad();
  }

  async generatePayroll() {
    const start = this.payrollForm.value?.generate_start_date;
    const end = this.payrollForm.value?.generate_end_date;

    if (!start || !end) {
      this.common.presentAlert('Please select a payroll date range before generating.');
      return;
    }

    this.isGenerating = true;
    this.apiService.sendAuthData('admin/generate_payroll', this.payrollForm.value, this.user?.token).subscribe((res: any) => {
      this.isGenerating = false;
      if (res?.status === 'success') {
        const message = `Batch: ${res.batch_number}. Payroll Period: ${res.period_start} - ${res.period_end}. Payments: ${res.total_records}. Total: $${res.total_amount}.`;
        this.common.presentAlertWithPromise('Payroll Batch Generated Successfully.', message);
        this.resetAndLoad();
      } else {
        this.common.presentAlert(res?.message || 'Payroll generation failed.');
      }
    }, err => {
      this.isGenerating = false;
      this.common.presentAlert(err?.error?.message || 'Payroll generation failed. Please try again.');
    });
  }

  async processPayroll() {
    try {
      await this.common.confirmationAlert(
        'This will process any payments in the Created status, proceed?',
        'Process Payroll?',
        'Cancel',
        'OK'
      );
    } catch {
      return;
    }

    this.isProcessing = true;
    this.apiService.sendAuthData('admin/process_payroll', {}, this.user?.token).subscribe((res: any) => {
      this.isProcessing = false;
      if (res?.status === 'success') {
        this.common.presentAlertWithPromise('Processing Started', res?.message || 'Payroll processing started.');
        this.resetAndLoad();
      } else {
        this.common.presentAlert(res?.message || 'Unable to process payroll.');
      }
    }, err => {
      this.isProcessing = false;
      this.common.presentAlert(err?.error?.message || 'Unable to process payroll.');
    });
  }

  getMoney(value: any): string {
    return `$${Number(value || 0).toFixed(2)}`;
  }

  getDate(value: any, format = 'MMM DD, YYYY'): string {
    return value ? moment(value).format(format) : '-';
  }

  getStatusLabel(row: any): string {
    const status = String(row?.status || 'created');
    return status === 'failed' ? 'Error' : status.charAt(0).toUpperCase() + status.slice(1);
  }

  getStatusClass(row: any): string {
    return `status-badge--${row?.status || 'created'}`;
  }

  onStatusesChange(event: any) {
    const values = event?.detail?.value || [];
    if (values.includes('all')) {
      this.filterForm.get('statuses')?.setValue(['all']);
    }
  }

  private buildQueryString(): string {
    const params = new URLSearchParams();
    params.set('page', String(this.page));
    params.set('per_page', String(this.perPage));

    Object.entries(this.filterForm.value || {}).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '' || value === false) {
        return;
      }

      if (Array.isArray(value)) {
        value.forEach((item) => params.append(`${key}[]`, String(item)));
        return;
      }

      params.set(key, String(value));
    });

    return params.toString();
  }
}
