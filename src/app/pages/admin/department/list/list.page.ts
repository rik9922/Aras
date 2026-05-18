import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
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
  params: any = { title: "Event Department", is_back: true };
  user: any;
  data: any;
  is_ready: boolean = false;
  count: number = 0;
  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public event:EventsService
  ) { 
    this.event.subscribe('reload_departments',res=>{
      this.getData();
    })
  }

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
    var table = jQuery('#department').DataTable({
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
        url: environment.auth_url + "admin/get_event_department", // Replace with your server-side script URL
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
        { data: "name" },
        { data: "description" },
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
    this.router.navigate(['/add-events-department'], { state: { id: data } });
  }
  goToPage(url: any) {
    this.router.navigate([url]);
  }
}
