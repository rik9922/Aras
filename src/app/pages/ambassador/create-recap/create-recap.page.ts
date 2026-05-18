import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';

declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-create-recap',
  templateUrl: './create-recap.page.html',
  styleUrls: ['./create-recap.page.scss'],
})
export class CreateRecapPage implements OnInit {
  params: any = { title: 'Create Recaps', is_back: true };
  user: any;
  isSpinner: boolean = false;
  data: any;
  filterEvents: any = [];
  events: any = [];
  type: any = '';
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
  ) { }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.data = [];
    this.filterEvents = [];
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getEventsData();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  getEventsData() {
    this.isSpinner = true;
    var self = this;
    var table = jQuery('#ambassador_create_recap').DataTable({
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
        url: environment.auth_url + "ambassador/get_created_recap",
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        data: function (d: any) {
          console.log(d)
          if (d.search.value === '') {
            d.search.value = self.type;
          }
          return d
        },
        dataSrc: function (a: any) {
          if (a?.is_searched === null || a?.is_searched === '') {
            self.events = a.data;
          }
          table.processing(false);
          return a.data
        },
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "event_name" },
        { data: "city" },
        // { data: "status" },
        { data: "action" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('.btn-warning').on('click', function (e: any) {
          self.completeIt(e.target.id)
        })
      }
    });
  }


  ionViewWillLeave() {
    if (jQuery('#ambassador_create_recap')) {
      jQuery('#ambassador_create_recap').DataTable().destroy();
    }
  }

  getStatusWise(event: any) {
    jQuery('#ambassador_create_recap').DataTable().destroy();
    this.ionViewWillEnter()
  }

  async completeIt(event_id: any) {
    // let alert = await this.alertCtrl.create({
    //   header: 'Complete',
    //   message: 'Submit Recap',
    //   backdropDismiss: false,
    //   buttons: [{
    //     text: 'Yes',
    //     handler: () => {

    //     }
    //   }, {
    //     text: 'No',
    //     handler: () => {

    //     }
    //   }
    //   ]
    // });

    // await alert.present();
      this.router.navigate(['/admin-add-recap'], { state: { id: event_id, type:'complete' } });
  } 

}
