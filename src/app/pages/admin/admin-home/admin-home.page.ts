import { CommonModule } from '@angular/common';
import { ChangeDetectorRef, Component, OnInit, signal, ViewChild } from '@angular/core';
import { Router, RouterOutlet } from '@angular/router';
import dayGridPlugin from '@fullcalendar/daygrid';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { Calendar, CalendarOptions, DateSelectArg, DatesSetArg, EventApi, EventClickArg, EventContentArg, EventInput } from '@fullcalendar/core';
import * as moment from 'moment';
import { EventsService } from 'src/app/services/events.service';
import { FullCalendarComponent } from '@fullcalendar/angular';
import { NavController } from '@ionic/angular';

declare var jQuery:any;

@Component({
  standalone: false,
  selector: 'app-admin-home',
  templateUrl: './admin-home.page.html',
  styleUrls: ['./admin-home.page.scss'],
})
export class AdminHomePage implements OnInit {
  @ViewChild('calendar') calendarComponent!: FullCalendarComponent;
  params: any = { title: "Home" };
  isOpen: boolean = false;
  selectedEvent: any;
  ready:boolean=false;
  
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
    datesSet:this.handleEvents.bind(this),
    eventContent: this.handleEventContent.bind(this),
    eventClick:this.handleEventClick.bind(this)
  };
  events: EventInput[] = [];
  allEvents: any = [];
  user: any;
  showCalendar:boolean=false;
  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public cdrf: ChangeDetectorRef,
    public event : EventsService,
    public nav : NavController
  ) { 
    this.event.subscribe('user_login',res=>{
      this.user=res;
    })
  }

  ngOnInit() {
  }

  ionViewWillLeave() {
    this.showCalendar=false;
  }


  ionViewWillEnter() {
    this.events = [];
    this.storage.get("apro_user").then((data) => {
      if (data != null) {
        this.user = data;
        this.showCalendar=true;
      } else {
        this.router.navigate(["/login"]);
      }
    });
  }

  getEvents(month: any) {
    // Clear both the local array AND the calendar display immediately
    this.events = [];
    this.calendarOptions = { ...this.calendarOptions, events: [] };
    this.ready = false;
    this.apiService.getAuthData('admin/get_events?start_date=' + month, this.user?.token).subscribe((res: any) => {
      this.ready = true;
      this.allEvents = res?.data ?? [];
      if (this.allEvents.length > 0) {
        // Use date string directly (not new Date()) to avoid UTC midnight timezone shift
        // where events land on the wrong calendar day for users in negative-offset timezones.
        const newEvents: EventInput[] = this.allEvents.map((element: any) => ({
          title: element?.date,
          start: element?.date,
        }));
        this.events = newEvents;
        this.calendarOptions = { ...this.calendarOptions, events: this.events };
        this.cdrf.detectChanges();
      } else {
        this.common.presentToast(res?.message);
      }
    }, err => {
      this.ready = true;
      console.log(err);
      this.common.dismissLoading();
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
    // dateInfo.start is the first visible day and can fall in the previous month (e.g., last week
    // of May when showing June). Adding 15 days reliably lands us in the displayed month.
    const startDate = moment(dateInfo.start).add(15, 'days').startOf('month').format('YYYY-MM-DD');
    this.getEvents(startDate);
  }

  handleEventContent(info : EventContentArg){
    let data=this.allEvents.find((x:any)=>x.date===info.event.title)
    let html:any='';
    if(data){
      html={ html: '<ion-badge color="danger">'+data?.count+'</ion-badge>' }
    } 
    return html
  }

  handleEventClick(info: EventClickArg) {
    info.jsEvent.preventDefault();
    // Use startStr (local ISO date string) instead of the Date object to avoid UTC midnight
    // timezone shift which would cause date-wise-list to query the wrong day.
    this.nav.navigateForward('/admin-event-date-wise-list', { state: { date: info.event.startStr } });
  }
}
