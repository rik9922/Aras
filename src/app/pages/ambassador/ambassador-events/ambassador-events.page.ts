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
  selector: 'app-ambassador-events',
  templateUrl: './ambassador-events.page.html',
  styleUrls: ['./ambassador-events.page.scss'],
  standalone: false
})
export class AmbassadorEventsPage implements OnInit {
  params: any = { title: 'Events', is_back: false };
  user: any;
  isSpinner: boolean = false;
  data: any;
  filterEvents: any = [];
  type: any = 'all';
  markets: any = [];
  market_id: any = '';
  start_date: any = '';
  end_date: any = '';
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController
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
    let columns;
    if (self.type === 'all') {
      columns = [
        { data: "datetime" },
        // { data: "duration" },
        { data: "brand" },
        { data: "varietals" },
        { data: "kit" },
        { data: "location" },
        { data: "city" },
        { data: "total" },
        { data: "driver_pay" },
        { data: "action" },
      ]
    } else if (self.type === 'completed') {
      columns = [
        { data: "date" },
        { data: "brand" },
        { data: "location" },
        { data: "total" },
        { data: "action" },
      ]
    } else {
      columns = [
        { data: "date" },
        { data: "time" },
        { data: "location" },
        { data: "brand" },
        { data: "action" },
      ]
    }
    var table = jQuery('#ambassador_event').DataTable({
      dom: 'rtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      bDestroy: true,
      bAutoWidth: false,
      columnDefs: [
        { responsivePriority: 1, targets: 0 },
        { responsivePriority: 2, targets: -1 }
      ],
      bJQueryUI: true,
      sDom: '<"H"ilfr><"applicationsList"t><"F"p>',

      ajax: {
        url: environment.auth_url + "ambassador/get_events_data?market_id=" + self.market_id,
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        data: function (d: any) {
          if (d.search.value === '') {
            d.search.value = self.type;
            d.search.regex = true;
          }
          d.start_date = self.start_date
          d.end_date = self.end_date
          return d
          return d
        },
        dataSrc: function (a: any) {
          table.processing(false);
          self.markets = a.markets;
          return a.data
        },
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: columns,
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="eye"]').on('click', function (e: any) {
          self.nav.navigateForward('event-details', { state: { id: e.target.id } })
        })
        jQuery('ion-icon[name="checkmark-circle"]').on('click', function (e: any) {
          self.completeIt(e.target.id)
        })
      }
    });

  }
  routeTo(url: any, data: any) {
    this.router.navigate([url], { state: data });
  }

  ionViewWillLeave() {
    if (jQuery('#ambassador_event')) {
      jQuery('#ambassador_event').DataTable().destroy();
    }
  }

  getStatusWise(event: any) {
    jQuery('#ambassador_event').DataTable().destroy();
    jQuery('#ambassador_event tbody').empty();
    var self = this;
    this.market_id = '';
    setTimeout(function () {
      self.getEventsData()
    }, 100)
  }

  market_change() {
    jQuery('#ambassador_event').DataTable().destroy();
    jQuery('#ambassador_event tbody').empty();
    var self = this;
    setTimeout(function () {
      self.getEventsData()
    }, 100)
  }

  async completeIt(event_id: any) {
    let alert = await this.alertCtrl.create({
      header: 'Are you sure?',
      message: 'You want to Apply for this Event.',
      backdropDismiss: false,
      buttons: [
        {
          text: 'No',
          handler: () => {
          }
        },
        {
          text: 'Yes',
          handler: () => {
            this.common.showLoader();
            this.apiService.sendAuthData('ambassador/apply_event', { event_id: event_id }, this.user?.token).subscribe(res => {
              this.common.dismissLoading()
              jQuery('#ambassador_event').DataTable().destroy();
              this.ionViewWillEnter()
            }, err => {
              this.common.dismissLoading()
              console.log(err);
            })
          }
        }]
    });

    await alert.present();
  }

}
