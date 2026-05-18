import { Component, OnInit } from '@angular/core';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-more',
  templateUrl: './more.page.html',
  styleUrls: ['./more.page.scss'],
})
export class MorePage implements OnInit {
  params: any = { title: "More" };
  user: any;
  is_more: boolean = false;
  is_more_pay: boolean = false;
  constructor(
    public storageService: StorageService,
    public event: EventsService
  ) { }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.storageService.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
      } else {
        // this.router.navigate(['/login']);
      }
    })
  }

  logout() {
    this.event.publish('logout', this.user)
  }

  more() {
    this.is_more = !this.is_more
  }

  pay() {
    this.is_more_pay = !this.is_more_pay
  }
}
