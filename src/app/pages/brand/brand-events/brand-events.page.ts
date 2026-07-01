import { Component, OnInit } from '@angular/core';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { AppLauncher } from '@capacitor/app-launcher';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter } from 'src/app/utils/api-response.util';


declare var jQuery: any;
@Component({
  selector: 'app-brand-events',
  templateUrl: './brand-events.page.html',
  styleUrls: ['./brand-events.page.scss'],
  standalone: false
})
export class BrandEventsPage implements OnInit {
  params: any = { title: 'Events', is_back: false };
  user: any;
  isSpinner: boolean = false;
  events: any;
  filterEvents: any = [];
  data: any;
  selectedDate: any;
  extraparams: any = '';
  totalHours: any;
  constructor(
    public nav: NavController,
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService
  ) { }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.data = {};
    this.selectedDate = "";
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getEventsCalendarData();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  getEventsCalendarData(key: any = null) {
    var self = this;
    var table = jQuery('#brand_event').DataTable({
      dom: 'frtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      bDestroy: true,
      bAutoWidth: false,
      bJQueryUI: true,
      searching: false,
      sDom: '<"H"ilfr><"applicationsList"t><"F"p>',
      ajax: {
        url: environment.auth_url + "brand/get_events_calender_data?" + this.extraparams, // Replace with your server-side script URL
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          table.processing(false);
          self.totalHours = a?.totalHours;
          return a.data
        },
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "id" },
        { data: "day" },
        { data: "start_date" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
      }
    });
  }
  ionViewWillLeave() {
    jQuery('#date').val('');
    this.extraparams = '';
    if (jQuery('#brand_event')) {
      jQuery('#brand_event').DataTable().destroy();
    }
  }
  async sendEmail() {
    const emailRecipient = 'bookwoodmans@araspromotions.com';
    const emailSubject = 'For Request Event';
    const emailBody = 'Your email body content here';
    const mailtoUrl = `mailto:${emailRecipient}?subject=${encodeURIComponent(emailSubject)}&body=${encodeURIComponent(emailBody)}`;
    try {
      let canOpen = await AppLauncher.canOpenUrl({ url: mailtoUrl });
      if (canOpen) {
        await AppLauncher.openUrl({ url: mailtoUrl });
      } else {
        this.common.presentAlert('No email application found on your device.');
      }
    } catch (error) {
      console.error('Error opening email app', error);
    }
  }
  onChange(event: any, key: any) {
    jQuery('#brand_event').DataTable().destroy();
    var value = event?.target?.value;
    this.extraparams = '&' + key + '=' + value
    this.ionViewWillEnter();
  }
  onClear() {
    this.extraparams = '';
    jQuery('#date').val('');
    this.ionViewWillEnter();
  }
}
