import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, FormControl, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
declare var jQuery: any
@Component({
  selector: 'app-client-account',
  templateUrl: './client-account.page.html',
  styleUrls: ['./client-account.page.scss'],
  standalone: false
})
export class ClientAccountPage implements OnInit {
  params: any = { title: 'My Account', is_back: false };
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
      { type: "maxlength", message: "Zip code is not more than 6 digits." },
      { type: "minlength", message: "Zip code is must have 5 digits." },
      { type: 'pattern', message: 'Please enter a valid zip code.' }
    ],
    phone: [
      { type: "required", message: "Phone number is required." },
      { type: 'pattern', message: 'Please enter a valid phone number.' },
      { type: "minlength", message: "Please enter only 10 to 12 digits." },
    ],
    city: [
      { type: "required", message: "City is required." },
      { type: 'pattern', message: 'Please enter a city name' }
    ],
    state_id: [
      { type: "required", message: "State is required." },
    ],
    address: [
      { type: "required", message: "Address is required." },
    ],
    password: [
      { type: "minlength", message: "Password must have eight character" },
    ],
  };
  formData: any;
  profileForm: FormGroup = new FormGroup({});
  user: any;
  data: any;
  isSpinner: boolean = false;
  profileChanged: boolean = false;
  passwordType: string = "password";
  passwordIcon: string = "eye-off";
  constructor(
    public router: Router,
    public storage: StorageService,
    public nav: NavController,
    public formBuilder: FormBuilder,
    public apiService: ApiService,
    public common: CommonService,
    public event: EventsService
  ) { };


  ngOnInit() {
    this.profileForm = this.formBuilder.group({
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
      'city': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^[A-Za-z]+(?: [A-Za-z]+)*$/)])),
      'state_id': new FormControl('', Validators.compose([Validators.required])),
      'address': new FormControl('', Validators.compose([Validators.required])),
      'password': new FormControl("", Validators.compose([Validators.minLength(8)])),
      'zip_code': new FormControl('', Validators.compose([Validators.required,
      Validators.pattern(/^\d+$/), Validators.minLength(5), Validators.maxLength(6)
      ]),),
      'status': new FormControl("", Validators.compose([Validators.required])),
    })

    this.profileForm.valueChanges.subscribe(() => {
      if (this.profileForm.dirty) {
        this.profileChanged = true;
      }
    });
  }
  ionViewWillEnter() {
    this.data = '';
    this.formData = new FormData();
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getData();
        this.profileForm.patchValue(this.user);
        this.profileForm.markAsPristine();
        this.profileChanged = false;
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }

  hideShowPassword() {
    this.passwordType = this.passwordType === "text" ? "password" : "text";
    this.passwordIcon = this.passwordIcon === "eye-off" ? "eye" : "eye-off";
  }

  submit(formValue: any) {
    this.formData = new FormData();
    delete formValue.profile_image
    for (const key in formValue) {
      if (key == 'state_id') {
        if (this.data?.state && this.data?.state?.length > 0) {
          let stateName = this.data?.state.find((x: any) => x.id == formValue[key]);
          this.formData.set('state', stateName.name);
        }
        this.formData.set('state_id', formValue[key]);
      } else {
        if (formValue[key]) {
          this.formData.set(key, formValue[key]);
        }
      }
    }
    this.common.showLoader();
    this.apiService.sendAuthData('update-profile', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      res.data.token = this.user.token;
      if (res?.data?.state_id) {
        res.data.state_id = +res.data.state_id;
      };
      const updatedUser = { ...this.user, ...res?.data, ...formValue, token: this.user.token };
      this.user = updatedUser;
      await this.storage.set('apro_user', updatedUser);
      this.profileForm.patchValue(updatedUser);
      this.profileForm.markAsPristine();
      this.profileChanged = false;
      let check = await this.common.presentAlertWithPromise('Success', res?.message);
      if (check) {
        this.nav.navigateRoot('/tabs/location-home');
      }
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message);
    });
  }
  onProfileFieldChange() {
    this.profileChanged = true;
  }

  onChange(event: any) {
    if (event.target.files?.length > 0) {
      this.formData.delete('profile_image');
      var self = this;
      jQuery(event.target.files).each(function (i: any, data: any) {
        self.user.profile_image = URL.createObjectURL(data),
          self.formData.append('profile_image', data)
      });
      this.profileChanged = true;
    }
  }
  select_profile() {
    jQuery('input[name="profile_image"]').trigger('click');
  }
  getData() {
    this.isSpinner = true;
    this.apiService.getAuthData('location/get_state_venue_data', this.user?.token).subscribe((res: any) => {
      this.isSpinner = false;
      this.data = res?.data;
    }, err => {
      this.isSpinner = false;
      console.log('err', err);
    });
  };
}
