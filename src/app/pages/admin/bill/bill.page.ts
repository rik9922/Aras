import { Component, OnInit } from '@angular/core';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

declare var jQuery:any;

@Component({
  standalone:false,
  selector: 'app-bill',
  templateUrl: './bill.page.html',
  styleUrls: ['./bill.page.scss'],
})
export class BillPage implements OnInit {
  params: any = { title: 'Bill', is_back: true };
  user:any
  constructor(
    public storage : StorageService,
    public common : CommonService,
    public apiService : ApiService,
    public nav : NavController
  ) { }

  ngOnInit() {
  }

    ionViewWillEnter() {
      this.storage.get('apro_user').then((data: any) => {
        if (data) {
          this.user = data;
          this.getEventsData();
        } else {
          this.nav.navigateRoot('/login');
        }
      })
    }
    getEventsData() {
      var self=this;
      var table= jQuery('#admin_bill').DataTable({
        dom: 'rtip',
      });
    }
}
