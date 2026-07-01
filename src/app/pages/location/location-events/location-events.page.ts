import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { an } from '@fullcalendar/core/internal-common';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter, resolveDataTableResponse } from 'src/app/utils/api-response.util';

declare var jQuery: any;

@Component({
  selector: 'app-location-events',
  templateUrl: './location-events.page.html',
  styleUrls: ['./location-events.page.scss'],
  standalone: false
})
export class LocationEventsPage implements OnInit {
  params: any = { title: 'Events', is_back: false };
  extraParams: any = '';
  user: any;
  data: any;
  eventForm: FormGroup;
  isSpinner: boolean = false;
  isSearch: boolean = false;
  filterEvents: any = [];
  constructor(
    public common: CommonService,
    public apiService: ApiService,
    public storage: StorageService,
    public nav: NavController,
    public router: Router,
    private fb: FormBuilder
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
        this.getEvents();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }

  searchClicked() {
    this.isSearch = !this.isSearch;
  }
  getEvents() {
    var self = this;
    var table = jQuery('#location_event').DataTable({
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
        url: environment.auth_url + "location/get_event_list?" + this.extraParams,
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          const { payload, rows } = resolveDataTableResponse(a);
          table.processing(false);
          self.data = payload;
          if (payload?.eventsData && payload?.eventsData?.length > 0) {
            self.filterEvents = payload?.eventsData;
          }
          return rows
        },
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "day" },
        { data: "date" },
        { data: "time" },
        { data: "brand_name" },
        { data: "distributor" },
        { data: "action" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="eye"]').on('click', function (e: any) {
          self.nav.navigateForward('/location-event-details', { state: { id: e.target.id } })
        })
      }
    });
  }
  onChange(event: any, key: any) {
    jQuery('#location_event').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  resetForm() {
    this.extraParams = '';
    jQuery('#location_event').DataTable().destroy();
    this.eventForm.reset();
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  onInput(event: any, key: any) {
    var value = event?.target?.value;
    jQuery('#location_event').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  onChangeCheckbox(event: any, key: any) {
    jQuery('#location_event').DataTable().destroy();
    var value = event?.detail?.checked ? 1 : 0;
    this.extraParams = '&' + key + '=' + value;
    this.ionViewWillEnter();
    this.searchClicked()
  }
  ionViewWillLeave() {
    if (jQuery('#location_event')) {
      jQuery('#location_event').DataTable().destroy();
    }
  }
}
