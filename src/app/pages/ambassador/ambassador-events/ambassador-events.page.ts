import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, ModalController, NavController } from '@ionic/angular';
import { CheckInModalComponent } from 'src/app/components/check-in-modal/check-in-modal.component';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter, resolveDataTableResponse } from 'src/app/utils/api-response.util';

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

  // ── Card view state (Available + Pending tabs) ──────────────────────────
  cardEvents: any[] = [];
  loadingCards: boolean = false;
  expandedIds: Set<number> = new Set();

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
    private modalCtrl: ModalController,
    private cdr: ChangeDetectorRef
  ) { }

  ngOnInit() { }

  ionViewWillEnter() {
    // [DIAG] Remove these logs once the render issue is confirmed fixed.
    console.log('[AmbassadorEvents] ionViewWillEnter — redesigned template loaded (v2-card)');
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

  /** True when the active tab uses the card layout instead of a DataTable. */
  get isCardTab(): boolean {
    const result = this.type === 'all' || this.type === 'pending';
    // [DIAG] Remove this log once confirmed.
    console.log(`[AmbassadorEvents] isCardTab evaluated — type="${this.type}" → isCardTab=${result}`);
    return result;
  }

  // ── DataTable helpers (Assigned / Completed tabs) ───────────────────────

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
    if (this.isCardTab) {
      // Tear down any live DataTable before switching to the card view.
      this.destroyDataTable();
      this.tableVisible = false;
      this.loadCardEvents();
    } else {
      this.tableVisible = true;
      this.refreshTableDom(() => this.getEventsData());
    }
  }

  // ── Card view methods ───────────────────────────────────────────────────

  /**
   * Fetch events for the Available or Pending card view.
   * Calls the same DataTables-compatible endpoint; no API changes.
   */
  loadCardEvents(): void {
    // [DIAG] Remove this log once confirmed.
    console.log(`[AmbassadorEvents] loadCardEvents() called — type="${this.type}"`);
    this.cardEvents = [];
    this.expandedIds = new Set();
    this.loadingCards = true;

    const qs = [
      'draw=1',
      'start=0',
      'length=1000',
      `search%5Bvalue%5D=${this.type}`,
      'search%5Bregex%5D=true',
      `market_id=${this.market_id}`,
    ].join('&');

    this.apiService
      .getAuthData(`ambassador/get_events_data?${qs}`, this.user?.token)
      .subscribe({
        next: (res: any) => {
          const { payload, rows } = resolveDataTableResponse(res);
          this.markets = payload.markets || [];
          this.cardEvents = rows;
          this.loadingCards = false;
        },
        error: (err: any) => {
          this.loadingCards = false;
          console.error('Card events fetch error', err);
        },
      });
  }

  toggleCard(id: number): void {
    if (this.expandedIds.has(id)) {
      this.expandedIds.delete(id);
    } else {
      this.expandedIds.add(id);
    }
  }

  isCardExpanded(id: number): boolean {
    return this.expandedIds.has(id);
  }

  viewEventDetails(id: any): void {
    this.nav.navigateForward('/event-details', { state: { id } });
  }

  // ── DataTable (Assigned / Completed) ────────────────────────────────────

  private getColumns(): { data: string }[] {
    if (this.type === 'completed') {
      return [
        { data: 'date' },
        { data: 'brand' },
        { data: 'location' },
        { data: 'total' },
        { data: 'action' },
      ];
    }
    // assigned
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
        { responsivePriority: 2, targets: -1 },
      ],
      ajax: {
        url: environment.auth_url + 'ambassador/get_events_data?market_id=' + self.market_id,
        type: 'GET',
        headers: { Authorization: 'Bearer ' + self.user?.token },
        data: function (d: any) {
          if (d.search.value === '') {
            d.search.value = self.type;
            d.search.regex = true;
          }
          d.start_date = self.start_date;
          d.end_date = self.end_date;
          return d;
        },
        dataFilter: dataTableAjaxDataFilter,
        dataSrc: function (a: any) {
          const { payload, rows } = resolveDataTableResponse(a);
          table.processing(false);
          self.isSpinner = false;
          self.markets = payload.markets;
          return rows;
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
        $table.off('click', 'ion-icon.check-in-action');
        $table.on('click', 'ion-icon[name="eye"]', function (e: any) {
          self.nav.navigateForward('/event-details', { state: { id: e.target.id } });
        });
        $table.on('click', 'ion-icon[name="checkmark-circle"]', function (e: any) {
          self.completeIt(e.target.id);
        });
        $table.on('click', 'ion-icon.check-in-action', function (e: any) {
          const eventId = jQuery(e.target).attr('data-event-id') || e.target.id;
          self.openCheckInModal(eventId);
        });
      },
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
    if (this.isCardTab) {
      this.loadCardEvents();
    } else {
      this.refreshTableDom(() => this.getEventsData());
    }
  }

  async openCheckInModal(eventId: any) {
    const id = Number(eventId);
    if (!id || !this.user) return;

    const modal = await this.modalCtrl.create({
      component: CheckInModalComponent,
      componentProps: { eventId: id, user: this.user },
    });

    await modal.present();
    const { data, role } = await modal.onDidDismiss();
    if (data?.checkedIn) this.refreshTable();
    if (data?.viewDetails || role === 'navigate') {
      this.nav.navigateForward('/event-details', { state: { id } });
    }
  }

  /** Unchanged apply-event flow — called by the card Apply button and DataTable click. */
  async completeIt(event_id: any) {
    const alert = await this.alertCtrl.create({
      header: 'Are you sure?',
      message: 'You want to Apply for this Event.',
      backdropDismiss: false,
      buttons: [
        { text: 'No', handler: () => { } },
        {
          text: 'Yes',
          handler: () => {
            this.common.showLoader();
            this.apiService
              .sendAuthData('ambassador/apply_event', { event_id: event_id }, this.user?.token)
              .subscribe({
                next: () => {
                  this.common.dismissLoading();
                  this.refreshTable();
                },
                error: (err) => {
                  this.common.dismissLoading();
                  console.log(err);
                },
              });
          },
        },
      ],
    });

    await alert.present();
  }
}
