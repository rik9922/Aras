import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';

@Component({
  standalone: false,
  selector: 'app-verify-otp',
  templateUrl: './verify-otp.page.html',
  styleUrls: ['./verify-otp.page.scss'],
})
export class VerifyOtpPage implements OnInit {
  resetForm!: FormGroup;
  messages: any = [];
  errorMsg: any;
  validation_messages = {
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Enter a valid email." }
    ],
  };
  params: any;
  constructor(
    private formBuilder: FormBuilder,
    private apiService: ApiService,
    private commonService: CommonService,
    public nav: NavController,
    public router: Router,
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.params = this.router.getCurrentNavigation()?.extras?.state;
      console.log(this.params)
    }
  }

  ngOnInit(): void {
    this.resetForm = this.formBuilder.group({
      'email': new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')
      ])),
      'enteredCode1': new FormControl('', Validators.compose([
        Validators.required,
        Validators.minLength(1)
      ])),
      'enteredCode2': new FormControl('', Validators.compose([
        Validators.required,
        Validators.minLength(1)
      ])),
      'enteredCode3': new FormControl('', Validators.compose([
        Validators.required,
        Validators.minLength(1)
      ])),
      'enteredCode4': new FormControl('', Validators.compose([
        Validators.required,
        Validators.minLength(1)
      ])),
    });
  }

  ionViewWillEnter() {
    this.errorMsg = '';
    this.resetForm.controls['email'].setValue(this.params?.email)
  }

  async moveFocus(event: any, nextElement: any, previousElement: any) {
    this.errorMsg = '';
    if (event.key == "Backspace" && previousElement) {
      return previousElement.setFocus();
    } else if (event.key >= 0 && event.key <= 9) {
      if (nextElement) {
        return nextElement.setFocus();
      }
    } else {
      return event.path[0].value = '';
    }
  }

  submit(formData: any) {
    formData.code = formData.enteredCode1 + formData.enteredCode2 + formData.enteredCode3 + formData.enteredCode4;
    this.commonService.showLoader();
    this.apiService.sendData('verify-otp', formData).subscribe(async (response: any) => {
      this.commonService.dismissLoading()
      if (response.status == 'success') {
        this.commonService.presentToast(response?.message)
        this.nav.navigateForward('reset-password', { state: { email: formData.email } });
      }
    }, (err) => {
      this.commonService.dismissLoading();
      // this.commonService.presentToast(err.error.message);
      this.errorMsg = err?.error?.message;
      this.messages = Object.values(err.error?.data)
    });
  }

  resendOTP() {
    this.commonService.showLoader('Sending...');
    this.apiService.sendData('forgot-password', { email: this.params?.email }).subscribe(async (response: any) => {
      this.commonService.dismissLoading()
      if (response.status == 'success') {
        this.commonService.presentToast(response?.message)
      }
    }, (err) => {
      this.commonService.dismissLoading();
      this.commonService.presentToast(err.error.message);
    });
  }
}
