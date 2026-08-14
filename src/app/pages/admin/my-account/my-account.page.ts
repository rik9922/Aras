import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

declare var jQuery: any;

@Component({
  selector: 'app-my-account',
  templateUrl: './my-account.page.html',
  styleUrls: ['./my-account.page.scss'],
  standalone: false
})
export class MyAccountPage implements OnInit {
  params: any = { title: 'Profile', is_back: true };
  data: any;
  isSpinner: boolean = false;
  profileChanged: boolean = false;
  user: any;
  profileForm: FormGroup = new FormGroup({});
  formData: any;
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
      { type: "maxlength", message: "Zip code must be 5 or 6 digits." },
      { type: "minlength", message: "Zip code must be 5 or 6 digits." },
      { type: 'pattern', message: 'Please enter a valid zip code.' }
    ],
    phone: [
      { type: "required", message: "Phone number is required." },
      { type: 'pattern', message: 'Please enter a valid phone number.' },
      { type: "minlength", message: "Please enter only 10 to 12 digits." },
    ],
  };
  constructor(
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    public apiService: ApiService,
    public event: EventsService,
    public storage: StorageService,
    public formBuilder: FormBuilder
  ) { }

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
      'zip_code': new FormControl('', Validators.compose([Validators.minLength(5),
      Validators.maxLength(6),
      Validators.pattern(/^\d+$/)])),
      'address': new FormControl(''),
      'password': new FormControl('')
    });

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
        this.profileForm.patchValue(this.user);
        this.profileForm.markAsPristine();
        this.profileChanged = false;
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  submit(formValue: any) {
    delete formValue.profile_image
    for (const key in formValue) {
      if (formValue[key]) {
        this.formData.set(key, formValue[key]);
      }
    }
    // Optional fields must still be sent when cleared, so the saved profile is updated.
    this.formData.set('address', formValue.address ?? '');
    this.formData.set('zip_code', formValue.zip_code ?? '');
    this.common.showLoader();
    this.apiService.sendAuthData('update-profile', this.formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      const updatedUser = {
        ...this.user,
        ...res?.data,
        token: this.user.token,
        // Retain values submitted by the user if an older API response omits them.
        address: res?.data?.address ?? formValue.address ?? '',
        zip_code: res?.data?.zip_code ?? formValue.zip_code ?? ''
      };
      if (updatedUser.state_id) {
        updatedUser.state_id = +updatedUser.state_id;
      }
      this.user = updatedUser;
      // Complete the device write before users can leave the success dialog and reopen Profile.
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
      console.log('error', err);
      this.common.presentAlert(err?.error?.message);
    });
  }
  onProfileInput(controlName: string, event: CustomEvent) {
    const control = this.profileForm.get(controlName);
    const value = event.detail?.value ?? '';

    // Ionic's native input event is the authoritative value in WKWebView as well.
    // Explicitly update and mark the control so the reactive form and Save state stay in sync.
    if (control?.value !== value) {
      control?.setValue(value);
    }
    control?.markAsDirty();
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
      this.profileForm.markAsDirty();
      this.profileChanged = true;
    }
  }
  select_profile() {
    jQuery('input[name="profile_image"]').trigger('click');
  }

}
