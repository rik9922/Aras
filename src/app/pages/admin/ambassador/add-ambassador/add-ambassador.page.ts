import { ChangeDetectorRef, Component, NgZone, OnInit, OnDestroy } from '@angular/core';
import { AbstractControl, FormBuilder, FormControl, FormGroup, ValidationErrors, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { Subscription } from 'rxjs';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { GoogleMapsLoaderService } from 'src/app/services/google-maps-loader.service';

declare var google: any;

@Component({
  selector: 'app-add-ambassador',
  templateUrl: './add-ambassador.page.html',
  styleUrls: ['./add-ambassador.page.scss'],
  standalone: false
})
export class AddAmbassadorPage implements OnInit, OnDestroy {
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
  isSubmitting: boolean = false;
  states: any = [];
  markets: any = [];
  maxDob = moment().subtract(18, 'years').format('YYYY-MM-DD');

  /**
   * Tracks whether the user has changed any field after the form was
   * pre-filled in edit mode.  Used instead of form.pristine because
   * Ionic custom components (ion-checkbox, ion-select, ion-datetime)
   * do not call markAsDirty() on the underlying FormControl.
   */
  formChanged = false;
  private valueChangesSub?: Subscription;

  // Google Places services — created lazily once the Maps API has loaded
  // (see ensureMapsReady). Never instantiated at construction time so the
  // page never crashes when the Maps script is unavailable.
  private acService?: any;
  // PlacesService needs a DOM node but we pass a dummy div (no map rendered)
  private placesService: any = null;
  emailServerError = '';

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
      { type: 'pattern',  message: 'Please enter a valid email address.' },
      { type: 'server',   message: () => this.emailServerError }
    ],
    password: [
      { type: 'required', message: 'Password is required.' }
    ],
    dob: [
      { type: 'required', message: 'Date of Birth is required' },
      { type: 'invalidDate', message: 'Please enter a valid date of birth.' },
      { type: 'underage', message: 'Ambassador must be at least 18 years old.' }
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
    private mapsLoader: GoogleMapsLoaderService,
  ) {
    // getCurrentNavigation() only works during the active navigation frame.
    // Angular Router also writes state to window.history.state — use that
    // as the reliable fallback so this.data survives after navigation ends.
    const navState = this.router?.getCurrentNavigation()?.extras?.state;
    const histState = (typeof history !== 'undefined' && history.state?.id)
      ? history.state
      : null;
    this.data = navState ?? histState ?? null;
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getStateMarketData();
        // Warm up Maps (and create the Places services) in the background;
        // never blocks the page from opening.
        void this.ensureMapsReady();
        if (this.data?.id) {
          this.params.title = 'Update Ambassador';
          this.ambassadorForm.controls['password'].clearValidators();
          this.ambassadorForm.controls['password'].updateValueAndValidity();
          // Reset flag — will be flipped to true by valueChanges once user edits
          this.formChanged = false;
          this.getUserById(this.data?.id);
        }
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  ionViewWillLeave() {
    this.valueChangesSub?.unsubscribe();
  }

  /**
   * Ensures the Google Maps API is loaded and the Places services exist before
   * any google.maps.* use. Returns false (instead of throwing) if Maps is
   * unavailable so the page still opens; only address autocomplete degrades.
   */
  private async ensureMapsReady(): Promise<boolean> {
    try {
      await this.mapsLoader.load();
      if (this.mapsLoader.isLoaded) {
        if (!this.acService) {
          this.acService = new google.maps.places.AutocompleteService();
        }
        if (!this.placesService) {
          this.placesService = new google.maps.places.PlacesService(document.createElement('div'));
        }
      }
      return this.mapsLoader.isLoaded;
    } catch {
      return false;
    }
  }

  ngOnDestroy() {
    this.valueChangesSub?.unsubscribe();
  }

  ngOnInit() {
    this.ambassadorForm = this.fb.group({
      'first_name':  new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'last_name':   new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'email':       new FormControl('', Validators.compose([Validators.required, Validators.pattern('^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')])),
      'password':    new FormControl('password', Validators.compose([Validators.required])),
      'nickname':    new FormControl(''),
      'dob':         new FormControl('', [Validators.required, AddAmbassadorPage.validDob]),
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
      // wage: allow decimals (API returns e.g. "15.00" from decimal:2 cast)
      'wage':       new FormControl('', [Validators.required, Validators.pattern(/^\d+(\.\d{1,2})?$/)]),
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
    const selectedDate = moment(event?.detail?.value, moment.ISO_8601, true);
    if (selectedDate.isValid()) {
      this.ambassadorForm.controls[key].setValue(selectedDate.format('MM-DD-YYYY'));
    }
    this.ambassadorForm.controls[key].updateValueAndValidity();
  }

  // ── Form submit ──────────────────────────────────────────────────────────
  submit(formValue: any) {
    if (this.isSubmitting) {
      return;
    }

    this.clearServerErrors();

    if (this.ambassadorForm.invalid) {
      this.ambassadorForm.markAllAsTouched();
      this.ambassadorForm.updateValueAndValidity();
      return;
    }

    this.isSubmitting = true;
    const payload = { ...formValue };

    if (this.data?.id) {
      payload.id = this.data?.id;
    }
    if (payload?.market_id && payload?.market_id?.length > 0) {
      payload.market_id = payload.market_id.toString();
    }
    if (payload?.dob) {
      payload.dob = moment(payload.dob, ['MM-DD-YYYY', 'YYYY-MM-DD']).format('YYYY-MM-DD');
    }
    if (payload?.hired) {
      payload.hired = moment(payload.hired, ['MM-DD-YYYY', 'YYYY-MM-DD']).format('YYYY-MM-DD');
    }

    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_ambassdor', payload, this.user?.token).subscribe(
      async (res: any) => {
        this.common.dismissLoading();
        this.isSubmitting = false;
        this.ambassadorForm.reset();
        const check = await this.common.presentAlertWithPromise('Success', res?.message);
        if (check) {
          this.router.navigate(['/admin-ambassador-list']);
          this.event.publish('reload_ambassador', 1);
        }
      },
      err => {
        this.common.dismissLoading();
        this.isSubmitting = false;
        this.applyApiValidationErrors(err?.error);
        console.log('err', err);
      }
    );
  }

  private clearServerErrors() {
    this.emailServerError = '';
    const emailControl = this.ambassadorForm.get('email');
    if (emailControl?.hasError('server')) {
      const { server, ...errors } = emailControl.errors ?? {};
      emailControl.setErrors(Object.keys(errors).length ? errors : null);
    }
  }

  private applyApiValidationErrors(error: any) {
    const message = error?.data?.email?.[0] ?? error?.message ?? 'Unable to save Ambassador.';

    if (error?.data?.email?.[0]) {
      this.emailServerError = error.data.email[0];
      this.ambassadorForm.get('email')?.setErrors({
        ...(this.ambassadorForm.get('email')?.errors ?? {}),
        server: true
      });
      this.ambassadorForm.get('email')?.markAsTouched();
    }

    this.common.presentToast(message);
  }

  // ── Load ambassador for edit ─────────────────────────────────────────────
  getUserById(userId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_user_by_id?id=${userId}`, this.user?.token).subscribe(
      (res: any) => {
        this.common.dismissLoading();
        const d = res?.data;
        if (!d) return;

        // Stop listening during patchValue so the fill itself doesn't
        // trigger formChanged.
        this.valueChangesSub?.unsubscribe();

        // ─ Normalise dates to MM-DD-YYYY for the date picker ─────────────
        // API returns YYYY-MM-DD; form stores MM-DD-YYYY (display only).
        const dob   = d.dob   ? moment(d.dob,   'YYYY-MM-DD').format('MM-DD-YYYY') : '';
        const hired = d.hired ? moment(d.hired, 'YYYY-MM-DD').format('MM-DD-YYYY') : '';

        // ─ Markets — array of int IDs from pivot ──────────────────
        // API returns market_id as int[]; ion-select multiple needs int[] too.
        const marketIds: number[] = Array.isArray(d.market_id)
          ? d.market_id.map(Number)
          : (d.market_id ? [Number(d.market_id)] : []);

        // ─ Patch all form controls ────────────────────────────
        // {emitEvent: false} prevents valueChanges from firing during
        // the programmatic fill, so formChanged stays false until the
        // user actually edits something.
        this.ambassadorForm.patchValue({
          // Basic info
          first_name:  d.first_name  ?? '',
          last_name:   d.last_name   ?? '',
          email:       d.email       ?? '',
          phone:       d.phone       ?? '',
          status:      d.status      ?? '',
          nickname:    d.nickname    ?? '',
          dob,
          venmo:       d.venmo       ?? '',
          region:      d.region      ?? '',

          // Legal address (auto-filled by Places on new selection; restored from DB on edit)
          legal_address:  d.legal_address  ?? '',
          legal_city:     d.legal_city     ?? '',
          legal_state:    d.legal_state    ?? '',
          legal_zip_code: d.legal_zip_code ?? '',
          latitude:       d.latitude       ?? '',
          longitude:      d.longitude      ?? '',

          // Mailing address
          same_as_legal:    d.same_as_legal    ?? false,
          mailing_address:  d.mailing_address  ?? '',
          mailing_city:     d.mailing_city     ?? '',
          mailing_state:    d.mailing_state    ?? '',
          mailing_zip_code: d.mailing_zip_code ?? '',

          // Certifications / flags
          titos_trained:      d.titos_trained      ?? false,
          attended_trainings: d.attended_trainings ?? false,
          thc_quiz_passed:    d.thc_quiz_passed    ?? false,
          tips_certified:     d.tips_certified     ?? false,
          male_ba_lgbtq_ok:   d.male_ba_lgbtq_ok   ?? false,
          has_permit:         d.has_permit         ?? false,

          // About
          favorite_coffee_shop:   d.favorite_coffee_shop   ?? '',
          favorite_restaurant:    d.favorite_restaurant    ?? '',
          favorite_place_to_shop: d.favorite_place_to_shop ?? '',
          favorite_charity:       d.favorite_charity       ?? '',

          // Work info
          hired,
          // Normalize wage: API returns decimal:2 (e.g. "15.00").
          // parseFloat strips trailing zeros so "15.00" → "15",
          // "15.50" → "15.5" — both match /^\d+(\.\d{1,2})?$/
          wage:       d.wage != null ? String(parseFloat(d.wage)) : '',
          shirt_size: d.shirt_size ?? '',
          market_id:  marketIds,
        }, { emitEvent: false });

        // In edit mode, legal_state and legal_zip_code may be empty for
        // ambassadors who have no address on file yet. Remove required/pattern
        // validators from those fields so a missing address does not keep the
        // form permanently invalid.
        if (!d.legal_state) {
          this.ambassadorForm.get('legal_state')?.clearValidators();
          this.ambassadorForm.get('legal_state')?.updateValueAndValidity({ emitEvent: false });
        }
        if (!d.legal_zip_code) {
          this.ambassadorForm.get('legal_zip_code')?.clearValidators();
          this.ambassadorForm.get('legal_zip_code')?.updateValueAndValidity({ emitEvent: false });
        }
        if (!d.legal_address) {
          this.ambassadorForm.get('legal_address')?.clearValidators();
          this.ambassadorForm.get('legal_address')?.updateValueAndValidity({ emitEvent: false });
        }
        if (!d.legal_city) {
          this.ambassadorForm.get('legal_city')?.clearValidators();
          this.ambassadorForm.get('legal_city')?.updateValueAndValidity({ emitEvent: false });
        }

        // After fill is complete, subscribe to valueChanges.
        // Any user edit — text, checkbox, select, date — fires this.
        this.valueChangesSub = this.ambassadorForm.valueChanges.subscribe(() => {
          this.formChanged = true;
          this.cdrf.markForCheck();
        });
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
  async updateLegalSearch(event: any) {
    const query = event?.detail?.value?.trim() ?? '';
    if (!query) {
      this.legalAutocompleteItems = [];
      return;
    }
    if (!(await this.ensureMapsReady()) || !this.acService) {
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
  async chooseLegalItem(item: any) {
    this.legalAutocompleteItems = [];
    // Set the address text immediately so the field feels responsive
    this.ambassadorForm.controls['legal_address'].setValue(item.description);

    if (!(await this.ensureMapsReady()) || !this.placesService) return;

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

          // Places callbacks use patchValue() which doesn’t mark the form dirty.
          // Mark dirty explicitly so the Update button enables in edit mode.
          this.ambassadorForm.markAsDirty();
        });
      }
    );
  }

  // ── Google Places — Mailing address ─────────────────────────────────────

  async updateMailingSearch(event: any) {
    if (this.ambassadorForm.value.same_as_legal) return;
    const query = event?.detail?.value?.trim() ?? '';
    if (!query) {
      this.mailingAutocompleteItems = [];
      return;
    }
    if (!(await this.ensureMapsReady()) || !this.acService) {
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

  async chooseMailingItem(item: any) {
    this.mailingAutocompleteItems = [];
    this.ambassadorForm.controls['mailing_address'].setValue(item.description);

    if (!(await this.ensureMapsReady()) || !this.placesService) return;

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
          this.ambassadorForm.markAsDirty();
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

  private static validDob(control: AbstractControl): ValidationErrors | null {
    const value = control.value;
    if (!value) {
      return null;
    }

    const dob = moment(value, ['MM-DD-YYYY', 'YYYY-MM-DD'], true);
    if (!dob.isValid()) {
      return { invalidDate: true };
    }

    const minimumDob = moment().subtract(18, 'years').startOf('day');
    if (dob.startOf('day').isAfter(minimumDob)) {
      return { underage: true };
    }

    return null;
  }

  get aboutAmbassadorName(): string {
    const name = this.ambassadorForm?.get('first_name')?.value?.trim();
    return name || 'Ambassador';
  }
}
