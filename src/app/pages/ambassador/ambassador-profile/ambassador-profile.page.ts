import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, FormControl, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

declare var jQuery: any
declare var google: any;
@Component({
  selector: 'app-ambassador-profile',
  templateUrl: './ambassador-profile.page.html',
  styleUrls: ['./ambassador-profile.page.scss'],
  standalone: false
})
export class AmbassadorProfilePage implements OnInit {
  autocompleteItems: any = []
  autocompleteItemsMailing: any = []
  params: any = { title: 'Profile', is_back: false };
  validation_messages = {
    first_name: [
      { type: "required", message: "First Name is required." },
      { type: 'pattern', message: 'Please enter a valid first name' }
    ],
    last_name: [
      { type: "required", message: "Last Name is required." },
      { type: 'pattern', message: 'Please enter a valid last name' }
    ],
    nickname: [
      { type: "required", message: "Nickname is required." },
      { type: 'pattern', message: 'Please enter a valid nickname' }
    ],
    venmo: [
      { type: "required", message: "Venmo name is required." },
      { type: 'pattern', message: 'Please enter a valid venmo name' }
    ],
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Please enter a valid email address." }
    ],
    zip_code: [
      { type: "required", message: "Zip code is required." },
      { type: "maxlength", message: "Zip code is not more than 5 digits." },
      { type: "minlength", message: "Zip code is must have 5 digits." },
      { type: 'pattern', message: 'Please enter a valid zip code.' }
    ],
    phone: [
      { type: "required", message: "Phone number is required." },
      { type: 'pattern', message: 'Please enter a valid phone number.' },
      { type: "minlength", message: "Please enter only 10 to 12 digits." },
    ],
    market_id: [
      { type: "required", message: "Market is required." },
      { type: 'pattern', message: 'Please enter a valid Market name.' },
    ],
    legal_address: [
      { type: "required", message: "Legal address is required." },
      { type: 'pattern', message: 'Please enter a valid legal address.' },
    ],
    legal_city: [
      { type: "required", message: "Legal city is required." },
      { type: 'pattern', message: 'Please enter a valid legal city.' },
    ],
    legal_state_id: [
      { type: "required", message: "Legal state is required." },
      { type: 'pattern', message: 'Please enter a valid legal state.' },
    ],
    legal_zip_code: [
      { type: "required", message: "Legal zip code is required." },
      { type: 'pattern', message: 'Please enter a valid legal zipcode.' },
    ],

    mailing_address: [
      { type: "required", message: "Mailing address is required." },
      { type: 'pattern', message: 'Please enter a valid mailing address.' },
    ],
    mailing_city: [
      { type: "required", message: "Mailing city is required." },
      { type: 'pattern', message: 'Please enter a valid mailing city.' },
    ],
    mailing_state_id: [
      { type: "required", message: "Mailing state is required." },
      { type: 'pattern', message: 'Please enter a valid mailing state.' },
    ],
    mailing_zip_code: [
      { type: "required", message: "Mailing zip code is required." },
      { type: 'pattern', message: 'Please enter a valid mailing zipcode.' },
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
  };
  formData: any;
  profileForm: FormGroup = new FormGroup({});
  acService = new google.maps.places.AutocompleteService();
  user: any;
  data: any;
  isSpinner: boolean = false;
  constructor(
    public router: Router,
    public storage: StorageService,
    public nav: NavController,
    public formBuilder: FormBuilder,
    public apiService: ApiService,
    public common: CommonService,
    public event: EventsService
  ) { };

  ngOnInit() {
    this.profileForm = this.formBuilder.group({
      'first_name': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'last_name': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)
      ])),
      'nickname': new FormControl('', Validators.compose([Validators.required])),
      'venmo': new FormControl('', Validators.compose([Validators.required])),
      'phone': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^\d+$/), Validators.minLength(10), Validators.maxLength(12)
      ])),
      'email': new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')
      ])),
      'market_id': new FormControl('', Validators.compose([Validators.required])),

      'legal_address': new FormControl('', Validators.compose([Validators.required])),
      'legal_city': new FormControl('', Validators.compose([Validators.required])),
      'legal_state_id': new FormControl('', Validators.compose([Validators.required])),
      'legal_zip_code': new FormControl('', Validators.compose([Validators.required])),

      'same_as_legal': new FormControl('', Validators.compose([Validators.required])),

      'mailing_address': new FormControl('', Validators.compose([Validators.required])),
      'mailing_city': new FormControl('', Validators.compose([Validators.required])),
      'mailing_state_id': new FormControl('', Validators.compose([Validators.required])),
      'mailing_zip_code': new FormControl('', Validators.compose([Validators.required])),

      // 'titos_trained': new FormControl('', Validators.compose([Validators.required])),
      // 'thc_quiz_passed': new FormControl('', Validators.compose([Validators.required])),
      // 'male_ba_lgbtq_ok': new FormControl('', Validators.compose([Validators.required])),
      // 'attended_trainings': new FormControl('', Validators.compose([Validators.required])),
      // 'tips_certified': new FormControl('', Validators.compose([Validators.required])),
      // 'has_permit': new FormControl('', Validators.compose([Validators.required])),
    });
  }
  ionViewWillEnter() {
    this.data = '';
    this.formData = new FormData();
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getData();

        if (this.user?.same_as_legal) {
          this.profileForm.controls['mailing_address'].setValue(this.profileForm.value.legal_address);
          this.profileForm.controls['mailing_city'].setValue(this.profileForm.value.legal_city);
          this.profileForm.controls['mailing_state_id'].setValue(this.profileForm.value.legal_state_id);
          this.profileForm.controls['mailing_zip_code'].setValue(this.profileForm.value.legal_zip_code);
        }
        if (this.user?.market_id?.length > 0) {
          this.user.market_id = this.user?.market_id.map(Number)
        }
        this.user.same_as_legal = +this.user?.same_as_legal;
        this.profileForm.patchValue(this.user);
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  sameAddress(event: any) {
    var value = event?.target?.checked;
    if (value) {
      this.profileForm.controls['mailing_address'].setValue(this.profileForm.value.legal_address);
      this.profileForm.controls['mailing_city'].setValue(this.profileForm.value.legal_city);
      this.profileForm.controls['mailing_state_id'].setValue(this.profileForm.value.legal_state_id);
      this.profileForm.controls['mailing_zip_code'].setValue(this.profileForm.value.legal_zip_code);
    } else {

    }
  }

  submit(formValue: any) {
    delete formValue.profile_image
    if (formValue?.market_id?.length > 0) {
      formValue.market_id = formValue?.market_id.toString();
    }
    for (const key in formValue) {
      const value = formValue[key];

      let converted =
        value === true ? 1 :
          value === false ? 0 :
            value;

      this.formData.set(key, converted);
    }
    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/update_profile', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      res.data.token = this.user.token;
      if (res?.data?.legal_state_id) {
        res.data.legal_state_id = +res.data.legal_state_id;
      };
      if (res?.data?.mailing_state_id) {
        res.data.mailing_state_id = +res.data.mailing_state_id;
      };
      this.storage.set('apro_user', res?.data);
      let check = await this.common.presentAlertWithPromise('Success', res?.message);
      if (check) {
        this.nav.navigateRoot('/tabs/ambassador-home');
      }
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message);
    });
  }
  onChange(event: any) {
    if (event.target.files?.length > 0) {
      this.formData.delete('profile_image');
      var self = this;
      jQuery(event.target.files).each(function (i: any, data: any) {
        self.user.profile_image = URL.createObjectURL(data),
          self.formData.append('profile_image', data)
      });
    }
  }
  select_profile() {
    jQuery('input[name="profile_image"]').trigger('click');
  }
  getData() {
    this.isSpinner = true;
    this.apiService.getAuthData('ambassador/get_state_market', this.user?.token).subscribe((res: any) => {
      this.isSpinner = false;
      this.data = res?.data;
    }, err => {
      this.isSpinner = false;
      console.log('err', err);
    });
  };
  chooseItem(item: any, key: string = "") {
    const geocoder = new google.maps.Geocoder();

    geocoder.geocode({ placeId: item.place_id }, (results: any, status: any) => {
      if (status === "OK" && results[0]) {
        this.autocompleteItems = [];
        this.autocompleteItemsMailing = [];

        this.profileForm.controls[key].setValue(results[0].formatted_address);

        const addressComponents = results[0].address_components;
        let city = "";
        let state = "";
        let zip = "";

        addressComponents.forEach((component: any) => {
          if (component.types.includes("locality")) {
            city = component.long_name;
          }
          if (component.types.includes("administrative_area_level_1")) {
            state = component.short_name;
          }
          if (component.types.includes("postal_code")) {
            zip = component.long_name;
          }
        });

        this.profileForm.controls["legal_city"].setValue(city);
        this.profileForm.controls["legal_zip_code"].setValue(zip);

        const selectedState = this.data?.states?.find(
          (s: any) => s.name === state || s.short_name === state
        );

        if (selectedState) {
          this.profileForm.controls["legal_state_id"].setValue(selectedState.id);
        }
      }
    });
  }

  updateSearch(event: any, key: any = '') {
    this.profileForm.controls['same_as_legal'].setValue(false);    
    this.profileForm.controls['same_as_legal'].updateValueAndValidity();
    if (event?.detail?.value == '') {
      this.autocompleteItems = [];
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
      self.autocompleteItems = [];
      if (predictions != null) {
        predictions.forEach(function (prediction: any) {
          if (key == 'legal_address') {
            self.autocompleteItems.push(prediction);
          } else {
            self.autocompleteItemsMailing.push(prediction);
          }
        });
      }
    });
  }
  changeSameAsStatus(status:any){
    this.profileForm.controls['same_as_legal'].setValue(status);
    this.profileForm.controls['same_as_legal'].updateValueAndValidity();
  }

}
