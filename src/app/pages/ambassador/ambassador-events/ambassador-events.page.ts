import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, ModalController, NavController } from '@ionic/angular';
import { CheckInModalComponent } from 'src/app/components/check-in-modal/check-in-modal.component';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  selector: 'app-ambassador-events',
  templateUrl: './ambassador-events.page.html',
  styleUrls: ['./ambassador-events.page.scss'],
  standalone: false
})
export class AmbassadorEventsPage implements OnInit {
  params: any = { title: 'Events', is_back: false };
  user: any;
  type: any = 'all';
  markets: any = [];
  market_id: any = '';
  start_date: any = '';
  end_date: any = '';

  // ── Card state ─────────────────────────────────────────────────────────
  cardEvents: any[] = [];
  loadingCards: boolean = false;
  expandedIds: Set<number> = new Set();

  // ── Pagination (mirrors original DataTable page size of 10) ───────────
  currentPage: number = 0;
  readonly pageSize: number = 10;
  totalRecords: number = 0;

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
    private modalCtrl: ModalController,
  ) { }

  ngOnInit() { }

  ionViewWillEnter() {
    console.log('[AmbassadorEvents] ionViewWillEnter — v3 (all-cards + pagination)');
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.loadCardEvents(0);
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  ionViewWillLeave() { }

  // ── Pagination helpers ─────────────────────────────────────────────────

  get totalPages(): number {
    return this.totalRecords === 0 ? 1 : Math.ceil(this.totalRecords / this.pageSize);
  }

  get startRecord(): number {
    return this.totalRecords === 0 ? 0 : this.currentPage * this.pageSize + 1;
  }

  get endRecord(): number {
    return Math.min((this.currentPage + 1) * this.pageSize, this.totalRecords);
  }

  get hasPrev(): boolean { return this.currentPage > 0; }
  get hasNext(): boolean { return this.currentPage < this.totalPages - 1; }

  prevPage(): void { if (this.hasPrev) this.loadCardEvents(this.currentPage - 1); }
  nextPage(): void { if (this.hasNext) this.loadCardEvents(this.currentPage + 1); }

  // ── Data fetching ──────────────────────────────────────────────────────

  /**
   * Fetch one page of events using the same DataTables-compatible endpoint.
   * pageSize=10 matches the original DataTable default.
   */
  loadCardEvents(page: number = 0): void {
    console.log(`[AmbassadorEvents] loadCardEvents — type="${this.type}", page=${page}`);
    this.cardEvents = [];
    this.expandedIds = new Set();
    this.loadingCards = true;
    this.currentPage = page;

    const params: string[] = [
      'draw=1',
      `start=${page * this.pageSize}`,
      `length=${this.pageSize}`,
      `search%5Bvalue%5D=${this.type}`,
      'search%5Bregex%5D=true',
      `market_id=${this.market_id}`,
    ];
    if (this.start_date) params.push(`start_date=${this.start_date}`);
    if (this.end_date) params.push(`end_date=${this.end_date}`);

    this.apiService
      .getAuthData(`ambassador/get_events_data?${params.join('&')}`, this.user?.token)
      .subscribe({
        next: (res: any) => {
          const { payload, rows } = resolveDataTableResponse(res);
          this.markets = payload.markets || [];
          this.cardEvents = rows;
          this.totalRecords = payload.recordsTotal ?? payload.recordsFiltered ?? 0;
          this.loadingCards = false;
        },
        error: (err: any) => {
          this.loadingCards = false;
          console.error('[AmbassadorEvents] fetch error', err);
        },
      });
  }

  // ── Card helpers ───────────────────────────────────────────────────────

  toggleCard(id: number): void {
    // Create a new Set so Angular's change detection always fires.
    const next = new Set(this.expandedIds);
    next.has(id) ? next.delete(id) : next.add(id);
    this.expandedIds = next;
  }

  isCardExpanded(id: number): boolean {
    return this.expandedIds.has(id);
  }

  viewEventDetails(id: any): void {
    this.nav.navigateForward('/event-details', { state: { id } });
  }

  routeTo(url: any, data: any) {
    this.router.navigate([url], { state: data });
  }

  // ── Tab / filter changes ───────────────────────────────────────────────

  getStatusWise(event: any) {
    const segmentValue = event?.detail?.value;
    const segmentTypes = ['all', 'pending', 'assigned', 'completed'];
    if (segmentValue && segmentTypes.includes(segmentValue)) {
      this.type = segmentValue;
      this.market_id = '';
      this.start_date = '';
      this.end_date = '';
    }
    this.loadCardEvents(0);
  }

  market_change() {
    this.loadCardEvents(0);
  }

  // ── Actions (unchanged business logic) ────────────────────────────────

  async openCheckInModal(eventId: any) {
    const id = Number(eventId);
    if (!id || !this.user) return;

    const modal = await this.modalCtrl.create({
      component: CheckInModalComponent,
      componentProps: { eventId: id, user: this.user },
    });

    await modal.present();
    const { data, role } = await modal.onDidDismiss();
    if (data?.checkedIn) this.loadCardEvents(this.currentPage);
    if (data?.viewDetails || role === 'navigate') {
      this.nav.navigateForward('/event-details', { state: { id } });
    }
  }

  /** Unchanged: shows the existing "Are you sure?" confirmation then calls apply_event. */
  async completeIt(event_id: any) {
    const alert = await this.alertCtrl.create({
      header: 'Are you sure?',
      message: 'You want to Apply for this Event.',
      backdropDismiss: false,
      buttons: [
        { text: 'No', handler: () => { } },
        {
          text: 'Yes',
          handler: () => {
            this.common.showLoader();
            this.apiService
              .sendAuthData('ambassador/apply_event', { event_id: event_id }, this.user?.token)
              .subscribe({
                next: () => {
                  this.common.dismissLoading();
                  this.loadCardEvents(0);
                },
                error: (err) => {
                  this.common.dismissLoading();
                  console.log(err);
                },
              });
          },
        },
      ],
    });
    await alert.present();
  }
}
