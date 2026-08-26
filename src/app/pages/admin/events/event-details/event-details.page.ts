import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ModalController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { Browser } from '@capacitor/browser';
import { CheckInModalComponent } from 'src/app/components/check-in-modal/check-in-modal.component';

@Component({
  standalone: false,
  selector: 'app-event-details',
  templateUrl: './event-details.page.html',
  styleUrls: ['./event-details.page.scss'],
})
export class EventDetailsPage implements OnInit {
  params: any = { title: 'Event Detail', is_back: true };
  data: any;
  user: any;
  eventDetail: any;
  base_url: any;

  constructor(
    public router: Router,
    public apiService: ApiService,
    public common: CommonService,
    public storage: StorageService,
    private modalCtrl: ModalController,
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.data = this.router.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.base_url = environment.base_url;
        if (this.data) {
          this.get_pay_forecast_detail();
        }
      }
    });
  }

  get_pay_forecast_detail() {
    const roleId = Number(this.user?.role_id);
    const detailsPath =
      roleId === 1 || roleId === 6
        ? `admin/get_event_details?id=${this.data?.id}`
        : `ambassador/get_event_details?id=${this.data?.id}`;
    this.apiService.getAuthData(detailsPath, this.user?.token).subscribe({
      next: (res: any) => {
        this.eventDetail = res?.data;
        if (this.data?.openCheckIn && this.canShowCheckIn()) {
          void this.openCheckInModal();
        }
      },
      error: (err) => {
        console.log(err);
      },
    });
  }

  async view(url: any) {
    await Browser.open({ url: this.base_url + url });
  }

  get eventId(): string | number | null {
    return this.eventDetail?.event?.id ?? this.data?.id ?? null;
  }

  get isAmbassador(): boolean {
    return Number(this.user?.role_id) === 5;
  }

  getStatusLabel(status: string | number): string {
    if (status == '1') {
      return 'Completed';
    }
    if (status == '2') {
      return 'Assigned';
    }
    return 'Pending';
  }

  getStatusClass(status: string | number): string {
    if (status == '1') {
      return 'status-pill--success';
    }
    if (status == '2') {
      return 'status-pill--primary';
    }
    return 'status-pill--warning';
  }

  getPaymentLabel(type: string | number): string {
    const map: Record<string, string> = {
      '1': 'Cash',
      '2': 'Check',
      '3': 'Credit Card',
      '4': 'Debit Card',
    };
    return map[String(type)] ?? '-';
  }

  parseBookedBy(value: any): string {
    if (!value) return '—';
    try {
      const parsed = JSON.parse(value);
      if (Array.isArray(parsed)) return parsed.join(', ');
      return String(parsed);
    } catch {
      return String(value);
    }
  }

  canShowCheckIn(): boolean {
    return (
      Number(this.user?.role_id) === 5 &&
      this.eventDetail?.event?.event_status == '2'
    );
  }

  async openCheckInModal() {
    if (!this.data?.id || !this.user) {
      return;
    }

    const modal = await this.modalCtrl.create({
      component: CheckInModalComponent,
      componentProps: {
        eventId: Number(this.data.id),
        user: this.user,
      },
    });

    await modal.present();
    const { data } = await modal.onDidDismiss();
    if (data?.checkedIn) {
      this.get_pay_forecast_detail();
    }
  }
}
