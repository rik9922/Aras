import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-add-brand',
  templateUrl: './add-brand.page.html',
  styleUrls: ['./add-brand.page.scss'],
  standalone: false
})
export class AddBrandPage implements OnInit {
  params: any = { title: 'Add Brand', is_back: true };
  brandForm: FormGroup = new FormGroup({});
  passwordType: string = 'password';
  passwordIcon: string = 'eye-off';
  user: any;
  brandCategory: any = [];
  data: any;
  isSpinner: boolean = false;
  selectedCategory: any = [];
  status: any = 1;
  validation_messages = {
    brand_name: [
      { type: "required", message: "Brand Name is required." },
    ],
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Please enter a valid email address." }
    ],
    password: [
      { type: "required", message: "Password is required." },
      { type: 'minlength', message: "Password must have eight character" }
    ],
  };
  constructor(
    public storage: StorageService,
    public event: EventsService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.data = this.router.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
    this.brandForm = new FormGroup({
      'brand_name': new FormControl('', Validators.compose([Validators.required])),
      // 'email': new FormControl('', Validators.compose([Validators.required, Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')])),
      'notes': new FormControl(''),
      'status': new FormControl(this.status, Validators.compose([Validators.required])),
      // 'password': new FormControl('')
    });
  }
  ionViewWillEnter() {
    this.selectedCategory = [];
    this.status = '1';
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getBrandCategories();
        if (this.data?.id) {
          this.params.title = 'Update Brand';
          this.getBrandById(this.data?.id);
        }
      } else {
        this.nav.navigateRoot('/login');
      };
    });
  }
  hideShowPassword() {
    this.passwordType = this.passwordType === 'text' ? 'password' : 'text';
    this.passwordIcon = this.passwordIcon === 'eye-off' ? 'eye' : 'eye-off';
  }
  onFormSubmit(formvalue: any) {
    if (this.selectedCategory?.length == 0) {
      this.common.presentAlert('Please select category');
      return
    }
    if (!formvalue?.password) {
      delete formvalue?.password;
      delete formvalue?.email;
    }
    if (this.data?.id) {
      formvalue.id = this.data?.id;
    }
    if (this.selectedCategory?.length > 0) {
      formvalue.brand_category_id = this.selectedCategory.toString();
    }
    formvalue.status = this.status;
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_brand', formvalue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      let check = await this.common.presentAlertWithPromise('Success', res?.message);
      this.event.publish('reload_brands', 1);
      if (check) {
        this.router.navigate(['/tabs/admin-brand-list']);
      }
    }, err => {
      this.common.dismissLoading();
    })
  }
  getBrandCategories() {
    this.isSpinner = true;
    this.apiService.getAuthData('admin/get_brand_category', this.user?.token).subscribe((res: any) => {
      this.isSpinner = false;
      this.brandCategory = res?.data;
    }, err => {
      this.isSpinner = false;
      console.log('err', err);
    });
  }
  getBrandById(Id: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_brand_by_id?id=${Id}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.selectedCategory = res.data.brand_category_id.map(Number);
      this.status = res.data.status.toString();
      this.brandForm.patchValue(res?.data);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    });
  };
  selectBrand(Id: any) {
    const index = this.selectedCategory.indexOf(Id);
    if (index > -1) {
      this.selectedCategory.splice(index, 1);
    } else {
      this.selectedCategory.push(Id);
    }
  }

}
