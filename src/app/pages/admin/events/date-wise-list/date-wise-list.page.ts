import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  standalone: false,
  selector: 'app-date-wise-list',
  templateUrl: './date-wise-list.page.html',
  styleUrls: ['./date-wise-list.page.scss'],
})
export class DateWiseListPage implements OnInit {
  params: any = { title: 'Events', is_back: true };
  user: any;
  param: any;

  // ── List state (infinite scroll) ─────────────────────────────────────────
  events: any[] = [];
  isLoading: boolean = false;
  hasMore: boolean = true;
  readonly pageSize: number = 10;

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    private cdr: ChangeDetectorRef,
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.param = this.router.getCurrentNavigation()?.extras?.state;
      this.params.title = moment(this.param?.date).format('MM/DD/YYYY') + ' Events';
    }
  }

  ngOnInit() { }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data != null) {
        this.user = data;
        this.loadEvents(true);
      } else {
        this.router.navigate(['/login']);
      }
    });
  }

  /**
   * Mobile API only exposes admin/location/brand/client/ambassador prefixes —
   * there is no "manager" route. Managers (role_id 6) use the admin endpoints.
   */
  private eventListAuthPrefix(): string {
    const rid = Number(this.user?.role_id);
    if (rid === 1 || rid === 6) {
      return 'admin';
    }
    const name = (this.user?.role?.name || '').toLowerCase();
    return name === 'administrator' ? 'admin' : name;
  }

  // ── Data fetching ─────────────────────────────────────────────────────────

  loadEvents(refresh: boolean = false): void {
    if (!this.user?.token) {
      return;
    }
    if (this.isLoading && !refresh) return;

    if (refresh) {
      this.events = [];
      this.hasMore = true;
    }
    if (!this.hasMore) return;

    this.isLoading = true;
    const start = this.events.length;
    const date = moment(this.param?.date).format('YYYY-MM-DD');

    const params: string[] = [
      `date=${date}`,
      'draw=1',
      `start=${start}`,
      `length=${this.pageSize}`,
      'search%5Bvalue%5D=',
      'search%5Bregex%5D=true',
    ];

    const url = `${this.eventListAuthPrefix()}/get_event_list?${params.join('&')}`;

    this.apiService.getAuthData(url, this.user?.token).subscribe({
      next: (res: any) => {
        const { rows } = resolveDataTableResponse(res);
        const mapped = rows.map((row: any) => ({
          ...row,
          _eventId: this.extractEventId(row),
          _statusLabel: this.stripHtml(row?.status) || '—',
          _statusClass: this.statusClass(this.stripHtml(row?.status)),
          _canEdit: /name=["']?create["']?/.test(String(row?.action ?? '')) && !this.isCompletedStatus(row?.status),
        }));
        this.events = [...this.events, ...mapped];
        this.hasMore = rows.length >= this.pageSize;
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        this.isLoading = false;
        console.error('Error fetching data', err);
        this.cdr.detectChanges();
      },
    });
  }

  loadMore(ionInfiniteEvent: any): void {
    this.loadEvents(false);
    setTimeout(() => ionInfiniteEvent.target.complete(), 500);
  }

  // ── Row helpers ───────────────────────────────────────────────────────────

  private extractEventId(row: any): string {
    if (row?.id != null) return String(row.id);
    if (row?.event_id != null) return String(row.event_id);
    const html = String(row?.action ?? '');
    const match = html.match(/id=["']?(\d+)/);
    return match ? match[1] : '';
  }

  private stripHtml(value: any): string {
    return String(value ?? '').replace(/<[^>]*>/g, '').trim();
  }

  private statusClass(label: string): string {
    const s = (label || '').toLowerCase();
    if (s.includes('complete')) return 'is-success';
    if (s.includes('assign')) return 'is-primary';
    if (s.includes('cancel')) return 'is-danger';
    return 'is-warning';
  }

  private isCompletedStatus(status: any): boolean {
    const value = this.stripHtml(status).toLowerCase();
    return value === '1' || value.includes('complete');
  }

  // ── Actions (unchanged behavior) ──────────────────────────────────────────

  view(id: any): void {
    if (!id) return;
    this.nav.navigateForward('event-details', { state: { id } });
  }

  edit(id: any): void {
    if (!id) return;
    const event = this.events.find((item: any) => String(item?._eventId) === String(id));
    if (event && this.isCompletedStatus(event?._statusLabel)) return;
    this.nav.navigateForward('add-events', { state: { id } });
  }
}
