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
import { GoogleMapsLoaderService } from 'src/app/services/google-maps-loader.service';

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
    dob: [
      { type: "required", message: "Date of birth is required." },
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
  acService?: any;
  user: any;
  data: any;
  isSpinner: boolean = false;
  profileChanged: boolean = false;
  // Tracks an in-flight save so the Save button can guard against double taps
  // WITHOUT being coupled to isSpinner (a hung get_state_market request used to
  // leave the button permanently disabled with no explanation).
  isSaving: boolean = false;

  // Bounds for the Date of Birth picker (ion-datetime expects ISO strings).
  // max = today (no future birth dates); min = a sane lower bound.
  maxDob: string = new Date().toISOString();
  minDob: string = '1900-01-01T00:00:00';
  // Friendly labels for the "what's missing" alert shown on an invalid save.
  private readonly fieldLabels: Record<string, string> = {
    first_name: 'Legal First Name',
    last_name: 'Legal Last Name',
    nickname: 'Nickname',
    venmo: 'Venmo Name',
    phone: 'Cell Phone Number',
    email: 'Email Address',
    dob: 'Date of Birth',
    legal_address: 'Legal Address',
    legal_city: 'Legal City',
    legal_state_id: 'Legal State',
    legal_zip_code: 'Legal Zip Code',
    mailing_address: 'Mailing Address',
    mailing_city: 'Mailing City',
    mailing_state_id: 'Mailing State',
    mailing_zip_code: 'Mailing Zip Code',
  };
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
    private permissionsService: PermissionsService,
    private mapsLoader: GoogleMapsLoaderService
  ) { };

  /**
   * Ensures the Google Maps API is loaded before any google.maps.* use.
   * Returns false (instead of throwing) if Maps is unavailable so the profile
   * page still opens and saves; only address autocomplete / the map degrade.
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
    this.profileForm = this.formBuilder.group({
      'first_name': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'last_name': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)
      ])),
      'nickname': new FormControl(''),
      'venmo': new FormControl(''),
      'phone': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^\d+$/), Validators.minLength(10), Validators.maxLength(12)
      ])),
      'email': new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')
      ])),
      // NOTE: market_id is intentionally NOT a control here. Ambassador markets
      // are assigned by Admins/Managers and the source of truth is the
      // ambassador_market pivot (exposed via the API's assigned markets). The
      // legacy users.market_id column is stale/unreliable and must not be read
      // or resubmitted by this form.
      'dob': new FormControl('', Validators.compose([Validators.required])),

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

      // Liquor license (ambassador self-service). Mirrors the web portal profile
      // fields and posts to the same ambassador/update_profile endpoint, which
      // stores them on the ambassador_liquor_license record. All optional.
      'has_liquor_license': new FormControl(false),
      'document_on_file': new FormControl(false),
      'license_number': new FormControl(''),
      'issuing_state': new FormControl(''),
      'issued_at': new FormControl(''),
      'expires_at': new FormControl(''),
      'notes': new FormControl(''),

      // lat/lng are optional on the client — the API validates server-side.
      // Keeping them required here would prevent any save for ambassadors who
      // have never opened the map (their stored values are null).
      'latitude': new FormControl(''),
      'longitude': new FormControl(''),

      // Certifications & preferences are managed by Admins/Managers in the web
      // portal only (admin/ambassador create+edit). They are intentionally NOT
      // part of this mobile form so a profile save can never overwrite the
      // manager-set certification values.
    });

    this.profileForm.valueChanges.subscribe(() => {
      if (this.profileForm.dirty) {
        this.profileChanged = true;
      }
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
        // Warm up Maps in the background; never blocks the page from opening.
        void this.ensureMapsReady();

        // Normalize same_as_legal to boolean
        this.user.same_as_legal = !!+this.user?.same_as_legal;

        // Sanitize nullable string fields before patching the form.
        // Root cause: a previous bad submit called FormData.set(key, null) which
        // JS silently converts to the STRING "null"; PHP saved that string to the
        // DB, so the API now returns "null" (string) rather than JS null.
        // Previous fix only caught === null / === undefined; this covers the
        // string variants too.
        const EMPTY_SENTINEL = /^(null|undefined|NULL|UNDEFINED)$/;
        const nullableStrings = [
          'nickname', 'venmo', 'dob',
          'legal_address', 'legal_city', 'legal_zip_code',
          'mailing_address', 'mailing_city', 'mailing_zip_code',
          'favorite_coffee_shop', 'favorite_restaurant',
          'favorite_place_to_shop', 'favorite_charity',
        ];
        nullableStrings.forEach(f => {
          const v = this.user[f];
          if (v == null || (typeof v === 'string' && EMPTY_SENTINEL.test(v.trim()))) {
            this.user[f] = '';
          }
        });

        // DOB is a `date` cast on the backend, so the API serializes it as a full
        // ISO string (e.g. "1994-07-23T00:00:00.000000Z"). A native
        // <ion-input type="date"> only accepts a plain "YYYY-MM-DD" value and
        // silently shows nothing otherwise — that's why the field looked empty
        // in the app while the web portal (which formats to Y-m-d) worked.
        this.user.dob = this.toDateInputValue(this.user.dob);

        this.profileForm.patchValue(this.user);
        this.profileForm.markAsPristine();
        this.profileChanged = false;

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
    const value = event?.detail?.checked ?? event?.target?.checked ?? false;
    this.profileForm.controls['same_as_legal'].setValue(!!value);
    this.profileForm.markAsDirty();
    this.profileChanged = true;
    if (value) {
      this.profileForm.controls['mailing_address'].setValue(this.profileForm.value.legal_address);
      this.profileForm.controls['mailing_city'].setValue(this.profileForm.value.legal_city);
      this.profileForm.controls['mailing_state_id'].setValue(this.profileForm.value.legal_state_id);
      this.profileForm.controls['mailing_zip_code'].setValue(this.profileForm.value.legal_zip_code);
    } else {

    }
  }

  submit(formValue: any) {
    // Guard against a double-submit while a save is already in flight.
    if (this.isSaving) {
      return;
    }

    // If the form is invalid, never silently block. Surface the inline errors
    // (markAllAsTouched) AND tell the user exactly which fields need attention,
    // then scroll to the first problem field.
    if (this.profileForm.invalid) {
      this.profileForm.markAllAsTouched();
      const missing = this.getInvalidFieldLabels();
      const message = missing.length
        ? 'Please complete the following before saving:\n\n• ' + missing.join('\n• ')
        : 'Please correct the highlighted fields before saving.';
      this.common.presentAlert(message);
      this.scrollToFirstInvalid();
      return;
    }

    // Always start with a fresh FormData to avoid stale / accumulated entries
    this.formData = new FormData();

    // Re-attach the selected profile image if the user chose one
    if (this.selectedImageFile) {
      this.formData.set('profile_image', this.selectedImageFile);
    }

    delete formValue.profile_image;
    // market_id is not part of this form and must never be resubmitted from the
    // mobile profile (markets live in the ambassador_market pivot, assigned by
    // Admins/Managers). Strip any stale value that may have leaked into the
    // stored user object so it can't be written back to the legacy column.
    delete formValue.market_id;

    // State is stored server-side as a short code (e.g. "WI"), but the form keeps
    // it in the *_state_id controls. The API resolves state from legal_state /
    // mailing_state first; if only *_state_id is sent it runs State::find() which
    // expects a numeric id, so a short code silently fails to persist. Mirror the
    // short code into the legal_state / mailing_state keys the API actually reads.
    formValue.legal_state = formValue.legal_state_id ?? '';
    formValue.mailing_state = formValue.mailing_state_id ?? '';

    for (const key in formValue) {
      const value = formValue[key];

      // FormData.set() calls String() on any non-Blob value, so null → "null"
      // and undefined → "undefined". Convert both to empty string to prevent
      // the DB from storing the literal strings "null" / "undefined".
      let converted =
        value === true  ? 1 :
        value === false ? 0 :
        value == null   ? '' :
        value;

      this.formData.set(key, converted);
    }
    this.isSaving = true;
    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/update_profile', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.isSaving = false;
      this.common.dismissLoading();
      res.data.token = this.user.token;
      // Note: legal_state_id / mailing_state_id come back as short codes (e.g.
      // "WI"), not numeric ids, so they must NOT be coerced with +value (that
      // would yield NaN). They are used as-is to populate the state inputs.
      const updatedUser = {
        ...this.user,
        ...res?.data,
        ...formValue,
        token: this.user.token,
      };
      this.user = updatedUser;
      // Persist submitted values before navigation, even if an older API response
      // does not yet serialize every Ambassador profile field.
      await this.storage.set('apro_user', updatedUser);
      this.profileForm.patchValue(updatedUser);
      // Mark the form pristine so the Save button disables until the user
      // makes another change (covers the case where they dismiss the alert).
      this.profileForm.markAsPristine();
      this.profileChanged = false;
      let check = await this.common.presentAlertWithPromise('Success', res?.message);
      if (check) {
        this.nav.navigateRoot('/tabs/ambassador-home');
      }
    }, err => {
      this.isSaving = false;
      this.common.dismissLoading();
      const raw: string = err?.error?.message ?? '';
      // Replace technical lat/lng field names with a user-friendly message.
      const msg = /latitude|longitude/i.test(raw)
        ? 'Please choose your location on the map before saving your profile.'
        : (raw || 'Unable to save your profile. Please try again.');
      this.common.presentAlert(msg);
    });
  }
  /**
   * Returns friendly labels for every enabled, invalid control so the user can
   * be told exactly what to fix. Disabled controls (e.g. mailing fields while
   * "Same as Legal" is checked) are excluded because Angular ignores them for
   * validity anyway.
   */
  private getInvalidFieldLabels(): string[] {
    const labels: string[] = [];
    Object.keys(this.profileForm.controls).forEach((name) => {
      const control = this.profileForm.get(name);
      if (control && control.enabled && control.invalid && this.fieldLabels[name]) {
        labels.push(this.fieldLabels[name]);
      }
    });
    return labels;
  }

  /** Scrolls the first invalid form control into view after errors are shown. */
  private scrollToFirstInvalid(): void {
    setTimeout(() => {
      const el = document.querySelector(
        'ion-input.ng-invalid, ion-select.ng-invalid, ion-checkbox.ng-invalid'
      ) as HTMLElement | null;
      el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 150);
  }

  onProfileFieldChange(event: Event) {
    // Mark the page changed before reading the Ionic event target. On native
    // WebViews the event can originate inside the component's shadow DOM.
    this.profileChanged = true;
    const target = event.target as HTMLElement | null;
    const controlName = target?.getAttribute('formcontrolname');
    if (!controlName) return;

    const control = this.profileForm.get(controlName);
    const detail = (event as CustomEvent).detail;
    const value = typeof detail?.checked === 'boolean' ? detail.checked : detail?.value;
    if (value !== undefined && control?.value !== value) {
      control?.setValue(value);
    }
    control?.markAsDirty();
    this.profileChanged = true;
  }

  onChange(event: any) {
    const file: File | undefined = event.target.files?.[0];
    if (file) {
      this.selectedImageFile = file;
      this.user.profile_image = URL.createObjectURL(file);
      // Profile image is handled outside the reactive form; mark the form
      // dirty manually so the Save button enables after an image selection.
      this.profileForm.markAsDirty();
      this.profileChanged = true;
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
      this.patchLiquorLicense(res?.data?.liquor_license);
    }, err => {
      this.isSpinner = false;
      console.log('err', err);
    });
  };

  /**
   * Normalizes any backend date representation into the plain "YYYY-MM-DD"
   * string a native <ion-input type="date"> requires. Handles ISO strings
   * (with or without a time/timezone suffix), Date objects, and already-clean
   * "YYYY-MM-DD" values. Returns '' for null/empty/"null"/"undefined" so the
   * control clears cleanly instead of showing a broken value.
   */
  private toDateInputValue(value: any): string {
    if (value == null) {
      return '';
    }
    if (value instanceof Date && !isNaN(value.getTime())) {
      return this.formatLocalDate(value);
    }
    const s = String(value).trim();
    if (!s || /^(null|undefined)$/i.test(s)) {
      return '';
    }
    // Fast path: ISO or "YYYY-MM-DD..." — take just the date portion.
    const iso = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (iso) {
      return `${iso[1]}-${iso[2]}-${iso[3]}`;
    }
    // Fallback: let the browser parse other formats, then reformat.
    const parsed = new Date(s);
    return isNaN(parsed.getTime()) ? '' : this.formatLocalDate(parsed);
  }

  private formatLocalDate(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  /**
   * Human-friendly label for a date value shown on the date trigger buttons
   * (e.g. "Jul 23, 1994"). Builds the Date from Y/M/D parts so it renders the
   * intended calendar day regardless of timezone (avoids the classic
   * "YYYY-MM-DD parsed as UTC → off by one day" bug). Returns '' when empty.
   */
  displayDate(value: any): string {
    const iso = this.toDateInputValue(value);
    if (!iso) {
      return '';
    }
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  }

  /**
   * Prefill the liquor-license controls from the server record. The record is
   * null when the ambassador has never saved one, in which case the controls
   * keep their empty defaults. Date fields arrive as ISO strings (e.g.
   * "2024-01-15T00:00:00.000000Z"); the <ion-input type="date"> control needs a
   * plain "YYYY-MM-DD" value, so we take the first 10 characters.
   */
  private patchLiquorLicense(liquor: any) {
    if (!liquor) {
      return;
    }
    this.profileForm.patchValue({
      has_liquor_license: !!+liquor.has_liquor_license,
      document_on_file: !!+liquor.document_on_file,
      license_number: liquor.license_number ?? '',
      issuing_state: liquor.issuing_state ?? '',
      issued_at: this.toDateInputValue(liquor.issued_at),
      expires_at: this.toDateInputValue(liquor.expires_at),
      notes: liquor.notes ?? '',
    });
    // Prefilling from the server is not a user edit — keep the form pristine so
    // the Save button state and profileChanged flag are not falsely triggered.
    this.profileForm.markAsPristine();
    this.profileChanged = false;
  }
  async chooseItem(item: any, key: string = "") {
    if (!(await this.ensureMapsReady())) {
      return;
    }
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

        const addressType = key === 'legal_address' ? 'legal' : 'mailing';
        this.profileForm.controls[`${addressType}_city`].setValue(city);
        this.profileForm.controls[`${addressType}_zip_code`].setValue(zip);

        const selectedState = this.data?.states?.find(
          (s: any) => s.name === state || s.short_name === state
        );
        if (selectedState) {
          this.profileForm.controls[`${addressType}_state_id`].setValue(selectedState.short_name ?? selectedState.name);
        }

        // The legal address is the profile location used for event matching.
        if (key === 'legal_address' && results[0].geometry?.location) {
          this.setMapCoordinates(
            results[0].geometry.location.lat(),
            results[0].geometry.location.lng()
          );
        }

        // If "Same as Legal" is on, mirror the newly selected legal address
        // into the mailing fields so they stay in sync instead of going stale.
        if (addressType === 'legal' && this.profileForm.value.same_as_legal) {
          this.profileForm.controls['mailing_address'].setValue(results[0].formatted_address);
          this.profileForm.controls['mailing_city'].setValue(city);
          this.profileForm.controls['mailing_zip_code'].setValue(zip);
          if (selectedState) {
            this.profileForm.controls['mailing_state_id'].setValue(selectedState.short_name ?? selectedState.name);
          }
        }

        this.profileForm.markAsDirty();
        this.profileChanged = true;
      }
    });
  }

  async updateSearch(event: any, key: any = '') {
    const isMailing = key === 'mailing_address';
    const isSameAsLegal = !!this.profileForm.value.same_as_legal;
    const typedValue = event?.detail?.value ?? '';

    if (isMailing) {
      // Editing the mailing address directly means the user is diverging from
      // the legal address, so "Same as Legal" no longer holds. (The input is
      // disabled while the box is checked, but guard anyway.)
      if (isSameAsLegal) {
        this.profileForm.controls['same_as_legal'].setValue(false);
        this.profileForm.controls['same_as_legal'].updateValueAndValidity();
      }
    } else if (isSameAsLegal) {
      // Editing the legal address while "Same as Legal" is on: keep the box
      // checked and mirror the change into mailing so it never goes stale.
      this.profileForm.controls['mailing_address'].setValue(typedValue);
    }

    if (event?.detail?.value == '') {
      this.clearAutocomplete(key);
      return;
    }
    if (!(await this.ensureMapsReady()) || !this.acService) {
      this.clearAutocomplete(key);
      return;
    }
    let self = this;
    let config = {
      input: event?.detail?.value,
      componentRestrictions: { country: ["us"] },
      types: ['address'],
    }
    this.acService.getPlacePredictions(config, function (predictions: any, status: any) {
      const items = (predictions ?? []);
      if (isMailing) {
        self.autocompleteItemsMailing = items;
      } else {
        self.autocompleteItems = items;
      }
    });
  }

  /** Clears the suggestion list for the given address field. */
  private clearAutocomplete(key: any = '') {
    if (key === 'mailing_address') {
      this.autocompleteItemsMailing = [];
    } else {
      this.autocompleteItems = [];
    }
  }

  /**
   * Dismisses the autocomplete dropdown when the address field loses focus.
   * Deferred slightly so a tap on a suggestion still registers with chooseItem()
   * before the list is cleared.
   */
  dismissAutocomplete(key: any = '') {
    setTimeout(() => this.clearAutocomplete(key), 200);
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

  private async geocodeAddress(): Promise<{ lat: number; lng: number } | null> {
    const address = this.buildAddressQuery();
    if (!address) {
      return null;
    }

    if (!(await this.ensureMapsReady())) {
      return null;
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

    if (!(await this.ensureMapsReady())) {
      this.common.presentAlert('Map is unavailable right now. Please check your connection and try again.');
      this.closeMapModal();
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
