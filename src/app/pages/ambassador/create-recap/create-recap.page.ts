import { ChangeDetectorRef, Component, NgZone, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter } from 'src/app/utils/api-response.util';

declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-create-recap',
  templateUrl: './create-recap.page.html',
  styleUrls: ['./create-recap.page.scss'],
})
export class CreateRecapPage implements OnInit {
  params: any = { title: 'Create Recap', is_back: false };
  user: any;
  isSpinner: boolean = false;
  data: any;
  filterEvents: any = [];
  events: any = [];
  type: any = '';
  tableVisible = true;

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
    private cdr: ChangeDetectorRef,
    private ngZone: NgZone,
  ) { }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.params.is_back = !this.router.url.includes('/tabs/ambassador-create-recap');
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
    const selector = '#ambassador_create_recap';
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

  getEventsData() {
    if (!this.user?.token || !document.getElementById('ambassador_create_recap')) {
      return;
    }

    this.destroyDataTable();
    this.isSpinner = true;
    const self = this;

    const table = jQuery('#ambassador_create_recap').DataTable({
      dom: 'rtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      destroy: true,
      autoWidth: false,

      ajax: {
        url: environment.auth_url + 'ambassador/get_created_recap',
        type: 'GET',
        headers: {
          Authorization: 'Bearer ' + self.user?.token,
        },
        data: function (d: any) {
          if (d.search.value === '') {
            d.search.value = self.type;
          }
          return d;
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          // Populate the filter dropdown from the dedicated `events` field
          // (all eligible events, not just the current page slice).
          // Run inside NgZone so Angular detects the change and shows the dropdown.
          if (a?.is_searched === null || a?.is_searched === '') {
            self.ngZone.run(() => {
              self.events = Array.isArray(a.events) ? a.events : [];
            });
          }
          table.processing(false);
          self.isSpinner = false;
          return a.data;
        },
      },
      error: function (err: any) {
        self.isSpinner = false;
        console.error('Error fetching recap data', err);
      },
      columns: [
        { data: 'event_name' },
        { data: 'city' },
        { data: 'action' },
      ],
      drawCallback: function () {
        const $table = jQuery('#ambassador_create_recap');
        $table.off('click', '.btn-warning');
        // Use jQuery(this) to reference the delegated host element so that
        // Ionic shadow-DOM re-targeting doesn't give us an empty e.target.id.
        $table.on('click', '.btn-warning', function (this: any) {
          const eventId = jQuery(this).attr('data-event-id') || jQuery(this).attr('id');
          self.ngZone.run(() => self.completeIt(eventId));
        });
      },
    });
  }

  ionViewWillLeave() {
    this.destroyDataTable();
  }

  getStatusWise(event: any) {
    this.refreshTable();
  }

  async completeIt(event_id: any) {
    this.router.navigate(['/admin-add-recap'], { state: { id: event_id, type: 'complete' } });
  }
}
