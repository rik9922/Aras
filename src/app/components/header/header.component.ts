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
    platform.domReady().then(() => {
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
    console.log('=== HAMBURGER CLICK ===');
    const menu = this.menuCtrl.get('main-menu');
    console.log('MenuController.get("main-menu"):', menu);
    this.menuCtrl.isEnabled('main-menu').then((isEnabled: boolean) => {
      console.log('isEnabled("main-menu"):', isEnabled);
    });
    this.menuCtrl.isOpen('main-menu').then((isOpen: boolean) => {
      console.log('isOpen("main-menu") before open():', isOpen);
    });
    this.menuCtrl.open('main-menu').then(() => {
      console.log('open("main-menu") resolved successfully');
      this.menuCtrl.isOpen('main-menu').then((isOpen: boolean) => {
        console.log('isOpen("main-menu") after open():', isOpen);
      });
    }).catch((err: any) => {
      console.error('open("main-menu") rejected:', err);
    });
  }

  goBack() {
    this.nav.back();
  }
}

