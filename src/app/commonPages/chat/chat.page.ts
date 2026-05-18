import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { getDatabase, onChildAdded, push, ref, set, query, limitToLast, onValue, onDisconnect, serverTimestamp } from "firebase/database";
import * as moment from 'moment';
import { IonContent, NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
declare var jQuery: any;
@Component({
  selector: 'app-chat',
  templateUrl: './chat.page.html',
  styleUrls: ['./chat.page.scss'],
  standalone: false,
})
export class ChatPage implements OnInit {
  @ViewChild(IonContent) content!: IonContent;
  params: any = { title: 'Chat', is_back: true }
  message: string = '';
  isSpinner: boolean = false;
  messages: any[] = [];
  user: any;
  node: string = '';
  dbRef: any;
  receiverId: any;
  receiver_data: any;
  receiverOnlineStatus: boolean = false;
  formData = new FormData
  constructor(
    public storage: StorageService,
    public router: Router,
    public common: CommonService,
    public nav: NavController,
    public apiService: ApiService
  ) { }

  ngOnInit() {
    this.setUserStatus(false);
  }



  async ionViewWillEnter() {
    this.message = '';
    this.messages = [];
    this.isSpinner = true;
    this.receiver_data = await this.storage.get('apro_chat');
    console.log(this.receiver_data)
    this.receiverId = this.receiver_data?.id;
    this.params.title = this.receiver_data?.first_name + ' ' + this.receiver_data?.last_name
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;

        this.setUserStatus(true);
        if (this.user.role_id === 5) {
          this.node = `/chat/user_${this.user.id}_${this.receiverId}/`;
        } else {
          this.node = `/chat/user_${this.receiverId}_${this.user.id}/`;
        }
        console.log(this.node)
        var self = this;
        setTimeout(function () {
          self.isSpinner = false;
          console.log("self.isSpinner", self.isSpinner);
          self.scrollToBottomManually();
        }, 1000);
        this.getMessage();
        this.monitorReceiverStatus();
      };
    });
  }
  setUserStatus(isOnline: boolean) {
    const db = getDatabase();
    if (this.user?.id) {
      const userStatusRef = ref(db, `/status/${this.user.id}`);
      if (isOnline) {
        onDisconnect(userStatusRef).set({
          isOnline: false,
          last_changed: serverTimestamp()
        });
        set(userStatusRef, {
          isOnline: true,
          last_changed: serverTimestamp()
        });
      } else {
        set(userStatusRef, {
          isOnline: false,
          last_changed: serverTimestamp()
        });
      }
    }
  }
  monitorReceiverStatus() {
    const db = getDatabase();
    const receiverStatusRef = ref(db, `/status/${this.receiverId}`);
    onValue(receiverStatusRef, (snapshot) => {
      const statusData = snapshot.val();
      console.log(statusData)
      if (statusData) {
        this.receiverOnlineStatus = statusData.isOnline;
      } else {
        this.receiverOnlineStatus = false;
      }
    });
  }
  scrollToBottomManually() {
    setTimeout(() => {
      if (this.content) {
        this.content.scrollToBottom(300);
      }
    }, 50);
  }

  sendMessage() {
    if (!this.message.trim()) return;
    let db = getDatabase();
    let data = {
      sender: this.user?.id,
      sender_name: this.user.first_name,
      type: 'message',
      receiver_id: this.receiverId,
      is_read: false,
      is_deleted: false,
      message: this.message,
      time: moment().valueOf(),
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
    };
    let reference = ref(db, this.node);
    push(reference, data);
    this.message = '';
  }


  getMessage() {
    const db = getDatabase();
    this.dbRef = ref(db, this.node);
    const queryRef = query(this.dbRef, limitToLast(20));
    onChildAdded(queryRef, (snapshot: any) => {
      this.isSpinner = false;
      const messageData = snapshot.val();
      if (messageData) {
        const exists = this.messages.some(message => message.time === messageData.time);
        if (!exists) {
          messageData.file_type = (typeof messageData.message === 'string')
            ? /\.(jpeg|jpg|gif|png)$/i.test(messageData.message)
            : false;
          this.messages.push(messageData);
        }
        this.messages.sort((a: any, b: any) => a.time - b.time);
        setTimeout(() => {
          this.scrollToBottomManually();
        }, 100);
      }
    }, (error: any) => {
      console.error('Error fetching messages:', error);
    });
  }

  ionViewWillLeave() {
    this.storage.remove('apro_chat');
    this.setUserStatus(false);
  }

  select_image() {
    jQuery('input[name="chat_file"]').trigger('click');
  }

  onChange(event: any) {
    if (event?.target?.files?.length > 0) {
      this.formData.set('picture', event?.target?.files[0]);
      this.common.showLoader();
      this.apiService.sendAuthData('chatFileUpload', this.formData, this.user?.token).subscribe((res: any) => {
        this.common.dismissLoading()
        let db = getDatabase();
        let data = {
          sender: this.user?.id,
          sender_name: this.user.first_name,
          type: 'file',
          receiver_id: this.receiver_data?.id,
          is_read: false,
          is_deleted: false,
          message: res?.data,
          time: moment().valueOf(),
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        };

        let reference = ref(db, this.node);
        push(reference, data);
        console.log(data)
        var self = this;
        setTimeout(function () {
          self.message = '';
        }, 500)
      }, err => {
        console.log(err)
        this.common.dismissLoading()
      })
    }
  }
}

