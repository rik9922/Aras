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
@Component({
  standalone: false,
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
})
export class ListPage implements OnInit {
  params: any = { title: "Ambassadors", is_back: true };
  user: any;
  data: any;
  is_ready: boolean = false;
  count: number = 0;
  isSearch: boolean = false;
  searchPayForecast!: FormGroup;
  registerForm: FormGroup = new FormGroup({});
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
    public cdrf: ChangeDetectorRef,
    public fb: FormBuilder,
    public event: EventsService
  ) {
    this.event.subscribe('reload_ambassador', res => {
      this.getData();
    })
  }

  ngOnInit() {
    this.registerForm = this.fb.group({
      'first_name': new FormControl(''),
      'last_name': new FormControl(''),
      'phone': new FormControl(''),
      'email': new FormControl(''),
      'city': new FormControl(''),
      'state': new FormControl(''),
      'startDate': new FormControl(''),
      'endDate': new FormControl(''),
      'market_id': new FormControl(''),
      'region': new FormControl(''),
      'status': new FormControl(''),
      'documents': [false],
      'videoWatched': [false],
      'hasUnwatchedVideos': [false],
      'comdataCardSent': [false],
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
    var table = jQuery('#ambassador').DataTable({
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
        url: environment.auth_url + "admin/get_ambassador?" + this.extraParams,
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          const { payload, rows } = resolveDataTableResponse(a);
          table.processing(false);
          self.data = payload;
          return rows},
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "name" },
        { data: "city" },
        { data: "action" },
      ],
      drawCallback: function (settings: any) {
        jQuery('ion-icon[name="trash"]').on('click', function (e: any) {
          self.delete(e.target?.id);
        });
        jQuery('ion-icon[name="create"]').on('click', function (e: any) {
          self.edit(e.target?.id);
          console.log('Table redrawn');
        })
      }
    });
  }

  edit(data: any) {
    this.router.navigate(['/admin-add-ambassador'], { state: { id: data } });
  }
  async delete(data: any) {
    let check: any = await this.common.confirmationAlert('You want to delete this Ambassador.', 'Alert', 'No', 'Yes');
    if (check) {
      this.common.showLoader();
      this.apiService.sendAuthData('admin/delete_ambassador', { id: data }, this.user?.token,).subscribe((res: any) => {
        this.common.dismissLoading();
        jQuery('#ambassador').DataTable().destroy();
        this.ionViewWillEnter()
      }, err => {
        this.common.dismissLoading();
        console.log('err', err);
      })
    }
  }
  searchClicked() {
    this.isSearch = !this.isSearch;
  }
  resetForm() {
    this.extraParams = '';
    jQuery('#ambassador').DataTable().destroy();
    this.registerForm.reset();
    this.isSearch = false;
    this.ionViewWillEnter();
  }
  onChangeCheckbox(event: any, key: any) {
    jQuery('#ambassador').DataTable().destroy();
    var value = event?.detail?.checked ? 1 : 0;
    this.extraParams = '&' + key + '=' + value
    this.ionViewWillEnter();
    this.searchClicked()
  }
  onChange(event: any, key: any) {
    jQuery('#ambassador').DataTable().destroy();
    var value = event?.target?.value;
    this.extraParams = '&' + key + '=' + value
    this.ionViewWillEnter();
    this.searchClicked()
  }



}
