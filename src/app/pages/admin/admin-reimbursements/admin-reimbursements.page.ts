import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-admin-reimbursements',
  templateUrl: './admin-reimbursements.page.html',
  styleUrls: ['./admin-reimbursements.page.scss'],
})
export class AdminReimbursementsPage implements OnInit {
  params: any = { title: 'Reimbursements', is_back: true };
  user: any;
  rows: any[] = [];
  filterForm!: FormGroup;
  isSearch = false;
  isLoading = false;
  hasMore = true;
  total = 0;
  page = 1;
  perPage = 20;

  readonly statusOptions = [
    { value: 'all', label: 'All Statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'paid', label: 'Paid' },
  ];

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public fb: FormBuilder
  ) { }

  ngOnInit() {
    this.filterForm = this.fb.group({
      status: [''],
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
    this.loadReimbursements(event);
  }

  loadReimbursements(event?: any) {
    if (this.isLoading || !this.hasMore) {
      event?.target?.complete();
      return;
    }

    this.isLoading = true;
    this.apiService.getAuthData(`admin/get_reimbursements?${this.buildQueryString()}`, this.user?.token).subscribe((res: any) => {
      const payload = res?.data || {};
      const newRows = payload?.rows || payload?.data || [];
      this.total = payload?.total ?? payload?.recordsFiltered ?? newRows.length;
      this.rows = this.page === 1 ? newRows : [...this.rows, ...newRows];
      this.hasMore = this.rows.length < this.total;
      this.page++;
      this.isLoading = false;
      event?.target?.complete();
    }, err => {
      this.isLoading = false;
      event?.target?.complete();
      this.common.presentAlert(err?.error?.message || 'Unable to load reimbursements.');
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
    this.filterForm.reset({ status: '' });
    this.isSearch = false;
    this.resetAndLoad();
  }

  viewReimbursement(row: any) {
    if (!row?.id) {
      return;
    }

    this.router.navigate(['/admin/reimbursements', row.id], { state: { id: row.id } });
  }

  getStatusClass(row: any): string {
    return `status-badge--${row?.status || 'pending'}`;
  }

  private buildQueryString(): string {
    const params = new URLSearchParams();
    params.set('page', String(this.page));
    params.set('per_page', String(this.perPage));

    Object.entries(this.filterForm.value || {}).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '' || value === false || value === 'all') {
        return;
      }

      params.set(key, String(value));
    });

    return params.toString();
  }
}
