import { Component, OnInit } from '@angular/core';
import { AlertController, NavController } from '@ionic/angular';
import { BankingService } from 'src/app/services/banking.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-banking',
  templateUrl: './banking.page.html',
  styleUrls: ['./banking.page.scss'],
})
export class BankingPage implements OnInit {
  params: any = { title: 'Bank Account', is_back: true };

  isLoading = true;
  bankSetupStatus = 'not_started';
  bankSetupStatusLabel = 'Not Started';
  hasDwollaCustomer = false;
  missingProfileFields: string[] = [];
  fundingSources: any[] = [];
  defaultSource: any = null;

  constructor(
    private banking: BankingService,
    private common: CommonService,
    private storage: StorageService,
    private nav: NavController,
    private alertCtrl: AlertController,
  ) {}

  ngOnInit() {}

  ionViewWillEnter() {
    this.loadStatus();
  }

  loadStatus() {
    this.isLoading = true;
    this.banking.getStatus().subscribe({
      next: (res: any) => {
        this.isLoading = false;
        const data = res?.data ?? res?.payload ?? {};
        this.bankSetupStatus      = data.bank_setup_status ?? 'not_started';
        this.bankSetupStatusLabel = data.bank_setup_status_label ?? 'Not Started';
        // Keep the shared-header banner in sync with the latest known status.
        this.banking.markSetupStatus(this.bankSetupStatus);
        this.hasDwollaCustomer    = !!data.has_dwolla_customer;
        this.missingProfileFields = data.missing_profile_fields ?? [];
        this.fundingSources       = (data.funding_sources ?? []).filter((s: any) => s.status !== 'removed');
        this.defaultSource        = data.default_source ?? null;
      },
      error: () => {
        this.isLoading = false;
        this.common.presentAlert('Could not load banking information. Please try again.');
      },
    });
  }

  get statusColor(): string {
    switch (this.bankSetupStatus) {
      case 'verified':              return 'success';
      case 'pending_verification':  return 'warning';
      default:                      return 'medium';
    }
  }

  goAddBank() {
    this.nav.navigateForward('/banking-add');
  }

  goVerify(source: any) {
    this.nav.navigateForward('/banking-verify', { state: { source } });
  }

  async setDefault(source: any) {
    const alert = await this.alertCtrl.create({
      header: 'Set as Default',
      message: `Set ${source.bank_name} (${source.account_number_masked}) as your default bank?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Confirm',
          handler: () => {
            this.banking.setDefault(source.id).subscribe({
              next: () => {
                this.common.presentToast('Default bank account updated.');
                this.loadStatus();
              },
              error: (err: any) => {
                this.common.presentAlert(err?.error?.message ?? 'Could not update default bank.');
              },
            });
          },
        },
      ],
    });
    await alert.present();
  }

  async removeBank(source: any) {
    const alert = await this.alertCtrl.create({
      header: 'Remove Bank Account',
      message: `Remove ${source.bank_name} (${source.account_number_masked})?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Remove',
          role: 'destructive',
          handler: () => {
            this.banking.removeBank(source.id).subscribe({
              next: () => {
                this.common.presentToast('Bank account removed.');
                this.loadStatus();
              },
              error: (err: any) => {
                this.common.presentAlert(err?.error?.message ?? 'Could not remove bank account.');
              },
            });
          },
        },
      ],
    });
    await alert.present();
  }
}
