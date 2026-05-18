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
  selector: 'app-add-location',
  templateUrl: './add-location.page.html',
  styleUrls: ['./add-location.page.scss'],
  standalone: false
})
export class AddLocationPage implements OnInit {
  params: any = { title: 'Add Location', is_back: true };
  user: any;
  addLocationForm: FormGroup = new FormGroup({});
  data: any;
  acService = new google.maps.places.AutocompleteService();
  validation_messages: any = {
    address: [
      { type: 'required', message: 'Address is required' },
      { type: 'pattern', message: 'Please enter a valid address' },
    ],
    city: [
      { type: 'required', message: 'City is required' },
      { type: 'pattern', message: 'Please enter a valid city' },
    ],
    state: [
      { type: 'required', message: 'State is required' },
      { type: 'pattern', message: 'Please enter a valid state' },
    ],
    internal_notes: [
      { type: 'required', message: 'Internal notes is required' },
      { type: 'pattern', message: 'Please enter a valid Internal notes' },
    ]
  };
  autocompleteItems: any = []
  constructor(
    public router: Router,
    public event: EventsService,
    public common: CommonService,
    public storage: StorageService,
    public nav: NavController,
    public apiService: ApiService
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
    this.addLocationForm = new FormGroup({
      'name': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'store_number': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'phone': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'location_payment_type': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'address': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'city': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'state': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'latitude': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'longitude': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'internal_notes': new FormControl('')
    });
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.params.title = 'Update Location';
          this.getLocationById(this.data?.id)
        }
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  getLocationById(Id: any) {
    this.common.showLoader();
    console.log('Id', Id);
    this.apiService.getAuthData(`admin/get_location_by_id?id=${Id}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.addLocationForm.patchValue(res?.data)
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }
  onSubmit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data?.id;
    }
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_location', formValue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      this.addLocationForm.reset();
      let check: any = await this.common.presentAlertWithPromise('Success', res?.message);
      this.event.publish('reload_locations', 1);
      if (check) {
        this.router.navigate(['/admin-location-list']);
      }
      console.log('res', res)
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
      this.addLocationForm.controls['latitude'].setValue(results[0].geometry.location.lat());
      this.addLocationForm.controls['longitude'].setValue(results[0].geometry.location.lng());
      this.autocompleteItems = [];
      this.addLocationForm.controls['address'].setValue(item.description);
    });
  }
  updateSearch(event: any) {
    if (event?.detail?.value == '') {
      this.autocompleteItems = [];
      this.addLocationForm.controls['latitude'].setValue('');
      this.addLocationForm.controls['longitude'].setValue('');
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
  }

}
