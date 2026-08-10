import { Component } from '@angular/core';
import { ModalController } from '@ionic/angular';
import { StorageService } from '../services/storage.service';
import { NavController } from '@ionic/angular';
import { EventsService } from '../services/events.service';
import { MoreMenuPage } from '../commonPages/more-menu/more-menu.page';

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
    public modalCtrl: ModalController
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

  presentMoreMenu() {
    this.modalCtrl.create({
      component: MoreMenuPage,
      cssClass: 'more-menu-modal',
      breakpoints: [0, 0.5, 0.75, 1],
      initialBreak: 0.75,
      backdropDismiss: false,
    }).then(modal => modal.present());
  }
}
