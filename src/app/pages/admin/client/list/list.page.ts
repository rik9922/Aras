import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
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
  params: any = { title: "Bill To", is_back:true };
  user: any;
  data: any;
  is_ready: boolean = false;
  count: number = 0;
  extraparams: any = '';
  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    public event: EventsService
  ) {
    this.event.subscribe('reload_clients', res => {
      this.getData();
    });
  };

  ngOnInit() {
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
    var table = jQuery('#client').DataTable({
      dom: 'frtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      bDestroy: true,
      bAutoWidth: false,
      bJQueryUI: true,
      sDom: '<"H"ilfr><"applicationsList"t><"F"p>',

      ajax: {
        url: environment.auth_url + "admin/get_clients?" + this.extraparams, // Replace with your server-side script URL
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          const { payload, rows } = resolveDataTableResponse(a);
          table.processing(false);
          return rows},
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "first_name" },
        { data: "last_name" },
        { data: "status" },
        { data: "action" },
        // { data: "action" }
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="create"]').on('click', function (e: any) {
          self.edit(e.target.id);
        })
      }
    });
  }

  edit(data: any) {
    this.nav.navigateForward('admin-client-add-update', { state: { id: data } })
  }

  view_inactive(event: any) {
    this.extraparams = 'is_inactive=' + event.detail.checked
    this.getData()
  }
}
