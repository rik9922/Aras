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
  isLoadingMore: boolean = false;   // separate flag for "loading more" spinner under last card
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

  loadEvents(refresh: boolean = false, infiniteScrollEvent?: any) {
    // Block concurrent calls — unless it's a hard refresh
    if (this.isLoading && !refresh) {
      if (infiniteScrollEvent) infiniteScrollEvent.target.complete();
      return;
    }
    if (refresh) {
      this.events = [];
      this.hasMore = true;
      this.page = 0;
      this.isLoadingMore = false;
    }
    if (!this.hasMore) {
      if (infiniteScrollEvent) infiniteScrollEvent.target.complete();
      return;
    }

    this.isLoading = true;
    if (infiniteScrollEvent) this.isLoadingMore = true;
    const start = this.events.length;
    const formValue = this.searchingForm.getRawValue();
    const statusParam = formValue.event_id && !formValue.event_status ? '' : `status=${this.status}&`;
    const url = `admin/get_event_list?${statusParam}start=${start}&length=${this.pageSize}&draw=1&include_filters=0${this.extraParams}`;

    this.apiService.getAuthData(url, this.user.token).subscribe({
      next: (res: any) => {
        const { payload, rows } = resolveDataTableResponse(res);
        this.applyFilterMetadata(payload);
        this.extraData = { ...this.extraData, ...payload };
        this.events = [...this.events, ...rows];
        this.hasMore = rows.length >= this.pageSize;
        this.isLoading = false;
        this.isLoadingMore = false;
        // Complete the infinite scroll INSIDE the callback — not in a timeout
        if (infiniteScrollEvent) infiniteScrollEvent.target.complete();
        this.cdrf.detectChanges();
      },
      error: () => {
        this.isLoading = false;
        this.isLoadingMore = false;
        if (infiniteScrollEvent) infiniteScrollEvent.target.complete();
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

  async confirmDeleteEvent(event: any) {
    const confirm = await this.common.presentAlertWithPromise(
      'Delete Event',
      `Are you sure you want to delete "${event?.event_name}"? This action cannot be undone.`
    );
    if (!confirm) return;
    this.deleteEvent(event);
  }

  deleteEvent(event: any) {
    this.common.showLoader();
    const formData = new FormData();
    formData.append('id', event?.id);
    this.apiService.sendAuthData('admin/delete_event', formData, this.user?.token).subscribe({
      next: async (res: any) => {
        this.common.dismissLoading();
        // Remove the event from the local list immediately
        this.events = this.events.filter((e: any) => e.id !== event?.id);
        if (this.extraData?.recordsFiltered) {
          this.extraData = { ...this.extraData, recordsFiltered: this.extraData.recordsFiltered - 1 };
        }
        this.cdrf.detectChanges();
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Failed to delete event. Please try again.');
      }
    });
  }

  loadMore(ionInfiniteEvent: any) {
    // Pass the event into loadEvents so complete() fires inside the API callback
    this.loadEvents(false, ionInfiniteEvent);
  }

  handleRefresh(event: any) {
    this.reloadTable();
    // Wait for loadEvents to finish then complete the refresher
    const done = setInterval(() => {
      if (!this.isLoading) {
        clearInterval(done);
        event.target.complete();
      }
    }, 100);
    // Safety timeout — always complete after 8s
    setTimeout(() => {
      clearInterval(done);
      event.target.complete();
    }, 8000);
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }

  view_data() {
    this.status = this.status == 0 ? 1 : 0;
    this.reloadTable();
  }

  searchClicked() {
    this.isSearch = !this.isSearch;
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
    this.data = {};
    this.filterEvents = [];
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

  // ── Active Filter Pills ──────────────────────────────────────

  /** Returns the display label for a lookup-based filter value */
  private lookupLabel(list: any[], id: any, labelKey: string | ((item: any) => string)): string {
    if (!list?.length || id == null) return String(id ?? '');
    const item = list.find((i: any) => String(i.id) === String(id));
    if (!item) return String(id);
    return typeof labelKey === 'function' ? labelKey(item) : (item[labelKey] ?? String(id));
  }

  /** Returns array of { key, label, value } for every active form filter */
  getActiveFilterPills(): { key: string; label: string; value: string }[] {
    const v = this.searchingForm.getRawValue();
    const pills: { key: string; label: string; value: string }[] = [];

    const add = (key: string, label: string, value: string) => {
      if (value && value.trim()) pills.push({ key, label, value });
    };

    // Status (radio)
    if (v.event_status !== null && v.event_status !== undefined && v.event_status !== '') {
      const statusMap: any = { '0': 'Pending', '1': 'Completed', '2': 'Cancelled' };
      add('event_status', 'Status', statusMap[String(v.event_status)] ?? String(v.event_status));
    }

    // Lookup filters
    if (v.department_id)   add('department_id',   'Department',  this.lookupLabel(this.data?.departments ?? [], v.department_id, 'name'));
    if (v.market_id)       add('market_id',       'Market',      this.lookupLabel(this.data?.markets ?? [], v.market_id, 'name'));
    if (v.manager_id)      add('manager_id',       'Manager',     this.lookupLabel(this.data?.managers ?? [], v.manager_id, this.managerLabel));
    if (v.event_type_id)   add('event_type_id',   'Event Type',  this.lookupLabel(this.data?.eventTypes ?? [], v.event_type_id, 'name'));
    if (v.brand_id)        add('brand_id',         'Brand',       this.lookupLabel(this.data?.brands ?? [], v.brand_id, 'brand_name'));
    if (v.bill_id)         add('bill_id',          'Bill To',     this.lookupLabel(this.data?.clients ?? [], v.bill_id, 'name'));
    if (v.distributor_id)  add('distributor_id',  'Distributor', this.lookupLabel(this.data?.distributors ?? [], v.distributor_id, 'name'));
    if (v.location)        add('location',         'Location',    this.lookupLabel(this.data?.locations ?? [], v.location, 'name'));
    if (v.location_state)  add('location_state',  'State',       this.lookupLabel(this.data?.states ?? [], v.location_state, 'state'));
    if (v.ambassador_name) add('ambassador_name',  'Ambassador',  this.lookupLabel(this.data?.ambassadors ?? [], v.ambassador_name, this.ambassadorLabel));
    if (v.ambassador_email)add('ambassador_email', 'Amb. Email',  this.lookupLabel(this.data?.ambassadors ?? [], v.ambassador_email, this.ambassadorEmailLabel));
    if (v.event_id)        add('event_id',         'Event ID',    this.lookupLabel(this.filterEvents ?? [], v.event_id, this.eventLabel));

    // Free-text filters
    if (v.varietals)       add('varietals',        'Varietals',   v.varietals);
    if (v.billable_name)   add('billable_name',    'Billable By', v.billable_name);
    if (v.backup_brand_name) add('backup_brand_name', 'Backup Brand', v.backup_brand_name);
    if (v.region)          add('region',           'Region',      v.region);
    if (v.payment_type)    add('payment_type',     'Payment',     v.payment_type);
    if (v.best_sync)       add('best_sync',        'BEST Sync',   v.best_sync);

    // Date range
    if (v.startDate && v.endDate) add('dateRange', 'Date', `${v.startDate} – ${v.endDate}`);

    // Checkbox filters
    if (v.include_nearby_events) add('include_nearby_events', 'Nearby Events', 'Yes');
    if (v.overview_needed)       add('overview_needed',       'Overview Needed', 'Yes');
    if (v.not_fully_staffed)     add('not_fully_staffed',     'Not Fully Staffed', 'Yes');
    if (v.kit_required)          add('kit_required',          'Kit Required', 'Yes');
    if (v.exclude_cancellations) add('exclude_cancellations', 'No Cancellations', 'Yes');
    if (v.backup_brand_used)     add('backup_brand_used',     'Backup Brand Used', 'Yes');

    return pills;
  }

  /** Remove a single filter pill by its key and reload */
  removeFilterPill(key: string) {
    if (key === 'dateRange') {
      this.searchingForm.patchValue({ startDate: '', endDate: '' });
    } else if (key === 'event_status') {
      this.searchingForm.patchValue({ event_status: null });
    } else {
      // booleans reset to false, others to null/empty
      const boolKeys = ['include_nearby_events','overview_needed','not_fully_staffed','kit_required','exclude_cancellations','backup_brand_used'];
      const patch: any = {};
      patch[key] = boolKeys.includes(key) ? false : null;
      this.searchingForm.patchValue(patch);
    }
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
