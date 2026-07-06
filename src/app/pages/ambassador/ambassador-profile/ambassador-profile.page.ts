import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, FormControl, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { Geolocation } from '@capacitor/geolocation';
import { Capacitor } from '@capacitor/core';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { PermissionsService } from 'src/app/services/permissions.service';
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
    latitude: [
      { type: "required", message: "Please select your location on the map." },
    ],
    longitude: [
      { type: "required", message: "Please select your location on the map." },
    ],
  };
  formData: any;
  profileForm: FormGroup = new FormGroup({});
  private selectedImageFile: File | null = null;
  acService = new google.maps.places.AutocompleteService();
  user: any;
  data: any;
  isSpinner: boolean = false;
  isMapModal = false;
  private map: any;
  private marker: any;
  private geocoder: any;
  private readonly fallbackLocation = { lat: 40.7128, lng: -74.0060 };
  constructor(
    public router: Router,
    public storage: StorageService,
    public nav: NavController,
    public formBuilder: FormBuilder,
    public apiService: ApiService,
    public common: CommonService,
    public event: EventsService,
    private permissionsService: PermissionsService
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
      'dob': new FormControl(''),

      'legal_address': new FormControl('', Validators.compose([Validators.required])),
      'legal_city': new FormControl('', Validators.compose([Validators.required])),
      'legal_state_id': new FormControl('', Validators.compose([Validators.required])),
      'legal_zip_code': new FormControl('', Validators.compose([Validators.required])),

      // same_as_legal is a boolean toggle — no required validator needed
      'same_as_legal': new FormControl(false),

      'mailing_address': new FormControl('', Validators.compose([Validators.required])),
      'mailing_city': new FormControl('', Validators.compose([Validators.required])),
      'mailing_state_id': new FormControl('', Validators.compose([Validators.required])),
      'mailing_zip_code': new FormControl('', Validators.compose([Validators.required])),

      'favorite_coffee_shop': new FormControl(''),
      'favorite_restaurant': new FormControl(''),
      'favorite_place_to_shop': new FormControl(''),
      'favorite_charity': new FormControl(''),

      // lat/lng are optional on the client — the API validates server-side.
      // Keeping them required here would prevent any save for ambassadors who
      // have never opened the map (their stored values are null).
      'latitude': new FormControl(''),
      'longitude': new FormControl(''),

      // Certifications & preferences (boolean toggles, all optional)
      'titos_trained': new FormControl(false),
      'thc_quiz_passed': new FormControl(false),
      'male_ba_lgbtq_ok': new FormControl(false),
      'attended_trainings': new FormControl(false),
      'tips_certified': new FormControl(false),
      'has_permit': new FormControl(false),
    });
  }
  ionViewWillEnter() {
    this.data = '';
    this.formData = new FormData();
    this.selectedImageFile = null;
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getData();

        // Normalize market_id: API may return it as a comma-separated string after save
        if (this.user?.market_id) {
          if (typeof this.user.market_id === 'string' && this.user.market_id.length > 0) {
            this.user.market_id = this.user.market_id.split(',').map(Number).filter(Boolean);
          } else if (Array.isArray(this.user.market_id)) {
            this.user.market_id = this.user.market_id.map(Number);
          }
        }

        // Normalize same_as_legal to boolean
        this.user.same_as_legal = !!+this.user?.same_as_legal;

        // Normalize certification booleans (DB may return "0"/"1" strings)
        const boolFields = ['titos_trained', 'thc_quiz_passed', 'male_ba_lgbtq_ok',
                            'attended_trainings', 'tips_certified', 'has_permit'];
        boolFields.forEach(f => {
          if (this.user[f] !== undefined && this.user[f] !== null) {
            this.user[f] = !!+this.user[f];
          }
        });

        // Sanitize nullable string fields: patchValue with null causes ion-input
        // to render the literal text "null". Replace null/undefined with ''.
        const nullableStrings = [
          'nickname', 'venmo', 'dob',
          'legal_address', 'legal_city', 'legal_zip_code',
          'mailing_address', 'mailing_city', 'mailing_zip_code',
          'favorite_coffee_shop', 'favorite_restaurant',
          'favorite_place_to_shop', 'favorite_charity',
        ];
        nullableStrings.forEach(f => {
          if (this.user[f] === null || this.user[f] === undefined) {
            this.user[f] = '';
          }
        });

        this.profileForm.patchValue(this.user);

        // If same_as_legal, copy legal address into mailing fields
        if (this.user?.same_as_legal) {
          this.profileForm.controls['mailing_address'].setValue(this.profileForm.value.legal_address);
          this.profileForm.controls['mailing_city'].setValue(this.profileForm.value.legal_city);
          this.profileForm.controls['mailing_state_id'].setValue(this.profileForm.value.legal_state_id);
          this.profileForm.controls['mailing_zip_code'].setValue(this.profileForm.value.legal_zip_code);
        }
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
    // Always start with a fresh FormData to avoid stale / accumulated entries
    this.formData = new FormData();

    // Re-attach the selected profile image if the user chose one
    if (this.selectedImageFile) {
      this.formData.set('profile_image', this.selectedImageFile);
    }

    delete formValue.profile_image;
    if (Array.isArray(formValue?.market_id) && formValue.market_id.length > 0) {
      formValue.market_id = formValue.market_id.toString();
    }
    for (const key in formValue) {
      const value = formValue[key];

      let converted =
        value === true  ? 1 :
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
      // Mark the form pristine so the Save button disables until the user
      // makes another change (covers the case where they dismiss the alert).
      this.profileForm.markAsPristine();
      let check = await this.common.presentAlertWithPromise('Success', res?.message);
      if (check) {
        this.nav.navigateRoot('/tabs/ambassador-home');
      }
    }, err => {
      this.common.dismissLoading();
      const raw: string = err?.error?.message ?? '';
      // Replace technical lat/lng field names with a user-friendly message.
      const msg = /latitude|longitude/i.test(raw)
        ? 'Please choose your location on the map before saving your profile.'
        : raw;
      this.common.presentAlert(msg);
    });
  }
  onChange(event: any) {
    const file: File | undefined = event.target.files?.[0];
    if (file) {
      this.selectedImageFile = file;
      this.user.profile_image = URL.createObjectURL(file);
      // Profile image is handled outside the reactive form; mark the form
      // dirty manually so the Save button enables after an image selection.
      this.profileForm.markAsDirty();
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

        if (key === 'legal_address') {
          this.profileForm.controls["legal_city"].setValue(city);
          this.profileForm.controls["legal_zip_code"].setValue(zip);

          const selectedState = this.data?.states?.find(
            (s: any) => s.name === state || s.short_name === state
          );

          if (selectedState) {
            this.profileForm.controls["legal_state_id"].setValue(selectedState.id);
          }

          if (results[0].geometry?.location) {
            this.setMapCoordinates(
              results[0].geometry.location.lat(),
              results[0].geometry.location.lng()
            );
          }
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

  get aboutAmbassadorName(): string {
    const name = this.profileForm?.get('first_name')?.value?.trim();
    return name || 'Ambassador';
  }

  openMapModal() {
    this.isMapModal = true;
    setTimeout(() => this.initializeMap(), 300);
  }

  closeMapModal() {
    this.isMapModal = false;
    this.map = null;
    this.marker = null;
  }

  saveMapLocation() {
    if (this.marker) {
      const position = this.marker.getPosition();
      this.setMapCoordinates(position.lat(), position.lng());
    }
    this.closeMapModal();
  }

  private setMapCoordinates(lat: number, lng: number) {
    this.profileForm.controls['latitude'].setValue(lat);
    this.profileForm.controls['longitude'].setValue(lng);
    this.profileForm.controls['latitude'].updateValueAndValidity();
    this.profileForm.controls['longitude'].updateValueAndValidity();
    // setValue() does not mark the form dirty; do it manually so the
    // Save button enables after a map location change.
    this.profileForm.controls['latitude'].markAsDirty();
    this.profileForm.controls['longitude'].markAsDirty();
  }

  private getExistingCoordinates(): { lat: number; lng: number } | null {
    const lat = parseFloat(this.profileForm.controls['latitude'].value);
    const lng = parseFloat(this.profileForm.controls['longitude'].value);
    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
      return { lat, lng };
    }
    return null;
  }

  private buildAddressQuery(): string {
    const value = this.profileForm.value;
    const state = this.data?.states?.find((s: any) => s.id == value.legal_state_id);
    const parts = [
      value.legal_address,
      value.legal_city,
      state?.name,
      value.legal_zip_code,
    ].filter((part: string) => !!part && String(part).trim() !== '');

    if (parts.length === 0 && value.legal_zip_code) {
      return `${value.legal_zip_code}, USA`;
    }

    return parts.length ? `${parts.join(', ')}, USA` : '';
  }

  private geocodeAddress(): Promise<{ lat: number; lng: number } | null> {
    const address = this.buildAddressQuery();
    if (!address) {
      return Promise.resolve(null);
    }

    if (!this.geocoder) {
      this.geocoder = new google.maps.Geocoder();
    }

    return new Promise((resolve) => {
      this.geocoder.geocode({ address }, (results: any, status: any) => {
        if (status === 'OK' && results[0]?.geometry?.location) {
          resolve({
            lat: results[0].geometry.location.lat(),
            lng: results[0].geometry.location.lng(),
          });
          return;
        }
        resolve(null);
      });
    });
  }

  private async getDeviceLocation(): Promise<{ lat: number; lng: number } | null> {
    try {
      if (Capacitor.isNativePlatform()) {
        const hasPermission = await this.permissionsService.ensureLocationPermission();
        if (!hasPermission) {
          return null;
        }
        const coordinates = await Geolocation.getCurrentPosition({
          enableHighAccuracy: true,
          timeout: 8000,
        });
        return {
          lat: coordinates.coords.latitude,
          lng: coordinates.coords.longitude,
        };
      }

      if (!navigator.geolocation) {
        return null;
      }

      return await new Promise((resolve) => {
        navigator.geolocation.getCurrentPosition(
          (position) => resolve({
            lat: position.coords.latitude,
            lng: position.coords.longitude,
          }),
          () => resolve(null),
          { timeout: 8000, maximumAge: 60000 }
        );
      });
    } catch {
      return null;
    }
  }

  private async resolveInitialLocation(): Promise<{ lat: number; lng: number }> {
    const existing = this.getExistingCoordinates();
    if (existing) {
      return existing;
    }

    const deviceLocation = await this.getDeviceLocation();
    if (deviceLocation) {
      return deviceLocation;
    }

    const geocoded = await this.geocodeAddress();
    if (geocoded) {
      return geocoded;
    }

    return this.fallbackLocation;
  }

  private async initializeMap() {
    const mapElement = document.getElementById('ambassador-location-map');
    if (!mapElement) {
      return;
    }

    const location = await this.resolveInitialLocation();
    this.map = new google.maps.Map(mapElement, {
      zoom: 14,
      center: location,
    });

    this.marker = new google.maps.Marker({
      position: location,
      map: this.map,
      draggable: true,
    });

    this.marker.addListener('dragend', () => {
      const position = this.marker.getPosition();
      this.setMapCoordinates(position.lat(), position.lng());
    });

    this.setMapCoordinates(location.lat, location.lng);
    google.maps.event.trigger(this.map, 'resize');
    this.map.setCenter(location);
  }

}
