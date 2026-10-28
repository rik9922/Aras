import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, ValidationErrors, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import * as moment from 'moment';
declare var jQuery: any;

/** Admin and manager roles use the same event create/update flow as the web app. */
const ROLE_ADMIN = 1;
const ROLE_MANAGER = 6;

@Component({
  selector: 'app-add-events',
  templateUrl: './add-events.page.html',
  styleUrls: ['./add-events.page.scss'],
  standalone: false
})
export class AddEventsPage implements OnInit {
  eventForm: FormGroup = new FormGroup({});
  params: any = { title: 'Add Event ', is_back: true };
  user: any;

  /** Small-dropdown data loaded from get_form_data */
  formDropdowns: any = {
    departments: [],
    event_types: [],
    managers: [],
    clients: [],
    payment_types: [],
    regions: [],
  };

  emailData: any = [];
  selectedEmail: any = [];
  selectedAmbassdors: any = [];
  formdata: any;
  formData: any;
  eventAttechments: any = [];
  base_url: any;
  start_time_model: any;
  end_time_model: any;
  minStartTime: string = '';
  minEndTime: string = '';
  private timeDefaultsCanFollowTimezone = true;
  booked_date_model: any;
  attachedImage: any;
  private selectedAttachmentFiles: File[] = [];
  currentDate: any;
  today: string = moment().format('YYYY-MM-DD');
  timezoneDisplay: string = '';
  isManager = false;
  canManageEvents = false;

  // Typeahead pre-population for edit mode
  venueInitialValue: any = null;
  venueInitialLabel: string = '';
  brandInitialValue: any = null;
  brandInitialLabel: string = '';
  distributorInitialValue: any = null;
  distributorInitialLabel: string = '';
  marketInitialValue: any = null;
  marketInitialLabel: string = '';

  validation_messages = {
    event_name: [
      { type: 'pattern', message: 'Please enter a valid event name' }
    ],
    city: [
      { type: 'maxlength', message: 'City must be 255 characters or less.' },
    ],
    venue_name: [
      { type: 'required', message: 'Venue is required.' },
    ],
    department_id: [
      { type: 'required', message: 'Select department' },
    ],
    distributor_id: [
      { type: 'required', message: 'Select distributor' },
    ],
    brand_id: [
      { type: 'required', message: 'Select brand' },
    ],
    market_id: [
      { type: 'required', message: 'Select market' },
    ],
    manager_id: [
      { type: 'required', message: 'Select manager' },
    ],
    varietals: [
      { type: 'required', message: 'Varietals is required' },
    ],
    notes: [
      { type: 'required', message: 'Notes is required' },
    ],
    internal_notes: [
      { type: 'required', message: 'Internal notes is required' },
    ],
    expedite_fee: [
      { type: 'required', message: 'Expedite fee is required' },
      { type: 'pattern', message: 'Please enter a valid expedite fee.' },
    ],
    shipping_fee: [
      { type: 'required', message: 'Shipping fee is required' },
      { type: 'pattern', message: 'Please enter a valid shipping fee.' },
    ],
    parking_fee: [
      { type: 'required', message: 'Parking fee is required' },
      { type: 'pattern', message: 'Please enter a valid parking fee.' },
    ],
    drive_time: [
      { type: 'required', message: 'Drive time is required' },
      { type: 'pattern', message: 'Please enter a valid drive time.' },
    ],
    payment_type: [
      { type: 'required', message: 'Payment type is required' },
    ],
    uniform: [
      { type: 'required', message: 'Uniform is required' },
    ],
    client_id: [
      { type: 'required', message: 'Client is required' },
    ],
    booked_by: [
      { type: 'required', message: 'Booked by is required' },
      { type: 'emailList', message: 'Enter one or more valid email addresses separated by commas.' },
    ],
    region: [
      { type: 'required', message: 'Select region' },
    ],
  };

  is_kit_requied: boolean = false;

  // ── Billing Parties ──────────────────────────────────────────
  billingPartyIds: number[] = [];
  primaryBillingPartyId: number | null = null;
  laborRates: { [clientId: number]: string } = {};
  billingDefaultsCache: { [clientId: number]: any } = {};
  showBillingFields: boolean = false;

  constructor(
    public fb: FormBuilder,
    public common: CommonService,
    public event: EventsService,
    public router: Router,
    public apiService: ApiService,
    public storage: StorageService,
    public nav: NavController,
    public cdrf: ChangeDetectorRef
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.formdata = this.router.getCurrentNavigation()?.extras?.state;
      this.params.title = 'Update Event';
    }
  }

  ngOnInit() {
    this.eventForm = this.fb.group({
      auto_event_name: [''],
      event_name: [''],
      city: ['', Validators.maxLength(255)],
      venue_name: [null, Validators.compose([Validators.required])],
      event_address: [''],
      latitude: [''],
      longitude: [''],
      timezone: [''],
      timezone_label: [''],
      department_id: [null, Validators.compose([Validators.required])],
      event_type_id: [null],
      position: [0],
      age_restricted: [true],
      apply_event: [true],
      recap_needed: [true],   // always checked by default on new events
      distributor_id: [null, Validators.compose([Validators.required])],
      brand_id: [null, Validators.compose([Validators.required])],
      market_id: [null, Validators.compose([Validators.required])],
      manager_id: [null, Validators.compose([Validators.required])],
      start_time: [this.today, Validators.compose([Validators.required])],
      end_time: [this.today, Validators.compose([Validators.required])],
      booked_date: [this.today, Validators.compose([Validators.required])],
      booked_by: ['', [this.bookedByEmailsValidator]],
      varietals: [''],
      notes: [''],
      internal_notes: [''],
      expedite_fee: [''],
      shipping_fee: [''],
      parking_fee: [''],
      drive_time: [''],
      payment_type: [null, Validators.compose([Validators.required])],
      receipt: [true],
      kit: [false],
      kit_description: [''],
      uniform: ['', Validators.compose([Validators.required])],
      client_id: ['', Validators.compose([Validators.required])],
      billing_party_ids: [[]],
      primary_billing_party_id: [null],
      buyback_amount: ['', [Validators.pattern(/^\d*\.?\d*$/)]],
      cups_amount: ['', [Validators.pattern(/^\d*\.?\d*$/)]],
      pinata_pay: [false],
      payment_status: [false],
      override_pay: [0],
      ambassador_email: [''],
      integrate_with_best: ['0'],
      region: ['', Validators.compose([Validators.required])],
      event_status: ['0'],
      ambassador_id: [null],
    });
  }

  ionViewWillEnter() {
    this.emailData = [];
    this.selectedEmail = [];
    const date = new Date();
    this.currentDate = date.toISOString().split('.')[0];
    this.minStartTime = date.toISOString().split('.')[0]; // no past dates on start
    this.minEndTime = date.toISOString().split('.')[0];   // will update when start changes
    this.formData = new FormData();

    this.storage.get('apro_user').then((data: any) => {
      if (!data) {
        this.nav.navigateRoot('/login');
        return;
      }

      this.user = data;
      const roleId = Number(data.role_id);
      this.canManageEvents = roleId === ROLE_ADMIN || roleId === ROLE_MANAGER;
      this.isManager = roleId === ROLE_MANAGER;

      if (!this.canManageEvents) {
        this.common.presentAlert('You do not have permission to create or edit events.');
        this.nav.back();
        return;
      }

      this.base_url = environment.base_url;
      this.initializeDefaultEventTimes(this.currentEventZone());
      this.loadFormDropdowns();
      setTimeout(() => this.initializeAmbassadorSelect(), 0);

      if (this.isManager && !this.formdata?.id) {
        this.applyManagerDefaults();
      }
    });
  }

  private applyManagerDefaults() {
    if (!this.user?.id) return;
    this.eventForm.patchValue({ manager_id: Number(this.user.id) });
    this.eventForm.controls['manager_id'].disable();
  }

  /** Load small dropdowns (departments, event types, managers, clients) */
  private loadFormDropdowns() {
    this.common.showLoader();
    this.apiService.getAuthData('admin/get_form_data', this.user.token).subscribe(
      (res: any) => {
        this.common.dismissLoading();
        this.formDropdowns = res?.data ?? this.formDropdowns;
        this.prefillEventNameForCreate(res?.data);
        this.setupBookedBySelect();

        if (this.formdata?.id) {
          this.getSingleEventData();
        } else if (this.isManager) {
          this.applyManagerDefaults();
        }
      },
      () => {
        this.common.dismissLoading();
      }
    );
  }

  private prefillEventNameForCreate(data: any) {
    if (this.formdata?.id) return;

    const autoEventName = data?.auto_event_name || data?.event_name || '';
    if (!autoEventName) return;

    this.eventForm.patchValue({
      auto_event_name: autoEventName,
      event_name: this.eventForm.value.event_name || autoEventName,
    });
  }

  private normalizeBookedByEmails(raw: any): string[] {
    let values: any = raw;
    if (typeof raw === 'string') {
      try {
        const decoded = JSON.parse(raw);
        values = Array.isArray(decoded) ? decoded : raw;
      } catch {
        values = raw;
      }
    }
    if (!Array.isArray(values)) {
      values = String(values ?? '').split(/[,;\n]+/);
    }

    const emails = values
      .map((value: any) => String(value).trim().toLowerCase())
      .filter((value: string) => value !== '');

    return Array.from(new Set(emails));
  }

  private bookedByEmailsValidator(control: AbstractControl): ValidationErrors | null {
    const raw = String(control.value ?? '').trim();
    if (!raw) return { required: true };

    const emails = raw.split(/[,;\n]+/).map(email => email.trim()).filter(Boolean);
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emails.length > 0 && emails.every(email => emailPattern.test(email))
      ? null
      : { emailList: true };
  }

  private setupBookedBySelect(bookedBy: any = null) {
    if (bookedBy === null || bookedBy === undefined) return;

    let parsedTags: any;
    try {
      parsedTags = typeof bookedBy === 'string' ? JSON.parse(bookedBy) : bookedBy;
    } catch {
      parsedTags = bookedBy;
    }
    if (!Array.isArray(parsedTags)) {
      parsedTags = [parsedTags];
    }

    this.eventForm.patchValue({ booked_by: parsedTags.map((value: any) => String(value).trim()).filter(Boolean).join(', ') });
  }

  private toBoolean(value: any): boolean {
    return value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
  }

  private toNumberOrNull(value: any): number | null {
    if (value === null || value === undefined || value === '') return null;
    const parsed = Number(value);
    return Number.isNaN(parsed) ? null : parsed;
  }

  private lookupDropdownLabel(collection: string, id: any, labelKey: string): string {
    const numericId = this.toNumberOrNull(id);
    const match = (this.formDropdowns?.[collection] || []).find((item: any) => {
      return item?.id === id || (numericId !== null && Number(item?.id) === numericId);
    });
    return match?.[labelKey] ? String(match[labelKey]) : '';
  }

  private initializeAmbassadorSelect() {
    if (!this.user?.token) return;
    const self = this;
    jQuery('#ambassador_email').select2({
      placeholder: 'Enter Email',
      tags: false,
      minimumInputLength: 3,
      width: 'resolve',
      allowClear: true,
      ajax: {
        url: environment.auth_url + 'admin/searchAmbassador',
        dataType: 'json',
        type: 'POST',
        contentType: 'application/json',
        headers: {
          Authorization: `Bearer ${this.user.token}`,
        },
        data: function (params: any) {
          return JSON.stringify({ search: params.term });
        },
        processResults: function (data: any) {
          self.emailData = data.data ?? [];
          self.cdrf.detectChanges();
          return {
            results: self.emailData.map((item: any) => ({
              id: item.id,
              text: item.email,
            })),
          };
        },
      },
    });

    jQuery('#ambassador_email').on('change', (event: any) => {
      const selectId = event.target.value;
      if (!selectId) {
        this.eventForm.controls['ambassador_email'].setValue('');
        return;
      }
      const ambassador = this.emailData.find((x: any) => x.id == selectId);
      if (ambassador) {
        this.eventForm.controls['ambassador_email'].setValue(ambassador.email);
        this.selectedEmail = [ambassador];
      }
      this.cdrf.detectChanges();
    });
  }

  onSubmit(_formValue: any) {
    if (!this.validateVenueLocation()) return;
    if (this.eventForm.invalid) {
      this.eventForm.markAllAsTouched();
      this.eventForm.updateValueAndValidity();
      this.common.presentAlert('Please complete required fields before saving.');
      this.cdrf.detectChanges();
      return;
    }

    const payload = { ...this.eventForm.getRawValue() };
    delete payload.attachments;

    // Remap venue_name → event_location_id (backend expects event_location_id)
    payload.event_location_id = payload.venue_name;
    delete payload.venue_name;

    // Add billing fields
    payload.billing_party_ids = this.billingPartyIds;
    payload.primary_billing_party_id = this.primaryBillingPartyId;
    payload.labor_rates = this.laborRates;
    // client_id is already synced to primaryBillingPartyId

    payload.start_time = this.formatDateTimeForPayload(payload.start_time);
    payload.end_time = this.formatDateTimeForPayload(payload.end_time);
    if (payload.booked_date) {
      payload.booked_date = moment(payload.booked_date).format('YYYY-MM-DD');
    }

    if (this.isManager && this.eventForm.controls['manager_id'].disabled) {
      payload.manager_id = this.user.id;
    }

    if (this.formdata?.id) {
      payload.id = this.formdata.id;
    }
    if (payload?.apply_event) {
      payload.apply_event = 'on';
    }
    // Normalize booked_by to the shared JSON array-of-email shape for the API.
    payload.booked_by = JSON.stringify(this.normalizeBookedByEmails(payload.booked_by));
    if (this.selectedAmbassdors?.length > 0) {
      payload.selectedAmbassadorIds = this.selectedAmbassdors.map((obj: any) => obj.id).join(',');
    }

    this.formData = new FormData();
    for (const key in payload) {
      if (payload[key] !== null && payload[key] !== undefined) {
        this.appendToFormData(this.formData, key, payload[key]);
      }
    }
    this.selectedAttachmentFiles.forEach((file) => {
      this.formData.append('attachments[]', file);
    });

    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_event', this.formData, this.user?.token).subscribe(
      async (res: any) => {
        this.common.dismissLoading();
        const check: any = await this.common.presentAlertWithPromise('Success', res?.message);
        if (check) {
          this.event.publish('reload_events', 1);
          this.nav.back();
        }
      },
      () => {
        this.common.dismissLoading();
      }
    );
  }

  private appendToFormData(formData: FormData, key: string, value: any) {
    if (Array.isArray(value)) {
      value.forEach((item) => formData.append(`${key}[]`, item));
      return;
    }
    if (value && typeof value === 'object' && !(value instanceof File) && !(value instanceof Blob)) {
      Object.keys(value).forEach((childKey) => {
        if (value[childKey] !== null && value[childKey] !== undefined) {
          formData.append(`${key}[${childKey}]`, value[childKey]);
        }
      });
      return;
    }
    formData.append(key, value);
  }

  private validateVenueLocation(): boolean {
    const venueId = this.eventForm.value.venue_name;
    const lat = this.eventForm.value.latitude;
    const lng = this.eventForm.value.longitude;

    if (!venueId) {
      this.common.presentAlert('Please select a venue.');
      return false;
    }
    if (!lat || !lng) {
      this.common.presentAlert('Selected venue is missing coordinates. Update the venue in Locations first.');
      return false;
    }
    return true;
  }

  // ── Billing Parties methods ──────────────────────────────────

  onBillingPartiesChange(selectedIds: number[]) {
    this.billingPartyIds = selectedIds || [];
    this.showBillingFields = this.billingPartyIds.length > 0;

    if (this.billingPartyIds.length === 1) {
      this.primaryBillingPartyId = this.billingPartyIds[0];
      this.eventForm.patchValue({ primary_billing_party_id: this.primaryBillingPartyId });
      this.syncClientId();
      this.loadDefaultsForParty(this.billingPartyIds[0], true);
    } else if (this.billingPartyIds.length > 1) {
      if (!this.billingPartyIds.includes(this.primaryBillingPartyId!)) {
        this.primaryBillingPartyId = this.billingPartyIds[0];
        this.eventForm.patchValue({ primary_billing_party_id: this.primaryBillingPartyId });
      }
      this.syncClientId();
      this.billingPartyIds.forEach(id => this.loadDefaultsForParty(id, id === this.primaryBillingPartyId));
    } else {
      this.primaryBillingPartyId = null;
      this.eventForm.patchValue({ primary_billing_party_id: null, client_id: '' });
    }
    this.cdrf.detectChanges();
  }

  onPrimaryPartyChange(clientId: number) {
    this.primaryBillingPartyId = clientId;
    this.syncClientId();
    this.loadDefaultsForParty(clientId, true);
    this.cdrf.detectChanges();
  }

  syncClientId() {
    this.eventForm.patchValue({ client_id: this.primaryBillingPartyId || '' });
  }

  getLaborRate(clientId: number): string {
    return this.laborRates[clientId] ?? '';
  }

  setLaborRate(clientId: number, value: string) {
    this.laborRates[clientId] = value;
  }

  getClientName(clientId: number): string {
    const client = (this.formDropdowns?.clients || []).find((c: any) => c.id === clientId);
    return client?.name || String(clientId);
  }

  loadDefaultsForParty(clientId: number, isPrimary: boolean) {
    if (!clientId || !this.user?.token) return;
    if (this.billingDefaultsCache[clientId]) {
      this.applyDefaults(clientId, isPrimary, this.billingDefaultsCache[clientId]);
      return;
    }
    this.apiService.getAuthData(`admin/client_billing_defaults?client_id=${clientId}`, this.user.token)
      .subscribe((res: any) => {
        const d = res?.data;
        if (d) {
          this.billingDefaultsCache[clientId] = d;
          this.applyDefaults(clientId, isPrimary, d);
        }
      });
  }

  applyDefaults(clientId: number, isPrimary: boolean, defaults: any) {
    if (!this.laborRates[clientId] && defaults.labor_amount != null) {
      this.laborRates[clientId] = String(defaults.labor_amount);
    }
    if (isPrimary) {
      if (defaults.cups_amount != null && !this.eventForm.value.cups_amount) {
        this.eventForm.patchValue({ cups_amount: defaults.cups_amount });
      }
      if (defaults.buyback_amount != null && !this.eventForm.value.buyback_amount) {
        this.eventForm.patchValue({ buyback_amount: defaults.buyback_amount });
      }
    }
    this.cdrf.detectChanges();
  }

  private loadEventBillingParties() {
    if (!this.formdata?.id || !this.user?.token) return;
    this.apiService.getAuthData(`admin/event_billing_parties?event_id=${this.formdata.id}`, this.user.token)
      .subscribe((res: any) => {
        const parties = res?.data || [];
        this.billingPartyIds = parties.map((p: any) => p.client_id);
        const primary = parties.find((p: any) => p.is_primary);
        this.primaryBillingPartyId = primary?.client_id || (parties[0]?.client_id || null);
        parties.forEach((p: any) => {
          if (p.labor_rate != null) this.laborRates[p.client_id] = String(p.labor_rate);
        });
        this.showBillingFields = this.billingPartyIds.length > 0;
        this.eventForm.patchValue({
          billing_party_ids: this.billingPartyIds,
          primary_billing_party_id: this.primaryBillingPartyId,
          client_id: this.primaryBillingPartyId || '',
        });
        this.cdrf.detectChanges();
      });
  }

  getSingleEventData() {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_event_details?id=${this.formdata?.id}`, this.user?.token).subscribe(
      (res: any) => {
        this.common.dismissLoading();
        const ev = res?.data?.event;
        if (!ev) return;

        // Set up booked_by with the existing JSON array or legacy string value.
        this.setupBookedBySelect(ev.booked_by);

        // Parse numeric IDs
        ev.client_id = parseInt(ev.client_id, 10);
        ev.department_id = ev.department_id ? parseInt(ev.department_id, 10) : null;
        ev.event_type_id = ev.event_type_id ? parseInt(ev.event_type_id, 10) : null;
        ev.manager_id = ev.manager_id ? parseInt(ev.manager_id, 10) : null;

        // Set typeahead pre-population for venue
        const venueId = ev.event_location_id ?? ev.venue_name;
        if (venueId) {
          this.venueInitialValue = this.toNumberOrNull(venueId) ?? venueId;
          this.venueInitialLabel = ev.venue_display_name
            || ev.event_location_label
            || ev.location?.name
            || ev.event_address
            || String(venueId);
          this.eventForm.controls['venue_name'].setValue(this.venueInitialValue);
          // Auto-fill address from event
          this.eventForm.patchValue({
            event_address: ev.event_address || '',
            latitude: ev.latitude || '',
            longitude: ev.longitude || '',
            timezone: ev.timezone || '',
            timezone_label: ev.timezone_label || '',
            city: ev.city || '',
          });
          this.timezoneDisplay = this.buildTimezoneDisplay(ev);
        }

        // Set typeahead pre-population for brand
        if (ev.brand_id) {
          this.brandInitialValue = this.toNumberOrNull(ev.brand_id) ?? ev.brand_id;
          this.brandInitialLabel = ev.brand_name
            || ev.brand?.brand_name
            || this.lookupDropdownLabel('brands', ev.brand_id, 'brand_name')
            || String(ev.brand_id);
          this.eventForm.controls['brand_id'].setValue(this.brandInitialValue);
        }

        // Set typeahead pre-population for distributor
        if (ev.distributor_id) {
          this.distributorInitialValue = this.toNumberOrNull(ev.distributor_id) ?? ev.distributor_id;
          this.distributorInitialLabel = ev.distributor_name
            || ev.distributor?.name
            || this.lookupDropdownLabel('distributors', ev.distributor_id, 'name')
            || String(ev.distributor_id);
          this.eventForm.controls['distributor_id'].setValue(this.distributorInitialValue);
        }

        // Set typeahead pre-population for market
        if (ev.market_id) {
          this.marketInitialValue = this.toNumberOrNull(ev.market_id) ?? ev.market_id;
          this.marketInitialLabel = ev.market_name
            || ev.market?.name
            || ev.markets?.[0]?.name
            || this.lookupDropdownLabel('markets', ev.market_id, 'name')
            || String(ev.market_id);
          this.eventForm.controls['market_id'].setValue(this.marketInitialValue);
        }

        // Datetime models
        this.booked_date_model = ev.booked_date;
        const rawStart = ev.start_time_raw || ev.start_time;
        const rawEnd = ev.end_time_raw || ev.end_time;
        this.timeDefaultsCanFollowTimezone = false;
        this.start_time_model = this.toDatetimeLocalValue(rawStart);
        this.end_time_model = this.toDatetimeLocalValue(rawEnd);

        if (this.isManager) {
          this.eventForm.controls['manager_id'].disable();
        }

        this.selectedAmbassdors = ev.ambassadors ?? [];
        this.eventAttechments = ev.event_attachments ?? [];

        // Load billing parties for this event
        this.loadEventBillingParties();

        // Patch remaining fields (excludes typeahead ones already set above)
        this.eventForm.patchValue({
          event_name: ev.event_name,
          city: ev.city,
          department_id: ev.department_id,
          event_type_id: ev.event_type_id,
          position: ev.position,
          age_restricted: this.toBoolean(ev.age_restricted),
          apply_event: this.toBoolean(ev.apply_event),
          recap_needed: this.toBoolean(ev.recap_needed),
          manager_id: ev.manager_id,
          start_time: rawStart,
          end_time: rawEnd,
          booked_date: ev.booked_date,
          varietals: ev.varietals,
          notes: ev.notes,
          internal_notes: ev.internal_notes,
          expedite_fee: ev.expedite_fee,
          shipping_fee: ev.shipping_fee,
          parking_fee: ev.parking_fee,
          drive_time: ev.drive_time,
          payment_type: ev.payment_type,
          receipt: this.toBoolean(ev.receipt),
          kit: this.toBoolean(ev.kit),
          kit_description: ev.kit_description,
          uniform: ev.uniform,
          client_id: ev.client_id,
          pinata_pay: this.toBoolean(ev.pinata_pay),
          payment_status: this.toBoolean(ev.payment_status),
          override_pay: ev.override_pay,
          region: ev.region,
          event_status: ev.event_status ?? '0',
        });
        this.is_kit_requied = this.toBoolean(ev.kit);
        this.eventForm.updateValueAndValidity();
        this.cdrf.detectChanges();
      },
      () => {
        this.common.dismissLoading();
      }
    );
  }

  private toDatetimeLocalValue(value: string): string | null {
    if (!value || !/^\d{4}-\d{2}-\d{2}/.test(String(value))) return null;
    return this.normalizeWallDateTime(value);
  }

  private normalizeWallDateTime(value: any): string | null {
    if (!value) return null;
    const match = String(value).trim().match(/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/);
    if (!match) return null;
    return `${match[1]}T${match[2]}:00`;
  }

  private formatDateTimeForPayload(value: any): string {
    const normalized = this.normalizeWallDateTime(value);
    return normalized ? normalized.slice(0, 16).replace('T', ' ') : '';
  }

  private currentEventZone(): string {
    return this.eventForm?.value?.timezone || 'UTC';
  }

  private timeZoneOffsetMinutes(epochMs: number, zone: string): number {
    const parts = new Intl.DateTimeFormat('en-US', {
      timeZone: zone || 'UTC',
      hour12: false,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    }).formatToParts(new Date(epochMs));
    const value = (type: string) => parts.find(part => part.type === type)?.value || '00';
    const hour = value('hour') === '24' ? '00' : value('hour');
    const zonedAsUtc = Date.UTC(
      Number(value('year')),
      Number(value('month')) - 1,
      Number(value('day')),
      Number(hour),
      Number(value('minute')),
      Number(value('second')),
    );
    return (zonedAsUtc - epochMs) / 60000;
  }

  private wallDateTimeToEpoch(value: any, zone: string): number | null {
    const normalized = this.normalizeWallDateTime(value);
    if (!normalized) return null;

    const match = normalized.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
    if (!match) return null;

    const wallAsUtc = Date.UTC(
      Number(match[1]),
      Number(match[2]) - 1,
      Number(match[3]),
      Number(match[4]),
      Number(match[5]),
      0,
    );

    try {
      let epoch = wallAsUtc - this.timeZoneOffsetMinutes(wallAsUtc, zone) * 60000;
      epoch = wallAsUtc - this.timeZoneOffsetMinutes(epoch, zone) * 60000;
      return epoch;
    } catch {
      return moment(normalized).valueOf();
    }
  }

  private epochToWallDateTime(epochMs: number, zone: string): string {
    try {
      const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: zone || 'UTC',
        hour12: false,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      }).formatToParts(new Date(epochMs));
      const value = (type: string) => parts.find(part => part.type === type)?.value || '00';
      const hour = value('hour') === '24' ? '00' : value('hour');
      return `${value('year')}-${value('month')}-${value('day')}T${hour}:${value('minute')}:00`;
    } catch {
      return moment(epochMs).format('YYYY-MM-DDTHH:mm:ss');
    }
  }

  private oneHourAfter(value: any, zone: string): string {
    const epoch = this.wallDateTimeToEpoch(value, zone);
    if (epoch === null) {
      return moment(value).add(1, 'hour').format('YYYY-MM-DDTHH:mm:ss');
    }
    return this.epochToWallDateTime(epoch + 60 * 60 * 1000, zone);
  }

  private twoHoursAfter(value: any, zone: string): string {
    const epoch = this.wallDateTimeToEpoch(value, zone);
    if (epoch === null) {
      return moment(value).add(2, 'hours').format('YYYY-MM-DDTHH:mm:ss');
    }
    return this.epochToWallDateTime(epoch + 2 * 60 * 60 * 1000, zone);
  }

  private isEndAtLeastOneHourAfterStart(startValue: any, endValue: any, zone: string): boolean {
    const startEpoch = this.wallDateTimeToEpoch(startValue, zone);
    const endEpoch = this.wallDateTimeToEpoch(endValue, zone);
    if (startEpoch === null || endEpoch === null) return false;
    return endEpoch - startEpoch >= 60 * 60 * 1000;
  }

  private nowInEventZone(zone: string): moment.Moment {
    try {
      const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: zone || 'UTC',
        hour12: false,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      }).formatToParts(new Date());
      const value = (type: string) => parts.find(part => part.type === type)?.value || '00';
      const hour = value('hour') === '24' ? '00' : value('hour');
      return moment(`${value('year')}-${value('month')}-${value('day')}T${hour}:${value('minute')}:${value('second')}`);
    } catch {
      return moment();
    }
  }

  private roundUpToNearestFive(value: moment.Moment): moment.Moment {
    const rounded = value.clone().seconds(0).milliseconds(0);
    const remainder = rounded.minutes() % 5;
    if (remainder !== 0) {
      rounded.add(5 - remainder, 'minutes');
    }
    return rounded;
  }

  private initializeDefaultEventTimes(zone: string) {
    if (this.formdata?.id || !this.timeDefaultsCanFollowTimezone) return;

    const start = this.roundUpToNearestFive(this.nowInEventZone(zone));
    const end = start.clone().add(2, 'hours');
    const startValue = start.format('YYYY-MM-DDTHH:mm:ss');
    const endValue = end.format('YYYY-MM-DDTHH:mm:ss');

    this.start_time_model = startValue;
    this.end_time_model = endValue;
    this.eventForm.patchValue({
      start_time: startValue,
      end_time: endValue,
    });
  }

  setDateOnChange(event: any, key: string = '') {
    const value = this.normalizeWallDateTime(event?.detail?.value ?? event?.target?.value);
    if (!value || !key) return;

    if (key === 'start_time' || key === 'end_time') {
      this.timeDefaultsCanFollowTimezone = false;
    }

    this.eventForm.controls[key].setValue(value);
    this.eventForm.controls[key].updateValueAndValidity();

    if (key === 'start_time') {
      const zone = this.currentEventZone();
      const minEnd = this.oneHourAfter(value, zone);
      const defaultEnd = this.twoHoursAfter(value, zone);
      this.minEndTime = minEnd;

      this.end_time_model = defaultEnd;
      this.eventForm.controls['end_time'].setValue(defaultEnd);
      this.eventForm.controls['end_time'].updateValueAndValidity();
      this.cdrf.detectChanges();
    }

    if (key === 'end_time') {
      const startVal = this.eventForm.controls['start_time'].value;
      if (startVal) {
        const zone = this.currentEventZone();
        if (!this.isEndAtLeastOneHourAfterStart(startVal, value, zone)) {
          const corrected = this.oneHourAfter(startVal, zone);
          this.end_time_model = corrected;
          this.eventForm.controls['end_time'].setValue(corrected);
          this.eventForm.controls['end_time'].updateValueAndValidity();
          this.common.presentAlert('End time must be at least 1 hour after start time.');
          this.cdrf.detectChanges();
        }
      }
    }
  }

  onChangeFile(event: any) {
    const files = Array.from(event?.target?.files || []) as File[];
    if (files.length > 0) {
      this.selectedAttachmentFiles = files;
      this.attachedImage = URL.createObjectURL(files[0]);
    }
  }

  isSubmitDisabled(): boolean {
    return !this.formdata?.id && this.eventForm.invalid;
  }

  is_kit_required(event: any) {
    this.is_kit_requied = event?.detail?.checked;
  }

  goToAddVenue() {
    this.router.navigate(['/admin-add-location']);
  }

  /** Called by TypeaheadSelect when a venue is chosen */
  onVenueSelected(selection: any) {
    if (!selection) {
      this.eventForm.patchValue({
        venue_name: null,
        event_address: '',
        latitude: '',
        longitude: '',
        timezone: '',
        timezone_label: '',
        city: '',
      });
      this.timezoneDisplay = '';
      return;
    }
    const venue = selection.item;
    const addressParts = [venue?.address, venue?.city, venue?.state, venue?.zip_code].filter(Boolean);
    const address = addressParts.join(', ');
    this.eventForm.patchValue({
      venue_name: venue.id,
      event_address: address,
      latitude: venue.latitude || '',
      longitude: venue.longitude || '',
      timezone: venue.timezone || '',
      timezone_label: venue.timezone_label || '',
      city: venue.city || '',
    });
    this.timezoneDisplay = this.buildTimezoneDisplay(venue);
    this.initializeDefaultEventTimes(this.currentEventZone());
    this.cdrf.detectChanges();
  }

  /** Called by TypeaheadSelect for brand */
  onBrandSelected(selection: any) {
    this.eventForm.controls['brand_id'].setValue(selection ? selection.id : null);
  }

  /** Called by TypeaheadSelect for distributor */
  onDistributorSelected(selection: any) {
    this.eventForm.controls['distributor_id'].setValue(selection ? selection.id : null);
  }

  /** Called by TypeaheadSelect for market */
  onMarketSelected(selection: any) {
    this.eventForm.controls['market_id'].setValue(selection ? selection.id : null);
  }

  buildTimezoneDisplay(venue: any): string {
    if (venue?.timezone_label) {
      const region = venue?.timezone ? ` (${venue.timezone})` : '';
      return `${venue.timezone_label}${region}`;
    }
    return venue?.timezone || '';
  }

  async addAnAmbassdor() {
    if (!this.eventForm.value.ambassador_email) {
      this.common.presentAlert('Please select an ambassador first!');
      return;
    }
    if (this.eventForm.value.ambassador_email && (this.eventForm.value.position == 0 || !this.eventForm.value.position)) {
      this.common.presentAlert('Please add the position!');
      return;
    }
    if (this.eventForm.value.ambassador_email && this.eventForm.value.position) {
      if (this.eventForm.value.position <= this.selectedAmbassdors?.length) {
        this.common.presentAlert(`You can only add up to ${this.eventForm.value.position} ambassador(s).`);
        return;
      }
    }
    if (this.selectedAmbassdors?.length > 0) {
      const existing = this.selectedAmbassdors.find((x: any) => x.email == this.eventForm.value.ambassador_email);
      if (existing) {
        const check = await this.common.presentAlertWithPromise('Alert', 'This ambassador is already added!');
        if (check) {
          this.selectedEmail = [];
          this.eventForm.controls['ambassador_email'].setValue('');
          this.eventForm.controls['override_pay'].setValue('');
          jQuery('#ambassador_email').val(null).trigger('change');
        }
        return;
      }
    }
    if (this.eventForm.value.override_pay) {
      const data = this.selectedEmail.find((x: any) => this.eventForm.value.ambassador_email == x.email);
      if (data) {
        data.override_pay = this.eventForm.value.override_pay;
        const index = this.selectedEmail.findIndex((x: any) => x.id == data?.id);
        this.selectedEmail.splice(index, 1, data);
      }
    }
    this.eventForm.controls['ambassador_email'].setValue('');
    this.eventForm.controls['override_pay'].setValue('');
    jQuery('#ambassador_email').val(null).trigger('change');
    this.selectedAmbassdors.push(this.selectedEmail[0]);
    this.cdrf.detectChanges();
  }

  deleteAmbassdor(index: any) {
    this.selectedAmbassdors.splice(index, 1);
  }

  openFileUpload() {
    jQuery('#fileUpload').trigger('click');
  }
}
