import { ChangeDetectorRef, Component, Input, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ModalController, NavController, MenuController, Platform } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'appHeader',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss'],
})
export class HeaderComponent implements OnInit {
  @Input() params: any;
  private menuReady = false;
  private pendingOpen = false;
  constructor(
    public nav: NavController,
    public menuCtrl: MenuController,
    private platform: Platform,
  ) {
    platform.ready().then(() => {
      this.menuReady = true;
      if (this.pendingOpen) {
        this.pendingOpen = false;
        this.menuCtrl.open('main-menu');
      }
    });
  }

  ngOnInit() {
  }

  openMenu() {
    if (this.menuReady) {
      this.menuCtrl.open('main-menu');
    } else {
      this.pendingOpen = true;
    }
  }

  goBack() {
    this.nav.back();
  }
}

