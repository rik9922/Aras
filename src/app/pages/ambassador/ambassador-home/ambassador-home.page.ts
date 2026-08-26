import { ChangeDetectorRef, Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { FullCalendarComponent } from '@fullcalendar/angular';
import { CalendarOptions, DatesSetArg, EventClickArg, EventContentArg, EventInput } from '@fullcalendar/core';
import { ModalController, NavController } from '@ionic/angular';
import * as moment from 'moment';
import { CheckInModalComponent } from 'src/app/components/check-in-modal/check-in-modal.component';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import dayGridPlugin from '@fullcalendar/daygrid';

@Component({
  standalone: false,
  selector: 'app-ambassador-home',
  templateUrl: './ambassador-home.page.html',
  styleUrls: ['./ambassador-home.page.scss'],
})
export class AmbassadorHomePage implements OnInit {
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
  readyCheckInEvents: any[] = [];
  readyCheckInLoading = false;
  initialLoad = true;
  private initialReadyCheckInDone = false;
  private initialEventsDone = false;
  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public cdrf: ChangeDetectorRef,
    public event : EventsService,
    public nav : NavController,
    private modalCtrl: ModalController,
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
    this.initialLoad = true;
    this.initialReadyCheckInDone = false;
    this.initialEventsDone = false;
    this.ready = false;
    this.storage.get("apro_user").then((data:any) => {
      if (data != null) {
        this.user = data;
        this.showCalendar=true;
        this.loadReadyCheckInEvents();
      } else {
        this.router.navigate(["/login"]);
      }
    });
  }

  private finishInitialLoad() {
    if (this.initialLoad && this.initialReadyCheckInDone && this.initialEventsDone) {
      this.initialLoad = false;
      this.cdrf.detectChanges();
    }
  }

  loadReadyCheckInEvents() {
    if (!this.user?.token) {
      this.initialReadyCheckInDone = true;
      this.finishInitialLoad();
      return;
    }

    this.readyCheckInLoading = true;
    this.apiService.getAuthData('ambassador/get_ready_check_in_events', this.user.token).subscribe({
      next: (res: any) => {
        this.readyCheckInEvents = res?.data ?? [];
        this.readyCheckInLoading = false;
        this.initialReadyCheckInDone = true;
        this.finishInitialLoad();
        this.cdrf.detectChanges();
      },
      error: () => {
        this.readyCheckInEvents = [];
        this.readyCheckInLoading = false;
        this.initialReadyCheckInDone = true;
        this.finishInitialLoad();
        this.cdrf.detectChanges();
      },
    });
  }

  async openCheckInModal(eventId: number) {
    if (!eventId || !this.user) {
      return;
    }

    const modal = await this.modalCtrl.create({
      component: CheckInModalComponent,
      componentProps: {
        eventId: Number(eventId),
        user: this.user,
      },
    });

    await modal.present();
    const { data } = await modal.onDidDismiss();
    if (data?.checkedIn) {
      this.loadReadyCheckInEvents();
    }
  }

  getEvents(month:any) {
    this.events=[];
    this.ready=false;
    this.apiService.getAuthData('ambassador/get_events?start_date='+(month==0 ? 12 : month), this.user?.token).subscribe((res: any) => {
      console.log(res);
      this.ready=true;
      this.initialEventsDone = true;
      this.finishInitialLoad();
      this.allEvents = res?.data;
      if (res?.data?.length > 0) {
        res?.data?.map((element: any, key: any) => {
          // Force a floating all-day event on the exact date the API returns
          // (DATE(start_time), e.g. "2026-08-17"). Using new Date('YYYY-MM-DD')
          // parses as UTC midnight and shifts to the previous day in negative
          // UTC-offset zones (e.g. America/Chicago) — that was the "shows 16th"
          // bug. Passing the raw date string + allDay:true pins it to that day
          // regardless of the device timezone.
          const newEvent: EventInput = {
            title: element?.date,
            start: element?.date,
            allDay: true,
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
      this.initialEventsDone = true;
      this.finishInitialLoad();
      console.log(err);
      this.common.dismissLoading()  
    })
  }

  addEvent(newEvent: EventInput){
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
    // Use startStr (local ISO date string, e.g. '2026-08-17') instead of
    // info.event.start (a Date object). Passing a Date to moment() in
    // date-wise-list converts to local time, shifting the query date by one day
    // on negative-offset timezones. The string is already the correct local day.
    this.nav.navigateForward('/admin-event-date-wise-list',{state:{date:info.event.startStr}})
  }

}
