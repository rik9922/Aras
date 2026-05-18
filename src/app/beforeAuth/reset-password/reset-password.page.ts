import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ConfirmPasswordValidator } from 'src/app/confirm-password.validator';
// import { ConfirmPasswordValidator } from 'src/app/confirm-password.validator';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';

@Component({
  standalone: false,
  selector: 'app-reset-password',
  templateUrl: './reset-password.page.html',
  styleUrls: ['./reset-password.page.scss'],
})
export class ResetPasswordPage implements OnInit {
  resetPasswordForm!: FormGroup;
  messages: any = [];
  passwordType: string = 'password';
  passwordIcon: string = 'eye-off';
  passwordIcon1: string = 'eye-off';
  passwordType1: string = 'password';
  validation_messages = {
    password: [
      { type: "required", message: "Password is required." },
      { type: 'minlength', message: "Password must have eight character" }
    ],
    password_confirmation: [
      { type: "required", message: "Confirm Password is required." },
      { type: 'minlength', message: "Confirm Password must have eight character" }
    ],
  };
  params: any;
  constructor(
    private formBuilder: FormBuilder,
    private apiService: ApiService,
    private commonService: CommonService,
    public nav: NavController,
    public router: Router
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.params = this.router.getCurrentNavigation()?.extras?.state;
      console.log(this.params)
    }
  }

  ngOnInit(): void {
    this.resetPasswordForm = this.formBuilder.group({
      'email': new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')
      ])),
      'password': new FormControl('', Validators.compose([Validators.required,Validators.minLength(8)])),
      'password_confirmation': new FormControl('', Validators.compose([Validators.required]))
    },
    {
      validators: ConfirmPasswordValidator("password", "password_confirmation")
    }
  );
  }

  ionViewWillEnter() {
    this.resetPasswordForm.controls['email'].setValue(this.params?.email)
  }

  hideShowPassword() {
    this.passwordType = this.passwordType === 'text' ? 'password' : 'text';
    this.passwordIcon = this.passwordIcon === 'eye-off' ? 'eye' : 'eye-off';
  }

  hideShowPassword1() {
    this.passwordType1 = this.passwordType1 === 'text' ? 'password' : 'text';
    this.passwordIcon1 = this.passwordIcon1 === 'eye-off' ? 'eye' : 'eye-off';
  }


  checkPass() {
    if ((this.resetPasswordForm.value.password_confirmation) && (this.resetPasswordForm.value.password != this.resetPasswordForm.value.password_confirmation)) {
      return true
    } else {
      return false
    }
  }

  submit(formData: any) {
    this.commonService.showLoader('Loading...');
    this.apiService.sendData('reset-password', formData).subscribe(async (response: any) => {
      this.commonService.dismissLoading()
      if (response.status == 'success') {
        this.commonService.presentToast(response?.message)
        this.nav.navigateForward('login');
      }
    }, (err) => {
      this.commonService.dismissLoading();
      this.commonService.presentToast(err.error.message);
      this.messages = Object.values(err.error?.data)
    });
  }
}
