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
declare var google: any;
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
  today:string = moment().format('DD MMM YYYY');
  validation_messages = {
    event_name: [
      { type: "required", message: "Event Name is required." },
      { type: 'pattern', message: 'Please enter a valid event name' }
    ],
    city: [
      { type: "maxlength", message: "City must be 255 characters or less." },
    ],
    event_address: [
      { type: "required", message: "Event address is required." },
      { type: "maxlength", message: "Event address must be 500 characters or less." },
    ],
    latitude: [
      { type: "required", message: "Select an address from the suggestions to set coordinates." },
    ],
    longitude: [
      { type: "required", message: "Select an address from the suggestions to set coordinates." },
    ],
    department_id: [
      { type: "required", message: "Select department" },
    ],
    distributor_id: [
      { type: "required", message: "Select distributor" },
    ],
    brand_id: [
      { type: "required", message: "Select brand" },
    ],
    market_id: [
      { type: "required", message: "Select market" },
    ],
    manager_id: [
      { type: "required", message: "Select manager" },
    ],
    timezone: [
      { type: "required", message: "Select timezone" },
    ],
    varietals: [
      { type: "required", message: "Varietals is required" },
    ],
    notes: [
      { type: "required", message: "Notes is required" },
    ],
    internal_notes: [
      { type: "required", message: "Internal notes is required" },
    ],
    expedite_fee: [
      { type: "required", message: "Expedite fee is required" },
      { type: "pattern", message: "Please enter a valid expedite fee." },
    ],
    shipping_fee: [
      { type: "required", message: "Shipping fee is required" },
      { type: "pattern", message: "Please enter a valid shipping fee." },
    ],
    parking_fee: [
      { type: "required", message: "Parking fee is required" },
      { type: "pattern", message: "Please enter a valid parking fee." },
    ],
    drive_time: [
      { type: "required", message: "Drive time is required" },
      { type: "pattern", message: "Please enter a valid drive time." },
    ],
    payment_type: [
      { type: "required", message: "Payment type is required" },
    ],
    uniform: [
      { type: "required", message: "Uniform is required" },
    ],
    client_id: [
      { type: "required", message: "Client is required" },
    ],
    booked_by: [
      { type: "required", message: "Booked by is required" },
    ],
    region: [
      { type: "required", message: "Select region" },
    ],
  };
  is_kit_requied:boolean=false;
  booked_options:any=[];
  autocompleteItems: any[] = [];
  acService: any;
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
      event_address: ['', Validators.compose([Validators.required, Validators.maxLength(500)])],
      latitude: ['', Validators.compose([Validators.required])],
      longitude: ['', Validators.compose([Validators.required])],
      department_id: [null, Validators.compose([Validators.required])],
      position: [0, Validators.compose([Validators.required])],
      age_restricted: [true],
      apply_event: [true],
      distributor_id: [null, Validators.compose([Validators.required])],
      brand_id: [null, Validators.compose([Validators.required])],
      market_id: [null, Validators.compose([Validators.required])],
      manager_id: [null, Validators.compose([Validators.required])],
      timezone: ['', Validators.compose([Validators.required])],
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
      kit_description:[''],
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
  };

  ionViewWillEnter() {
    if (typeof google !== 'undefined' && google?.maps?.places?.AutocompleteService) {
      this.acService = new google.maps.places.AutocompleteService();
    }
    this.emailData = [];
    this.selectedEmail = [];
    var date = new Date();
    this.currentDate = date.toISOString().split('.')[0];
    this.formData = new FormData();
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.base_url = environment.base_url;
        this.getPageData();
        if (this.formData) {
          var removeValidation: any = ["expedite_fee", "shipping_fee", "parking_fee", "drive_time"];
          removeValidation.forEach((element: any) => {
            this.eventForm.controls[element].clearValidators();
            this.eventForm.controls[element].updateValueAndValidity();
          });
        }
      } else {
        this.nav.navigateRoot('/login');
      }
    });
    const initializeSelect2 = (selector: any) => {
      var self = this;
      jQuery(selector).select2({
        placeholder: "Enter Email",
        tags: false,
        minimumInputLength: 3,
        width: 'resolve',
        allowClear: true,
        ajax: {
          url: 'https://aras.betaplanets.com/api/auth/v1/admin/searchAmbassador',
          dataType: 'json',
          type: "POST",
          contentType: "application/json",
          headers: {
            "Authorization": `Bearer ${this.user?.token}`
          },
          data: function (params: any) {
            return JSON.stringify({
              search: params.term
            });
          },
          processResults: function (data: any) {
            self.emailData = [];
            self.emailData = data.data;
            self.cdrf.detectChanges();
            return {
              results: data.data.map((item: any) => ({
                id: item.id,
                text: item.email
              }))
            };
          }
        }
      });
      jQuery(selector).on('change', (event: any) => {
        var selectId = event.target.value;
        if (!selectId) {
          this.eventForm.controls['ambassador_email'].setValue('');
        }
        var data = this.emailData.find((x: any) => x.id == selectId);
        if (data) {
          this.eventForm.controls['ambassador_email'].setValue(data?.email);
          this.selectedEmail.push(data);
        }
        this.cdrf.detectChanges();
      });
    }

    const initializeSelect2WithData = (selector: any, placeholder: any = '') => {
      var self = this;
      jQuery(selector).select2({
        placeholder: placeholder,
        tags: false,
        minimumInputLength: 3,
        width: 'resolve',
        allowClear: true,
      });
    }

    setTimeout(() => {
      initializeSelect2("#ambassador_email");      
    }, 0);
  }
  onSubmit(formValue: any) {
    delete formValue.attachments;
    formValue.start_time = moment(formValue.start_time).format('YYYY-MM-DD');
    formValue.end_time = moment(formValue.end_time).format('YYYY-MM-DD');
    formValue.booked_date = moment(formValue.booked_date).format('YYYY-MM-DD');
    if (this.formdata?.id) {
      formValue.id = this.formdata?.id;
    }
    if (formValue?.apply_event) {
      formValue.apply_event = 'on';
    }
    if (this.selectedAmbassdors?.length > 0) {
      const ids = this.selectedAmbassdors.map((obj: any) => obj.id).join(',');
      formValue.selectedAmbassadorIds = ids
    };
    for (const key in formValue) {
      this.formData.append(key, formValue[key]);
    }
    console.log('formValue', formValue);
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_event', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.eventForm.reset();
      this.common.dismissLoading();
      let check: any = await this.common.presentAlertWithPromise('Success', res?.message);
      if (check) {
        this.event.publish('reload_events', 1);
        this.nav.back();
        // this.router.navigate(['/tabs/admin-event-list']);
      }
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }
  async addAnAmbassdor() {
    if (!this.eventForm.value.ambassador_email) {
      this.common.presentAlert('Please select an ambassador first!');
      return
    }
    if ((this.eventForm.value.ambassador_email) && (this.eventForm.value.position == 0 || !this.eventForm.value.position)) {
      this.common.presentAlert('Please add the position!');
      return
    }
    if ((this.eventForm.value.ambassador_email) && (this.eventForm.value.position)) {
      if (this.eventForm.value.position <= this.selectedAmbassdors?.length) {
        this.common.presentAlert(`You can only add up to ${this.eventForm.value.position} ambassador(s).`);
        return
      }
    }
    if (this.selectedAmbassdors?.length > 0) {
      let data = this.selectedAmbassdors.find((x: any) => x.email == this.eventForm.value.ambassador_email);
      if (data) {
        let check = await this.common.presentAlertWithPromise('Alert', 'This ambassador is already added!');
        if (check) {
          this.selectedEmail = [];
          this.eventForm.controls['ambassador_email'].setValue('');
          this.eventForm.controls['override_pay'].setValue('');
          jQuery('#ambassador_email').val(null).trigger('change');
        };
        return
      };
    };
    if (this.eventForm.value.override_pay) {
      let data = this.selectedEmail.find((x: any) => this.eventForm.value.ambassador_email == x.email);
      if (data) {
        data.override_pay = this.eventForm.value.override_pay;
        let index = this.selectedEmail.findIndex((x: any) => x.id == data?.id);
        this.selectedEmail.splice(index, 1, data);
      };
    };
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
    this.apiService.getAuthData('admin/get_events_data', this.user.token).subscribe((res: any) => {
      this.data = res?.data;
      this.common.dismissLoading();
      this.selectedAmbassdors = [];
      if (this.formdata) {
        this.getSingleEventData()
      }
      if(res?.data?.booked_user?.length>0){
        res?.data?.booked_user.map((element:any)=>{
          jQuery('#booked_by').append('<option value="'+element?.name+'">'+element?.name+'</option>');
        })
        
      }
    }, err => {
      this.common.dismissLoading();
      console.log('err', err)
    })
  }
  openFileUpload() {
    jQuery('#fileUpload').trigger('click');
  }

  getSingleEventData() {
    this.common.showLoader();
    this.apiService.getAuthData('admin/get_event_details?id=' + this.formdata?.id, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      if (res?.data?.event) {
        var $selectElement=jQuery("#booked_by").select2({
            tags: true,
            width: 'resolve',
            allowClear: true,
        });        
        var defaultTags:any;
        try {
            defaultTags=JSON.parse(res?.data?.event.booked_by);
        } catch (e) {
            defaultTags=[res?.data?.event.booked_by];
        }
        defaultTags.forEach(function(tagText:any) {
            if (!$selectElement.find('option:contains(' + tagText + ')').length) {
                $selectElement.append(jQuery('<option>').text(tagText));
            }
        });
        setTimeout(function(){
          console.log(defaultTags)
          $selectElement.val(defaultTags).trigger('change');
        },500)
        


        res.data.event.client_id = parseInt(res.data.event.client_id);

        this.booked_date_model = res?.data?.event?.booked_date;
        // this.start_time_model = new Date(res?.data?.event?.start_time).toISOString();
        // this.end_time_model = new Date(res?.data?.event?.end_time).toISOString();
        this.start_time_model = res?.data?.event?.start_time?.replace(' ', 'T');
        this.end_time_model = res?.data?.event?.end_time.replace(' ', 'T');
      }
      this.selectedAmbassdors = res?.data?.event?.ambassadors;
      this.eventAttechments = res?.data?.event?.event_attachments
      this.eventForm.patchValue(res?.data?.event)
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
      console.log(err)
    })
  }
  setDateOnChange(event: any, key: any = '') {
    var value = event?.target?.value;
    if (value) {
      this.eventForm.controls[key].setValue(value);
      this.eventForm.controls[key].updateValueAndValidity();
    };
  }
  onChangeFile(event: any) {
    var file = event?.target?.files[0];
    this.attachedImage = URL.createObjectURL(file);
    this.formData.append('attachments', file);
  }

  is_kit_required(event:any){
    this.is_kit_requied=event?.detail?.checked;
  }

  updateEventAddressSearch(event: any) {
    const value = event?.detail?.value ?? '';
    if (value === '') {
      this.autocompleteItems = [];
      this.eventForm.controls['latitude'].setValue('');
      this.eventForm.controls['longitude'].setValue('');
      this.eventForm.controls['latitude'].updateValueAndValidity();
      this.eventForm.controls['longitude'].updateValueAndValidity();
      return;
    }

    if (!this.acService) {
      return;
    }

    this.acService.getPlacePredictions(
      {
        input: value,
        componentRestrictions: { country: ['us'] },
        types: ['geocode'],
      },
      (predictions: any) => {
        this.autocompleteItems = predictions ?? [];
        this.cdrf.detectChanges();
      }
    );
  }

  chooseEventAddress(item: any) {
    if (!item?.place_id) {
      return;
    }

    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ placeId: item.place_id }, (results: any, status: string) => {
      if (status !== 'OK' || !results?.[0]?.geometry) {
        this.common.presentToast('Unable to resolve this address. Please try another suggestion.');
        return;
      }

      const result = results[0];
      this.autocompleteItems = [];
      this.eventForm.controls['event_address'].setValue(result.formatted_address || item.description);
      this.eventForm.controls['latitude'].setValue(result.geometry.location.lat());
      this.eventForm.controls['longitude'].setValue(result.geometry.location.lng());

      const cityComponent = (result.address_components || []).find((component: any) =>
        component.types.includes('locality') ||
        component.types.includes('administrative_area_level_2')
      );
      if (cityComponent) {
        this.eventForm.controls['city'].setValue(cityComponent.long_name);
      }

      this.eventForm.controls['event_address'].markAsDirty();
      this.eventForm.controls['latitude'].updateValueAndValidity();
      this.eventForm.controls['longitude'].updateValueAndValidity();
      this.cdrf.detectChanges();
    });
  }
}
