import { ChangeDetectorRef, Component, NgZone, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';
import { Router } from '@angular/router';
import { AlertController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';

interface FilterPill {
  key: string;
  label: string;
  value: string;
}

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

  // ── Pagination ────────────────────────────────────────────────────────
  private page     = 0;
  private pageSize = 25;
  hasMore          = true;
  isLoading        = false;
  isInitialLoad    = true;
  totalAmbassadors = 0;
  filteredAmbassadors = 0;

  // ── Search ────────────────────────────────────────────────────────────
  searchText    = '';
  isFilterOpen  = false;

  // ── Filter form — param names match web + API exactly ─────────────────
  filterForm: FormGroup = new FormGroup({});

  readonly defaultAvatar = 'https://aras.betaplanets.com/user.png';

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
      // Text filters
      first_name: new FormControl(''),
      last_name:  new FormControl(''),
      email:      new FormControl(''),
      phone:      new FormControl(''),
      city:       new FormControl(''),
      state:      new FormControl(''),
      // Select filters
      market_id:      new FormControl(''),
      region:         new FormControl(''),
      // Date range — param names matching web (start_date / end_date)
      start_date:     new FormControl(''),
      end_date:       new FormControl(''),
      // Radio filters
      status:         new FormControl(''),   // '' | '1' | '0'
      liquor_license: new FormControl(''),   // '' | '1' | '0'
      bank_setup:     new FormControl('all'),// 'all' | 'verified' | 'pending_verification' | 'not_started'
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

  refresh() {
    this.ambassadors   = [];
    this.page          = 0;
    this.hasMore       = true;
    this.isInitialLoad = true;
    this.loadPage();
  }

  loadPage(infiniteScrollEvent?: any) {
    if (this.isLoading || !this.hasMore) {
      infiniteScrollEvent?.target?.complete();
      return;
    }
    this.isLoading = true;

    const qs  = this.buildQueryString();
    const url = `admin/get_ambassador?${qs}&start=${this.page * this.pageSize}&length=${this.pageSize}&draw=${this.page + 1}`;

    this.apiService.getAuthData(url, this.user?.token).subscribe({
      next: (res: any) => {
        this.zone.run(() => {
          const rows: any[] = res?.data?.data ?? [];
          if (this.page === 0 && res?.data?.markets) {
            this.markets = res.data.markets;
          }
          this.totalAmbassadors = Number(res?.data?.recordsTotal ?? this.totalAmbassadors);
          this.filteredAmbassadors = Number(res?.data?.recordsFiltered ?? rows.length);
          this.ambassadors   = [...this.ambassadors, ...rows];
          this.hasMore       = this.ambassadors.length < this.filteredAmbassadors;
          this.page++;
          this.isLoading     = false;
          this.isInitialLoad = false;
          infiniteScrollEvent?.target?.complete();
          this.cdrf.detectChanges();
        });
      },
      error: (err: any) => {
        console.error('getAmbassador error', err);
        this.zone.run(() => {
          this.isLoading     = false;
          this.isInitialLoad = false;
          infiniteScrollEvent?.target?.complete();
          this.cdrf.detectChanges();
        });
      },
    });
  }

  onInfiniteScroll(event: any) { this.loadPage(event); }

  // ── Search ────────────────────────────────────────────────────────────

  onSearchInput(event: any) {
    this.searchText = event?.detail?.value?.trim() ?? '';
    this.refresh();
  }

  // ── Filters ───────────────────────────────────────────────────────────

  toggleFilter()  { this.isFilterOpen = !this.isFilterOpen; }

  applyFilters()  { this.isFilterOpen = false; this.refresh(); }

  resetFilters()  {
    this.filterForm.reset({ bank_setup: 'all' });
    this.searchText   = '';
    this.isFilterOpen = false;
    this.refresh();
  }

  get displayAmbassadorCount(): number {
    return this.hasActiveFilters() ? this.filteredAmbassadors : this.totalAmbassadors;
  }

  getActiveFilterPills(): FilterPill[] {
    const f = this.filterForm.value;
    const pills: FilterPill[] = [];

    if (this.searchText) {
      pills.push({ key: 'search', label: 'Search', value: this.searchText });
    }

    this.addTextPill(pills, 'first_name', 'First Name', f.first_name);
    this.addTextPill(pills, 'last_name', 'Last Name', f.last_name);
    this.addTextPill(pills, 'email', 'Email', f.email);
    this.addTextPill(pills, 'phone', 'Phone', f.phone);
    this.addTextPill(pills, 'city', 'City', f.city);
    this.addTextPill(pills, 'state', 'State', f.state);
    this.addTextPill(pills, 'market_id', 'Market', this.marketLabel(f.market_id));
    this.addTextPill(pills, 'region', 'Division', f.region);
    this.addTextPill(pills, 'start_date', 'Hired From', f.start_date);
    this.addTextPill(pills, 'end_date', 'Hired To', f.end_date);

    if (f.status !== null && f.status !== '') {
      pills.push({ key: 'status', label: 'Status', value: f.status === '1' ? 'Active' : 'Inactive' });
    }
    if (f.liquor_license !== null && f.liquor_license !== '') {
      pills.push({
        key: 'liquor_license',
        label: 'Liquor License',
        value: f.liquor_license === '1' ? 'Has License' : 'No License',
      });
    }
    if (f.bank_setup && f.bank_setup !== 'all') {
      pills.push({ key: 'bank_setup', label: 'Bank Setup', value: this.bankSetupLabel(f.bank_setup) });
    }

    return pills;
  }

  removeFilterPill(key: string) {
    if (key === 'search') {
      this.searchText = '';
    } else if (key === 'bank_setup') {
      this.filterForm.patchValue({ bank_setup: 'all' });
    } else {
      this.filterForm.patchValue({ [key]: '' });
    }
    this.refresh();
  }

  // ── Query string builder ─────────────────────────────────────────────

  private buildQueryString(): string {
    const params = new URLSearchParams();
    const f = this.filterForm.value;
    const columns = [
      { data: 'first_name', search: f.first_name },
      { data: 'last_name', search: f.last_name },
      { data: 'email', search: f.email },
      { data: 'phone', search: f.phone },
      { data: 'city', search: f.city },
      { data: 'state', search: f.state },
      { data: 'market_id', search: f.market_id },
      { data: 'region', search: f.region },
      { data: 'status', search: f.status },
      { data: 'liquor_license', search: f.liquor_license },
      { data: 'bank_setup', search: f.bank_setup !== 'all' ? f.bank_setup : '' },
      { data: 'action', search: '' },
    ];

    params.set('search[value]', this.searchText);

    columns.forEach((column, index) => {
      params.set(`columns[${index}][data]`, column.data);
      params.set(`columns[${index}][search][value]`, cleanParam(column.search));
    });

    setIfPresent(params, 'first_name', f.first_name);
    setIfPresent(params, 'last_name', f.last_name);
    setIfPresent(params, 'email', f.email);
    setIfPresent(params, 'phone', f.phone);
    setIfPresent(params, 'city', f.city);
    setIfPresent(params, 'state', f.state);
    setIfPresent(params, 'market_id', f.market_id);
    setIfPresent(params, 'region', f.region);
    setIfPresent(params, 'start_date', f.start_date);
    setIfPresent(params, 'end_date', f.end_date);
    setIfPresent(params, 'status', f.status);
    setIfPresent(params, 'liquor_license', f.liquor_license);
    if (f.bank_setup && f.bank_setup !== 'all') params.set('bank_setup', cleanParam(f.bank_setup));

    return params.toString();
  }

  // ── Actions ───────────────────────────────────────────────────────────

  edit(amb: any) {
    this.router.navigate(['/admin-add-ambassador'], { state: { id: amb.id } });
  }

  async confirmDelete(amb: any) {
    const alert = await this.alertCtrl.create({
      header:  'Delete Ambassador',
      message: `Are you sure you want to delete <strong>${amb.name}</strong>?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Delete', role: 'destructive', cssClass: 'alert-delete-btn',
          handler: () => this.deleteAmbassador(amb),
        },
      ],
    });
    await alert.present();
  }

  private deleteAmbassador(amb: any) {
    this.common.showLoader();
    this.apiService.sendAuthData('admin/delete_ambassador', { id: amb.id }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.ambassadors = this.ambassadors.filter(a => a.id !== amb.id);
        this.cdrf.detectChanges();
        this.common.presentToast?.(res?.message ?? 'Ambassador deleted.');
      },
      error: (err: any) => { this.common.dismissLoading(); console.error(err); },
    });
  }

  // ── Helpers ───────────────────────────────────────────────────────────

  avatarSrc(amb: any): string {
    return amb?.profile_image || this.defaultAvatar;
  }

  /** Returns badge config for the ambassador's status value. */
  statusBadge(amb: any): { label: string; cls: string } {
    return amb?.status === '1' || amb?.status === 1
      ? { label: 'Active',   cls: 'badge-active'   }
      : { label: 'Inactive', cls: 'badge-inactive' };
  }

  trackById(_: number, item: any) { return item?.id; }

  private hasActiveFilters(): boolean {
    return this.getActiveFilterPills().length > 0;
  }

  private addTextPill(pills: FilterPill[], key: string, label: string, value: any) {
    const cleaned = cleanParam(value);
    if (cleaned) pills.push({ key, label, value: cleaned });
  }

  private marketLabel(marketId: any): string {
    const cleaned = cleanParam(marketId);
    if (!cleaned) return '';
    return this.markets.find((market: any) => String(market?.id) === cleaned)?.name ?? cleaned;
  }

  private bankSetupLabel(value: string): string {
    const labels: Record<string, string> = {
      verified: 'Verified',
      pending_verification: 'Pending',
      not_started: 'Not Started',
    };
    return labels[value] ?? value;
  }
}

function cleanParam(v: any): string {
  return String(v ?? '').trim();
}

function setIfPresent(params: URLSearchParams, key: string, value: any) {
  const cleaned = cleanParam(value);
  if (cleaned !== '') params.set(key, cleaned);
}
