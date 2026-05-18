import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-add-update',
  templateUrl: './add-update.page.html',
  styleUrls: ['./add-update.page.scss'],

})
export class AddUpdatePage implements OnInit {
  params: any = { title: " Add Client ", is_back: true };
  clientForm!: FormGroup;
  data: any;
  user: any;
  passwordType: string = "password";
  passwordIcon: string = "eye-off";
  isSpinner: boolean = false;
  brands: any = [];
  distributors: any = [];
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
    brand_id: [
      { type: "required", message: "Phone number is required." },
    ],
    distributor_id: [
      { type: "required", message: "Distributors is required." },
    ],
    password: [
      { type: "required", message: "Password is required." },
      { type: 'minlength', message: "Password must have eight character" }
    ],
    status: [
      { type: "required", message: "Status is required." },
    ]
  }
  constructor(
    public router: Router,
    public formBuilder: FormBuilder,
    public storage: StorageService,
    public nav: NavController,
    public apiService: ApiService,
    public common: CommonService,
    public event: EventsService
  ) {
    this.data = this.router.getCurrentNavigation()?.extras?.state
    if (this.data?.id) {
      this.params.title = 'Update Client';
    };
  }

  ngOnInit() {
    this.clientForm = this.formBuilder.group({
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
      'status': new FormControl('1', Validators.compose([Validators.required])),
      'brand_id': new FormControl('', Validators.compose([Validators.required])),
      'distributor_id': new FormControl('', Validators.compose([Validators.required])),
      'password': new FormControl('password', Validators.compose([Validators.required])),
    });
  }
  ionViewWillEnter() {
    this.brands = [];
    this.distributors = [];
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (!this.data?.id) {
          console.log('this.data?.id', this.data?.id);
          this.clientForm.controls['password'].setValidators([Validators.required, Validators.minLength(8)]);
          this.clientForm.controls['password'].updateValueAndValidity();
        }
        if (this.data) {
          this.getProfileById(this.data?.id)
        }
        this.getBrandsAndDistributors();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }
  getProfileById(id: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`location/get_profile_data_by_id?id=${id}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      console.log('res', res);
      res.data.distributor_id = res.data.distributor_id.map(Number);
      res.data.brand_id = res.data.brand_id.map(Number);
      res.data.status = res.data.status;
      this.clientForm.patchValue(res?.data);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })

  }
  getBrandsAndDistributors() {
    this.isSpinner = true;
    this.apiService.getAuthData('admin/get_brands_distributions_data', this.user?.token).subscribe((res: any) => {
      this.isSpinner = false;
      this.distributors = res?.data?.distributors;
      this.brands = res?.data?.brands;
      console.log('res', res);
    }, err => {
      this.isSpinner = false;
      console.log('err', err);
    });
  };



  hideShowPassword() {
    this.passwordType = this.passwordType === "text" ? "password" : "text";
    this.passwordIcon = this.passwordIcon === "eye-off" ? "eye" : "eye-off";
  }

  submit(formvalue: any) {
    if (this.data?.id) {
      formvalue.user_id = this.data?.id;
    }
    formvalue.distributor_id = formvalue.distributor_id.toString();
    formvalue.brand_id = formvalue.brand_id.toString();
    formvalue.role_id = 3;
    console.log('formvalue', formvalue);
    this.common.showLoader();
    this.apiService.sendAuthData('admin/update_client_profile', formvalue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      this.clientForm.reset();
      let check = await this.common.presentAlertWithPromise('Success', res?.message);
      this.event.publish('reload_clients', 1);
      if (check) {
        this.router.navigate(['/admin-client-list']);
      }
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }


}
