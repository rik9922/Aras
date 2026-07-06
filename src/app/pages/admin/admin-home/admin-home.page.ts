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

  getEvents(month:any) {
    this.events=[];
    this.ready=false;
    this.apiService.getAuthData('admin/get_events?start_date='+(month==0 ? 12 : month), this.user?.token).subscribe((res: any) => {
      console.log(res);
      this.ready=true;
      this.allEvents = res?.data;
      if (res?.data?.length > 0) {
        res?.data?.map((element: any, key: any) => {
          const newEvent: EventInput = {
            title: element?.date,
            start: new Date(element?.date),
            // id: element?.id,
          };
          this.addEvent(newEvent);
          this.cdrf.detectChanges()
        })
      } else {
        this.common.presentToast(res?.message)
      }
    }, err => {
      this.ready=true;
      console.log(err);
      this.common.dismissLoading()  
    })
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
    let data =this.calendarComponent.getApi().getCurrentData();
    const monthName = moment('01 '+data.viewTitle).format('YYYY-MM-DD');
    this.getEvents(monthName)    
  }

  handleEventContent(info : EventContentArg){
    let data=this.allEvents.find((x:any)=>x.date===info.event.title)
    let html:any='';
    if(data){
      html={ html: '<ion-badge color="danger">'+data?.count+'</ion-badge>' }
    } 
    return html
  }

  handleEventClick(info : EventClickArg){
    info.jsEvent.preventDefault(); 
    this.nav.navigateForward('/admin-event-date-wise-list',{state:{date:info.event.start}})
  }
}
