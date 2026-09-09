import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Capacitor, CapacitorHttp } from '@capacitor/core';
import { FileViewer } from '@capacitor/file-viewer';
import { Directory, Filesystem } from '@capacitor/filesystem';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter, resolveDataTableResponse } from 'src/app/utils/api-response.util';
declare var jQuery: any;

interface RecapFilterConfig {
  key: string;
  label: string;
  placeholder: string;
  endpointType: string;
  controlName: string;
}

@Component({
  standalone: false,
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
})
export class ListPage implements OnInit {
  params: any = { title: "Recap", is_back: false };
  user: any;
  data: any;
  recapRows: any[] = [];
  searchText: string = '';
  filterSearch: any = {};
  filterOptions: any = {};
  selectedFilters: any = {};
  filterLoading: any = {};
  filterTimers: any = {};
  readonly minFilterSearchLength = 2;
  readonly recapFilters: RecapFilterConfig[] = [
    { key: 'departments', label: 'Departments', placeholder: 'Search departments', endpointType: 'departments', controlName: 'department_id' },
    { key: 'markets', label: 'Markets', placeholder: 'Search markets', endpointType: 'markets', controlName: 'market_id' },
    { key: 'managers', label: 'Manager', placeholder: 'Search manager', endpointType: 'managers', controlName: 'manager_id' },
    { key: 'eventTypes', label: 'Event Type', placeholder: 'Search event type', endpointType: 'event_types', controlName: 'event_type_id' },
    { key: 'distributors', label: 'Distributors', placeholder: 'Search distributors', endpointType: 'distributors', controlName: 'distributor_id' },
    { key: 'events', label: 'Events', placeholder: 'Search event name or ID', endpointType: 'events', controlName: 'event_id' },
    { key: 'states', label: 'State', placeholder: 'Search state', endpointType: 'states', controlName: 'state_id' },
    { key: 'eventLocations', label: 'Event Location', placeholder: 'Search event location', endpointType: 'event_locations', controlName: 'event_location_id' },
    { key: 'ambassadorNames', label: 'Ambassador Name', placeholder: 'Search ambassador name', endpointType: 'ambassador_names', controlName: 'ambassador_id' },
    { key: 'ambassadorEmails', label: 'Ambassador Email', placeholder: 'Search ambassador email', endpointType: 'ambassador_emails', controlName: 'ambassador_email' },
    { key: 'brands', label: 'Brands', placeholder: 'Search brands', endpointType: 'brands', controlName: 'brand_id' },
    { key: 'clients', label: 'Bill To / Clients', placeholder: 'Search clients', endpointType: 'clients', controlName: 'bill_id' },
  ];
  is_ready: boolean = false;
  isLoadingRecaps: boolean = false;
  count: number = 0;
  isSearch: boolean = false;
  searchPayForecast!: FormGroup;
  years: any = [];
  months: any = [];
  days: any = [];
  extraParams: any = '';
  addForm: FormGroup = new FormGroup({});
  fromDate: any = '';
  toDate: any = '';
  isFromModalOpen = false;
  isToModalOpen = false;

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public fb: FormBuilder,
    public cd: ChangeDetectorRef,
    public event: EventsService
  ) {
    this.event.subscribe('reload_recaps', res => {
      this.getData();
    });
  }

  ngOnInit() {
    this.addForm = this.fb.group({
      'department_id': new FormControl(''),
      'manager_id': new FormControl(''),
      'location_id': new FormControl(''),
      'brand_id': new FormControl(''),
      'market_id': new FormControl(''),
      'venue_id': new FormControl(''),
      'event_type_id': new FormControl(''),
      'state_id': new FormControl(''),
      'event_location_id': new FormControl(''),

      'region': new FormControl(''),
      'startDate': new FormControl(''),
      'endDate': new FormControl(''),
      'distributor_id': new FormControl(''),
      'ambassador_id': new FormControl(''),
      'bill_id': new FormControl(''),
      'event_id': new FormControl(''),
      'ambassador_email': new FormControl(''),
      'recap_needed': [false]
    })
  }

  ionViewWillEnter() {
    this.storage.get("apro_user").then((data) => {
      if (data != null) {
        this.user = data;
        this.getData();
        this.data = null;
      } else {
        this.router.navigate(["/login"]);
      }
    });
  }

  getData() {
    var self = this;
    this.isLoadingRecaps = true;
    this.recapRows = [];
    this.count = 0;
    var table = jQuery('#recap').DataTable({
      dom: 'rtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      bDestroy: true,
      bAutoWidth: false,
      bJQueryUI: true,
      sDom: '<"H"ilfr><"applicationsList"t><"F"p>',
      ajax: {
        url: environment.auth_url + "admin/get_recap?" + this.extraParams, // Replace with your server-side script URL
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          const { payload, rows } = resolveDataTableResponse(a);
          table.processing(false);
          self.recapRows = rows;
          self.count = payload?.recordsFiltered ?? payload?.recordsTotal ?? rows.length;
          self.isLoadingRecaps = false;
          self.cd.detectChanges();
          return rows},
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
        self.isLoadingRecaps = false;
        self.cd.detectChanges();
      },
      columns: [
        { data: "brand" },
        { data: "city" },
        { data: "action" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="create"]').on('click', function (e: any) {
          self.edit(e?.target?.id);
        });
        jQuery('ion-icon[name="eye"]').on('click', function (e: any) {
          self.router.navigate(['/admin-view-recap'], { state: { id: e?.target?.id } })
        })
      }
    });
  }

  edit(data: any) {
    this.router.navigate(['/admin-add-recap'], { state: { id: data } });
  }

  view(data: any) {
    this.router.navigate(['/admin-view-recap'], { state: { id: data } });
  }

  onSearchInput(event: any) {
    this.searchText = event?.detail?.value || '';
    var table = jQuery.fn.DataTable.isDataTable('#recap') ? jQuery('#recap').DataTable() : null;
    if (table) {
      table.search(this.searchText).draw();
    }
  }

  exportRecap(row: any) {
    const recapId = this.getRecapId(row);
    if (!recapId) {
      this.common.presentAlert('Unable to export this recap.');
      return;
    }

    this.common.showLoader();
    this.apiService.sendAuthData('admin/export_recap_email', { id: recapId }, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.common.presentAlert(res?.message || 'Recap export has been emailed.');
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message || 'Unable to export this recap.');
    });
  }

  exportRecaps() {
    const params = this.buildWebExportParams();
    if (Capacitor.getPlatform() !== 'web') {
      this.openNativeRecapExport(params);
      return;
    }

    const queryString = params.toString();
    const endpoint = `admin/export_recaps${queryString ? '?' + queryString : ''}`;

    this.common.showLoader();
    this.apiService.getAuthBlob(endpoint, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.downloadBlobResponse(res, 'events_recap_export.xlsx');
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message || 'Unable to export recaps.');
    });
  }

  private async openNativeRecapExport(params: URLSearchParams) {
    const queryString = params.toString();
    const url = `${environment.auth_url}admin/export_recaps${queryString ? '?' + queryString : ''}`;

    try {
      this.common.showLoader();
      const response = await CapacitorHttp.get({
        url,
        headers: {
          Authorization: `Bearer ${this.user?.token || ''}`,
        },
        responseType: 'arraybuffer',
      });

      if (response.status < 200 || response.status >= 300) {
        throw new Error('Export request failed.');
      }

      const filename = this.filenameFromHeaders(response.headers) || 'events_recap_export.xlsx';
      const path = `exports/${filename}`;
      await Filesystem.writeFile({
        path,
        data: await this.toBase64(response.data),
        directory: Directory.Cache,
        recursive: true,
      });

      const file = await Filesystem.getUri({
        path,
        directory: Directory.Cache,
      });
      this.common.dismissLoading();
      await FileViewer.openDocumentFromLocalPath({ path: file.uri });
    } catch (error) {
      this.common.dismissLoading();
      this.common.presentAlert('Unable to export recaps.');
    }
  }

  private buildWebExportParams(): URLSearchParams {
    const params = new URLSearchParams();
    const formValue = this.addForm?.value || {};
    const exportKeyMap: Record<string, string> = {
      event_type_id: 'venue_id',
      state_id: 'location_state',
      ambassador_id: 'ambassador_name_id',
      ambassador_email: 'ambassador_email_id',
      bill_id: 'bill_id',
      recap_needed: 'overview_needed',
    };

    Object.entries(formValue).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '' || value === false) {
        return;
      }

      const exportKey = exportKeyMap[key] || key;
      params.set(exportKey, String(value));
    });

    return params;
  }

  private downloadBlobResponse(response: any, fallbackFilename: string) {
    const blob = response?.body;
    if (!blob) {
      this.common.presentAlert('Export file could not be generated.');
      return;
    }

    const filename = this.filenameFromDisposition(response?.headers?.get('content-disposition')) || fallbackFilename;
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => window.URL.revokeObjectURL(url), 1000);
  }

  private filenameFromDisposition(disposition: string | null): string {
    if (!disposition) {
      return '';
    }

    const utfMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
      return decodeURIComponent(utfMatch[1].replace(/"/g, ''));
    }

    const match = disposition.match(/filename="?([^"]+)"?/i);
    return match?.[1] || '';
  }

  private filenameFromHeaders(headers: Record<string, string> = {}): string {
    return this.filenameFromDisposition(headers['content-disposition'] || headers['Content-Disposition'] || null);
  }

  private async toBase64(data: any): Promise<string> {
    if (typeof data === 'string') {
      const base64Prefix = /^data:.*;base64,/;
      return data.replace(base64Prefix, '');
    }

    if (data instanceof Blob) {
      return (await this.blobToBase64(data)).replace(/^data:.*;base64,/, '');
    }

    if (data instanceof ArrayBuffer) {
      return this.arrayBufferToBase64(data);
    }

    if (ArrayBuffer.isView(data)) {
      return this.arrayBufferToBase64(data.buffer as ArrayBuffer);
    }

    throw new Error('Unsupported export response.');
  }

  private arrayBufferToBase64(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach((byte) => binary += String.fromCharCode(byte));
    return btoa(binary);
  }

  private blobToBase64(blob: Blob): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(String(reader.result || ''));
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
  }

  getRecapId(row: any): any {
    if (row?.id || row?.recap_id || row?.event_id) {
      return row?.id || row?.recap_id || row?.event_id;
    }

    const action = row?.action || '';
    const match = action.match(/id=["']([^"']+)["']/);
    return match?.[1];
  }

  getRecapTitle(row: any): string {
    return this.cleanText(row?.brand || row?.brand_name || row?.event_name || 'Recap');
  }

  getRecapSubtitle(row: any): string {
    return this.cleanText(row?.event || row?.event_id || row?.event_name || row?.name || '');
  }

  getRecapCity(row: any): string {
    return this.cleanText(row?.city || row?.market || row?.location || row?.venue_city || '');
  }

  getRecapDate(row: any): string {
    const date = row?.date || row?.event_date || row?.start_date || row?.created_at || '';
    return date ? moment(date).format('MMM DD, YYYY') : '';
  }

  getRecapAmbassador(row: any): string {
    return this.cleanText(row?.ambassador || row?.ambassador_name || row?.user_name || row?.name || '');
  }

  getRecapStatus(row: any): string {
    return this.cleanText(row?.status || row?.approval_status || row?.recap_status || 'Approved');
  }

  getBrandInitial(row: any): string {
    return this.getRecapTitle(row).charAt(0).toUpperCase();
  }

  private cleanText(value: any): string {
    if (value === null || value === undefined) {
      return '';
    }

    return String(value).replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
  }

  searchClicked() {
    this.isSearch = !this.isSearch;
  }

  onFilterSearch(event: any, filter: RecapFilterConfig) {
    const term = event?.detail?.value || '';
    this.filterSearch[filter.key] = term;
    this.selectedFilters[filter.key] = null;
    this.addForm.get(filter.controlName)?.setValue('');

    clearTimeout(this.filterTimers[filter.key]);
    if (term.trim().length < this.minFilterSearchLength) {
      this.filterOptions[filter.key] = [];
      return;
    }

    this.filterTimers[filter.key] = setTimeout(() => {
      this.filterLoading[filter.key] = true;
      const endpoint = `admin/search_recap_filters?type=${filter.endpointType}&q=${encodeURIComponent(term.trim())}&limit=20`;
      this.apiService.getAuthData(endpoint, this.user?.token).subscribe((res: any) => {
        this.filterOptions[filter.key] = res?.data || [];
        this.filterLoading[filter.key] = false;
      }, () => {
        this.filterOptions[filter.key] = [];
        this.filterLoading[filter.key] = false;
      });
    }, 350);
  }

  selectFilterOption(filter: RecapFilterConfig, option: any) {
    this.selectedFilters[filter.key] = option;
    this.filterSearch[filter.key] = this.getFilterOptionLabel(filter, option);
    this.filterOptions[filter.key] = [];
    this.addForm.get(filter.controlName)?.setValue(option?.id);
  }

  clearFilter(filter: RecapFilterConfig) {
    this.selectedFilters[filter.key] = null;
    this.filterSearch[filter.key] = '';
    this.filterOptions[filter.key] = [];
    this.addForm.get(filter.controlName)?.setValue('');
  }

  getFilterOptionLabel(filter: RecapFilterConfig, option: any): string {
    if (!option) {
      return '';
    }

    if (filter.endpointType === 'brands') {
      return option?.brand_name || '';
    }
    if (filter.endpointType === 'events') {
      return option?.event_name ? `${option.event_name} (${option.id})` : String(option?.id || '');
    }
    if (filter.endpointType === 'event_locations') {
      return [option?.name, option?.city, option?.state].filter(Boolean).join(' - ') || option?.address || '';
    }
    if (filter.endpointType === 'ambassador_emails') {
      return option?.email || '';
    }

    return option?.name || [option?.first_name, option?.last_name].filter(Boolean).join(' ') || option?.email || '';
  }

  applyFilters() {
    const params = new URLSearchParams();
    Object.entries(this.addForm.value).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '' && value !== false) {
        params.set(key, String(value));
      }
    });

    this.extraParams = params.toString() ? `&${params.toString()}` : '';
    if (jQuery.fn.DataTable.isDataTable('#recap')) {
      jQuery('#recap').DataTable().destroy();
    }
    this.isSearch = false;
    this.ionViewWillEnter();
  }

  resetForm() {
    this.extraParams = '';
    if (jQuery.fn.DataTable.isDataTable('#recap')) {
      jQuery('#recap').DataTable().destroy();
    }
    this.addForm.reset();
    this.searchText = '';
    this.filterSearch = {};
    this.filterOptions = {};
    this.selectedFilters = {};
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  onChangeCheckbox(event: any, key: any) {
    var value = event?.detail?.checked ? 1 : 0;
    this.addForm.get(key)?.setValue(value);
  }

  onChange(event: any, key: any) {
    var value = event?.target?.value;
    this.addForm.get(key)?.setValue(value);
  }


}
