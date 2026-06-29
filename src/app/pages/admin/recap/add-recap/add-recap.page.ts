import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

declare var jQuery: any;

@Component({
  selector: 'app-add-recap',
  templateUrl: './add-recap.page.html',
  styleUrls: ['./add-recap.page.scss'],
  standalone: false
})
export class AddRecapPage implements OnInit {
  params: any = { title: 'Add Recap', is_back: true };
  data: any;
  user: any;
  editRecapForm!: FormGroup;
  selectedDate: any = '';
  isModalOpen: any = false;
  brands: any = [];
  states: any = [];
  key: any;
  recapData: any = {};
  formData: any = new FormData();
  submit_type: any;
  recapType: string = 'off_premise';
  recapFormLabel: string = '';
  eventName: string = '';
  eventTypeName: string = '';
  eventId: any = null;
  recapId: any = null;

  opEventPhotos: File[] = [];
  opEventReceipts: File[] = [];
  showCaseDisplayCount: boolean = false;
  showBackupBrandName: boolean = false;

  constructor(
    public router: Router,
    public apiService: ApiService,
    public common: CommonService,
    public storage: StorageService,
    public event: EventsService,
    public nav: NavController,
    public fb: FormBuilder,
    public cdrf: ChangeDetectorRef
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
      this.submit_type = this.data?.type;
    }
  }

  ngOnInit() {
    this.buildForm();
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.loadRecapForm();
          this.params.title = 'Submit Recap';
        }
      } else {
        this.nav.navigateRoot(['/login']);
      }
    });
  }

  buildForm() {
    if (this.recapType === 'off_premise') {
      this.editRecapForm = this.fb.group({
        traffic_flow: new FormControl('', Validators.required),
        demographics_female: new FormControl(''),
        demographics_male: new FormControl(''),
        demographics_age_range: new FormControl(''),
        sampling_number: new FormControl(''),
        receipt_total: new FormControl(''),
        op_parking_fee: new FormControl(''),
        store_aware_of_demo: new FormControl(false),
        store_prepared_for_demo: new FormControl(false),
        product_on_sale: new FormControl(false),
        coupons_rebates_available: new FormControl(false),
        enough_product_in_stock: new FormControl(false),
        case_display: new FormControl(false),
        case_display_count: new FormControl(''),
        objections_to_sale: new FormControl(''),
        consumer_comments_suggestions: new FormControl(''),
        account_feedback: new FormControl(''),
      });
      return;
    }

    // Base form (on_premise / special_event)
    const controls: Record<string, FormControl> = {
      traffic_location: new FormControl('', Validators.required),
      close_time: new FormControl('', Validators.required),
      check_in_with_person: new FormControl('', Validators.required),
      promotion_product_status: new FormControl('', Validators.required),
      tip: new FormControl('', Validators.required),
      brand_dmeo_name: new FormControl('', Validators.required),
      interaction_gender: new FormControl('', Validators.required),
      interaction_age: new FormControl('', Validators.required),
      total_interaction: new FormControl('', Validators.required),
      samples: new FormControl('', Validators.required),
      brand_selling_id: new FormControl('', Validators.required),
      sold_unit: new FormControl('', Validators.required),
      state_id: new FormControl('', Validators.required),
      brand_count: new FormControl('', Validators.required),
      backup_brand_used: new FormControl('0', Validators.required),
      backup_brand_name: new FormControl(''),
      giveaway_count: new FormControl(''),
      giveaway_comments: new FormControl(''),
      buyback_amount: new FormControl(''),
    };

    if (this.recapType === 'on_premise') {
      controls['drinks_purchased_after_sampling'] = new FormControl('', Validators.required);
      controls['consumer_feedback'] = new FormControl('', Validators.compose([Validators.required, Validators.minLength(200), Validators.maxLength(255)]));
      controls['manager_comments'] = new FormControl('', Validators.compose([Validators.required, Validators.minLength(200)]));
      controls['consumer_comments_suggestions'] = new FormControl('', Validators.compose([Validators.required, Validators.minLength(200)]));
    } else if (this.recapType === 'special_event') {
      controls['consumer_interactions'] = new FormControl('', Validators.required);
      controls['consumer_sampled'] = new FormControl('', Validators.required);
      controls['event_summary'] = new FormControl('', Validators.compose([Validators.required, Validators.minLength(200)]));
      controls['consumer_feedback'] = new FormControl('');
      controls['ambassador_feedback'] = new FormControl('');
    } else {
      controls['consumer_feedback'] = new FormControl('', Validators.required);
      controls['ambassador_feedback'] = new FormControl('', Validators.required);
    }

    this.editRecapForm = this.fb.group(controls);
  }

  onCaseDisplayChange(event: any) {
    this.showCaseDisplayCount = event.detail.checked;
    if (!this.showCaseDisplayCount) {
      this.editRecapForm.controls['case_display_count']?.setValue('');
    }
  }

  onBackupBrandUsedChange(event: any) {
    this.showBackupBrandName = event.detail.value === '1';
  }

  loadRecapForm() {
    const isCreate = this.data?.type === 'complete';
    const query = isCreate
      ? `ambassador/get_recap_by_id?event_id=${this.data.id}`
      : `ambassador/get_recap_by_id?id=${this.data.id}`;

    this.common.showLoader();
    this.apiService.getAuthData(query, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.recapType = res?.data?.recap_type || 'off_premise';
      this.recapFormLabel = res?.data?.recap_form_label || '';
      this.eventName = res?.data?.event?.event_name || '';
      this.eventTypeName = res?.data?.event_type_name || res?.data?.event?.event_type?.name || '';
      this.eventId = isCreate ? this.data.id : res?.data?.event?.id;
      this.recapId = res?.data?.recap?.id || null;
      this.brands = res?.data?.brands || [];
      this.states = res?.data?.states || [];
      this.recapData = res?.data?.recap || {};
      this.buildForm();

      this.showCaseDisplayCount = !!res?.data?.recap?.case_display;
      this.showBackupBrandName = res?.data?.recap?.backup_brand_used == '1' || res?.data?.recap?.backup_brand_used === true;

      if (res?.data?.recap) {
        const recap = { ...res.data.recap };
        if (recap.state_id) {
          recap.state_id = +recap.state_id;
        }
        this.editRecapForm.patchValue(recap);
        if (res?.data?.recap?.brand?.id) {
          this.editRecapForm.controls['brand_selling_id'].setValue(res.data.recap.brand.id);
        }
      }
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    });
  }

  attachphoto(event: any) {
    const files = event?.target?.files;
    if (this.key === 'op_event_photos') {
      this.opEventPhotos = Array.from(files);
      return;
    }
    if (this.key === 'op_event_receipts') {
      this.opEventReceipts = Array.from(files);
      return;
    }
    if (this.key == 'consumer_photo' && files?.length > 1) {
      this.recapData['consumer_photos'] = [];
      for (let index = 0; index < files.length; index++) {
        const file = files[index];
        this.recapData['consumer_photos'].push(URL.createObjectURL(file));
        this.formData.append('consumer_photo[]', file);
      }
    } else {
      const file = files[0];
      this.recapData[this.key] = URL.createObjectURL(file);
      this.formData.append(this.key, file);
    }
  }

  openFileUpload(key: any) {
    this.key = key;
    jQuery('#fileUpload').trigger('click');
  }

  events() {
    this.isModalOpen = true;
  }

  selectEvent(event: any) {
    this.selectedDate = moment(event?.detail.value).format('YYYY-MM-DDTHH:mm');
    this.editRecapForm.controls['close_time'].setValue(this.selectedDate);
    this.isModalOpen = false;
  }

  submit(formValue: any) {
    this.formData = new FormData();
    if (this.recapId) {
      formValue.id = this.recapId;
    }
    if (this.eventId) {
      formValue.event_id = this.eventId;
    }
    // Append off_premise file arrays
    this.opEventPhotos.forEach(file => this.formData.append('op_event_photos[]', file));
    this.opEventReceipts.forEach(file => this.formData.append('op_event_receipts[]', file));

    for (const key in formValue) {
      if (formValue[key] !== null && formValue[key] !== undefined) {
        this.formData.set(key, formValue[key]);
      }
    }
    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/add_update_recap', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      const check: any = await this.common.presentAlertWithPromise('Success', res?.message);
      if (check) {
        this.event.publish('reload_recaps', 1);
        this.nav.back();
      }
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
      const message = err?.error?.message || 'Unable to submit recap.';
      this.common.presentAlert(message);
    });
  }
}
