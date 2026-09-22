import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
  standalone: false
})
export class ListPage implements OnInit {
  user: any;
  params: any = { title: 'Market List', is_back: true };
  searchQuery: string = '';
  markets: any[] = [];
  totalMarkets: number = 0;
  isLoading: boolean = false;
  isInitialLoading: boolean = false;
  hasMore: boolean = true;

  private page: number = 0;
  private pageSize: number = 20;

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    public apiService: ApiService,
    public event: EventsService
  ) {
    this.event.subscribe('reload_market', () => {
      this.refresh();
    });
  }

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.refresh();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  refresh() {
    this.markets = [];
    this.totalMarkets = 0;
    this.page = 0;
    this.hasMore = true;
    this.isInitialLoading = true;
    this.loadPage();
  }

  loadPage(infiniteScrollEvent?: any) {
    if (this.isLoading) {
      infiniteScrollEvent?.target?.complete();
      return;
    }

    if (!this.hasMore && infiniteScrollEvent) {
      infiniteScrollEvent.target?.complete();
      return;
    }

    this.isLoading = true;
    const start = this.page * this.pageSize;
    const search = encodeURIComponent(this.searchQuery || '');
    const endpoint = `admin/get_market_data?start=${start}&length=${this.pageSize}&draw=${this.page + 1}&search=${search}`;

    this.apiService.getAuthData(endpoint, this.user?.token).subscribe({
      next: (res: any) => {
        const { payload, rows } = resolveDataTableResponse(res);
        this.totalMarkets = Number(payload?.recordsTotal ?? rows.length);
        this.markets = [...this.markets, ...rows];
        this.hasMore = rows.length === this.pageSize;
        this.page++;
        this.isLoading = false;
        this.isInitialLoading = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: (err: any) => {
        this.isLoading = false;
        this.isInitialLoading = false;
        infiniteScrollEvent?.target?.complete();
        this.common.presentAlert(err?.error?.message || 'Unable to load markets.');
      }
    });
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  onSearch(event: any) {
    this.searchQuery = event?.detail?.value?.trim() ?? '';
    this.refresh();
  }

  edit(market: any) {
    this.router.navigate(['/admin-add-market'], { state: { id: market?.id ?? market } });
  }

  async deleteMarket(market: any) {
    const confirmed = await this.common.presentConfirmationAlert(
      'Delete market?',
      `Delete ${market?.name || 'this market'}? This cannot be undone.`
    );
    if (!confirmed) return;

    this.common.showLoader('Deleting market...');
    this.apiService.sendAuthData('admin/delete_market', { id: market?.id }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.common.presentToast(res?.message || 'Market deleted successfully.');
        this.refresh();
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to delete this market.');
      }
    });
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }

  marketLocation(market: any): string {
    return market?.location || market?.description || '-';
  }

  statusLabel(status: any): string {
    return String(status || 'active').replace(/_/g, ' ');
  }

  trackByMarketId(index: number, market: any) {
    return market?.id ?? index;
  }
}
