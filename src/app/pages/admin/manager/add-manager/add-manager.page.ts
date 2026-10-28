import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { GoogleMapsLoaderService } from 'src/app/services/google-maps-loader.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

declare var google: any;
@Component({
  selector: 'app-add-manager',
  templateUrl: './add-manager.page.html',
  styleUrls: ['./add-manager.page.scss'],
  standalone: false
})
export class AddManagerPage implements OnInit {
  user: any;
  data: any;
  params: any = { title: "Add Manager", is_back: true };
  acService?: any;
  managerForm: FormGroup = new FormGroup({});
  passwordType: string = "password";
  passwordIcon: string = "eye-off";
  markets: any[] = [];
  hasManagerChanges: boolean = false;
  private initialManagerFormValue: string | null = null;
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
    address: [
      { type: "required", message: "Address is required." },
    ],
    password: [
      { type: "required", message: "Password is required." },
      { type: "minlength", message: "Password must have eight character" },
    ]
  };
  autocompleteItems: any = []
  constructor(
    public event: EventsService,
    public router: Router,
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public formBuilder: FormBuilder,
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
    this.managerForm = this.formBuilder.group({
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
      'status': new FormControl(1, Validators.compose([Validators.required])),
      'market_ids': new FormControl([]),
      'longitude': new FormControl('', Validators.compose([Validators.required])),
      'latitude': new FormControl('', Validators.compose([Validators.required])),
      'password': new FormControl('')
    });
    this.managerForm.controls['status'].valueChanges.subscribe((status: any) => {
      this.updateMarketAccess(status);
    });
    this.managerForm.valueChanges.subscribe(() => {
      this.updateManagerChangeState();
    });
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        void this.ensureMapsReady();
        this.getMarkets();
        if (this.data?.id) {
          this.params.title = 'Update Manager';
          this.getManagerById(this.data?.id);
          this.managerForm.controls['password'].clearValidators();
        } else {
          this.managerForm.controls['password'].setValidators(Validators.compose([Validators.required, Validators.minLength(8)]));
        }
        this.managerForm.controls['password'].updateValueAndValidity();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  submit(formValue: any) {
    if (!this.data?.id && this.managerForm.invalid) {
      this.managerForm.markAllAsTouched();
      return;
    }
    formValue.phone = this.normalizePhone(formValue.phone);
    formValue.market_ids = String(formValue.status) === '0'
      ? []
      : Array.isArray(formValue.market_ids)
      ? formValue.market_ids.map((id: any) => Number(id))
      : [];
    if (this.data?.id) {
      formValue.id = this.data.id;
    }
    if (!formValue?.password) {
      delete formValue.password;
    }
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_manager', formValue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      let check: any = await this.common.presentAlertWithPromise("Success", res?.message);
      this.event.publish('reload_managers', 1);
      if (check) {
        this.router.navigate(['/admin-manager-list']);
      }
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
      this.common.presentAlert(err?.error?.message);
    });
  }
  hideShowPassword() {
    this.passwordType = this.passwordType === "text" ? "password" : "text";
    this.passwordIcon = this.passwordIcon === "eye-off" ? "eye" : "eye-off";
  }

  getManagerById(managerId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_manager_by_id?id=${managerId}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      const manager = res.data;
      const managerMarkets = [
        manager?.manager_markets,
        manager?.managerMarkets,
        manager?.markets,
        manager?.market_ids,
      ].find((value: any) => Array.isArray(value) && value.length > 0) || [];
      const marketIds = managerMarkets
        .map((market: any) => Number(market?.id ?? market?.market_id ?? market))
        .filter(Boolean);
      this.managerForm.patchValue({
        ...manager,
        phone: this.normalizePhone(manager?.phone),
        market_ids: marketIds,
      });
      this.updateMarketAccess(this.managerForm.controls['status'].value);
      this.initialManagerFormValue = this.serializeManagerFormValue();
      this.hasManagerChanges = false;
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err)
    });
  }

  getMarkets() {
    this.apiService.getAuthData('admin/get_market_data?start=0&length=1000&draw=1', this.user?.token).subscribe({
      next: (res: any) => {
        const { rows } = resolveDataTableResponse(res);
        this.markets = rows;
      },
      error: (err: any) => {
        this.common.presentAlert(err?.error?.message || 'Unable to load markets.');
      }
    });
  }

  private updateMarketAccess(status: any) {
    const marketControl = this.managerForm.controls['market_ids'];
    if (String(status) === '0') {
      marketControl.setValue([], { emitEvent: false });
      marketControl.disable({ emitEvent: false });
      return;
    }
    marketControl.enable({ emitEvent: false });
  }

  private updateManagerChangeState() {
    this.hasManagerChanges = !!this.data?.id
      && this.initialManagerFormValue !== null
      && this.serializeManagerFormValue() !== this.initialManagerFormValue;
  }

  private serializeManagerFormValue(): string {
    const value = this.managerForm.getRawValue();
    return JSON.stringify({
      ...value,
      status: String(value.status ?? ''),
      market_ids: (value.market_ids || []).map((id: any) => Number(id)).sort((a: number, b: number) => a - b),
    });
  }

  onPhoneInput(event: any) {
    const phone = this.normalizePhone(event?.detail?.value);
    if (event?.target) {
      event.target.value = phone;
    }
    if (this.managerForm.controls['phone']?.value !== phone) {
      this.managerForm.controls['phone'].setValue(phone);
    }
  }

  private normalizePhone(value: any): string {
    return String(value ?? '').replace(/\D/g, '').slice(0, 12);
  }


  async chooseItem(item: any) {
    if (!(await this.ensureMapsReady())) {
      return;
    }
    let geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      'address': item.description
    }, async (results: any, status: any) => {
      this.autocompleteItems = [];
      this.managerForm.controls['address'].setValue(item.description);
      this.managerForm.controls['latitude'].setValue(results[0].geometry.location.lat());
      this.managerForm.controls['longitude'].setValue(results[0].geometry.location.lng());
    });
  }
  async updateSearch(event: any) {
    if (event?.detail?.value == '') {
      this.autocompleteItems = [];
      this.managerForm.controls['latitude'].setValue('');
      this.managerForm.controls['longitude'].setValue('');
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

}
