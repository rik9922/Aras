import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  standalone: false,
  selector: 'app-create-recap',
  templateUrl: './create-recap.page.html',
  styleUrls: ['./create-recap.page.scss'],
})
export class CreateRecapPage implements OnInit {
  params: any = { title: 'Create Recap', is_back: false };
  user: any;

  // ── List state ──────────────────────────────────────────────────────────
  recaps: any[] = [];
  events: any[] = [];          // populates the filter dropdown
  isLoading: boolean = false;
  hasMore: boolean = true;
  type: any = '';              // selected event filter (event id) or '' for all

  // ── Sort state (mirrors the Events tab) ──────────────────────────────────
  sortOrder: 'asc' | 'desc' = 'desc';

  readonly pageSize: number = 10;

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
    private cdr: ChangeDetectorRef,
  ) { }

  ngOnInit() { }

  ionViewWillEnter() {
    this.params.is_back = !this.router.url.includes('/tabs/ambassador-create-recap');
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.loadRecaps(true);
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  // ── Data fetching (infinite scroll) ───────────────────────────────────────

  /**
   * Load unsubmitted recaps from the DataTables-compatible server-side
   * endpoint. Rows accumulate for infinite scroll; `refresh` resets the list
   * (used on first load, filter change and sort toggle).
   */
  loadRecaps(refresh: boolean = false): void {
    if (!this.user?.token) {
      return;
    }
    if (this.isLoading && !refresh) return;

    if (refresh) {
      this.recaps = [];
      this.hasMore = true;
    }
    if (!this.hasMore) return;

    this.isLoading = true;
    const start = this.recaps.length;

    const params: string[] = [
      'draw=1',
      `start=${start}`,
      `length=${this.pageSize}`,
      `search%5Bvalue%5D=${this.type ?? ''}`,
      'search%5Bregex%5D=true',
      `sort_order=${this.sortOrder}`,
    ];

    this.apiService
      .getAuthData(`ambassador/get_created_recap?${params.join('&')}`, this.user?.token)
      .subscribe({
        next: (res: any) => {
          const { payload, rows } = resolveDataTableResponse(res);

          // Populate the filter dropdown from the unfiltered response so the
          // full event list stays available regardless of the selection.
          if (Array.isArray(payload?.events) && payload.events.length && !this.type) {
            this.events = payload.events;
          }

          const mapped = rows.map((row: any) => ({
            ...row,
            _eventId: this.extractEventId(row),
            _statusLabel: this.extractStatusLabel(row),
          }));

          this.recaps = [...this.recaps, ...mapped];
          this.hasMore = rows.length >= this.pageSize;
          this.isLoading = false;
          this.cdr.detectChanges();
        },
        error: (err: any) => {
          this.isLoading = false;
          console.error('Error fetching recap data', err);
          this.cdr.detectChanges();
        },
      });
  }

  loadMore(ionInfiniteEvent: any): void {
    this.loadRecaps(false);
    setTimeout(() => ionInfiniteEvent.target.complete(), 500);
  }

  getStatusWise(_event?: any): void {
    this.loadRecaps(true);
  }

  /** Flip the date/time sort order and reload from the top. */
  toggleSort(): void {
    if (this.isLoading) return;
    this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
    this.loadRecaps(true);
  }

  // ── Row helpers ─────────────────────────────────────────────────────────

  /**
   * The event id used to open the recap form. Prefer a direct field, then
   * fall back to parsing the server-rendered action markup (which carried a
   * `data-event-id` / `id` attribute in the old table).
   */
  private extractEventId(row: any): string {
    if (row?.id != null) return String(row.id);
    if (row?.event_id != null) return String(row.event_id);
    const html = String(row?.action ?? '');
    const match = html.match(/data-event-id=["']?(\d+)/) || html.match(/id=["']?(\d+)/);
    return match ? match[1] : '';
  }

  /** Human-readable status pulled from the action markup, defaults to Incomplete. */
  private extractStatusLabel(row: any): string {
    const text = String(row?.action ?? '').replace(/<[^>]*>/g, '').trim();
    return text || 'Incomplete';
  }

  async completeIt(event_id: any): Promise<void> {
    if (!event_id) return;
    this.router.navigate(['/admin-add-recap'], { state: { id: event_id, type: 'complete' } });
  }
}
