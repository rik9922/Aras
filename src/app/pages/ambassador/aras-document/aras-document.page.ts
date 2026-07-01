import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { FileViewer } from "@capacitor/file-viewer";
import { dataTableAjaxDataFilter, resolveDataTableResponse } from 'src/app/utils/api-response.util';

declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-aras-document',
  templateUrl: './aras-document.page.html',
  styleUrls: ['./aras-document.page.scss'],
})
export class ArasDocumentPage implements OnInit {
  params: any = { title: 'Aras Documents', is_back: true };
  user: any;
  isSpinner: boolean = false;
  data: any;
  filterEvents: any = [];
  events: any = [];
  type: any = '';
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public event: EventsService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public alertCtrl: AlertController,
  ) { }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.data = [];
    this.filterEvents = [];
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        // this.getEventsData();
        this.getDocuments();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }

  getDocuments(){
    this.isSpinner = true;
    var self = this;
    var table = jQuery('#ambassador_aras_document').DataTable({
      dom: 'rtip',
      processing: true,
      serverSide: true,
      ordering: false,
      responsive: true,
      bDestroy: true,
      bAutoWidth: false,
      bJQueryUI: true,
      sDom: '<"H"ilfr><"applicationsList"t><"F"p>',

      ajax: {
        url: environment.auth_url + "admin/get_aras_documents",
        type: "GET",
        headers: {
          "Authorization": "Bearer " + self.user?.token
        },
        data: function (d: any) {
        },
        dataFilter: dataTableAjaxDataFilter,

        dataSrc: function (a: any) {
          const { payload, rows } = resolveDataTableResponse(a);
          if (payload?.is_searched === null || payload?.is_searched === '') {
            self.events = rows;
          }
          table.processing(false);
          return rows},
      },
      error: function (err: any) {
        console.error("Error fetching data", err);
      },
      columns: [
        { data: "file_name" },
        { data: "view" },
      ],
      drawCallback: function (settings: any) {
        console.log('Table redrawn');
        jQuery('ion-icon[name="eye"]').on('click', function (e: any) {
          self.view_document(e.target.id)
        })
      }
    });    
  }

  async view_document(url:any){
    await FileViewer.openDocumentFromUrl({
      url:url
    });
  }

  select_file(){
    jQuery('input[name="document"]').trigger('click');
  }

  onChange(event:any){
    console.log(event.target.files);
    if(event.target.files?.length>0){
      this.common.showLoader();
      var formdata=new FormData();
      formdata.append('document',event.target.files[0]);
      this.apiService.sendAuthData('admin/upload_aras_document',formdata,this.user?.token).subscribe((res:any)=>{
        this.common.dismissLoading()
        this.common.presentAlert(res?.message)
        this.getDocuments()
      },err=>{
        this.common.dismissLoading()
        this.common.presentAlert(err?.error?.message)
      })
    }
  }






  // getEventsData() {
  //   this.isSpinner = true;
  //   var self = this;
  //   var table = jQuery('#ambassador_aras_document').DataTable({
  //     dom: 'rtip',
  //     processing: true,
  //     serverSide: true,
  //     ordering: false,
  //     responsive: true,
  //     bDestroy: true,
  //     bAutoWidth: false,
  //     bJQueryUI: true,
  //     sDom: '<"H"ilfr><"applicationsList"t><"F"p>',

  //     ajax: {
  //       url: environment.auth_url + "ambassador/get_created_recap",
  //       type: "GET",
  //       headers: {
  //         "Authorization": "Bearer " + self.user?.token
  //       },
  //       data: function (d: any) {
  //         if (d.search.value === '') {
  //           d.search.value = self.type;
  //         }
  //         return d
  //       },
  //       dataSrc: function (a: any) {
  //         if (a?.is_searched === null || a?.is_searched === '') {
  //           self.events = a.data;
  //         }
  //         table.processing(false);
  //         return a.data
  //       },
  //     },
  //     error: function (err: any) {
  //       console.error("Error fetching data", err);
  //     },
  //     columns: [
  //       { data: "event_name" },
  //       { data: "city" },
  //       // { data: "status" },
  //       { data: "action" },
  //     ],
  //     drawCallback: function (settings: any) {
  //       console.log('Table redrawn');
  //       jQuery('.btn-warning').on('click', function (e: any) {
  //         self.completeIt(e.target.id)
  //       })
  //     }
  //   });
  // }


  // ionViewWillLeave() {
  //   if (jQuery('#ambassador_aras_document')) {
  //     jQuery('#ambassador_aras_document').DataTable().destroy();
  //   }
  // }

  // getStatusWise(event: any) {
  //   jQuery('#ambassador_aras_document').DataTable().destroy();
  //   this.ionViewWillEnter()
  // }

  // async completeIt(event_id: any) {
  //     this.router.navigate(['/admin-add-recap'], { state: { id: event_id, type:'complete' } });
  //   // let alert = await this.alertCtrl.create({
  //   //   header: 'Complete',
  //   //   message: 'Submit Recap',
  //   //   backdropDismiss: false,
  //   //   buttons: [{
  //   //     text: 'Yes',
  //   //     handler: () => {

  //   //     }
  //   //   }, {
  //   //     text: 'No',
  //   //     handler: () => {

  //   //     }
  //   //   }
  //   //   ]
  //   // });

  //   // await alert.present();
  // }


}
