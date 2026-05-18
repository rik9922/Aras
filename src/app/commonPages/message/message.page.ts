import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { getDatabase, ref, get, query, update } from "firebase/database";
import { CommonService } from 'src/app/services/common.service';

@Component({
  selector: 'app-message',
  templateUrl: './message.page.html',
  styleUrls: ['./message.page.scss'],
  standalone: false,
})
export class MessagePage implements OnInit {
  params: any = { title: "Message", is_back: true };
  user: any;
  alluser: any;
  is_ready: boolean = false;
  data: any[] = [];
  connected: boolean = false;
  filteredData: any[] = [];
  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public nav: NavController,
    public event: EventsService,
    public router: Router,
    public cd: ChangeDetectorRef,
    public common: CommonService
  ) {
    this.event.subscribe('reload_home', res => {
      this.ionViewWillEnter()
    })
  }

  ngOnInit() { }

  ionViewWillEnter() {
    this.storage.get("apro_user").then((data: any) => {
      if (data) {
        this.user = data;
        this.getUsers();
      }
    });
  }
  getUsers() {
    this.is_ready = false;
    this.apiService.getAuthData('common/get_users', this.user?.token).subscribe(async (res: any) => {
      this.data = res?.data || [];
      console.log(this.data)
      let firebaseTasks: Promise<any>[] = [];

      this.data.forEach((element: any) => {
        let node =
          this.user.role_id == 5
            ? `/chat/user_${this.user?.id}_${element?.id}/`
            : `/chat/user_${element?.id}_${this.user?.id}/`;

        let db = ref(getDatabase(), node);

        let task = get(query(db)).then((snapshot) => {
          if (snapshot.exists()) {
            const messages = snapshot.val() as Record<string, any>;
            const msgKeys = Object.keys(messages);
            const msgArray = msgKeys.map(k => ({ ...messages[k], key: k }));
            msgArray.sort((a, b) => b.time - a.time);

            const lastMsg: any = msgArray[0];
            lastMsg.datetime = new Date(lastMsg.time).toISOString();
            lastMsg.is_received = lastMsg.sender_id !== this.user.id;
            element.message = lastMsg;
          } else {
            element.message = null;
          }
        });

        firebaseTasks.push(task);
      });

      await Promise.all(firebaseTasks);

      this.data.sort((a, b) => {
        return (b.message?.time || 0) - (a.message?.time || 0);
      });
      this.filteredData = [...this.data];

      this.is_ready = true;
      this.cd.detectChanges();

    }, (err) => {
      this.is_ready = true;
      console.log(err);
    });
  }

  goToChat(data: any) {
    if (data?.message && data.message.is_received && data.message.is_read === false) {
      let node =
        this.user.role_id == 5
          ? `/chat/user_${this.user?.id}_${data?.id}/${data.message?.key}`
          : `/chat/user_${data?.id}_${this.user?.id}/${data.message?.key}`;

      const dbRef = ref(getDatabase(), node);

      update(dbRef, { is_read: true }).then(() => {
        data.message.is_read = true;
        this.cd.detectChanges();
      });
    }
    this.storage.set('apro_chat', data);
    this.router.navigate(['/chat']);
  }

}
