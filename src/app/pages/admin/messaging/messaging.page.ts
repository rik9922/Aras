import { Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import Quill from 'quill';
import hljs from 'highlight.js';

@Component({
  standalone: false,
  selector: 'app-messaging',
  templateUrl: './messaging.page.html',
  styleUrls: ['./messaging.page.scss'],
})
export class MessagingPage implements OnInit {
  params: any = { title: 'Messaging', is_back: true };
  markets: any = []
  user: any;
  messageForm!:FormGroup
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public fb: FormBuilder
  ) { }

  ngOnInit() {
    this.messageForm= new FormGroup({
      'market_id' : new FormControl('',Validators.compose([Validators.required])),
      'contents': this.fb.array([]),
    });
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getMarkets()
        // const quill = new Quill('#editor', {
        //   modules: {
        //     syntax: {
        //       hljs, // Pass the imported highlight.js instance
        //     },
        //     toolbar: [
        //       ['bold', 'italic', 'underline'],
        //     ]
        //   },
        //   // ... other Quill options
        // });
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }

  get getPreview(): FormArray {
    return (<FormArray>this.messageForm.get('contents'));
  }

  private previews_form(val:any): FormGroup {
    return this.fb.group({
      'market': new FormControl(val, Validators.compose([Validators.required,])),
      'content': new FormControl('', Validators.compose([Validators.required,])),
    })
  } 

  onSelect(event:any){
    if(event?.detail?.value?.length>0){
      var self=this
      event?.detail?.value.map((val:any)=>{
         self.getPreview.push(self.previews_form(val))
      })
    }
  }

  getMarkets() {
    // this.common.showLoader();
    this.apiService.getAuthData('admin/get_markets', this.user?.token).subscribe((res: any) => {
      // this.common.dismissLoading();
      this.markets = res;
    }, err => {
      console.log('err', err);
      // this.common.dismissLoading();
    });
  }

  onSubmit(){
    console.log(this.messageForm.value)
    this.common.showLoader();
    var formdata=this.messageForm.value
    this.apiService.sendAuthData('admin/send_message',formdata ,this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.messageForm.reset()
      this.common.presentAlert(res?.message)
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
    });
  }
}
