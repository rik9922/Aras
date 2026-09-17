import { Component, OnInit } from '@angular/core';
import { NavController } from '@ionic/angular';
import { BankingService } from 'src/app/services/banking.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-banking-transactions',
  templateUrl: './banking-transactions.page.html',
  styleUrls: ['./banking-transactions.page.scss'],
})
export class BankingTransactionsPage implements OnInit {
  params: any = { title: 'Transactions', is_back: true };
  user: any;
  isLoading = false;
  transactions: any[] = [];
  totalEarning = 0;
  page = 1;
  perPage = 10;
  lastPage = 1;
  hasMore = false;

  constructor(
    private banking: BankingService,
    private common: CommonService,
    private storage: StorageService,
    private nav: NavController,
  ) {}

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (!data) {
        this.nav.navigateRoot('/login');
        return;
      }

      this.user = data;
      this.loadTransactions(true);
    });
  }

  loadTransactions(refresh: boolean = false, infiniteEvent?: any) {
    if (this.isLoading && !refresh) return;

    if (refresh) {
      this.page = 1;
      this.transactions = [];
      this.hasMore = false;
    }

    this.isLoading = true;
    this.banking.getTransactions(this.page, this.perPage).subscribe({
      next: (res: any) => {
        const data = res?.data ?? res?.payload ?? {};
        const rows = data?.transactions ?? [];
        const pagination = data?.pagination ?? {};

        this.totalEarning = Number(data?.total_earning ?? 0);
        this.transactions = refresh ? rows : [...this.transactions, ...rows];
        this.lastPage = Number(pagination?.last_page ?? 1);
        this.hasMore = this.page < this.lastPage;
        this.page += 1;
        this.isLoading = false;
        infiniteEvent?.target?.complete();
      },
      error: (err: any) => {
        this.isLoading = false;
        infiniteEvent?.target?.complete();
        this.common.presentAlert(err?.error?.message ?? 'Could not load transactions. Please try again.');
      },
    });
  }

  loadMore(event: any) {
    this.loadTransactions(false, event);
  }

  statusColor(status: any): string {
    const value = String(status ?? '').toLowerCase();
    return value === 'completed' || value === 'paid' ? 'success' : 'warning';
  }

  isPaid(status: any): boolean {
    const value = String(status ?? '').toLowerCase();
    return value === 'completed' || value === 'paid';
  }

  formatAmount(amount: any): string {
    return Number(amount || 0).toFixed(2);
  }

  bankLabel(transaction: any): string {
    const bank = transaction?.bank_name || '-';
    return transaction?.bank_account_last4 ? `${bank} - ****${transaction.bank_account_last4}` : bank;
  }
}
