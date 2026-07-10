import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  selector: 'app-client-events',
  templateUrl: './client-events.page.html',
  styleUrls: ['./client-events.page.scss'],
  standalone: false
})
export class ClientEventsPage implements OnInit {
  params: any = { title: 'Events', is_back: false };
  user: any;
  data: any;
  eventForm: FormGroup;
  isSearch: boolean = false;
  filterEvents: any = [];
  extraParams: any = '';

  events: any[] = [];
  isLoading: boolean = false;
  hasMore: boolean = true;
  pageSize: number = 20;

  constructor(
    public common: CommonService,
    public apiService: ApiService,
    public storage: StorageService,
    public nav: NavController,
    public router: Router,
    private fb: FormBuilder,
    private cdrf: ChangeDetectorRef
  ) {
    this.eventForm = this.fb.group({
      department_id: [''],
      market_id: [''],
      event_id: [''],
      distributor_id: [''],
      venue_id: [''],
      varietals: [''],
      brand_id: [''],
      ambassador_name: [''],
      ambassador_email: [''],
    });
  }

  ngOnInit() {}

  ionViewWillEnter() {
    this.data = {};
    this.isSearch = false;
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.loadEvents(true);
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  ionViewWillLeave() {
    // cleanup if needed
  }

  loadEvents(refresh: boolean = false) {
    if (this.isLoading && !refresh) return;
    if (refresh) {
      this.events = [];
      this.hasMore = true;
    }
    if (!this.hasMore) return;

    this.isLoading = true;
    const start = this.events.length;
    const url = `client/get_events_calendar_data?start=${start}&length=${this.pageSize}&draw=1${this.extraParams}`;

    this.apiService.getAuthData(url, this.user.token).subscribe({
      next: (res: any) => {
        const { payload, rows } = resolveDataTableResponse(res);
        this.data = { ...(this.data || {}), ...payload };
        if (payload?.events?.length) this.filterEvents = payload.events;
        this.events = [...this.events, ...rows];
        this.hasMore = rows.length >= this.pageSize;
        this.isLoading = false;
        this.cdrf.detectChanges();
      },
      error: () => {
        this.isLoading = false;
        this.cdrf.detectChanges();
      }
    });
  }

  loadMore(ionInfiniteEvent: any) {
    this.loadEvents(false);
    setTimeout(() => ionInfiniteEvent.target.complete(), 500);
  }

  formatTime(time: string): string {
    if (!time) return '';
    try {
      const [h, m] = time.split(':').map(Number);
      const ampm = h >= 12 ? 'PM' : 'AM';
      const h12 = h % 12 || 12;
      return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
    } catch { return time; }
  }

  getStatusLabel(status: any): string {
    const s = String(status ?? '');
    if (s === '1') return 'Completed';
    if (s === '2') return 'Assigned';
    if (s === '3' || s.toLowerCase().includes('cancel')) return 'Cancelled';
    return 'Pending';
  }

  getStatusClass(status: any): string {
    const s = String(status ?? '');
    if (s === '1') return 'badge-success';
    if (s === '2') return 'badge-primary';
    if (s === '3') return 'badge-danger';
    return 'badge-warning';
  }

  viewEvent(event: any) {
    this.router.navigate(['/client-event-details'], { state: { id: event?.id } });
  }

  onSubmit() {}

  searchClicked() {
    this.isSearch = !this.isSearch;
  }

  onChange(event: any, key: any) {
    const value = event?.detail?.value ?? event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.isSearch = false;
    this.loadEvents(true);
  }

  onInput(event: any, key: any) {
    const value = event?.detail?.value ?? event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.isSearch = false;
    this.loadEvents(true);
  }

  resetForm() {
    this.extraParams = '';
    this.eventForm.reset();
    this.isSearch = false;
    this.loadEvents(true);
  }

  routeTo(url: any, data: any) {
    this.router.navigate([url], { state: data });
  }
}
