import { Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import Quill from 'quill';
import hljs from 'highlight.js';
import { getDatabase, push, ref } from 'firebase/database';

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
    const selectedMarkets = (event?.detail?.value || []).map((value: any) => String(value));
    const existingContent = new Map<string, any>();

    this.getPreview.controls.forEach((control: any) => {
      const market = control.get('market')?.value;
      if (market !== undefined && market !== null) {
        existingContent.set(String(market), control.get('content')?.value || '');
      }
    });

    this.getPreview.clear();
    selectedMarkets.forEach((market: string) => {
      const preview = this.previews_form(market);
      preview.get('content')?.setValue(existingContent.get(market) || '');
      this.getPreview.push(preview);
    });
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

  private async bridgeNotificationToChat(formdata: any, response: any): Promise<void> {
    // The notification endpoint is the source of truth. When it returns
    // recipients, mirror the same delivery into the existing chat node so
    // mobile Message/Chat can display it; never invent recipients here.
    const recipients = response?.data?.recipients || response?.recipients;
    if (!Array.isArray(recipients)) return;
    const selected = Array.isArray(formdata?.market_id) ? formdata.market_id.map(Number) : [Number(formdata?.market_id)];
    const db = getDatabase();
    for (const recipient of recipients) {
      if (!recipient?.id) continue;
      const recipientMarkets = Array.isArray(recipient.market_id) ? recipient.market_id.map(Number)
        : String(recipient.market_id || '').split(',').map(Number).filter(Boolean);
      if (!selected.some((id: number) => recipientMarkets.includes(id))) continue;
      const node = ref(db, `/chat/user_${recipient.id}_${this.user?.id}/`);
      const content = (formdata?.contents || []).find((item: any) => Number(item.market) === Number(recipientMarkets.find((id: number) => selected.includes(id))));
      await push(node, { sender: this.user?.id, sender_name: this.user?.first_name, receiver_id: recipient.id, type: 'message', message: content?.content || '', is_read: false, is_deleted: false, time: Date.now(), timezone: Intl.DateTimeFormat().resolvedOptions().timeZone });
    }
  }

  onSubmit(){
    console.log(this.messageForm.value)
    this.common.showLoader();
    var formdata=this.messageForm.value
    this.apiService.sendAuthData('admin/send_message',formdata ,this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      await this.bridgeNotificationToChat(formdata, res);
      this.messageForm.reset()
      this.common.presentAlert(res?.message)
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
    });
  }
}
