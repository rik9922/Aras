import { HttpHeaders, HttpParams } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  standalone: false,
  selector: 'app-submitted-recap',
  templateUrl: './submitted-recap.page.html',
  styleUrls: ['./submitted-recap.page.scss'],
})
export class SubmittedRecapPage implements OnInit {
  params: any = { title: 'Recaps', is_back: true };
  user: any;
  isSpinner: boolean = false;
  data: any;
  filterEvents: any = [];
  brands: any = [];
  type: any = '';
  searchTerm: string = '';
  recordsTotal = 0;
  recordsFiltered = 0;
  pageLength = 10;
  start = 0;
  draw = 1;
  hasMore = false;
  tableVisible = false;

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
  ) { }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.data = [];
    this.filterEvents = [];
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getEventsData();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  getEventsData(append = false, infiniteEvent?: any) {
    if (!this.user?.token) {
      return;
    }

    if (!append) {
      this.start = 0;
      this.filterEvents = [];
    }

    this.isSpinner = true;
    const searchValue = this.searchTerm?.trim() || this.type || '';
    const headers = new HttpHeaders({
      Authorization: 'Bearer ' + this.user?.token,
    });
    let params = new HttpParams()
      .set('draw', String(this.draw++))
      .set('start', String(this.start))
      .set('length', String(this.pageLength))
      .set('search[value]', searchValue);

    this.apiService.http.get(environment.auth_url + 'ambassador/get_recap_data', { headers, params }).subscribe({
      next: (response: any) => {
        const { payload, rows } = resolveDataTableResponse(response);
        if (payload?.is_searched === null || payload?.is_searched === '') {
          this.brands = payload.brands || [];
        }
        this.data = payload;
        this.recordsTotal = Number(payload?.recordsTotal || rows.length || 0);
        this.recordsFiltered = Number(payload?.recordsFiltered || rows.length || 0);
        this.filterEvents = append ? [...this.filterEvents, ...rows] : rows;
        this.start = this.filterEvents.length;
        this.hasMore = this.filterEvents.length < this.recordsFiltered;
        this.isSpinner = false;
        infiniteEvent?.target?.complete();
      },
      error: (err: any) => {
        this.isSpinner = false;
        infiniteEvent?.target?.complete();
        console.error('Error fetching submitted recap data', err);
      },
    });
  }

  ionViewWillLeave() {
  }

  getStatusWise(event: any) {
    this.getEventsData();
  }

  searchRecaps(event: any) {
    this.searchTerm = event?.target?.value || '';
    this.getEventsData();
  }

  loadMore(event: any) {
    this.getEventsData(true, event);
  }

  viewRecap(row: any) {
    this.nav.navigateForward('pay-forecast-detail', { state: { id: this.getEventId(row) } });
  }

  editRecap(row: any) {
    this.nav.navigateForward('ambassador-edit-recap', { state: { id: this.getRecapId(row) } });
  }

  /** Keep the complete submitted timestamp visible on narrow mobile tables. */
  formatSubmittedDate(row: any): string {
    const value = row?.submitted_at || row?.submitted_date || row?.created_at || row?.date_submitted;
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString([], {
      year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'
    });
  }

  getRecapDate(row: any): string {
    const value = row?.event_date || row?.date || row?.start_date || row?.submitted_at || row?.submitted_date || row?.created_at || row?.date_submitted;
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString([], {
      year: 'numeric', month: 'short', day: 'numeric'
    });
  }

  getBrandName(row: any): string {
    return row?.brand || row?.brand_name || row?.brandName || '—';
  }

  getBrandInitials(row: any): string {
    return this.getBrandName(row)
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((word: string) => word.charAt(0))
      .join('')
      .toUpperCase() || 'R';
  }

  getBrandLogo(row: any): string {
    return row?.brand_logo || row?.logo || row?.image || row?.brand_image || '';
  }

  getEventName(row: any): string {
    return row?.event_name || row?.name || 'Untitled Event';
  }

  getManagerName(row: any): string {
    return row?.manager || row?.manager_name || row?.managerName || '—';
  }

  getLocationName(row: any): string {
    return row?.city || row?.location || row?.market || '—';
  }

  getRecapStatus(row: any): string {
    return row?.status || row?.recap_status || 'Approved';
  }

  getUploadedFileGroups(row: any): any[] {
    return Array.isArray(row?.uploaded_files) ? row.uploaded_files : [];
  }

  getRecapCountLabel(): string {
    const count = this.recordsFiltered || this.filterEvents.length;
    return `${count} ${count === 1 ? 'recap' : 'recaps'} found`;
  }

  private getRecapId(row: any): any {
    if (row?.recap_id) return row.recap_id;
    if (row?.id) return row.id;

    const action = String(row?.action || '');
    const match = action.match(/id=["']?([^"'\s>]+)["']?/);
    return match?.[1] || '';
  }

  private getEventId(row: any): any {
    return row?.event_id || row?.id || '';
  }
}
