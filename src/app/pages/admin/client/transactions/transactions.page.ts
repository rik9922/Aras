import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-client-transactions',
  templateUrl: './transactions.page.html',
  styleUrls: ['./transactions.page.scss'],
})
export class TransactionsPage implements OnInit {
  params: any = { title: 'Bill To Transactions', is_back: true };
  user: any;
  clientId: number | null = null;
  client: any;
  transactions: any[] = [];
  isLoading = false;
  hasMore = false;
  page = 1;
  perPage = 15;
  lastPage = 1;
  total = 0;
  dateFrom = '';
  dateTo = '';
  errorMessage = '';
  qbConnected = false;

  constructor(
    private router: Router,
    private nav: NavController,
    private apiService: ApiService,
    private common: CommonService,
    private storage: StorageService,
  ) {
    const state = this.router.getCurrentNavigation()?.extras?.state as any;
    this.clientId = state?.id ? Number(state.id) : null;
  }

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (!data) {
        this.nav.navigateRoot('/login');
        return;
      }

      this.user = data;
      if (!this.clientId) {
        this.common.presentAlert('Bill To not found.');
        this.nav.back();
        return;
      }
      this.loadTransactions(true);
    });
  }

  loadTransactions(refresh = false, infiniteEvent?: any) {
    if (!this.clientId || (this.isLoading && !refresh)) {
      infiniteEvent?.target?.complete();
      return;
    }

    if (refresh) {
      this.page = 1;
      this.transactions = [];
      this.hasMore = false;
      this.errorMessage = '';
    }

    this.isLoading = true;
    const params = new URLSearchParams({
      client_id: String(this.clientId),
      page: String(this.page),
      per_page: String(this.perPage),
    });
    if (this.dateFrom) params.set('date_from', this.dateFrom);
    if (this.dateTo) params.set('date_to', this.dateTo);

    this.apiService.getAuthData(`admin/get_client_transactions?${params.toString()}`, this.user?.token).subscribe({
      next: (response: any) => {
        const data = response?.status === 'success' ? response.data : response;
        const rows = Array.isArray(data?.transactions) ? data.transactions : [];
        const pagination = data?.pagination ?? {};

        this.client = data?.client ?? this.client;
        this.errorMessage = data?.error ?? '';
        this.qbConnected = Boolean(data?.quickbooks_connected);
        this.transactions = refresh ? rows : [...this.transactions, ...rows];
        this.total = Number(pagination?.total ?? this.transactions.length);
        this.lastPage = Number(pagination?.last_page ?? 1);
        this.hasMore = Number(pagination?.current_page ?? this.page) < this.lastPage;
        this.page += 1;
        this.isLoading = false;
        infiniteEvent?.target?.complete();
      },
      error: (error: any) => {
        this.isLoading = false;
        infiniteEvent?.target?.complete();
        this.common.presentAlert(error?.error?.message || 'Unable to load transactions.');
      }
    });
  }

  applyFilters() {
    this.loadTransactions(true);
  }

  clearFilters() {
    this.dateFrom = '';
    this.dateTo = '';
    this.loadTransactions(true);
  }

  loadMore(event: any) {
    this.loadTransactions(false, event);
  }

  async syncTransactions() {
    if (!this.clientId) return;
    if (!this.qbConnected) {
      this.common.presentAlert('Please connect to QuickBooks before syncing transactions.');
      return;
    }

    const confirmed = await this.common.presentConfirmationAlert(
      'Sync transactions?',
      `Sync QuickBooks transactions for ${this.client?.name || 'this Bill To'}?`
    );
    if (!confirmed) return;

    this.common.showLoader('Syncing transactions...');
    this.apiService.sendAuthData('admin/sync_client_transactions', { client_id: this.clientId }, this.user?.token).subscribe({
      next: (response: any) => {
        this.common.dismissLoading();
        this.common.presentToast(response?.message || 'Transactions synced successfully.');
        this.loadTransactions(true);
      },
      error: (error: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(error?.error?.message || 'Unable to sync transactions.');
      }
    });
  }

  backToBillTo() {
    if (!this.clientId) return;
    this.nav.navigateBack('admin-client-view', { state: { id: this.clientId } });
  }

  formatMoney(value: any): string {
    return `$${Number(value || 0).toFixed(2)}`;
  }

  isPaid(transaction: any): boolean {
    return String(transaction?.status ?? '').toLowerCase() === 'paid';
  }
}
