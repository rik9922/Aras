import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter } from 'src/app/utils/api-response.util';

declare var jQuery:any
@Component({
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
  standalone: false
})
export class ListPage implements OnInit {
  user: any;
  params: any = { title: 'Unapprove Ambassador for Events List ', is_back: true };
  constructor(
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    public apiService: ApiService,
    public storage: StorageService,
    public event: EventsService
  ) { }

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
    var table = jQuery('#admin-unapprove-ambassador').DataTable({
      dom: 'frtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      bDestroy: true,
      bAutoWidth: false,
      bJQueryUI: true,
      sDom: '<"H"ilfr><"applicationsList"t><"F"p>',
      searching: false,

      ajax: {
        url: environment.auth_url + "admin/get_unapprove_ambassador_list", // Replace with your server-side script URL
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
        { data: "event" },
        { data: "name" },
        { data: "action" },

      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="checkbox"]').on('click', function (e: any) {
          self.assign(e.target.id);
        });
      }
    });
  }

  assign(data: any) {
    this.common.showLoader()
    this.apiService.sendAuthData('admin/assign_ambassabor_event',{id:data},this.user?.token).subscribe((res:any)=>{
      this.common.dismissLoading()
      jQuery('#admin-unapprove-ambassador').DataTable().destroy();
      this.ionViewWillEnter()
    },err=>{
      console.log(err)
      this.common.presentAlert(err?.error?.message)
      this.common.dismissLoading()
    })
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }

}
