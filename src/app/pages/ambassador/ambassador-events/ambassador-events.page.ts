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

  // ── Sort state ────────────────────────────────────────────────────────
  sortOrder: 'asc' | 'desc' = 'desc';

  // ── Card state ─────────────────────────────────────────────────────────
  cardEvents: any[] = [];
  loadingCards: boolean = false;
  expandedIds: Set<number> = new Set();

  // ── Infinite scroll (page size mirrors the original DataTable) ─────────
  readonly pageSize: number = 10;
  hasMore: boolean = true;
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
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.loadCardEvents(true);
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  ionViewWillLeave() { }

  // ── Data fetching (infinite scroll) ────────────────────────────────────

  /**
   * Fetch events using the DataTables-compatible endpoint. Rows accumulate for
   * infinite scroll; `refresh` resets the list (first load, tab/filter/sort
   * change). pageSize=10 matches the original DataTable default.
   */
  loadCardEvents(refresh: boolean = false): void {
    if (this.loadingCards && !refresh) return;

    if (refresh) {
      this.cardEvents = [];
      this.expandedIds = new Set();
      this.hasMore = true;
    }
    if (!this.hasMore) return;

    this.loadingCards = true;
    const start = this.cardEvents.length;

    const params: string[] = [
      'draw=1',
      `start=${start}`,
      `length=${this.pageSize}`,
      `search%5Bvalue%5D=${this.type}`,
      'search%5Bregex%5D=true',
      `market_id=${this.market_id}`,
      `sort_order=${this.sortOrder}`,
    ];
    if (this.start_date) params.push(`start_date=${this.start_date}`);
    if (this.end_date) params.push(`end_date=${this.end_date}`);


    this.apiService
      .getAuthData(`ambassador/get_events_data?${params.join('&')}`, this.user?.token)
      .subscribe({
        next: (res: any) => {
          const { payload, rows } = resolveDataTableResponse(res);
          // Prefer the server-provided assigned set; never broaden market access
          // from the global catalogue when an assigned set is present.
          this.markets = this.assignedMarketsFrom(payload);
          if (this.market_id && !this.markets.some((market: any) => String(market.id) === String(this.market_id))) {
            this.market_id = '';
          }
          this.cardEvents = [...this.cardEvents, ...rows];
          this.hasMore = rows.length >= this.pageSize;
          this.totalRecords = payload.recordsTotal ?? payload.recordsFiltered ?? this.cardEvents.length;
          this.loadingCards = false;
        },
        error: (err: any) => {
          this.loadingCards = false;
          console.error('[AmbassadorEvents] fetch error', err);
        },
      });
  }

  loadMore(ionInfiniteEvent: any): void {
    this.loadCardEvents(false);
    setTimeout(() => ionInfiniteEvent.target.complete(), 500);
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

  toggleSort(): void {
    // Ignore taps while a fetch is already in flight to avoid firing two
    // overlapping requests (the header stays visible during loading now).
    if (this.loadingCards) return;
    this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
    this.loadCardEvents(true);
  }

  getStatusWise(event: any) {
    const segmentValue = event?.detail?.value;
    const segmentTypes = ['all', 'pending', 'assigned', 'completed'];
    if (segmentValue && segmentTypes.includes(segmentValue)) {
      this.type = segmentValue;
      this.market_id = '';
      this.start_date = '';
      this.end_date = '';
      // Temporary: all tabs default to desc (Available/Pending/Assigned changed from asc)
    this.sortOrder = 'desc';
    }
    this.loadCardEvents(true);
  }

  market_change() {
    this.loadCardEvents(true);
  }

  private assignedMarketsFrom(payload: any): any[] {
    const assigned = payload?.assigned_markets ?? payload?.assignedMarkets ?? payload?.user_markets;
    return Array.isArray(assigned) ? assigned : (Array.isArray(payload?.markets) ? payload.markets : []);
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
    if (data?.checkedIn) this.loadCardEvents(true);
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
                  this.loadCardEvents(true);
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
