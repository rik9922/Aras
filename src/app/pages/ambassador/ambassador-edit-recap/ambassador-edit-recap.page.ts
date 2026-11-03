import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-ambassador-edit-recap',
  templateUrl: './ambassador-edit-recap.page.html',
  styleUrls: ['./ambassador-edit-recap.page.scss'],
})
export class AmbassadorEditRecapPage implements OnInit {
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
  locationName = '';
  eventDate = '';
  requiresReceipt = false;
  recapType: string = 'off_premise';
  recapFormLabel: string = '';
  eventName: string = '';
  eventTypeName: string = '';
  eventId: any = null;
  recapId: any = null;

  opEventPhotos: File[] = [];
  opEventReceipts: File[] = [];
  productsSoldRows: any[] = [];
  selectedFiles: Record<string, File[]> = {};
  showCaseDisplayCount: boolean = false;
  showBackupBrandName: boolean = false;
  readonly productTypeOptions = [
    { value: 'beer', label: 'Beer (6 Packs)' },
    { value: 'beer12', label: 'Beer (12 Packs)' },
    { value: 'rtd4', label: 'RTD-4 pack' },
    { value: 'rtd6', label: 'RTD-6 pack' },
    { value: 'rtd8', label: 'RTD-8 pack' },
    { value: 'rtd12', label: 'RTD-12 pack' },
    { value: 'thc4', label: 'THC-4 pack' },
    { value: 'thc6', label: 'THC-6 pack' },
    { value: 'thc8', label: 'THC-8 pack' },
    { value: 'thc12', label: 'THC-12 pack' },
    { value: 'thc750ml', label: 'THC- 750ml' },
    { value: 'thcShot', label: 'THC- Shot' },
    { value: 'spirit', label: 'Spirit (Bottles)' },
    { value: 'wine', label: 'Wine (Bottles)' },
    { value: 'food', label: 'Food' },
    { value: 'na beverage', label: 'NA Beverage' },
    { value: 'other', label: 'Other' },
  ];
  private draftTimer: any;
  private draftLoaded = false;

  private get draftKey(): string {
    return `aras_recap_draft_${this.recapId || this.data?.id || this.eventId || 'new'}`;
  }

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
          this.getRecapById(this.data?.id);
          this.params.title = 'Edit Recap';
        } else {
          this.loadDraft();
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
        demographics_male: new FormControl(''),
        demographics_female: new FormControl({ value: '', disabled: true }),
        demographics_age_range: new FormControl(''),
        sampling_number: new FormControl(''),
        consumer_interactions: new FormControl('', Validators.min(0)),
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
        is_expedited_requested: new FormControl(false),
      });
      if (this.productsSoldRows.length === 0) {
        this.addProductSoldRow();
      }
      this.editRecapForm.valueChanges.subscribe(() => this.scheduleDraftSave());
      this.editRecapForm.controls['demographics_male'].valueChanges.subscribe(() => {
        this.syncDemographicPercentages();
      });
      return;
    }

    // Base form (on_premise / special_event)
    const controls: Record<string, FormControl> = {
      traffic_location: new FormControl('', Validators.required),
      timing_successful: new FormControl('', Validators.required),
      check_in_with_person: new FormControl('', Validators.required),
      promotion_product_status: new FormControl('', Validators.required),
      tip: new FormControl('', Validators.required),
      brand_dmeo_name: new FormControl('', Validators.required),
      interaction_gender: new FormControl('', Validators.required),
      interaction_age: new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern(/^\d{2}$/),
        Validators.min(21),
        Validators.max(25),
      ])),
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
      is_expedited_requested: new FormControl(false),
    };

    if (this.recapType === 'on_premise') {
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
    this.editRecapForm.valueChanges.subscribe(() => this.scheduleDraftSave());
  }

  onCaseDisplayChange(event: any) {
    this.showCaseDisplayCount = event.detail.checked;
    if (!this.showCaseDisplayCount) {
      this.editRecapForm.controls['case_display_count']?.setValue('');
    }
  }

  onInteractionAgeInput(event: any) {
    const value = String(event?.detail?.value ?? '').replace(/\D/g, '').slice(0, 2);
    if (event?.target) {
      event.target.value = value;
    }
    if (this.editRecapForm.controls['interaction_age']?.value !== value) {
      this.editRecapForm.controls['interaction_age'].setValue(value);
    }
  }

  formatTrafficFlow(event: CustomEvent): void {
    const value = String(event.detail?.value ?? '');
    const formatted = value
      .replace(/(^\s*|[.!?]\s+)([a-z])/gm, (_match, prefix, letter) => prefix + letter.toUpperCase())
      .replace(/\bi\b/g, 'I');

    if (formatted !== value) {
      this.editRecapForm.controls['traffic_flow']?.setValue(formatted, { emitEvent: false });
    }
  }

  onBackupBrandUsedChange(event: any) {
    this.showBackupBrandName = event.detail.value === '1';
  }

  private toBoolean(value: any): boolean {
    return value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
  }

  addProductSoldRow(row: any = {}): void {
    this.productsSoldRows.push({
      type: row?.type || '',
      name: row?.name || '',
      sold: row?.sold ?? '',
      regular_price: row?.regular_price ?? '',
      sale_price: row?.sale_price ?? '',
    });
    this.scheduleDraftSave();
  }

  removeProductSoldRow(index: number): void {
    this.productsSoldRows.splice(index, 1);
    if (this.productsSoldRows.length === 0) {
      this.addProductSoldRow();
    }
    this.scheduleDraftSave();
  }

  private hydrateProductsSold(value: any): void {
    let products = value;
    if (typeof products === 'string') {
      try {
        products = JSON.parse(products);
      } catch (error) {
        products = [];
      }
    }
    this.productsSoldRows = [];
    if (Array.isArray(products) && products.length > 0) {
      products.forEach((product) => this.addProductSoldRow(product));
    } else {
      this.addProductSoldRow();
    }
  }

  getRecapById(recapId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`ambassador/get_recap_by_id?id=${recapId}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.recapType = res?.data?.recap_type || 'off_premise';
      this.recapFormLabel = res?.data?.recap_form_label || '';
      this.eventName = res?.data?.event?.event_name || '';
      this.locationName = res?.data?.event?.location?.name || res?.data?.event?.location_name || '';
      this.eventDate = String(res?.data?.event?.start_time || '').slice(0, 10);
      this.requiresReceipt = ['comdata', '3'].includes(String(res?.data?.event?.payment_type || '').trim().toLowerCase());
      this.eventTypeName = res?.data?.event_type_name || res?.data?.event?.event_type?.name || '';
      this.eventId = res?.data?.event?.id;
      this.recapId = res?.data?.recap?.id;
      this.brands = res?.data?.brands;
      this.states = res?.data?.states;
      this.recapData = res?.data?.recap;
      this.hydrateProductsSold(this.recapData?.products_sold);
      this.buildForm();

      this.showCaseDisplayCount = !!res?.data?.recap?.case_display;
      this.showBackupBrandName = res?.data?.recap?.backup_brand_used == '1' || res?.data?.recap?.backup_brand_used === true;

      const recap = { ...res.data.recap };
      recap.state_id = +recap.state_id;
      recap.is_expedited_requested = this.toBoolean(recap.is_expedited_requested);
      this.editRecapForm.patchValue({ ...recap, timing_successful: recap.timing_successful == null ? '' : (recap.timing_successful ? '1' : '0') });
      this.syncDemographicPercentages();
      this.editRecapForm.controls['brand_selling_id'].setValue(res?.data.recap?.brand?.id);
      this.loadDraft();
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    });
  }

  attachphoto(event: any) {
    const files: File[] = Array.from(event?.target?.files || []);
    if (!files.length) return;
    if (this.key === 'op_event_photos') {
      this.opEventPhotos = this.mergeSelectedFiles(this.opEventPhotos, files);
      return;
    }
    if (this.key === 'op_event_receipts') {
      this.opEventReceipts = this.mergeSelectedFiles(this.opEventReceipts, files);
      return;
    }
    this.selectedFiles[this.key] = files;
    if (this.key == 'consumer_photo') {
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

  attachOffPremiseFiles(key: 'op_event_photos' | 'op_event_receipts', event: Event): void {
    this.key = key;
    this.attachphoto(event);

    // Permit selecting the same file again after it has been removed.
    (event.target as HTMLInputElement).value = '';
  }

  openFileUpload(key: any) {
    this.key = key;
    const input = document.getElementById('fileUpload') as HTMLInputElement | null;
    if (!input) return;

    input.multiple = key === 'consumer_photo';
    input.accept = 'image/*';
    input.value = '';
    input.click();
  }

  removeOpEventPhoto(index: number): void {
    this.opEventPhotos.splice(index, 1);
  }

  removeOpEventReceipt(index: number): void {
    this.opEventReceipts.splice(index, 1);
  }

  private mergeSelectedFiles(current: File[], incoming: File[]): File[] {
    const files = [...current];
    incoming.forEach((file) => {
      const duplicate = files.some((existing) =>
        existing.name === file.name
        && existing.size === file.size
        && existing.lastModified === file.lastModified
      );
      if (!duplicate) files.push(file);
    });
    return files;
  }

  events() {
    this.isModalOpen = true;
  }



  private scheduleDraftSave(): void {
    if (!this.draftLoaded || !this.editRecapForm) return;
    clearTimeout(this.draftTimer);
    this.draftTimer = setTimeout(() => this.saveDraft(), 500);
  }

  private async saveDraft(): Promise<void> {
    if (!this.editRecapForm) return;
    await this.storage.set(this.draftKey, {
      form: this.editRecapForm.getRawValue(),
      productsSoldRows: this.productsSoldRows,
      // Keep existing display photos in the draft. File objects themselves are
      // not serializable, so selected files remain in memory until submit.
      recapData: this.recapData,
      savedAt: Date.now(),
    });
  }

  private async loadDraft(): Promise<void> {
    const draft = await this.storage.get(this.draftKey);
    if (draft?.form) this.editRecapForm.patchValue(draft.form, { emitEvent: false });
    this.syncDemographicPercentages();
    if (Array.isArray(draft?.productsSoldRows)) this.hydrateProductsSold(draft.productsSoldRows);
    if (draft?.recapData) this.recapData = { ...this.recapData, ...draft.recapData };
    this.draftLoaded = true;
  }

  submit(formValue: any) {
    formValue = this.editRecapForm.getRawValue();
    this.formData = new FormData();
    if (this.recapId) {
      formValue.id = this.recapId;
    }
    if (this.eventId) {
      formValue.event_id = this.eventId;
    }
    // Append off_premise file arrays
    this.appendSelectedFiles(this.formData);

    this.appendFormValue(this.formData, formValue);
    this.appendProductsSold(this.formData);
    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/add_update_recap', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      const check: any = await this.common.presentAlertWithPromise('Success', res?.message);
      if (check) {
        await this.storage.remove(this.draftKey);
        this.event.publish('reload_recaps', 1);
        this.nav.navigateRoot('/submitted-recap');
      }
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
      const message = err?.error?.message || 'Unable to submit recap.';
      this.common.presentAlert(message);
    });
  }

  private appendFormValue(fd: FormData, formValue: any): void {
    for (const key in formValue) {
      const value = formValue[key];
      if (value === null || value === undefined) continue;
      fd.set(key, value === true ? '1' : value === false ? '0' : value);
    }
  }

  private syncDemographicPercentages(): void {
    const maleValue = this.editRecapForm.controls['demographics_male']?.value;
    const femaleValue = maleValue === '' || maleValue === null || maleValue === undefined
      ? ''
      : 100 - Number(maleValue);
    this.editRecapForm.controls['demographics_female']?.setValue(femaleValue, { emitEvent: false });
  }

  private appendProductsSold(fd: FormData): void {
    if (this.recapType !== 'off_premise') return;

    this.productsSoldRows
      .filter((row) => Object.values(row).some((value) => value !== null && value !== undefined && value !== ''))
      .forEach((row, index) => {
        fd.set(`products_sold[${index}][type]`, row.type || '');
        fd.set(`products_sold[${index}][name]`, row.name || '');
        fd.set(`products_sold[${index}][sold]`, row.sold ?? '');
        fd.set(`products_sold[${index}][regular_price]`, row.regular_price ?? '');
        fd.set(`products_sold[${index}][sale_price]`, row.sale_price ?? '');
      });
  }

  private appendSelectedFiles(fd: FormData): void {
    this.opEventPhotos.forEach(file => fd.append('op_event_photos[]', file));
    this.opEventReceipts.forEach(file => fd.append('op_event_receipts[]', file));

    Object.entries(this.selectedFiles).forEach(([key, files]) => {
      if (key === 'consumer_photo') {
        files.forEach(file => fd.append('consumer_photo[]', file));
        return;
      }
      if (files[0]) {
        fd.set(key, files[0]);
      }
    });
  }
}
