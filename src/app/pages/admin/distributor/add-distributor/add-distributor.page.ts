import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-add-distributor',
  templateUrl: './add-distributor.page.html',
  styleUrls: ['./add-distributor.page.scss'],
  standalone: false
})
export class AddDistributorPage implements OnInit {
  params: any = { title: 'Add Distributor', is_back: true };
  user: any;
  data: any;
  distributorForm: FormGroup = new FormGroup({});
  validation_messages = {
    name: [
      { type: "required", message: "Name is required." },
      { type: 'pattern', message: 'Please enter a valid name' }
    ],
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Please enter a valid email address." }
    ],
    phone: [
      { type: "required", message: "Phone number is required." },
      { type: 'pattern', message: 'Please enter a valid phone number.' },
      { type: "minlength", message: "Please enter only 10 to 12 digits." },
    ],
    company_name: [
      { type: "required", message: "Company name is required." },
      { type: "pattern", message: "Please enter a valid company name." },
    ],
    address: [
      { type: "required", message: "Address is required." },
      { type: "pattern", message: "Please enter a valid address" },
    ],
    distributor_type: [
      { type: "required", message: "Distributor type is required." },
      { type: "pattern", message: "Please enter a valid distributor type." },
    ],
  }
  constructor(
    public common: CommonService,
    public apiService: ApiService,
    public router: Router,
    public nav: NavController,
    public event: EventsService,
    public storage: StorageService
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
    this.distributorForm = new FormGroup({
      'name': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      // 'email': new FormControl('', Validators.compose([Validators.required,Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')])),
      // 'phone': new FormControl('', Validators.compose([Validators.required,
      // Validators.pattern(/^\d+$/), Validators.minLength(10), Validators.maxLength(12)])),
      // 'company_name': new FormControl('', Validators.compose([Validators.required,Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      // 'address': new FormControl('', Validators.compose([Validators.required])),
      // 'distributor_type': new FormControl('', Validators.compose([Validators.required,Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'status': new FormControl(1)
    });
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.params.title = 'Update Distributor';
          this.getDistributorsById(this.data?.id);
        }
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  submit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data.id;
    }
    if(formValue?.status){
      formValue.status = 1;
    }else{
      formValue.status = 0;
    }
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_distributor', formValue, this.user?.token).subscribe(async (res: any) => {
      this.distributorForm.reset();
      this.common.dismissLoading();
      let check: any = await this.common.presentAlertWithPromise("Success", res?.message);
      this.event.publish('reload_distributor', 1);
      if (check) {
        this.router.navigate(['/admin-distributor-list']);
      }
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
      this.common.presentAlert(err?.error?.message);
    });
  }
  getDistributorsById(Id: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_distributors_by_id?id=${Id}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      res.data.status  =+res.data.status;      
      this.distributorForm.patchValue(res?.data);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    });
  };

}
