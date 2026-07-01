import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter } from 'src/app/utils/api-response.util';
declare var jQuery: any;

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
          table.processing(false);
          self.data = a;
          return a.data
        },
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
  searchClicked() {
    this.isSearch = !this.isSearch;
  }
  resetForm() {
    this.extraParams = '';
    jQuery('#recap').DataTable().destroy();
    this.addForm.reset();
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  onChangeCheckbox(event: any, key: any) {
    jQuery('#recap').DataTable().destroy();
    var value = event?.detail?.checked ? 1 : 0;
    this.extraParams = '&' + key + '=' + value;
    this.ionViewWillEnter();
    this.searchClicked();
  }

  onChange(event: any, key: any) {
    jQuery('#recap').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.ionViewWillEnter();
    this.searchClicked();
  }


}
