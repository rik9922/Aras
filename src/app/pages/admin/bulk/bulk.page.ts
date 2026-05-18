import { Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone:false,
  selector: 'app-bulk',
  templateUrl: './bulk.page.html',
  styleUrls: ['./bulk.page.scss'],
})
export class BulkPage implements OnInit {
  params: any = { title: 'Bulk', is_back: true };
  search_key:any;
  user:any;
  previewForm!:FormGroup
  data:any=[];
  constructor(
    public storage : StorageService,
    public common : CommonService,
    public apiService : ApiService,
    public nav : NavController,
    public fb : FormBuilder
  ) { }

  ngOnInit() {
    this.previewForm = new FormGroup({
      'previews': this.fb.array([]),
    });
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  get getPreview(): FormArray {
    return (<FormArray>this.previewForm.get('previews'));
  }

  private previews_form(val: any): FormGroup {
    return this.fb.group({
      'id': new FormControl(val?.id, Validators.compose([Validators.required,])),
      'event_name': new FormControl(val?.event_name, Validators.compose([Validators.required,])),
      'position': new FormControl(val?.position, Validators.compose([Validators.required,])),
      'start_time': new FormControl(val?.start_time, Validators.compose([Validators.required,])),
      'end_time': new FormControl(val?.end_time, Validators.compose([Validators.required,])),
      'pinata_pay': new FormControl(val?.pinata_pay, Validators.compose([Validators.required,])),
      'booked_by': new FormControl(val?.booked_by, Validators.compose([Validators.required,])),
      'booked_date': new FormControl(val?.booked_date, Validators.compose([Validators.required,])),
      'event_status': new FormControl(val?.event_status, Validators.compose([Validators.required,])),
    })
  }

  getEventsData() {
    if(this.search_key?.trim()!==''){
      this.data=[];
      this.getPreview.clear();
      this.common.showLoader();
      this.apiService.getAuthData('admin/get_bulk_preview?notes='+this.search_key,this.user?.token).subscribe((res:any)=>{
        this.common.dismissLoading();
        this.data=res;
        if(res?.length>0){
          res.map((val:any)=>{
            this.getPreview.push(this.previews_form(val))
          })
        }
      },err=>{
        this.common.dismissLoading();
      })
    }
  }

  onSubmit(){
    console.log(this.previewForm.value);
  }
}
