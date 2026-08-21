import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { GoogleMapsLoaderService } from 'src/app/services/google-maps-loader.service';

declare var google: any;
@Component({
  selector: 'app-add-pos-user',
  templateUrl: './add-pos-user.page.html',
  styleUrls: ['./add-pos-user.page.scss'],
  standalone: false
})
export class AddPosUserPage implements OnInit {
  params: any = { title: 'Add POS User', is_back: true };
  posUserForm: FormGroup = new FormGroup({});
  data: any;
  passwordType: string = "password";
  passwordIcon: string = "eye-off";
  autocompleteItems: any = [];
  acService?: any;
  user: any;
  validation_messages = {
    first_name: [
      { type: "required", message: "First Name is required." },
      { type: 'pattern', message: 'Please enter a valid first name' }
    ],
    last_name: [
      { type: "required", message: "Last Name is required." },
      { type: 'pattern', message: 'Please enter a valid last name' }
    ],
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Please enter a valid email address." }
    ],
    phone: [
      { type: "required", message: "Phone number is required." },
      { type: 'pattern', message: 'Please enter a valid phone number.' },
      { type: "minlength", message: "Please enter only 10 to 12 digits." },
    ],
    address: [
      { type: "required", message: "Address is required." },
    ],
    city: [
      { type: "required", message: "City is required." },
      { type: 'pattern', message: 'Please enter a city name' }
    ],
    state: [
      { type: "required", message: "State is required." },
    ],
    password: [
      { type: "required", message: "Password is required." },
      { type: "minlength", message: "Password must have eight character" },
    ]
  };
  constructor(
    public storage: StorageService,
    public event: EventsService,
    public common: CommonService,
    public apiService: ApiService,
    public router: Router,
    public nav: NavController,
    private mapsLoader: GoogleMapsLoaderService
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  /**
   * Ensures Google Maps is loaded before any google.maps.* use. Returns false
   * (instead of throwing) if Maps is unavailable so the page still works.
   */
  private async ensureMapsReady(): Promise<boolean> {
    try {
      await this.mapsLoader.load();
      if (!this.acService && this.mapsLoader.isLoaded) {
        this.acService = new google.maps.places.AutocompleteService();
      }
      return this.mapsLoader.isLoaded;
    } catch {
      return false;
    }
  }

  ngOnInit() {
    this.posUserForm = new FormGroup({
      'first_name': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'last_name': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)
      ])),
      'email': new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')
      ])),
      'phone': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^\d+$/), Validators.minLength(10), Validators.maxLength(12)
      ])),
      'address': new FormControl('', Validators.compose([Validators.required])),
      'city': new FormControl('', Validators.compose([Validators.required])),
      'state': new FormControl('', Validators.compose([Validators.required])),

      'latitude': new FormControl('', Validators.compose([Validators.required])),
      'longitude': new FormControl('', Validators.compose([Validators.required])),
      'password': new FormControl(''),
    })
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        void this.ensureMapsReady();
        if (this.data?.id) {
          this.params.title = 'Update POS User';
          this.getUserById(this.data?.id);
          this.posUserForm.controls['password'].clearValidators();
        } else {
          this.posUserForm.controls['password'].setValidators(Validators.compose([Validators.required, Validators.minLength(8)]));
        }
        this.posUserForm.controls['password'].updateValueAndValidity();
      } else {
        this.nav.navigateRoot('/login');
      };
    });
  }
  getUserById(userId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_user_by_id?id=${userId}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.posUserForm.patchValue(res?.data);
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }

  hideShowPassword() {
    this.passwordType = this.passwordType === "text" ? "password" : "text";
    this.passwordIcon = this.passwordIcon === "eye-off" ? "eye" : "eye-off";
  }
  async chooseItem(item: any) {
    if (!(await this.ensureMapsReady())) {
      return;
    }
    let geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      'address': item.description
    }, async (results: any, status: any) => {
      this.posUserForm.controls['latitude'].setValue(results[0].geometry.location.lat());
      this.posUserForm.controls['longitude'].setValue(results[0].geometry.location.lng());
      this.autocompleteItems = [];
      this.posUserForm.controls['address'].setValue(item.description);
    });
  }
  async updateSearch(event: any) {
    if (event?.detail?.value == '') {
      this.autocompleteItems = [];
      this.posUserForm.controls['latitude'].setValue('');
      this.posUserForm.controls['longitude'].setValue('');
      return;
    }
    if (!(await this.ensureMapsReady()) || !this.acService) {
      this.autocompleteItems = [];
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
  submit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data.id;
    }
    if (!formValue?.password) {
      delete formValue.password;
    }
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_pos_user', formValue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      this.posUserForm.reset();
      let check: any = await this.common.presentAlertWithPromise('Success', res?.message);
      this.event.publish('reload_pos_users', 1);
      if (check) {
        this.router.navigate(['/admin-pos-user-list']);
      }
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err)
      this.common.presentAlert(err?.error?.message);
    })
  }


}
