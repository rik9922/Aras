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
  params: any = { title: 'Locations List', is_back: true };
  user: any;
  searchQuery: string = '';
  locations: any[] = [];
  totalLocations: number = 0;
  isLoading: boolean = false;
  isInitialLoading: boolean = false;
  hasMore: boolean = true;

  private page: number = 0;
  private pageSize: number = 20;

  constructor(
    public common: CommonService,
    public apiService: ApiService,
    public storage: StorageService,
    public event: EventsService,
    public router: Router,
    public nav: NavController
  ) {
    this.event.subscribe('reload_locations', () => {
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
    this.locations = [];
    this.totalLocations = 0;
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
    const endpoint = `admin/get_locations?start=${start}&length=${this.pageSize}&draw=${this.page + 1}&search=${search}`;

    this.apiService.getAuthData(endpoint, this.user?.token).subscribe({
      next: (res: any) => {
        const { payload, rows } = resolveDataTableResponse(res);
        this.totalLocations = Number(payload?.recordsTotal ?? rows.length);
        this.locations = [...this.locations, ...rows];
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
        this.common.presentAlert(err?.error?.message || 'Unable to load locations.');
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

  edit(location: any) {
    this.router.navigate(['/admin-add-location'], { state: { id: location?.id ?? location } });
  }

  async deleteLocation(location: any) {
    const confirmed = await this.common.presentConfirmationAlert(
      'Delete location?',
      `Delete ${this.locationAddress(location)}? This cannot be undone.`
    );
    if (!confirmed) return;

    this.common.showLoader('Deleting location...');
    this.apiService.sendAuthData('admin/delete_location', { id: location?.id }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.common.presentToast(res?.message || 'Location deleted successfully.');
        this.refresh();
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to delete this location.');
      }
    });
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }

  locationAddress(location: any): string {
    return location?.address || 'this location';
  }

  locationLine(location: any): string {
    return [location?.city, location?.state, location?.zip_code].filter(Boolean).join(', ') || '-';
  }

  trackByLocationId(index: number, location: any) {
    return location?.id ?? index;
  }
}
