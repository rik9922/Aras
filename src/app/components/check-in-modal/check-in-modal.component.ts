import { Component, Input, OnInit } from '@angular/core';
import { ModalController } from '@ionic/angular';
import { CheckInService } from 'src/app/services/check-in.service';
import { CommonService } from 'src/app/services/common.service';

@Component({
  selector: 'app-check-in-modal',
  templateUrl: './check-in-modal.component.html',
  styleUrls: ['./check-in-modal.component.scss'],
  standalone: false,
})
export class CheckInModalComponent implements OnInit {
  @Input() eventId!: number;
  @Input() user: any;

  eventDetail: any;
  loading = true;
  checkingIn = false;
  isCheckedIn = false;

  constructor(
    private readonly modalCtrl: ModalController,
    private readonly checkInService: CheckInService,
    private readonly common: CommonService
  ) {}

  ngOnInit() {
    this.loadEvent();
  }

  loadEvent() {
    if (!this.eventId || !this.user?.token) {
      this.loading = false;
      return;
    }

    this.loading = true;
    this.checkInService.loadEventDetails(this.eventId, this.user.token).subscribe({
      next: (res: any) => {
        this.eventDetail = res?.data;
        this.isCheckedIn = this.eventDetail?.event?.is_checked_in == 1;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.common.presentToast('Unable to load event details.');
      },
    });
  }

  get locationLabel(): string {
    return this.checkInService.getLocationLabel(this.eventDetail);
  }

  async confirmCheckIn() {
    if (this.checkingIn || this.isCheckedIn) {
      return;
    }

    this.checkingIn = true;
    const result = await this.checkInService.performCheckIn(
      this.eventId,
      this.user.token,
      this.eventDetail,
      false
    );
    this.checkingIn = false;

    if (result.success) {
      this.isCheckedIn = true;
      this.common.presentToast(result.message || 'Checked in successfully.');
      this.modalCtrl.dismiss({ checkedIn: true }, 'confirm');
    } else if (result.message) {
      this.common.presentToast(result.message);
    }
  }

  viewEventDetails() {
    this.modalCtrl.dismiss({ viewDetails: true }, 'navigate');
  }

  close() {
    this.modalCtrl.dismiss(null, 'cancel');
  }
}
