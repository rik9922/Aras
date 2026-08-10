import { Component, OnInit } from '@angular/core';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-more-menu',
  templateUrl: './more-menu.page.html',
  styleUrls: ['./more-menu.page.scss'],
  standalone: false,
})
export class MoreMenuPage implements OnInit {
  user: any;
  is_more: boolean = false;
  is_more_pay: boolean = false;

  constructor(
    private storageService: StorageService,
    public event: EventsService
  ) { }

  ngOnInit() {
    this.storageService.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
      }
    });
  }

  ionViewWillEnter() {
    this.storageService.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
      }
    });
  }

  logout() {
    this.event.publish('logout', this.user);
  }

  more() {
    this.is_more = !this.is_more;
  }

  pay() {
    this.is_more_pay = !this.is_more_pay;
  }
}