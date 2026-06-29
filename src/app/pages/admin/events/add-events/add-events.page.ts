import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
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
  data: any = [];
  emailData: any = [];
  selectedEmail: any = [];
  selectedAmbassdors: any = [];
  formdata: any;
  formData: any;
  eventAttechments: any = [];
  base_url: any;
  start_time_model: any;
  end_time_model: any;
  booked_date_model: any;
  attachedImage: any;
  currentDate: any;
  today: string = moment().format('YYYY-MM-DD');
  timezoneDisplay: string = '';
  isManager = false;
  canManageEvents = false;

  validation_messages = {
    event_name: [
      { type: 'required', message: 'Event Name is required.' },
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
    ],
    region: [
      { type: 'required', message: 'Select region' },
    ],
  };

  is_kit_requied: boolean = false;
  booked_options: any = [];

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
      event_name: ['', Validators.compose([Validators.required])],
      city: ['', Validators.maxLength(255)],
      venue_name: [null, Validators.compose([Validators.required])],
      venue_type_id: [null],
      event_address: [''],
      latitude: [''],
      longitude: [''],
      timezone_label: [''],
      department_id: [null, Validators.compose([Validators.required])],
      event_type_id: [null],
      position: [0, Validators.compose([Validators.required])],
      age_restricted: [true],
      apply_event: [true],
      distributor_id: [null, Validators.compose([Validators.required])],
      brand_id: [null, Validators.compose([Validators.required])],
      market_id: [null, Validators.compose([Validators.required])],
      manager_id: [null, Validators.compose([Validators.required])],
      timezone: [''],
      start_time: [this.today, Validators.compose([Validators.required])],
      end_time: [this.today, Validators.compose([Validators.required])],
      varietals: [''],
      notes: [''],
      internal_notes: [''],
      expedite_fee: ['', Validators.compose([Validators.required, Validators.pattern(/^\d+$/)])],
      shipping_fee: ['', Validators.compose([Validators.required, Validators.pattern(/^\d+$/)])],
      parking_fee: ['', Validators.compose([Validators.required, Validators.pattern(/^\d+$/)])],
      drive_time: ['', Validators.compose([Validators.required, Validators.pattern(/^\d+$/)])],
      payment_type: [null, Validators.compose([Validators.required])],
      receipt: [true],
      kit: [false],
      kit_description: [''],
      client_id: ['', Validators.compose([Validators.required])],
      uniform: ['', Validators.compose([Validators.required])],
      ambassador_email: [''],
      override_pay: [0],
      attachments: [''],
      booked_date: [this.today, Validators.compose([Validators.required])],
      booked_by: ['', Validators.compose([Validators.required])],
      integrate_with_best: [false, Validators.compose([Validators.required])],
      region: ['', Validators.compose([Validators.required])],
      event_status: ['active', Validators.compose([Validators.required])],
      ambassador_id: [null],
      pinata_pay: [false, Validators.compose([Validators.required])],
    });
  }

  ionViewWillEnter() {
    this.emailData = [];
    this.selectedEmail = [];
    const date = new Date();
    this.currentDate = date.toISOString().split('.')[0];
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
      this.getPageData();

      const removeValidation: any = ['expedite_fee', 'shipping_fee', 'parking_fee', 'drive_time'];
      removeValidation.forEach((element: any) => {
        this.eventForm.controls[element].clearValidators();
        this.eventForm.controls[element].updateValueAndValidity();
      });

      if (this.isManager && !this.formdata?.id) {
        this.applyManagerDefaults();
      }
    });

    setTimeout(() => this.initializeAmbassadorSelect(), 0);
  }

  private applyManagerDefaults() {
    if (!this.user?.id) {
      return;
    }
    this.eventForm.patchValue({ manager_id: Number(this.user.id) });
    this.eventForm.controls['manager_id'].disable();
  }

  private initializeAmbassadorSelect() {
    if (!this.user?.token) {
      return;
    }
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
    if (!this.validateVenueLocation()) {
      return;
    }

    const payload = { ...this.eventForm.getRawValue() };
    delete payload.attachments;

    if (payload.start_time) {
      payload.start_time = moment(payload.start_time).format('YYYY-MM-DD HH:mm');
    }
    if (payload.end_time) {
      payload.end_time = moment(payload.end_time).format('YYYY-MM-DD HH:mm');
    }
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
    if (this.selectedAmbassdors?.length > 0) {
      payload.selectedAmbassadorIds = this.selectedAmbassdors.map((obj: any) => obj.id).join(',');
    }

    this.formData = new FormData();
    for (const key in payload) {
      if (payload[key] !== null && payload[key] !== undefined) {
        this.formData.append(key, payload[key]);
      }
    }

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

  getPageData() {
    this.common.showLoader();
    this.apiService.getAuthData('admin/get_events_data', this.user.token).subscribe(
      (res: any) => {
        this.data = res?.data;
        this.common.dismissLoading();
        this.selectedAmbassdors = [];

        if (res?.data?.booked_user?.length > 0) {
          res.data.booked_user.forEach((element: any) => {
            jQuery('#booked_by').append(`<option value="${element?.name}">${element?.name}</option>`);
          });
        }

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

  getSingleEventData() {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_event_details?id=${this.formdata?.id}`, this.user?.token).subscribe(
      (res: any) => {
        this.common.dismissLoading();
        const event = res?.data?.event;
        if (!event) {
          return;
        }

        this.setupBookedBySelect(event.booked_by);

        event.client_id = parseInt(event.client_id, 10);
        event.venue_name = event.venue_name ? parseInt(event.venue_name, 10) : null;
        event.department_id = event.department_id ? parseInt(event.department_id, 10) : null;
        event.event_type_id = event.event_type_id ? parseInt(event.event_type_id, 10) : null;
        event.distributor_id = event.distributor_id ? parseInt(event.distributor_id, 10) : null;
        event.brand_id = event.brand_id ? parseInt(event.brand_id, 10) : null;
        event.market_id = event.market_id ? parseInt(event.market_id, 10) : null;
        event.manager_id = event.manager_id ? parseInt(event.manager_id, 10) : null;

        this.applyVenueFromEvent(event);
        this.updateTimezoneDisplayFromEvent(event);

        this.booked_date_model = event.booked_date;
        const rawStart = event.start_time_raw || event.start_time;
        const rawEnd = event.end_time_raw || event.end_time;
        this.start_time_model = this.toDatetimeLocalValue(rawStart);
        this.end_time_model = this.toDatetimeLocalValue(rawEnd);

        if (this.isManager) {
          this.eventForm.controls['manager_id'].disable();
        }

        this.selectedAmbassdors = event.ambassadors ?? [];
        this.eventAttechments = event.event_attachments ?? [];
        this.eventForm.patchValue(event);
        this.eventForm.controls['start_time'].setValue(rawStart);
        this.eventForm.controls['end_time'].setValue(rawEnd);
      },
      () => {
        this.common.dismissLoading();
      }
    );
  }

  private setupBookedBySelect(bookedBy: any) {
    const $selectElement = jQuery('#booked_by').select2({
      tags: true,
      width: 'resolve',
      allowClear: true,
    });
    let defaultTags: any;
    try {
      defaultTags = JSON.parse(bookedBy);
    } catch {
      defaultTags = [bookedBy];
    }
    defaultTags.forEach((tagText: any) => {
      if (!$selectElement.find(`option:contains(${tagText})`).length) {
        $selectElement.append(jQuery('<option>').text(tagText));
      }
    });
    setTimeout(() => {
      $selectElement.val(defaultTags).trigger('change');
    }, 500);
  }

  private toDatetimeLocalValue(value: string): string | null {
    if (!value || !/^\d{4}-\d{2}-\d{2}/.test(String(value))) {
      return null;
    }
    const normalized = String(value).trim().replace(' ', 'T');
    return normalized.length === 16 ? normalized + ':00' : normalized.split('.')[0];
  }

  setDateOnChange(event: any, key: string = '') {
    const value = event?.detail?.value ?? event?.target?.value;
    if (value && key) {
      this.eventForm.controls[key].setValue(value);
      this.eventForm.controls[key].updateValueAndValidity();
    }
  }

  onChangeFile(event: any) {
    const file = event?.target?.files[0];
    if (file) {
      this.attachedImage = URL.createObjectURL(file);
      this.formData.append('attachments', file);
    }
  }

  is_kit_required(event: any) {
    this.is_kit_requied = event?.detail?.checked;
  }

  goToAddVenue() {
    this.router.navigate(['/admin-add-location']);
  }

  formatVenueLabel(venue: any): string {
    const address = this.formatVenueAddress(venue);
    return venue?.name ? `${venue.name} — ${address}` : address;
  }

  formatVenueAddress(venue: any): string {
    const parts = [venue?.address, venue?.city, venue?.state, venue?.zip_code].filter((part) => !!part);
    return parts.length ? parts.join(', ') : (venue?.name || 'Venue');
  }

  onVenueChange(event: any) {
    const venueId = event?.detail?.value;
    const venue = (this.data?.states || []).find((v: any) => String(v.id) === String(venueId));
    if (!venue) {
      this.timezoneDisplay = '';
      this.eventForm.patchValue({
        event_address: '',
        latitude: '',
        longitude: '',
        timezone: '',
        timezone_label: '',
        city: '',
      });
      return;
    }

    const address = this.formatVenueAddress(venue);
    this.eventForm.patchValue({
      event_address: address,
      latitude: venue.latitude || '',
      longitude: venue.longitude || '',
      timezone: venue.timezone || '',
      timezone_label: venue.timezone_label || '',
      city: venue.city || '',
    });
    this.timezoneDisplay = this.buildTimezoneDisplay(venue);
    this.cdrf.detectChanges();
  }

  private applyVenueFromEvent(event: any) {
    if (!event?.venue_name) {
      return;
    }
    const venue = (this.data?.states || []).find((v: any) => String(v.id) === String(event.venue_name));
    if (venue) {
      this.onVenueChange({ detail: { value: venue.id } });
      return;
    }
    if (event.event_address) {
      this.eventForm.patchValue({
        event_address: event.event_address,
        latitude: event.latitude || '',
        longitude: event.longitude || '',
        timezone: event.timezone || '',
        timezone_label: event.timezone_label || '',
        city: event.city || '',
      });
    }
  }

  buildTimezoneDisplay(venue: any): string {
    if (venue?.timezone_label) {
      const region = venue?.timezone ? ` (${venue.timezone})` : '';
      return `${venue.timezone_label}${region}`;
    }
    return venue?.timezone || '';
  }

  updateTimezoneDisplayFromEvent(event: any) {
    if (event?.time_display) {
      this.timezoneDisplay = event.time_display;
      return;
    }
    if (event?.timezone_label || event?.timezone) {
      this.timezoneDisplay = this.buildTimezoneDisplay(event);
      return;
    }
    const venue = (this.data?.states || []).find((v: any) => String(v.id) === String(event?.venue_name));
    if (venue) {
      this.timezoneDisplay = this.buildTimezoneDisplay(venue);
    }
  }

  openFileUpload() {
    jQuery('#fileUpload').trigger('click');
  }
}
