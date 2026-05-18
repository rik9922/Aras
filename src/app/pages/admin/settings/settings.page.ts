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
  stripeForm: FormGroup = new FormGroup({});
  quickbookForm: FormGroup = new FormGroup({});
  segmentValue: any = 'mapkey';
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
  stripe_validation_messages: any = {
    public_key: [
      { type: 'required', message: 'Stripe public key is required' },
      { type: 'pattern', message: 'Please enter a valid stripe public key' }
    ],
    secret_key: [
      { type: 'required', message: 'Stripe secret key is required' },
      { type: 'pattern', message: 'Please enter a valid stripe secret key' }
    ],
    currency: [
      { type: 'required', message: 'Currency is required' },
      { type: 'pattern', message: 'Please enter a valid currency' }
    ]
  }
  quickbook_validation_messages: any = {
    public_key: [
      { type: 'required', message: 'QuickBook public key is required' },
      { type: 'pattern', message: 'Please enter a valid QuickBook public key' }
    ],
    secret_key: [
      { type: 'required', message: 'QuickBook secret key is required' },
      { type: 'pattern', message: 'Please enter a valid QuickBook secret key' }
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
    this.stripeForm = new FormGroup({
      'public_key': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'secret_key': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'currency': new FormControl('USD', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
    });
    this.quickbookForm = new FormGroup({
      'public_key': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'secret_key': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
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
      this.stripeForm.patchValue(res?.data?.stripe);
      this.quickbookForm.patchValue(res?.data?.quickbook);
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
  stripeFormSubmit(formValue: any) {
    let stripe: any = {};
    stripe.stripe = JSON.stringify(formValue);
    this.callAPI(stripe);
  }
  quickbookFormSubmit(formValue: any) {
    let quickbook: any = {};
    quickbook.quickbook = JSON.stringify(formValue);
    this.callAPI(quickbook);
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

}
