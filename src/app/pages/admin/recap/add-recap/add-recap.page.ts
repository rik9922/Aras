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
  imageElement: any;
  fileName: any;
  selectedDate: any = '';
  isModalOpen: any = false;
  brands: any = [];
  states: any = [];
  key: any;
  recapData:any={};
  formData: any = new FormData();
  submit_type:any;
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
      this.submit_type=this.data?.type
    }
  }

  ngOnInit() {
    this.editRecapForm = this.fb.group({
      'traffic_location': new FormControl('',Validators.compose([Validators.required])),
      'close_time': new FormControl('',Validators.compose([Validators.required])),
      'check_in_with_person': new FormControl('',Validators.compose([Validators.required])),
      'promotion_product_status': new FormControl('',Validators.compose([Validators.required])),
      'tip': new FormControl('',Validators.compose([Validators.required])),
      'brand_dmeo_name': new FormControl('',Validators.compose([Validators.required])),
      'interaction_gender': new FormControl('',Validators.compose([Validators.required])),
      'interaction_age': new FormControl('',Validators.compose([Validators.required])),
      'total_interaction': new FormControl('',Validators.compose([Validators.required])),
      'samples': new FormControl('',Validators.compose([Validators.required])),
      'brand_selling_id': new FormControl('',Validators.compose([Validators.required])),
      'sold_unit': new FormControl('',Validators.compose([Validators.required])),
      'consumer_feedback': new FormControl('',Validators.compose([Validators.required])),
      'ambassador_feedback': new FormControl('',Validators.compose([Validators.required])),
      'state_id': new FormControl('',Validators.compose([Validators.required])),
      'brand_count': new FormControl('',Validators.compose([Validators.required])),
    });
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.getRecapById(this.data?.id);
          this.params.title = 'Submit Recap';
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
      res.data.recap.state_id = + res.data.recap.state_id;
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
    if(this.key=='consumer_photo' && event?.target?.files?.length > 1){
      this.recapData['consumer_photos']=[];
      console.log('files',event?.target?.files,this.recapData);
      for(let index=0; index< event?.target?.files?.length; index++){
        var file = event?.target?.files[index];
        this.recapData['consumer_photos'].push(URL.createObjectURL(file));
        this.formData.append('consumer_photos[]', file);  
        console.log('file',this.recapData);
      }
    } else {
       var file = event?.target?.files[0];
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
    this.apiService.sendAuthData('admin/add_update_recap', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      let check :any = await this.common.presentAlertWithPromise('Success',res?.message)
      if(check){
        this.event.publish('reload_recaps',1);
        this.nav.back();
      }
    }, err => {
      console.log('err', err);
      this.common.dismissLoading();
    })
  }
}
