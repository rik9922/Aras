import { ChangeDetectorRef, Component, NgZone, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';
import { Router } from '@angular/router';
import { AlertController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';

@Component({
  standalone: false,
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
})
export class ListPage implements OnInit {
  params: any = { title: 'Ambassadors', is_back: true };
  user: any;

  // ── Card data ─────────────────────────────────────────────────────────
  ambassadors: any[] = [];
  markets: any[]     = [];

  // ── Pagination state ──────────────────────────────────────────────────
  private page       = 0;
  private pageSize   = 25;
  hasMore            = true;
  isLoading          = false;
  isInitialLoad      = true;

  // ── Search ────────────────────────────────────────────────────────────
  searchText         = '';
  isFilterOpen       = false;

  // ── Filter form ───────────────────────────────────────────────────────
  filterForm: FormGroup = new FormGroup({});

  // ── Default avatar ────────────────────────────────────────────────────
  readonly defaultAvatar = 'assets/icon/default-avatar.png';

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public cdrf: ChangeDetectorRef,
    public fb: FormBuilder,
    public event: EventsService,
    private alertCtrl: AlertController,
    private zone: NgZone,
  ) {
    this.event.subscribe('reload_ambassador', () => this.refresh());
  }

  ngOnInit() {
    this.filterForm = this.fb.group({
      first_name: new FormControl(''),
      last_name:  new FormControl(''),
      phone:      new FormControl(''),
      email:      new FormControl(''),
      city:       new FormControl(''),
      state:      new FormControl(''),
      startDate:  new FormControl(''),
      endDate:    new FormControl(''),
      market_id:  new FormControl(''),
      region:     new FormControl(''),
      status:     new FormControl(''),
    });
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.refresh();
      } else {
        this.router.navigate(['/login']);
      }
    });
  }

  // ── Data loading ──────────────────────────────────────────────────────

  /** Full reset — used on enter, search change, filter apply, refresh. */
  refresh() {
    this.ambassadors  = [];
    this.page         = 0;
    this.hasMore      = true;
    this.isInitialLoad = true;
    this.loadPage();
  }

  /** Called by infinite scroll and refresh(). */
  loadPage(infiniteScrollEvent?: any) {
    if (this.isLoading || !this.hasMore) {
      infiniteScrollEvent?.target?.complete();
      return;
    }

    this.isLoading = true;

    const params = this.buildQueryParams();
    const url    = `${environment.auth_url}admin/get_ambassador?${params}&start=${this.page * this.pageSize}&length=${this.pageSize}&draw=${this.page + 1}`;

    this.apiService.getAuthData(url.replace(environment.auth_url, ''), this.user?.token)
      .subscribe({
        next: (res: any) => {
          this.zone.run(() => {
            const rows: any[] = res?.data?.data ?? [];

            // Capture markets from first response for the filter dropdown
            if (this.page === 0 && res?.data?.markets) {
              this.markets = res.data.markets;
            }

            this.ambassadors = [...this.ambassadors, ...rows];
            this.hasMore     = rows.length === this.pageSize;
            this.page++;
            this.isLoading    = false;
            this.isInitialLoad = false;
            infiniteScrollEvent?.target?.complete();
            this.cdrf.detectChanges();
          });
        },
        error: (err: any) => {
          console.error('getAmbassador error', err);
          this.zone.run(() => {
            this.isLoading    = false;
            this.isInitialLoad = false;
            infiniteScrollEvent?.target?.complete();
            this.cdrf.detectChanges();
          });
        },
      });
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  // ── Search ────────────────────────────────────────────────────────────

  onSearchInput(event: any) {
    this.searchText = event?.detail?.value?.trim() ?? '';
    this.refresh();
  }

  // ── Filters ───────────────────────────────────────────────────────────

  toggleFilter() {
    this.isFilterOpen = !this.isFilterOpen;
  }

  applyFilters() {
    this.isFilterOpen = false;
    this.refresh();
  }

  resetFilters() {
    this.filterForm.reset();
    this.searchText   = '';
    this.isFilterOpen = false;
    this.refresh();
  }

  // ── Build query string ────────────────────────────────────────────────

  private buildQueryParams(): string {
    const parts: string[] = [];
    const f = this.filterForm.value;

    if (this.searchText) {
      // Search across first + last name
      parts.push(`first_name=${encodeURIComponent(this.searchText)}`);
    }
    if (f.last_name)  parts.push(`last_name=${encodeURIComponent(f.last_name)}`);
    if (f.phone)      parts.push(`phone=${encodeURIComponent(f.phone)}`);
    if (f.email)      parts.push(`email=${encodeURIComponent(f.email)}`);
    if (f.city)       parts.push(`city=${encodeURIComponent(f.city)}`);
    if (f.state)      parts.push(`state=${encodeURIComponent(f.state)}`);
    if (f.market_id)  parts.push(`market_id=${encodeURIComponent(f.market_id)}`);
    if (f.region)     parts.push(`region=${encodeURIComponent(f.region)}`);
    if (f.startDate)  parts.push(`startDate=${encodeURIComponent(f.startDate)}`);
    if (f.endDate)    parts.push(`endDate=${encodeURIComponent(f.endDate)}`);
    if (f.status !== null && f.status !== '') {
      parts.push(`status=${encodeURIComponent(f.status)}`);
    }

    return parts.join('&');
  }

  // ── Actions ───────────────────────────────────────────────────────────

  edit(ambassador: any) {
    this.router.navigate(['/admin-add-ambassador'], { state: { id: ambassador.id } });
  }

  async confirmDelete(ambassador: any) {
    const alert = await this.alertCtrl.create({
      header:  'Delete Ambassador',
      message: `Are you sure you want to delete <strong>${ambassador.name}</strong>?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text:    'Delete',
          role:    'destructive',
          cssClass: 'alert-delete-btn',
          handler: () => this.deleteAmbassador(ambassador),
        },
      ],
    });
    await alert.present();
  }

  private deleteAmbassador(ambassador: any) {
    this.common.showLoader();
    this.apiService.sendAuthData('admin/delete_ambassador', { id: ambassador.id }, this.user?.token)
      .subscribe({
        next: (res: any) => {
          this.common.dismissLoading();
          // Remove from local list without full reload
          this.ambassadors = this.ambassadors.filter(a => a.id !== ambassador.id);
          this.cdrf.detectChanges();
          this.common.presentToast?.(res?.message ?? 'Ambassador deleted.');
        },
        error: (err: any) => {
          this.common.dismissLoading();
          console.error('delete_ambassador error', err);
        },
      });
  }

  // ── Helpers ───────────────────────────────────────────────────────────

  avatarSrc(ambassador: any): string {
    return ambassador?.profile_image || this.defaultAvatar;
  }

  trackById(_: number, item: any) {
    return item?.id;
  }
}
