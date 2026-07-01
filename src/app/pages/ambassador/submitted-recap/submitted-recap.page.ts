import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter, resolveDataTableResponse } from 'src/app/utils/api-response.util';

declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-submitted-recap',
  templateUrl: './submitted-recap.page.html',
  styleUrls: ['./submitted-recap.page.scss'],
})
export class SubmittedRecapPage implements OnInit {
  params: any = { title: 'Submitted Recaps', is_back: true };
  user: any;
  isSpinner: boolean = false;
  data: any;
  filterEvents: any = [];
  brands: any = [];
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
    const selector = '#ambassador_submitted_recap';
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
    if (!this.user?.token || !document.getElementById('ambassador_submitted_recap')) {
      return;
    }

    this.destroyDataTable();
    this.isSpinner = true;
    const self = this;

    const table = jQuery('#ambassador_submitted_recap').DataTable({
      dom: 'frtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      destroy: true,
      autoWidth: false,

      ajax: {
        url: environment.auth_url + 'ambassador/get_submitted_recap',
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
          const { payload, rows } = resolveDataTableResponse(a);
          if (payload?.is_searched === null || payload?.is_searched === '') {
            self.brands = payload.brands;
          }
          table.processing(false);
          self.isSpinner = false;
          return rows
        },
      },
      error: function (err: any) {
        self.isSpinner = false;
        console.error('Error fetching submitted recap data', err);
      },
      columns: [
        { data: 'event_name' },
        { data: 'city' },
        { data: 'action' },
      ],
      drawCallback: function () {
        const $table = jQuery('#ambassador_submitted_recap');
        $table.off('click', 'ion-icon[name="eye"]');
        $table.off('click', 'ion-icon[name="create"]');
        $table.on('click', 'ion-icon[name="eye"]', function (e: any) {
          self.nav.navigateForward('pay-forecast-detail', { state: { id: e.target.id } });
        });
        $table.on('click', 'ion-icon[name="create"]', function (e: any) {
          self.nav.navigateForward('ambassador-edit-recap', { state: { id: e.target.id } });
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
}
