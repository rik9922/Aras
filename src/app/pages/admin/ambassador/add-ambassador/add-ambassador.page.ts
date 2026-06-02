import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

declare var google: any;
@Component({
  selector: 'app-add-ambassador',
  templateUrl: './add-ambassador.page.html',
  styleUrls: ['./add-ambassador.page.scss'],
  standalone: false
})
export class AddAmbassadorPage implements OnInit {
  params: any = { title: 'Add Ambassador', is_back: true };
  autocompleteItems: any = [];
  user: any;
  data: any;
  ambassadorForm: FormGroup = new FormGroup({});
  passwordIcon: string = 'eye-off';
  passwordType: string = 'password';
  fromDate: any = '';
  toDate: any = '';
  isFromModalOpen = false;
  isToModalOpen = false;
  isSpinner: boolean = false;
  states: any = [];
  markets: any = [];
  isMapModal: boolean = false;
  acService = new google.maps.places.AutocompleteService();
  validation_message: any = {
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
    password: [
      { type: "required", message: "Password is required." }
    ],
    dob: [
      { type: "required", message: "Date of Birth is required" }
    ],
    region: [
      { type: 'required', message: 'Division is required.' },
    ],
    phone: [
      { type: "required", message: "Phone number is required." },
      { type: 'pattern', message: 'Please enter a valid phone number.' },
      { type: "minlength", message: "Please enter only 10 to 12 digits." },
      { type: "maxlength", message: "Please enter only 10 to 12 digits." },
    ],
    legal_address: [
      { type: "required", message: "Legal address is required." },
    ],
    legal_city: [
      { type: "required", message: "Legal city is required." },
    ],
    legal_state_id: [
      { type: "required", message: "Legal state is required." },
    ],
    legal_zip_code: [
      { type: "required", message: "Zip code is required." },
      { type: "pattern", message: "Please enter a valid zip code." }
    ],
    hired: [
      { type: "required", message: "Hired date is required." },
    ],
    wage: [
      { type: "required", message: "Wage is required." },
      { type: 'pattern', message: 'Please enter a valid wage.' }
    ],
    market_id: [
      { type: "required", message: "Market is required." },
    ],
    shirt_size: [
      { type: "required", message: "shirt_size size is required." },
    ],
  }
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public router: Router,
    public apiService: ApiService,
    public event: EventsService,
    public nav: NavController,
    public fb: FormBuilder,
    public cdrf: ChangeDetectorRef
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getStateMarketData();
        if (this.data?.id) {
          this.params.title = 'Update Ambassador';
          this.ambassadorForm.controls['password'].clearValidators();
          this.ambassadorForm.controls['password'].updateValueAndValidity();
          this.getUserById(this.data?.id);
        }
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  };
  ngOnInit() {
    this.ambassadorForm = this.fb.group({
      'first_name': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'last_name': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'email': new FormControl('', Validators.compose([Validators.required, Validators.pattern('^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')])),
      'password': new FormControl('password', Validators.compose([Validators.required])),
      'nickname': new FormControl(''),
      'dob': new FormControl('', [Validators.required]),
      'venmo': new FormControl(''),
      'region': new FormControl('', [Validators.required]),
      'phone': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^\d+$/), Validators.minLength(10), Validators.maxLength(12)
      ])),
      'titos_trained': [false],
      'attended_trainings': [false],
      'thc_quiz_passed': [false],
      'tips_certified': [false],
      'male_ba_lgbtq_ok': [false],
      'has_permit': [false],
      'legal_city': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'legal_state_id': new FormControl('', Validators.compose([Validators.required])),
      'legal_address': new FormControl('', Validators.compose([Validators.required])),
      'legal_zip_code': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\d{5,6}$/)])),
      'same_as_legal': [false],
      'mailing_address': new FormControl(''),
      'mailing_city': new FormControl(''),
      'mailing_state_id': new FormControl(''),
      'mailing_zip_code': new FormControl('', Validators.compose([Validators.pattern(/^\d{5,6}$/)])),
      'favorite_coffee_shop': new FormControl(''),
      'favorite_restaurant': new FormControl(''),
      'favorite_place_to_shop': new FormControl(''),
      'favorite_charity': new FormControl(''),
      'hired': new FormControl('', [Validators.required]),
      'wage': new FormControl('', [Validators.required, Validators.pattern(/^\d+$/)]),
      'market_id': new FormControl('', [Validators.required]),
      'shirt_size': new FormControl('', [Validators.required]),
      'status': new FormControl('', [Validators.required]),
      'longitude': new FormControl('', [Validators.required]),
      'latitude': new FormControl('', [Validators.required]),
    });
  };

  hideShowPassword() {
    this.passwordType = this.passwordType === 'text' ? 'password' : 'text';
    this.passwordIcon = this.passwordIcon === 'eye-off' ? 'eye' : 'eye-off';
  }

  openFromModal() {
    this.isFromModalOpen = true;
    this.cdrf.detectChanges();
  }

  openToModal() {
    this.isToModalOpen = true;
    this.cdrf.detectChanges();
  }
  selectDate(event: any, key: any) {
    var value = moment(event.detail.value).format("MM-DD-YYYY");
    if (value) {
      this.ambassadorForm.controls[key].setValue(value);
    }
    this.ambassadorForm.controls[key].updateValueAndValidity();
  }
  submit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data?.id;
    };
    if (formValue?.market_id && formValue?.market_id?.length > 0) {
      formValue.market_id = formValue.market_id.toString();
    }
    if (formValue?.dob) {
      formValue.dob = moment(formValue.dob).format('YYYY-MM-DD');
    }
    if (formValue?.hired) {
      formValue.hired = moment(formValue.hired).format('YYYY-MM-DD');
    }

    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_ambassdor', formValue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      this.ambassadorForm.reset();
      let check = await this.common.presentAlertWithPromise('Success', res?.message);
      if (check) {
        this.router.navigate(['/admin-ambassador-list']);
        this.event.publish('reload_ambassador', 1);
      }
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }
  getUserById(userId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_user_by_id?id=${userId}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      res.data.market_id = res.data.market_id.map(Number);
      res.data.dob = moment(res?.data?.dob).format('MM-DD-YYYY');
      res.data.hired = moment(res?.data?.hired).format('MM-DD-YYYY');
      this.ambassadorForm.patchValue(res?.data);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }
  getStateMarketData() {
    this.isSpinner = true;
    this.apiService.getAuthData('admin/get_market_state', this.user?.token).subscribe((res: any) => {
      this.isSpinner = false;
      this.states = res?.data?.state;
      this.markets = res?.data?.market;
    }, err => {
      this.isSpinner = false;
      console.log('err', err);
    })
  }

  sameAddress(event: any) {
    var value = event?.target?.checked;
    if (value) {
      this.ambassadorForm.controls['mailing_address'].setValue(this.ambassadorForm.value.legal_address);
      this.ambassadorForm.controls['mailing_city'].setValue(this.ambassadorForm.value.legal_city);
      this.ambassadorForm.controls['mailing_state_id'].setValue(this.ambassadorForm.value.legal_state_id);
      this.ambassadorForm.controls['mailing_zip_code'].setValue(this.ambassadorForm.value.legal_zip_code);
    } else {

    }
  }
  chooseItem(item: any) {
    let geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      'address': item.description
    }, async (results: any, status: any) => {
      this.ambassadorForm.controls['latitude'].setValue(results[0].geometry.location.lat());
      this.ambassadorForm.controls['longitude'].setValue(results[0].geometry.location.lng());
      this.autocompleteItems = [];
      this.ambassadorForm.controls['legal_address'].setValue(item.description);
    });
  }
  updateSearch(event: any) {
    if (event?.detail?.value == '') {
      this.autocompleteItems = [];
      this.ambassadorForm.controls['latitude'].setValue('');
      this.ambassadorForm.controls['longitude'].setValue('');
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
  openMapModal() {
    this.isMapModal = !this.isMapModal;
  }
}
