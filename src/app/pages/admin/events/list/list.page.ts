import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  standalone: false,
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
})
export class ListPage implements OnInit {
  params: any = { title: 'Events List' };
  user: any;
  data: any = {};
  page: number = 0;
  events: any = [];
  is_ready: boolean = false;
  extraData: any;
  status: any = 0;
  extraParams: any = '';
  isSearch: boolean = false;
  filterEvents: any[] = [];
  searchingForm: FormGroup = new FormGroup({});
  maxdate: any = moment('2050-01-01').format('YYYY-MM-DD');

  isLoading: boolean = false;
  hasMore: boolean = true;
  pageSize: number = 20;

  readonly paymentTypeOptions = [
    { value: 'No purchase', label: 'No purchase' },
    { value: 'Product on site', label: 'Product on site' },
    { value: 'Comdata', label: 'Comdata' },
    { value: 'Check', label: 'Check' },
    { value: 'Reimbursement', label: 'Reimbursement' },
  ];

  readonly regionOptions = [
    { value: 'Southeast', label: 'Southeast' },
    { value: 'Midwest', label: 'Midwest' },
  ];

  readonly bestSyncOptions = [
    { value: '', label: 'All' },
    { value: 'synced', label: 'Synced' },
    { value: 'not_synced', label: 'Not Synced' },
    { value: 'failed', label: 'Failed' },
    { value: 'pending', label: 'Pending' },
  ];

  readonly managerLabel = (manager: any) =>
    manager?.name || `${manager?.first_name || ''} ${manager?.last_name || ''}`.trim();

  readonly eventLabel = (event: any) =>
    event?.label || `${event?.event_name || ''} (${event?.id})`;

  readonly ambassadorLabel = (ambassador: any) => {
    if (ambassador?.label) {
      return ambassador.label;
    }
    const name = ambassador?.name || `${ambassador?.first_name || ''} ${ambassador?.last_name || ''}`.trim();
    return name || ambassador?.email || '';
  };

  readonly ambassadorEmailLabel = (ambassador: any) =>
    ambassador?.email || ambassador?.label || ambassador?.name || '';

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    public event: EventsService,
    public fb: FormBuilder,
    public cdrf: ChangeDetectorRef
  ) {
    this.event.subscribe('reload_events', () => {
      this.reloadTable();
    });
  }

  ngOnInit() {
    this.searchingForm = this.fb.group({
      department_id: new FormControl(null),
      market_id: new FormControl(null),
      manager_id: new FormControl(null),
      event_type_id: new FormControl(null),
      location: new FormControl(null),
      distributor_id: new FormControl(null),
      event_id: new FormControl(null),
      location_state: new FormControl(null),
      varietals: new FormControl(''),
      bill_id: new FormControl(null),
      billable_name: new FormControl(''),
      payment_type: new FormControl(null),
      best_sync: new FormControl(null),
      startDate: new FormControl(''),
      endDate: new FormControl(''),
      ambassador_name: new FormControl(null),
      brand_id: new FormControl(null),
      ambassador_email: new FormControl(null),
      region: new FormControl(null),
      include_nearby_events: [false],
      overview_needed: [false],
      not_fully_staffed: [false],
      kit_required: [false],
      exclude_cancellations: [false],
      backup_brand_used: [false],
      backup_brand_name: new FormControl(''),
      event_status: new FormControl(null),
    });
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data) => {
      if (data != null) {
        this.user = data;
        this.page = 0;
        this.loadFilterOptions();
        this.reloadTable();
        this.events = [];
      } else {
        this.router.navigate(['/login']);
      }
    });
  }

  ionViewWillLeave() {
    // cleanup if needed
  }

  loadFilterOptions() {
    if (!this.user?.token) {
      return;
    }
    this.apiService
      .getAuthData(`admin/get_events_data?status=${this.status}`, this.user.token)
      .subscribe(
        (res: any) => {
          if (res?.status === 'success' || res?.data) {
            this.applyFilterMetadata(res?.data ?? res);
            this.cdrf.detectChanges();
          }
        },
        () => {
          // Table can still load filter metadata from get_event_list response.
        }
      );
  }

  private applyFilterMetadata(source: any) {
    if (!source || typeof source !== 'object') {
      return;
    }

    this.data = {
      ...(this.data || {}),
      departments: source.departments ?? this.data?.departments ?? [],
      markets: source.markets ?? this.data?.markets ?? [],
      managers: source.managers ?? this.data?.managers ?? [],
      distributors: source.distributors ?? this.data?.distributors ?? [],
      states: source.states ?? this.data?.states ?? [],
      brands: source.brands ?? this.data?.brands ?? [],
      clients: source.clients ?? this.data?.clients ?? [],
      eventTypes: source.eventTypes ?? this.data?.eventTypes ?? [],
      venueTypes: source.venueTypes ?? this.data?.venueTypes ?? [],
      locations: source.locations ?? this.data?.locations ?? [],
    };

    const ambassadors =
      source.filter_ambassadors ?? source.ambassadors ?? null;
    if (Array.isArray(ambassadors) && ambassadors.length) {
      this.data.ambassadors = ambassadors;
    }

    const eventOptions =
      source.filter_events ??
      source.eventsData ??
      (Array.isArray(source.events) && source.events[0]?.id != null ? source.events : null);

    if (Array.isArray(eventOptions) && eventOptions.length) {
      this.filterEvents = eventOptions;
    }
  }

  private buildExtraParams(): string {
    const v = this.searchingForm.getRawValue();
    const parts: string[] = [];

    const addParam = (key: string, value: any) => {
      if (value !== null && value !== undefined && value !== '') {
        parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);
      }
    };

    [
      'department_id',
      'market_id',
      'manager_id',
      'event_type_id',
      'location',
      'distributor_id',
      'event_id',
      'location_state',
      'varietals',
      'bill_id',
      'billable_name',
      'payment_type',
      'best_sync',
      'ambassador_name',
      'brand_id',
      'ambassador_email',
      'region',
      'backup_brand_name',
    ].forEach((key) => addParam(key, v[key]));

    if (v.startDate && v.endDate) {
      addParam('startDate', v.startDate);
      addParam('endDate', v.endDate);
    }

    if (v.include_nearby_events) {
      addParam('include_nearby_events', 1);
    }
    if (v.overview_needed) {
      addParam('overview_needed', 1);
    }
    if (v.not_fully_staffed) {
      addParam('not_fully_staffed', 1);
    }
    if (v.kit_required) {
      addParam('kit_required', 1);
    }
    if (v.exclude_cancellations) {
      addParam('exclude_cancellations', 1);
    }
    if (v.backup_brand_used) {
      addParam('backup_brand_used', 1);
    }

    if (v.event_status !== null && v.event_status !== undefined && v.event_status !== '') {
      addParam('event_status', v.event_status);
    }

    return parts.length ? '&' + parts.join('&') : '';
  }

  private syncStatusFromForm() {
    const eventStatus = this.searchingForm.get('event_status')?.value;
    if (eventStatus === '1' || eventStatus === 1) {
      this.status = 1;
    } else if (eventStatus === '0' || eventStatus === 0 || eventStatus === '2' || eventStatus === 2) {
      this.status = 0;
    }
  }

  applyFilters(closeModal: boolean = true) {
    this.syncStatusFromForm();
    this.extraParams = this.buildExtraParams();
    this.reloadTable();
    if (closeModal) {
      this.isSearch = false;
    }
    this.cdrf.detectChanges();
  }

  reloadTable() {
    this.loadEvents(true);
  }

  loadEvents(refresh: boolean = false) {
    if (this.isLoading && !refresh) return;
    if (refresh) {
      this.events = [];
      this.hasMore = true;
      this.page = 0;
    }
    if (!this.hasMore) return;

    this.isLoading = true;
    const start = this.events.length;
    const url = `admin/get_event_list?status=${this.status}&start=${start}&length=${this.pageSize}&draw=1${this.extraParams}`;

    this.apiService.getAuthData(url, this.user.token).subscribe({
      next: (res: any) => {
        const { payload, rows } = resolveDataTableResponse(res);
        this.applyFilterMetadata(payload);
        this.extraData = { ...this.extraData, ...payload };
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

  editEvent(event: any) {
    this.nav.navigateForward('add-events', { state: { id: event?.id } });
  }

  viewEvent(event: any) {
    this.nav.navigateForward('event-details', { state: { id: event?.id } });
  }

  loadMore(ionInfiniteEvent: any) {
    this.loadEvents(false);
    setTimeout(() => ionInfiniteEvent.target.complete(), 500);
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }

  view_data() {
    this.status = this.status == 0 ? 1 : 0;
    this.loadFilterOptions();
    this.reloadTable();
  }

  searchClicked() {
    const opening = !this.isSearch;
    this.isSearch = opening;
    if (
      opening &&
      (!this.data?.ambassadors?.length || !this.filterEvents?.length || !this.data?.eventTypes?.length)
    ) {
      this.loadFilterOptions();
    }
  }

  onFilterChange() {
    this.applyFilters(false);
  }

  onChangeCheckbox() {
    this.applyFilters(false);
  }

  onChangeRadioButton(event: any) {
    const value = event?.detail?.value ?? event?.target?.value;
    this.searchingForm.patchValue({ event_status: value });
    this.applyFilters(false);
  }

  resetForm() {
    this.extraParams = '';
    this.status = 0;
    this.searchingForm.reset({
      include_nearby_events: false,
      overview_needed: false,
      not_fully_staffed: false,
      kit_required: false,
      exclude_cancellations: false,
      backup_brand_used: false,
    });
    this.isSearch = false;
    this.reloadTable();
  }

  onTextBlur() {
    this.applyFilters(false);
  }

  changeDate(event: any, type: 'startDate' | 'endDate') {
    const control = this.searchingForm.controls[type];
    control.setValue(moment(event?.detail?.value).format('YYYY-MM-DD'));
    const { startDate, endDate } = this.searchingForm.value;
    if (startDate && endDate) {
      setTimeout(() => this.applyFilters(false), 300);
    }
  }
}
