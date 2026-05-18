import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { AbstractControl, FormBuilder, FormControl, FormGroup, ValidationErrors, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import * as moment from 'moment';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

declare var jQuery: any;
@Component({
  standalone: false,
  selector: 'app-ambassador-edit-recap',
  templateUrl: './ambassador-edit-recap.page.html',
  styleUrls: ['./ambassador-edit-recap.page.scss'],
})
export class AmbassadorEditRecapPage implements OnInit {
  validation_message: any = {
    traffic_location: [
      { type: "required", message: "Traffic location is required." },
      { type: 'pattern', message: 'Please enter a valid traffic location' }
    ],
    check_in_with_person: [
      { type: 'required', message: 'Contact information is required.' },
      { type: 'invalidFormat', message: 'Please enter a valid Name, Email, or Phone Number.' }
    ],
    interaction_age: [
      { type: "required", message: "Interaction age is required." },
    ],
    samples: [
      { type: "required", message: "Samples is required." },
    ],
    promotion_product_status: [
      { type: "required", message: "Age is required." },

    ],
    consuumer: [
      { type: "required", message: "Consuumer feedback is required." },
    ],

    brand: [
      { type: 'required', message: 'The brand awareness field is required.' },
      { type: 'maxlength', message: 'Please enter no more than 3 digits.' },
      { type: 'pattern', message: 'Please enter numbers only.' }
    ],
    tip: [
      { type: 'required', message: 'Tip is required.' },
      { type: 'minlength', message: 'Minimum 3 characters required.' },
      { type: 'maxlength', message: 'Maximum 50 characters allowed.' }
    ],
    brand_dmeo_name: [
      { type: 'required', message: 'This field is required.' },
      { type: 'minlength', message: 'Minimum 3 characters required.' },
      { type: 'maxlength', message: 'Maximum 200 characters allowed.' }
    ],
    interaction_gender: [
      { type: "required", message: "Age is required." },
    ],
    total_interaction: [
      { type: "required", message: "Consuumer feedback is required." },
    ],
    sold_unit: [
      { type: "required", message: "Consuumer feedback is required." },
    ],
    ambassador_feedback: [
      { type: "required", message: "Location is required." },
      { type: 'pattern', message: 'Please enter a valid location' }
    ],
    state_id: [
      { type: "required", message: "State is required" },
    ],
    consumer_feedback: [
      { type: "required", message: "Consumer feedback is required." },
    ],
  };
  params: any = { title: 'Add Recap', is_back: true };
  data: any;
  user: any;
  editRecapForm!: FormGroup;
  imageElement: any;
  fileName: any;
  selectedDate: any = '';
  isModalOpen: any = false;
  brands: any = [];
  states: any = [];
  key: any;
  recapData: any = {};
  formData: any = new FormData();

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
    this.editRecapForm = this.fb.group({
      'traffic_location': new FormControl('', Validators.compose([Validators.required])),
      'close_time': new FormControl('', Validators.compose([Validators.required])),
      'check_in_with_person': new FormControl('', Validators.compose([Validators.required])),
      'promotion_product_status': new FormControl('', Validators.compose([Validators.required])),
      'tip': new FormControl('', Validators.compose([Validators.required])),
      'brand_dmeo_name': new FormControl('', Validators.compose([Validators.required])),
      'interaction_gender': new FormControl('', Validators.compose([Validators.required])),
      'interaction_age': new FormControl('', Validators.compose([Validators.required])),
      'total_interaction': new FormControl('', Validators.compose([Validators.required])),
      'samples': new FormControl('', Validators.compose([Validators.required])),
      'brand_selling_id': new FormControl('', Validators.compose([Validators.required])),
      'sold_unit': new FormControl('', Validators.compose([Validators.required])),
      'consumer_feedback': new FormControl('', Validators.compose([Validators.required])),
      'ambassador_feedback': new FormControl('', Validators.compose([Validators.required])),
      'state_id': new FormControl('', Validators.compose([Validators.required])),
      'brand_count': new FormControl('', Validators.compose([Validators.required])),
    });
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.getRecapById(this.data?.id);
          this.params.title = 'Edit Recap';
        }
      } else {
        this.nav.navigateRoot(['/login']);
      }
    })
  }

  getRecapById(recapId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`ambassador/get_recap_by_id?id=${recapId}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      res.data.recap.state_id =+ res.data.recap.state_id;
      this.brands = res?.data?.brands;
      this.states = res?.data?.states;
      this.recapData = res?.data?.recap;
      this.editRecapForm.patchValue(res?.data.recap);
      this.editRecapForm.controls['brand_selling_id'].setValue(res?.data.recap?.brand?.id)
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }

  attachphoto(event: any) {
    var file = event?.target?.files[0];
    this.recapData[this.key] = URL.createObjectURL(file);
    this.formData.append(this.key, file);
  }
  openFileUpload(key: any) {
    this.key = key;
    jQuery('#fileUpload').trigger('click');
  }
  events() {
    this.isModalOpen = true;
    this.cdrf.detectChanges();
  }

  selectEvent(event: any) {
    this.selectedDate = moment(event?.detail.value).format("YYYY-MM-DDTHH:mm");
    this.editRecapForm.controls['close_time'].setValue(this.selectedDate);
    this.isModalOpen = false;
  }
  submit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data.id;
    }
    for (const key in formValue) {
      this.formData.set(key, formValue[key]);
    }
    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/add_update_recap', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      let check: any = await this.common.presentAlertWithPromise('Success', res?.message)
      if (check) {
        this.event.publish('reload_recaps', 1);
        this.nav.back();
      }
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
    })
  }

}
