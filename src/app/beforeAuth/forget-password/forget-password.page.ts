import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';

@Component({
  standalone: false,
  selector: 'app-forget-password',
  templateUrl: './forget-password.page.html',
  styleUrls: ['./forget-password.page.scss'],
})
export class ForgetPasswordPage implements OnInit {
  forgetForm!: FormGroup;
  messages: any = [];
  errorMsg: any = '';
  validation_messages = {
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Incorrect Email Address." }
    ],
  };
  constructor(
    private formBuilder: FormBuilder,
    private apiService: ApiService,
    private commonService: CommonService,
    public nav: NavController
  ) {

  }

  ngOnInit(): void {
    this.forgetForm = this.formBuilder.group({
      'email': new FormControl('', Validators.compose([
        Validators.required,
        Validators.pattern('^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$')
      ])),
    });
  }
  ionViewWillEnter() {
    this.errorMsg = '';
  }

  submit(formData: any) {
    this.errorMsg = '';
    this.commonService.showLoader('Verifing Email...');
    this.apiService.sendData('forgot-password', formData).subscribe(async (response: any) => {
      this.commonService.dismissLoading()
      if (response.status == 'success') {
         this.commonService.presentToast(response?.message);
        // this.commonService.presentAlert(response?.message)
        this.nav.navigateForward('verify-otp', { state: { email: formData.email } });
      }
    }, (err) => {
      this.commonService.dismissLoading();
      this.errorMsg = err?.error?.message;
      this.commonService.presentToast(err?.error?.message);
      // this.messages = Object.values(err.error?.data)
    });
  }
}
