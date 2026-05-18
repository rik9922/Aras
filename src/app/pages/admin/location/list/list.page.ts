import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';

declare var jQuery:any
@Component({
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
  standalone: false
})
export class ListPage implements OnInit {
  params: any = { title: 'Locations List', is_back: true };
  user: any;
  extraparams: any = '';
  constructor(
    public common: CommonService,
    public apiService: ApiService,
    public storage: StorageService,
    public event: EventsService,
    public router: Router,
    public nav: NavController
  ) {
    this.event.subscribe('reload_locations',res=>{
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
      }
    })
  }
  getData() {
    var self = this;
    var table = jQuery('#admin-location').DataTable({
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
        url: environment.auth_url + "admin/get_locations?"+self.extraparams, // Replace with your server-side script URL
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataSrc: function (a: any) {
          table.processing(false);
          return a.data
        },
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "address" },
        { data: "city" },
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
    this.router.navigate(['/admin-add-location'], { state: { id: data } });
  }
  goToPage(url: any) {
    this.router.navigate([url]);
  }

  view_inactive(event: any) {
    this.extraparams = 'is_inactive=' + event.detail.checked
    this.getData()
  }
}
