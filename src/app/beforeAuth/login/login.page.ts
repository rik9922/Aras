import { ChangeDetectorRef, Component, OnInit } from "@angular/core";
import { FormBuilder,FormControl,FormGroup,Validators,} from "@angular/forms";
import { Router } from "@angular/router";
import { MenuController, Platform } from "@ionic/angular";
import { ApiService } from "src/app/services/api.service";
import { CommonService } from "src/app/services/common.service";
import { EventsService } from "src/app/services/events.service";
import { StorageService } from "src/app/services/storage.service";

@Component({
  standalone: false,
  selector: "app-login",
  templateUrl: "./login.page.html",
  styleUrls: ["./login.page.scss"],
})
export class LoginPage implements OnInit {
  loginForm!: FormGroup; // Declare loginForm property
  passwordType: string = "password";
  passwordIcon: string = "eye-off";
  rememberMeval: any;
  validation_messages = {
    email: [
      { type: "required", message: "Email is required." },
      { type: "pattern", message: "Enter a valid email." },
    ],

    password: [
      { type: "required", message: "Password is required." },
      { type: "minlength", message: "Password must have eight character" },
    ],
  };
  segment: any;
  constructor(
    private formBuilder: FormBuilder,
    private menuCtrl: MenuController,
    private apiService: ApiService,
    private commonService: CommonService,
    private storage: StorageService,
    public router: Router,
    public event: EventsService,
    public platform: Platform,
    public cd: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.segment = "signin";
    this.loginForm = this.formBuilder.group({
      email: new FormControl(
        "",
        Validators.compose([
          Validators.required,
          Validators.pattern(
            "^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$"
          ),
        ])
      ),
      password: new FormControl(
        "",
        Validators.compose([Validators.required, Validators.minLength(8)])
      ),
    });
  }

  ionViewWillEnter() {
    this.storage.get("remeberPassword").then((val: any = []) => {
      if (val != null) {
        this.loginForm.controls["email"].setValue(val.email);
        this.loginForm.controls["password"].setValue(val.password);
        this.rememberMeval = true;
      } else {
        this.rememberMeval = false;
      }
    });
  }

  ionViewDidEnter() {
    this.segment = "signin";
  }

  login(formData: any) {
    this.apiService.getNetworkStatus().then((res: any) => {
      if (res?.connected) {
        this.commonService.showLoader("Authenticating...");
        this.apiService.sendData("login", formData).subscribe(
          async (response: any) => {
            if (response.status == "success") {
              this.event.publish('firebase_login',formData)
              this.storage.set("apro_user", response["data"]);
              this.menuCtrl.enable(true);
              this.commonService.dismissLoading();
              const userData = response["data"];
              this.event.publish('user_login', userData);

              //  redirect role wise 
              if (userData?.role_id === 1) {
                this.router.navigate(["/tabs/admin-home"]);
              } else if (userData?.role_id === 2) {
                this.router.navigate(["/tabs/brand-home"]);
              } else if (userData?.role_id === 3) {
                this.router.navigate(["/tabs/client-home"]);
              } else if (userData?.role_id === 4) {
                this.router.navigate(["/tabs/location-home"]);
              } else if (userData?.role_id === 5) {
                this.router.navigate(["/tabs/ambassador-home"]);
              }

              // BA: permission alerts run from app root after navigation (survives leaving login page)
              if (Number(userData?.role_id) === 5) {
                setTimeout(() => {
                  this.event.publish('ba_request_permissions', userData);
                }, 500);
              }


              // remember me setting
              if (this.rememberMeval) {
                let remeberPassword = {
                  email: this.loginForm.value.email,
                  password: this.loginForm.value.password,
                };
                this.storage.set("remeberPassword", remeberPassword);
                this.loginForm.reset();
              } else {
                this.loginForm.reset();
                this.storage.remove("remeberPassword");
              }
            }
          },
          (err) => {
            this.commonService.dismissLoading();
            this.commonService.presentToast(err.error.message);
          }
        );
      } else {
        this.commonService.presentToast("No internet connection! ");
      }
    });
  }

  rememberMe(ev: any) {
    this.rememberMeval = ev.detail.checked;
  }

  hideShowPassword() {
    this.passwordType = this.passwordType === "text" ? "password" : "text";
    this.passwordIcon = this.passwordIcon === "eye-off" ? "eye" : "eye-off";
  }
  redirect(event: any) {
    var self = this;
    setTimeout(function () {
      self.segment = "signin";
      self.router.navigate(["/register"]);
    }, 25);
  }
}
