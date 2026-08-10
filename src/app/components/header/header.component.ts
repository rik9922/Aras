import { ChangeDetectorRef, Component, Input, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ModalController, NavController, MenuController } from '@ionic/angular';
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
  constructor(
    public nav: NavController,
    public menuCtrl: MenuController,
  ) {
  }

  ngOnInit() {
  }

  goBack() {
    this.nav.back();
  }
}

