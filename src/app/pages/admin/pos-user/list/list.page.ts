import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter } from 'src/app/utils/api-response.util';

declare var jQuery: any;
@Component({
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
  standalone: false
})
export class ListPage implements OnInit {
  params: any = { title: 'POS User List', is_back: true };
  user: any;
  data: any;
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    public apiService: ApiService,
    public event: EventsService
  ) {
    this.event.subscribe('reload_pos_users', res => {
      this.getData();
    })
  }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getData();
      } else {
        this.nav.navigateRoot('/login');
      };
    });
  }
  getData() {
    var self = this;
    var table = jQuery('#admin-pos-user').DataTable({
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
        url: environment.auth_url + "admin/get_pos_users", // Replace with your server-side script URL
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          table.processing(false);
          return a.data
        },
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "name" },
        { data: "phone" },
        { data: "action" },

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
    this.router.navigate(['/admin-add-pos-user'], { state: { id: data } });
  }
  goToPage(url: any) {
    this.router.navigate([url]);
  }


}
