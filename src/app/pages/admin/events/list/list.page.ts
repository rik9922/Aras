import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
})
export class ListPage implements OnInit {
  params: any = { title: "Events List" };
  user: any;
  data: any;
  page: number = 0;
  events: any = [];
  is_ready: boolean = false;
  extraData: any;
  status: any = 0;
  extraParams: any = '';
  isSearch: boolean = false;
  filterEvents: any = [];
  fromDate: any = '';
  toDate: any = '';
  isFromModalOpen = false;
  isToModalOpen = false;
  searchingForm: FormGroup = new FormGroup({});
  maxdate: any = moment('2050-01-01').format('YYYY-MM-DD');
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
    this.event.subscribe('reload_events', res => {
      this.getEvents();
    });
  }

  ngOnInit() {
    this.searchingForm = this.fb.group({
      'department_id': new FormControl(''),
      'market_id': new FormControl(''),
      'manager_id': new FormControl(''),//
      'venue_id': new FormControl(''),
      'distributor_id': new FormControl(''),
      'event_id': new FormControl(''),
      'state_id': new FormControl(''),

      'varietals': new FormControl(''),
      'client_id': new FormControl(''),
      'payment_type': new FormControl(''),
      'startDate': new FormControl(''),
      'endDate': new FormControl(''),
      'ambassador_id': new FormControl(''),
      'brand_id': new FormControl(''),
      'ambassador_email': new FormControl(''),
      'region': new FormControl(''),

      'include_nearby_events': [false],
      'overview_needed': [false],
      'not_fully_staffed': [false],
      'kit_required': [false],
      'exclude_cancellations': [false],
      'event_status': new FormControl('')
    })
  }

  ionViewWillEnter() {
    this.storage.get("apro_user").then((data) => {
      if (data != null) {
        this.user = data;
        this.page = 0;
        this.getEvents();
        this.data = null;
        this.events = [];
      } else {
        this.router.navigate(["/login"]);
      }
    });
  }

  getEvents(event: any = null) {
    var self = this;
    var table = jQuery('#events').DataTable({
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
        url: environment.auth_url + "admin/get_event_list?status=" + self.status + this.extraParams,
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataSrc: function (a: any) {
          table.processing(false);
          self.extraData = a;
          self.data = a;
          if (a?.eventsData && a?.eventsData?.length > 0) {
            self.filterEvents = a?.eventsData;
          }
          return a.data;
        },
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "event_name" },
        { data: "status" },
        { data: "action" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="create"]').on('click', function (e: any) {
          self.nav.navigateForward('add-events', { state: { id: e.target.id } })
        })
      }
    });
  }
  onIonInfinite(event: any) {
    this.page = this.page + 1;
    this.getEvents(event);
  }
  goToPage(url: any) {
    this.router.navigate([url]);
  }

  view_data() {
    this.status = this.status == 0 ? 1 : 0;
    jQuery('#events').DataTable().destroy();
    this.ionViewWillEnter()
  }
  searchClicked() {
    this.isSearch = !this.isSearch;
  }

  onChangeCheckbox(event: any, key: any) {
    jQuery('#events').DataTable().destroy();
    var value = event?.detail?.checked ? 1 : 0;
    this.extraParams = '&' + key + '=' + value
    this.ionViewWillEnter();
    this.searchClicked()
  }
  onChangeRadioButton(event: any, key: any) {
    jQuery('#events').DataTable().destroy();
    var value = event?.detail?.value ?? event?.target?.value;
    if (key === 'event_status' && value === '1') {
      this.status = 1;
    } else if (key === 'event_status') {
      this.status = 0;
    }
    this.extraParams = '&' + key + '=' + value;
    this.ionViewWillEnter();
    this.searchClicked();
  }


  onSubmit(formValue: any) {
    console.log('formValue', formValue);
  }
  openToModal() {
    this.isToModalOpen = true;
    this.cdrf.detectChanges();
  }

  openFromModal() {
    this.isFromModalOpen = true;
    this.cdrf.detectChanges();
  }




  submit(formvalue: any) {
    console.log(formvalue);
  }

  resetForm() {
    this.extraParams = '';
    jQuery('#events').DataTable().destroy();
    this.searchingForm.reset();
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  onChange(event: any, key: any) {
    jQuery('#events').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  onInput(event: any, key: any) {
    var value = event?.target?.value;
    jQuery('#events').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value;
    this.isSearch = false;
    this.ionViewWillEnter();
  }

  changeDate(event: any,type:any) {
    let form: any = this.searchingForm.controls[type];
    form.setValue(moment(event?.detail?.value).format('YYYY-MM-DD'));
    this.extraParams = '&' + type + '=' + moment(event?.detail?.value).format('YYYY-MM-DD');
    var self=this;
    setTimeout(function(){
      self.isSearch = false;
      self.ionViewWillEnter();
    },1000)
  }
}
