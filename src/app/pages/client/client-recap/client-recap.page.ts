import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  selector: 'app-client-recap',
  templateUrl: './client-recap.page.html',
  styleUrls: ['./client-recap.page.scss'],
  standalone: false
})
export class ClientRecapPage implements OnInit {
  params: any = { title: 'Recap', is_back: false };
  user: any;
  data: any;
  eventForm: FormGroup;
  isSpinner: boolean = false;
  isSearch: boolean = false;
  activeFilters: any = {};
  filterEvents: any = [];
  searchTerm: string = '';
  recordsTotal: number = 0;
  recordsFiltered: number = 0;
  hasMore: boolean = false;
  start: number = 0;
  pageSize: number = 20;
  draw: number = 1;
  constructor(
    public common: CommonService,
    public apiService: ApiService,
    public storage: StorageService,
    public nav: NavController,
    public router: Router,
    private fb: FormBuilder,
    public cdrf: ChangeDetectorRef
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
  ngOnInit() {
  }
  ionViewWillEnter() {
    this.data = {};
    this.isSearch = false;
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getEventsCalendarData(false);
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }

  searchClicked() {
    this.isSearch = !this.isSearch;
    this.cdrf.detectChanges();
  }
  onChange(event: any, key: any) {
    const value = event?.detail?.value ?? event?.target?.value;
    this.setFilter(key, value);
    this.isSearch = false;
    this.getEventsCalendarData(false);
  }
  onInput(event: any, key: any) {
    const value = event?.detail?.value ?? event?.target?.value;
    this.setFilter(key, value);
    this.isSearch = false;
    this.getEventsCalendarData(false);
  }

  getEventsCalendarData(append: boolean = false, infiniteScrollEvent?: any) {
    if (!this.user?.token) return;

    if (!append) {
      this.start = 0;
      this.filterEvents = [];
      this.hasMore = false;
    }

    this.isSpinner = true;
    const params = new URLSearchParams();
    params.set('draw', String(this.draw++));
    params.set('start', String(this.start));
    params.set('length', String(this.pageSize));
    params.set('search[value]', this.searchTerm?.trim() || '');

    Object.keys(this.activeFilters).forEach((key) => {
      const value = this.activeFilters[key];
      if (value !== undefined && value !== null && value !== '') {
        params.set(key, value);
      }
    });

    this.apiService.getAuthData(`client/get_recap_calendar_data?${params.toString()}`, this.user?.token).subscribe({
      next: (res: any) => {
        const { payload, rows } = resolveDataTableResponse(res);
        this.data = payload;
        this.recordsTotal = Number(payload?.recordsTotal || rows.length || 0);
        this.recordsFiltered = Number(payload?.recordsFiltered || rows.length || 0);
        this.filterEvents = append ? [...this.filterEvents, ...rows] : rows;
        this.start = this.filterEvents.length;
        this.hasMore = this.filterEvents.length < this.recordsFiltered;
        this.isSpinner = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: (err: any) => {
        this.isSpinner = false;
        infiniteScrollEvent?.target?.complete();
        this.common.presentAlert(err?.error?.message || 'Unable to load recaps.');
      }
    });
  }
  edit(data: any) {
    this.router.navigate(['/client-event-details'], { state: data });
  }
  routeTo(url: any, data: any) {
    this.router.navigate([url], { state: data });
  }
  resetForm() {
    this.activeFilters = {};
    this.searchTerm = '';
    this.eventForm.reset();
    this.isSearch = false;
    this.getEventsCalendarData(false);
  }

  searchRecaps(event: any) {
    this.searchTerm = event?.detail?.value ?? event?.target?.value ?? '';
    this.getEventsCalendarData(false);
  }

  loadMore(event: any) {
    this.getEventsCalendarData(true, event);
  }

  viewRecap(row: any) {
    this.router.navigate(['/client-event-details'], { state: this.getEventId(row) });
  }

  getRecapCountLabel(): string {
    const count = this.recordsFiltered || this.filterEvents.length;
    return `${count} ${count === 1 ? 'recap' : 'recaps'} found`;
  }

  getBrandName(row: any): string {
    return row?.brand || row?.brand_name || '—';
  }

  getEventName(row: any): string {
    return row?.event_name || row?.name || 'Untitled Event';
  }

  getLocationName(row: any): string {
    return row?.location || row?.city || '—';
  }

  getRecapDate(row: any): string {
    const value = row?.event_date || row?.start_time || row?.submitted_at || row?.created_at;
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString([], {
      year: 'numeric', month: 'short', day: 'numeric'
    });
  }

  getSubmittedDate(row: any): string {
    const value = row?.submitted_at || row?.created_at;
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString([], {
      year: 'numeric', month: 'short', day: 'numeric'
    });
  }

  getAmbassadorName(row: any): string {
    return row?.ambassador || row?.ambassador_name || '—';
  }

  getRecapStatus(row: any): string {
    return row?.status || row?.recap_status || 'Submitted';
  }

  trackByRecap(index: number, recap: any) {
    return recap?.recap_id || recap?.id || recap?.event_id || index;
  }

  private setFilter(key: string, value: any) {
    if (value === undefined || value === null || value === '') {
      delete this.activeFilters[key];
      return;
    }
    this.activeFilters[key] = value;
  }

  private getEventId(row: any): any {
    if (row?.event_id) return row.event_id;

    const action = String(row?.action || '');
    const match = action.match(/id=["']?([^"'\s>]+)["']?/);
    if (match?.[1]) return match[1];

    return row?.id || '';
  }

}
