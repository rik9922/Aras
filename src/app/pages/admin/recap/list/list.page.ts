import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
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
          self.cd.detectChanges();
          return rows},
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
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
    const action = row?.action || '';
    const match = action.match(/href=["']([^"']*(?:export|download)[^"']*)["']/i);
    if (match?.[1]) {
      window.location.href = match[1];
    }
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
