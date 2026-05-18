import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';

declare var jQuery: any;
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
  extraParams: any = '';
  filterEvents: any = [];
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
        this.getEventsCalendarData();
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
    jQuery('#client_recap_event').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  onInput(event: any, key: any) {
    var value = event?.target?.value;
    jQuery('#client_recap_event').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.isSearch = false;
    this.ionViewWillEnter();
  }

  getEventsCalendarData() {
    var self = this;
    var table = jQuery('#client_recap_event').DataTable({
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
        url: environment.auth_url + "client/get_recap_calendar_data?" + this.extraParams, // Replace with your server-side script URL
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataSrc: function (a: any) {
          table.processing(false);
          self.data = a;
          if (a?.events && a?.events?.length > 0) {
            self.filterEvents = a?.events;
          }
          return a.data;
        },
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "name" },
        { data: "brand" },
        { data: "action" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="eye"]').on('click', function (e: any) {
          self.edit(e?.target?.id);
        })
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
    this.extraParams = '';
    jQuery('#client_recap_event').DataTable().destroy();
    this.eventForm.reset();
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  ionViewWillLeave() {
    if (jQuery('#client_recap_event')) {
      jQuery('#client_recap_event').DataTable().destroy();
    }
  }

}
