import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
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
  tableVisible = true;

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
    private cdr: ChangeDetectorRef
  ) { }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.data = [];
    this.filterEvents = [];
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.refreshTable();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  private destroyDataTable(): void {
    const selector = '#ambassador_event';
    if (jQuery.fn.dataTable?.isDataTable?.(selector)) {
      jQuery(selector).DataTable().clear().destroy();
    }
    jQuery(`${selector} tbody`).empty();
  }

  private refreshTableDom(callback: () => void): void {
    this.destroyDataTable();
    this.tableVisible = false;
    this.cdr.detectChanges();
    this.tableVisible = true;
    this.cdr.detectChanges();
    setTimeout(callback, 0);
  }

  private refreshTable(): void {
    this.refreshTableDom(() => this.getEventsData());
  }

  private getColumns(): { data: string }[] {
    if (this.type === 'all') {
      return [
        { data: 'datetime' },
        { data: 'brand' },
        { data: 'varietals' },
        { data: 'kit' },
        { data: 'location' },
        { data: 'city' },
        { data: 'total' },
        { data: 'driver_pay' },
        { data: 'action' },
      ];
    }
    if (this.type === 'completed') {
      return [
        { data: 'date' },
        { data: 'brand' },
        { data: 'location' },
        { data: 'total' },
        { data: 'action' },
      ];
    }
    return [
      { data: 'date' },
      { data: 'time' },
      { data: 'location' },
      { data: 'brand' },
      { data: 'action' },
    ];
  }

  getEventsData() {
    if (!this.user?.token || !document.getElementById('ambassador_event')) {
      return;
    }

    this.destroyDataTable();
    this.isSpinner = true;
    const self = this;
    const columns = this.getColumns();

    const table = jQuery('#ambassador_event').DataTable({
      dom: 'rtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      destroy: true,
      autoWidth: false,
      columnDefs: [
        { responsivePriority: 1, targets: 0 },
        { responsivePriority: 2, targets: -1 }
      ],
      ajax: {
        url: environment.auth_url + 'ambassador/get_events_data?market_id=' + self.market_id,
        type: 'GET',
        headers: {
          Authorization: 'Bearer ' + self.user?.token
        },
        data: function (d: any) {
          if (d.search.value === '') {
            d.search.value = self.type;
            d.search.regex = true;
          }
          d.start_date = self.start_date;
          d.end_date = self.end_date;
          return d;
        },
        dataSrc: function (a: any) {
          table.processing(false);
          self.isSpinner = false;
          self.markets = a.markets;
          return a.data;
        },
      },
      error: function (err: any) {
        self.isSpinner = false;
        console.error('Error fetching data', err);
      },
      columns: columns,
      drawCallback: function () {
        const $table = jQuery('#ambassador_event');
        $table.off('click', 'ion-icon[name="eye"]');
        $table.off('click', 'ion-icon[name="checkmark-circle"]');
        $table.on('click', 'ion-icon[name="eye"]', function (e: any) {
          self.nav.navigateForward('/event-details', { state: { id: e.target.id } });
        });
        $table.on('click', 'ion-icon[name="checkmark-circle"]', function (e: any) {
          self.completeIt(e.target.id);
        });
      }
    });
  }

  routeTo(url: any, data: any) {
    this.router.navigate([url], { state: data });
  }

  ionViewWillLeave() {
    this.destroyDataTable();
  }

  getStatusWise(event: any) {
    const segmentValue = event?.detail?.value;
    const segmentTypes = ['all', 'pending', 'assigned', 'completed'];
    if (segmentValue && segmentTypes.includes(segmentValue)) {
      this.type = segmentValue;
      this.market_id = '';
      this.start_date = '';
      this.end_date = '';
    }
    this.refreshTable();
  }

  market_change() {
    this.refreshTableDom(() => this.getEventsData());
  }

  async completeIt(event_id: any) {
    const alert = await this.alertCtrl.create({
      header: 'Are you sure?',
      message: 'You want to Apply for this Event.',
      backdropDismiss: false,
      buttons: [
        {
          text: 'No',
          handler: () => { }
        },
        {
          text: 'Yes',
          handler: () => {
            this.common.showLoader();
            this.apiService.sendAuthData('ambassador/apply_event', { event_id: event_id }, this.user?.token).subscribe({
              next: () => {
                this.common.dismissLoading();
                this.refreshTable();
              },
              error: (err) => {
                this.common.dismissLoading();
                console.log(err);
              }
            });
          }
        }]
    });

    await alert.present();
  }

}
