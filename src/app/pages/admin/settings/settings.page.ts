import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-settings',
  templateUrl: './settings.page.html',
  styleUrls: ['./settings.page.scss'],
  standalone: false
})
export class SettingsPage implements OnInit {
  params: any = { title: 'Settings', is_back: true };
  user: any;
  mapForm: FormGroup = new FormGroup({});
  dwollaBalanceAlertForm: FormGroup = new FormGroup({});
  segmentValue: any = 'mapkey';
  quickbooksSetup: any;
  isQuickbooksLoading: boolean = false;
  quickbooksActionLoading: string | null = null;
  map_validation_messages: any = {
    public_key: [
      { type: 'required', message: 'Map API key is required' },
      { type: 'pattern', message: 'Please enter a valid Map API key ' }
    ],
    zoom: [
      { type: 'required', message: 'Zoom is required' },
      { type: 'pattern', message: 'Please enter a valid zoom value' }
    ]
  };
  dwolla_balance_alert_validation_messages: any = {
    fixed_floor_amount: [
      { type: 'min', message: 'Bottom line limit cannot be negative' },
      { type: 'pattern', message: 'Please enter a valid amount' }
    ],
  }

  constructor(
    public common: CommonService,
    public router: Router,
    public apiService: ApiService,
    public nav: NavController,
    public event: EventsService,
    public storage: StorageService
  ) { }

  ngOnInit() {
    this.mapForm = new FormGroup({
      'public_key': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'zoom': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
    });
    this.dwollaBalanceAlertForm = new FormGroup({
      'fixed_floor_amount': new FormControl('', Validators.compose([
        Validators.min(0),
        Validators.pattern(/^\d+(\.\d{1,2})?$/)
      ])),
    });
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getSettingData();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  getSettingData() {
    this.common.showLoader();
    this.apiService.getAuthData('admin/get_settings', this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.mapForm.patchValue(res?.data?.map);
      this.dwollaBalanceAlertForm.patchValue(res?.data?.dwolla_balance_alert);
      this.getQuickbooksSetup();
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err)
      this.common.presentAlert(err?.error?.message);
    })
  }
  mapFormSubmit(formValue: any) {
    let map: any = {};
    map.map = JSON.stringify(formValue);
    this.callAPI(map);
  }
  dwollaBalanceAlertFormSubmit(formValue: any) {
    const fixedFloorAmount = formValue.fixed_floor_amount;
    let dwollaBalanceAlert: any = {};
    dwollaBalanceAlert.dwolla_balance_alert = JSON.stringify({
      fixed_floor_amount: fixedFloorAmount === null || fixedFloorAmount === '' ? null : Number(fixedFloorAmount).toFixed(2)
    });
    this.callAPI(dwollaBalanceAlert);
  }
  callAPI(data: any) {
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_settings', data, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      console.log('res', res)
      this.common.presentAlertWithPromise('Success', res?.message);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
      this.common.presentAlert(err?.error?.message);
    });
  }

  getQuickbooksSetup(showLoader: boolean = false) {
    this.isQuickbooksLoading = true;
    if (showLoader) this.common.showLoader();

    this.apiService.getAuthData('admin/quickbooks_setup', this.user?.token).subscribe({
      next: (res: any) => {
        if (showLoader) this.common.dismissLoading();
        this.quickbooksSetup = res?.data;
        this.isQuickbooksLoading = false;
      },
      error: (err: any) => {
        if (showLoader) this.common.dismissLoading();
        this.isQuickbooksLoading = false;
        this.common.presentAlert(err?.error?.message || 'Unable to load QuickBooks setup.');
      }
    });
  }

  runQuickbooksAction(step: any) {
    if (!step?.action) return;

    if (step.action === 'connect') {
      this.openQuickbooksAuth();
      return;
    }

    if (!this.quickbooksSetup?.connected) {
      this.common.presentAlert('Please connect to QuickBooks first.');
      return;
    }

    this.quickbooksActionLoading = step.action;
    this.common.showLoader(step.button || 'Syncing QuickBooks...');
    this.apiService.sendAuthData('admin/quickbooks_setup_action', { action: step.action }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.quickbooksActionLoading = null;
        this.quickbooksSetup = res?.data || this.quickbooksSetup;
        this.common.presentToast(res?.message || 'QuickBooks setup updated.');
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.quickbooksActionLoading = null;
        if (err?.error?.data) {
          this.quickbooksSetup = err.error.data;
        }
        this.common.presentAlert(err?.error?.message || 'QuickBooks sync failed.');
      }
    });
  }

  openQuickbooksAuth() {
    const authUrl = this.quickbooksSetup?.auth_url;
    if (!authUrl) {
      this.common.presentAlert('QuickBooks connection URL is not available.');
      return;
    }

    window.open(authUrl, '_blank');
  }

  quickbooksStepClass(step: any): string {
    return step?.done ? 'setup-step--done' : 'setup-step--pending';
  }

  quickbooksStatusLabel(step: any): string {
    if (step?.key === 'connection') {
      return step.done ? 'Connected' : 'Not Connected';
    }

    if (step?.key === 'services') {
      return step.done ? 'All Synced' : 'Pending';
    }

    if (step?.key === 'ready') {
      return step.done ? 'Completed' : 'Pending';
    }

    return step?.done ? 'Synced' : 'Not Synced';
  }

  quickbooksChecklist(): any[] {
    const steps = this.quickbooksSetup?.steps || [];
    const allDone = !!this.quickbooksSetup?.progress?.all_done;
    return [
      ...steps.map((step: any) => ({
        label: step.label,
        done: !!step.done
      })),
      { label: 'All Set!', done: allDone }
    ];
  }
}
