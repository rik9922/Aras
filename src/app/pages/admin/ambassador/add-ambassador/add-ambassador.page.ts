import { ChangeDetectorRef, Component, NgZone, OnInit } from '@angular/core';
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
  // Legal address autocomplete suggestions
  legalAutocompleteItems: any[] = [];
  // Mailing address autocomplete suggestions
  mailingAutocompleteItems: any[] = [];

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

  // Google Places AutocompleteService — predictions only (no map needed)
  private acService = new google.maps.places.AutocompleteService();
  // PlacesService needs a DOM node but we pass a dummy div (no map rendered)
  private placesService: any = null;

  validation_message: any = {
    first_name: [
      { type: 'required', message: 'First Name is required.' },
      { type: 'pattern',  message: 'Please enter a valid first name' }
    ],
    last_name: [
      { type: 'required', message: 'Last Name is required.' },
      { type: 'pattern',  message: 'Please enter a valid last name' }
    ],
    email: [
      { type: 'required', message: 'Email is required.' },
      { type: 'pattern',  message: 'Please enter a valid email address.' }
    ],
    password: [
      { type: 'required', message: 'Password is required.' }
    ],
    dob: [
      { type: 'required', message: 'Date of Birth is required' }
    ],
    region: [
      { type: 'required', message: 'Division is required.' },
    ],
    phone: [
      { type: 'required',  message: 'Phone number is required.' },
      { type: 'pattern',   message: 'Please enter a valid phone number.' },
      { type: 'minlength', message: 'Please enter only 10 to 12 digits.' },
      { type: 'maxlength', message: 'Please enter only 10 to 12 digits.' },
    ],
    legal_address: [
      { type: 'required', message: 'Legal address is required.' },
    ],
    legal_city: [
      { type: 'required', message: 'Legal city is required.' },
    ],
    legal_state: [
      { type: 'required', message: 'Legal state is required.' },
    ],
    legal_zip_code: [
      { type: 'required', message: 'Zip code is required.' },
      { type: 'pattern',  message: 'Please enter a valid zip code.' }
    ],
    hired: [
      { type: 'required', message: 'Hired date is required.' },
    ],
    wage: [
      { type: 'required', message: 'Wage is required.' },
      { type: 'pattern',  message: 'Please enter a valid wage.' }
    ],
    market_id: [
      { type: 'required', message: 'Market is required.' },
    ],
    shirt_size: [
      { type: 'required', message: 'Shirt size is required.' },
    ],
  };

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public router: Router,
    public apiService: ApiService,
    public event: EventsService,
    public nav: NavController,
    public fb: FormBuilder,
    public cdrf: ChangeDetectorRef,
    private zone: NgZone,
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
        // Initialise PlacesService using a throwaway div (no map rendered)
        if (!this.placesService) {
          this.placesService = new google.maps.places.PlacesService(
            document.createElement('div')
          );
        }
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
  }

  ngOnInit() {
    this.ambassadorForm = this.fb.group({
      'first_name':  new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'last_name':   new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'email':       new FormControl('', Validators.compose([Validators.required, Validators.pattern('^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')])),
      'password':    new FormControl('password', Validators.compose([Validators.required])),
      'nickname':    new FormControl(''),
      'dob':         new FormControl('', [Validators.required]),
      'venmo':       new FormControl(''),
      'region':      new FormControl('', [Validators.required]),
      'phone':       new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern(/^\d+$/),
        Validators.minLength(10),
        Validators.maxLength(12),
      ])),
      'titos_trained':      [false],
      'attended_trainings': [false],
      'thc_quiz_passed':    [false],
      'tips_certified':     [false],
      'male_ba_lgbtq_ok':   [false],
      'has_permit':         [false],

      // Legal address — state stored as 2-letter short code (matches web + API)
      'legal_address':  new FormControl('', Validators.compose([Validators.required])),
      'legal_city':     new FormControl('', Validators.compose([Validators.required])),
      'legal_state':    new FormControl('', Validators.compose([Validators.required])),
      'legal_zip_code': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\d{5,6}$/)])),
      // Hidden geo fields populated by Places detail lookup
      'latitude':  new FormControl(''),
      'longitude': new FormControl(''),

      'same_as_legal':   [false],
      'mailing_address': new FormControl(''),
      'mailing_city':    new FormControl(''),
      'mailing_state':   new FormControl(''),
      'mailing_zip_code':new FormControl('', Validators.compose([Validators.pattern(/^\d{5,6}$/)])),

      'favorite_coffee_shop':    new FormControl(''),
      'favorite_restaurant':     new FormControl(''),
      'favorite_place_to_shop':  new FormControl(''),
      'favorite_charity':        new FormControl(''),
      'hired':      new FormControl('', [Validators.required]),
      'wage':       new FormControl('', [Validators.required, Validators.pattern(/^\d+$/)]),
      'market_id':  new FormControl('', [Validators.required]),
      'shirt_size': new FormControl('', [Validators.required]),
      'status':     new FormControl('', [Validators.required]),
    });
  }

  // ── Password toggle ─────────────────────────────────────────────────────
  hideShowPassword() {
    this.passwordType = this.passwordType === 'text' ? 'password' : 'text';
    this.passwordIcon = this.passwordIcon === 'eye-off' ? 'eye' : 'eye-off';
  }

  // ── Date pickers ────────────────────────────────────────────────────────
  openFromModal() { this.isFromModalOpen = true; this.cdrf.detectChanges(); }
  openToModal()   { this.isToModalOpen   = true; this.cdrf.detectChanges(); }

  selectDate(event: any, key: any) {
    const value = moment(event.detail.value).format('MM-DD-YYYY');
    if (value) {
      this.ambassadorForm.controls[key].setValue(value);
    }
    this.ambassadorForm.controls[key].updateValueAndValidity();
  }

  // ── Form submit ──────────────────────────────────────────────────────────
  submit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data?.id;
    }
    if (formValue?.market_id && formValue?.market_id?.length > 0) {
      formValue.market_id = formValue.market_id.toString();
    }
    if (formValue?.dob) {
      formValue.dob = moment(formValue.dob, ['MM-DD-YYYY', 'YYYY-MM-DD']).format('YYYY-MM-DD');
    }
    if (formValue?.hired) {
      formValue.hired = moment(formValue.hired, ['MM-DD-YYYY', 'YYYY-MM-DD']).format('YYYY-MM-DD');
    }

    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_ambassdor', formValue, this.user?.token).subscribe(
      async (res: any) => {
        this.common.dismissLoading();
        this.ambassadorForm.reset();
        const check = await this.common.presentAlertWithPromise('Success', res?.message);
        if (check) {
          this.router.navigate(['/admin-ambassador-list']);
          this.event.publish('reload_ambassador', 1);
        }
      },
      err => {
        this.common.dismissLoading();
        console.log('err', err);
      }
    );
  }

  // ── Load ambassador for edit ─────────────────────────────────────────────
  getUserById(userId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_user_by_id?id=${userId}`, this.user?.token).subscribe(
      (res: any) => {
        this.common.dismissLoading();
        res.data.market_id = res.data.market_id.map(Number);
        res.data.dob   = moment(res?.data?.dob).format('MM-DD-YYYY');
        res.data.hired = moment(res?.data?.hired).format('MM-DD-YYYY');
        this.ambassadorForm.patchValue(res?.data);
      },
      err => {
        this.common.dismissLoading();
        console.log('err', err);
      }
    );
  }

  // ── States + Markets ─────────────────────────────────────────────────────
  getStateMarketData() {
    this.isSpinner = true;
    this.apiService.getAuthData('admin/get_market_state', this.user?.token).subscribe(
      (res: any) => {
        this.isSpinner = false;
        this.states  = res?.data?.state;
        this.markets = res?.data?.market;
      },
      err => { this.isSpinner = false; console.log('err', err); }
    );
  }

  // ── Same-as-legal mirror ─────────────────────────────────────────────────
  sameAddress(event: any) {
    if (event?.target?.checked) {
      this.ambassadorForm.patchValue({
        mailing_address:  this.ambassadorForm.value.legal_address,
        mailing_city:     this.ambassadorForm.value.legal_city,
        mailing_state:    this.ambassadorForm.value.legal_state,
        mailing_zip_code: this.ambassadorForm.value.legal_zip_code,
      });
    }
  }

  // ── Google Places — Legal address ────────────────────────────────────────

  /**
   * Called on every keystroke in the legal address field.
   * Fetches place predictions and shows the dropdown.
   */
  updateLegalSearch(event: any) {
    const query = event?.detail?.value?.trim() ?? '';
    if (!query) {
      this.legalAutocompleteItems = [];
      return;
    }
    this.acService.getPlacePredictions(
      { input: query, componentRestrictions: { country: ['us'] }, types: ['address'] },
      (predictions: any, status: any) => {
        this.zone.run(() => {
          this.legalAutocompleteItems = predictions ?? [];
        });
      }
    );
  }

  /**
   * Called when a legal address suggestion is tapped.
   * Fetches full place details and populates all legal address fields.
   * Also mirrors to mailing if "same as legal" is checked.
   */
  chooseLegalItem(item: any) {
    this.legalAutocompleteItems = [];
    // Set the address text immediately so the field feels responsive
    this.ambassadorForm.controls['legal_address'].setValue(item.description);

    if (!this.placesService) return;

    this.placesService.getDetails(
      { placeId: item.place_id, fields: ['address_components', 'formatted_address', 'geometry'] },
      (place: any, status: any) => {
        if (status !== google.maps.places.PlacesServiceStatus.OK || !place) return;

        const parsed = this.parsePlaceComponents(place);

        this.zone.run(() => {
          this.ambassadorForm.patchValue({
            legal_address:  place.formatted_address,
            legal_city:     parsed.city,
            legal_state:    parsed.state,   // 2-letter short code
            legal_zip_code: parsed.zip,
            latitude:       parsed.lat,
            longitude:      parsed.lng,
          });

          // Mirror to mailing if checkbox is ticked
          if (this.ambassadorForm.value.same_as_legal) {
            this.ambassadorForm.patchValue({
              mailing_address:  place.formatted_address,
              mailing_city:     parsed.city,
              mailing_state:    parsed.state,
              mailing_zip_code: parsed.zip,
            });
          }
        });
      }
    );
  }

  // ── Google Places — Mailing address ─────────────────────────────────────

  updateMailingSearch(event: any) {
    if (this.ambassadorForm.value.same_as_legal) return;
    const query = event?.detail?.value?.trim() ?? '';
    if (!query) {
      this.mailingAutocompleteItems = [];
      return;
    }
    this.acService.getPlacePredictions(
      { input: query, componentRestrictions: { country: ['us'] }, types: ['address'] },
      (predictions: any, status: any) => {
        this.zone.run(() => {
          this.mailingAutocompleteItems = predictions ?? [];
        });
      }
    );
  }

  chooseMailingItem(item: any) {
    this.mailingAutocompleteItems = [];
    this.ambassadorForm.controls['mailing_address'].setValue(item.description);

    if (!this.placesService) return;

    this.placesService.getDetails(
      { placeId: item.place_id, fields: ['address_components', 'formatted_address'] },
      (place: any, status: any) => {
        if (status !== google.maps.places.PlacesServiceStatus.OK || !place) return;

        const parsed = this.parsePlaceComponents(place);

        this.zone.run(() => {
          this.ambassadorForm.patchValue({
            mailing_address:  place.formatted_address,
            mailing_city:     parsed.city,
            mailing_state:    parsed.state,
            mailing_zip_code: parsed.zip,
          });
        });
      }
    );
  }

  // ── Helpers ──────────────────────────────────────────────────────────────

  /**
   * Extract city, state (short code), zip, lat, lng from a Places detail result.
   * Mirrors the same component logic used by the web implementation.
   */
  private parsePlaceComponents(place: any): { city: string; state: string; zip: string; lat: string; lng: string } {
    let city = '', state = '', zip = '', lat = '', lng = '';

    for (const component of (place.address_components ?? [])) {
      const types = component.types ?? [];
      if (types.includes('locality')) {
        city = component.long_name;
      } else if (types.includes('administrative_area_level_1')) {
        state = component.short_name; // e.g. "NY", "CA"
      } else if (types.includes('postal_code')) {
        zip = component.long_name;
      }
    }

    if (place.geometry?.location) {
      lat = place.geometry.location.lat().toString();
      lng = place.geometry.location.lng().toString();
    }

    return { city, state, zip, lat, lng };
  }

  get aboutAmbassadorName(): string {
    const name = this.ambassadorForm?.get('first_name')?.value?.trim();
    return name || 'Ambassador';
  }
}
