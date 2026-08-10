import { Component } from '@angular/core';
import { MenuController } from '@ionic/angular';
import { StorageService } from '../services/storage.service';
import { NavController } from '@ionic/angular';
import { EventsService } from '../services/events.service';

@Component({
  selector: 'app-tabs',
  templateUrl: 'tabs.page.html',
  styleUrls: ['tabs.page.scss'],
  standalone: false,
})
export class TabsPage {
  user:any;
  constructor(
    public storage : StorageService,
    public nav : NavController,
    public event:EventsService,
    public menuCtrl: MenuController
  ) {
    this.event.subscribe('user_login',user=>{
      this.user = user;
    })
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data) => {
      if (data) {
        this.user = data;
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
 
  logout() {
    this.event.publish('logout', this.user)
  }

  openMenu() {
    this.menuCtrl.open('main-menu');
  }
}
