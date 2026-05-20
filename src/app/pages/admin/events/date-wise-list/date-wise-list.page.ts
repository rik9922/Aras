import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
declare var jQuery: any;


@Component({
  standalone:false,
  selector: 'app-date-wise-list',
  templateUrl: './date-wise-list.page.html',
  styleUrls: ['./date-wise-list.page.scss'],
})
export class DateWiseListPage implements OnInit {
  params: any = { title: "Events Management" , is_back:true};
  user:any;
  data:any;
  page:number=0;
  events:any=[];
  is_ready:boolean=false;
  param:any;
  constructor(
    public storage : StorageService,
    public apiService : ApiService,
    public common : CommonService,
    public router : Router,
    public nav : NavController
  ) {
    if(this.router.getCurrentNavigation()?.extras?.state){
      this.param=this.router.getCurrentNavigation()?.extras?.state
      this.params.title=(moment(this.param?.date).format('MM/DD/YYYY'))+' Events'
    }
   }

  ngOnInit() {
  }

  ionViewWillEnter(){
    this.events=[];          
    this.storage.get("apro_user").then((data) => {
      if (data != null) {
        this.user = data;
        this.page=0;    
        this.getEvents();  
        this.data=null;
      } else {
        this.router.navigate(["/login"]);
      }
    });
  }

  /**
   * Mobile API only exposes admin/location/brand/client/ambassador prefixes — there is no "manager" route.
   * Managers (role_id 6) must use the same AdminController endpoints as admins.
   */
  private eventListAuthPrefix(): string {
    const rid = Number(this.user?.role_id);
    if (rid === 1 || rid === 6) {
      return 'admin';
    }
    const name = (this.user?.role?.name || '').toLowerCase();
    return name === 'administrator' ? 'admin' : name;
  }

  getEvents(event: any = null){
    var self=this;
    var table= jQuery('#date-wise-event-list').DataTable({
      dom: 'rtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive:true,
      bDestroy: true,
      bAutoWidth: false,
      bJQueryUI: true,
      sDom: '<"H"ilfr><"applicationsList"t><"F"p>',
      ajax: {
          url: environment.auth_url + this.eventListAuthPrefix() + "/get_event_list?date=" + moment(this.param?.date).format('YYYY-MM-DD'),
          type: "GET",
          headers: {
            "Authorization": "Bearer " + self.user?.token
          },
          dataSrc:function(a:any){
            table.processing(false);

            return a.data
          },
      },
      error: function(err:any) {
          console.error("Error fetching data", err);
      },
      columns: [
          { data: "event_name" },
          // { data: "booked_date" },
          { data: "status" },
          { data: "action" },
          // { data: "action" }
      ],
      drawCallback: function(settings:any) {
          console.log('Table redrawn');
          jQuery('ion-icon[name="eye"]').on('click',function(e:any){
            self.nav.navigateForward('event-details',{state:{id:e.target.id}})
          })
          jQuery('ion-icon[name="create"]').on('click',function(e:any){
            self.nav.navigateForward('add-events',{state:{id:e.target.id}})
          })
        }
    });    
  }
}
