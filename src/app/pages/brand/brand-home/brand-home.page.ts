import { ChangeDetectorRef, Component, OnInit, ViewChild } from '@angular/core';
import { FullCalendarComponent } from '@fullcalendar/angular';
import { CalendarOptions, DatesSetArg, EventClickArg, EventContentArg, EventInput } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-brand-home',
  templateUrl: './brand-home.page.html',
  styleUrls: ['./brand-home.page.scss'],
})
export class BrandHomePage implements OnInit {
  @ViewChild('calendar') calendarComponent!: FullCalendarComponent;
  showCalendar: boolean = false;
  params: any = { title: 'Home', is_back: false };
  calendarOptions: CalendarOptions = {
    initialView: 'dayGridMonth',
    plugins: [dayGridPlugin],
    buttonText: {
      today: 'Today',
      month: 'Month',
      week: 'Week',
      day: 'Day',
      year: 'Year',
    },
    events: [],
    datesSet: this.handleEvents.bind(this),
    eventContent: this.handleEventContent.bind(this),
    eventClick: this.handleEventClick.bind(this)
  };
  isOpen: boolean = false;
  allEvents: any = [];
  events: EventInput[] = [];
  selectedEvent: any;
  user: any;
  isSpinner: boolean = false;
  constructor(
    public apiService: ApiService,
    public storage: StorageService,
    public common: CommonService,
    public nav: NavController,
    public cdrf: ChangeDetectorRef,
  ) { }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.showCalendar = true;
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  getEvents(month: any) {
    this.events = [];
    this.isSpinner = true;
    this.apiService.getAuthData('brand/get_events?start_date=' + (month == 0 ? 12 : month), this.user?.token).subscribe((res: any) => {
      this.isSpinner = false;
      this.allEvents = res?.data;
      if (res?.data?.length > 0) {
        res?.data?.map((element: any, key: any) => {
          const newEvent: EventInput = {
            title: element?.date,
            start: new Date(element?.date),
          };
          this.addEvent(newEvent);
          this.cdrf.detectChanges()
        })
      } else {
        this.events = [];
        this.common.presentToast(res?.message)
      }
    }, err => {
      console.log('error', err);
      this.isSpinner = false;
    });
  }
  addEvent(newEvent: EventInput) {
    this.events = [...this.events, newEvent]; // Add new event to the array
    this.calendarOptions = {
      ...this.calendarOptions, // Keep existing options
      events: this.events // Update the events source
    };
  }
  closeModal() {
    this.isOpen = false;
    this.cdrf.detectChanges();
  }
  handleEvents(dateInfo: DatesSetArg) {
    let data = this.calendarComponent.getApi().getCurrentData();
    const monthName = moment('01 ' + data.viewTitle).format('YYYY-MM-DD');
    this.getEvents(monthName)
  }
  handleEventContent(info: EventContentArg) {
    let data = this.allEvents.find((x: any) => x.date === info.event.title)
    let html: any = '';
    if (data) {
      html = { html: '<ion-badge color="danger">' + data?.count + '</ion-badge>' }
    }
    return html;
  }
  ionViewWillLeave() {
    this.showCalendar = false;
  }
   handleEventClick(info : EventClickArg){
    info.jsEvent.preventDefault(); 
    this.nav.navigateForward('/admin-event-date-wise-list',{state:{date:info.event.start}})
  }

}
