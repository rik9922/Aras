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
  selector: 'app-add-user-location',
  templateUrl: './add-user-location.page.html',
  styleUrls: ['./add-user-location.page.scss'],
  standalone: false
})
export class AddUserLocationPage implements OnInit {
  autocompleteItems: any = [];
  user: any;
  data: any;
  isSpinner: boolean = false;
  passwordType: string = "password";
  passwordIcon: string = "eye-off";
  states: any = [];
  venues: any = [];
  eventLocations: any = [];
  acService = new google.maps.places.AutocompleteService();
  params: any = { title: 'Add User Location', is_back: true };
  userLocationForm: FormGroup = new FormGroup({});
  validation_messages = {
    first_name: [
      { type: "required", message: "First Name is required." },
      { type: 'pattern', message: 'Please enter a valid first name' }
    ],
    last_name: [
      { type: "required", message: "Last Name is required." },
      { type: 'pattern', message: 'Please enter a valid last name' }
    ],
    name: [
      { type: "required", message: "Name is required." },
      { type: 'pattern', message: 'Please enter a valid name' }
    ],
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Please enter a valid email address." }
    ],
    venue_name: [
      { type: "required", message: "Venue Name is required." },
    ],
    venue_type: [
      { type: "required", message: "Venue Type is required." },
    ],
    event_location_id: [
      { type: "required", message: "Event Location is required." },
    ],
    phone: [
      { type: "required", message: "Phone number is required." },
      { type: 'pattern', message: 'Please enter a valid phone number.' },
      { type: "minlength", message: "Please enter only 10 to 12 digits." },
    ],
    city: [
      { type: "required", message: "City is required." },
      { type: 'pattern', message: 'Please enter a city name' }
    ],
    state_id: [
      { type: "required", message: "State is required." },
    ],
    address: [
      { type: "required", message: "Address is required." },
    ],
    password: [
      { type: "required", message: "Password is required." },
      { type: "minlength", message: "Password must have eight character" },
    ]
  };
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public event: EventsService
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.data = this.router.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
    this.userLocationForm = new FormGroup({
      // 'first_name': new FormControl('', Validators.compose([Validators.required,
      // Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      // 'last_name': new FormControl('', Validators.compose([Validators.required,
      // Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)
      // ])),
      'name': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)
      ])),
      'email': new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')
      ])),
      'phone': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^\d+$/), Validators.minLength(10), Validators.maxLength(12)
      ])),
      'venue_name': new FormControl('', Validators.compose([Validators.required])),
      'venue_type': new FormControl('', Validators.compose([Validators.required])),
      'event_location_id': new FormControl('', Validators.compose([Validators.required])),
      'address': new FormControl('', Validators.compose([Validators.required])),
      'status': new FormControl(1, Validators.compose([Validators.required])),
      'city': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'state_id': new FormControl('', Validators.compose([Validators.required])),
      'latitude': new FormControl('', Validators.compose([Validators.required])),
      'longitude': new FormControl('', Validators.compose([Validators.required])),
      'password': new FormControl(''),
    })
  }
  ionViewWillEnter() {
    this.states = [];
    this.venues = [];
    this.eventLocations = [];
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getData();
        if (this.data?.id) {
          this.params.title = 'Update User Location';
          this.userLocationForm.controls['password'].clearValidators();
          this.getUserDataById(this.data?.id);
        } else {
          this.userLocationForm.controls['password'].setValidators(Validators.compose([Validators.required, Validators.minLength(8)]));
        }
        this.userLocationForm.controls['password'].updateValueAndValidity();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  submit(formValue: any) {
    formValue.role_id = 4;
    if (this.data?.id) {
      formValue.id = this.data?.id;
    }
    if (!formValue.password) {
      delete formValue.password;
    }
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_user_location', formValue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      let check = await this.common.presentAlertWithPromise("Success", res?.message);
      this.event.publish('reload_location_users', 1);
      if (check) {
        this.router.navigate(['/admin-user-location-list']);
      }
    }, err => {
      console.log('err', err)
      this.common.presentAlert(err?.error?.message);
      this.common.dismissLoading();
    })
  }
  getUserDataById(Id: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_user_by_id?id=${Id}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.userLocationForm.patchValue(res?.data);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }
  getData() {
    this.isSpinner = true;
    this.apiService.getAuthData('admin/get_user_location_data', this.user?.token).subscribe((res: any) => {
      this.eventLocations = res?.data?.eventLocations;
      this.states = res?.data?.states;
      this.venues = res?.data?.venues;
      this.isSpinner = false;
    }, err => {
      console.log('err', err);
      this.isSpinner = false;
    })
  }
  hideShowPassword() {
    this.passwordType = this.passwordType === "text" ? "password" : "text";
    this.passwordIcon = this.passwordIcon === "eye-off" ? "eye" : "eye-off";
  }
  chooseItem(item: any) {
    let geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      'address': item.description
    }, async (results: any, status: any) => {
      this.userLocationForm.controls['latitude'].setValue(results[0].geometry.location.lat());
      this.userLocationForm.controls['longitude'].setValue(results[0].geometry.location.lng());
      this.autocompleteItems = [];
      this.userLocationForm.controls['address'].setValue(item.description);
    });
  }
  updateSearch(event: any) {
    if (event?.detail?.value == '') {
      this.autocompleteItems = [];
      this.userLocationForm.controls['latitude'].setValue('');
      this.userLocationForm.controls['longitude'].setValue('');
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
