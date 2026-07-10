import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

declare var google: any;
@Component({
  selector: 'app-add-market',
  templateUrl: './add-market.page.html',
  styleUrls: ['./add-market.page.scss'],
  standalone: false
})
export class AddMarketPage implements OnInit {
  data: any;
  user: any;
  marketForm: FormGroup = new FormGroup({});
  params: any = { title: 'Add Market', is_back: true };
  autocompleteItems: any = [];
  acService = new google.maps.places.AutocompleteService();
  validation_messages: any = {
    name: [
      { type: 'required', message: 'Market name is required' },
      { type: 'pattern', message: 'Please enter a valid name' },
    ],
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Please enter a valid email address." }
    ],
    description: [
      { type: 'required', message: 'Description is required' }
    ],
    region: [
      { type: 'required', message: 'Division is required' }
    ],
    status: [
      { type: 'required', message: 'Status is required' }
    ]

  };
  constructor(
    public common: CommonService,
    public storage: StorageService,
    public apiService: ApiService,
    public router: Router,
    public nav: NavController,
    public event: EventsService
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
    this.marketForm = new FormGroup({
      'name': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      // 'email': new FormControl('', Validators.compose([
      //   Validators.required,
      //   Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')
      // ])),
      'location': new FormControl(''),
      'description': new FormControl('', Validators.compose([Validators.required])),
      // 'status': new FormControl('', Validators.compose([Validators.required])),
      'region': new FormControl('', Validators.compose([Validators.required])),
      'latitude': new FormControl(''),
      'longitude': new FormControl(''),
    });
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.params.title = 'Update Market';
          this.getMarketById(this.data?.id)
        }
      } else {
        this.nav.navigateRoot('/login');
      };
    });
  }
  onSubmit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data?.id;
    }
    formValue.status = 'active';
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_market', formValue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      this.marketForm.reset();
      let check: any = await this.common.presentAlertWithPromise('Success', res?.message);
      this.event.publish('reload_market', 1);
      if (check) {
        this.router.navigate(['/admin-market-list']);
      }
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
      this.common.presentAlert(err?.error?.message);
    });
  }

  getMarketById(Id: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_market_by_id?id=${Id}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.marketForm.patchValue(res?.data);
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
      this.common.presentAlert(err?.error?.message);
    })
  }
  chooseItem(item: any) {
    let geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      'address': item.description
    }, async (results: any, status: any) => {
      this.marketForm.controls['latitude'].setValue(results[0].geometry.location.lat());
      this.marketForm.controls['longitude'].setValue(results[0].geometry.location.lng());
      this.autocompleteItems = [];
      this.marketForm.controls['location'].setValue(item.description);
    });
  }
  updateSearch(event: any) {
    if (event?.detail?.value == '') {
      this.autocompleteItems = [];
      this.marketForm.controls['latitude'].setValue('');
      this.marketForm.controls['longitude'].setValue('');
      return;
    }
    let self = this;
    let config = {
      input: event?.detail?.value,
      componentRestrictions: { country: ["us"] },
      types: ['address'],
    }
    this.acService.getPlacePredictions(config, function (predictions: any, status: any) {
      self.autocompleteItems = [];
      if (predictions != null) {
        predictions.forEach(function (prediction: any) {
          self.autocompleteItems.push(prediction);
        });
      }
    });
  };
}
