import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-add-events-department',
  templateUrl: './add-events-department.page.html',
  styleUrls: ['./add-events-department.page.scss'],
  standalone: false
})
export class AddEventsDepartmentPage implements OnInit {
  user: any;
  params: any = { title: 'Add Department', is_back: true };
  departmentForm: FormGroup = new FormGroup({});
  data: any;
  validation_messages: any = {
    name: [
      { type: 'required', message: 'Department Name is required' },
      { type: 'pattern', message: 'Please enter a valid department name' },
    ],
    description: [
      { type: 'required', message: 'Description is required' },
      { type: 'pattern', message: 'Please enter a valid description' },
    ]
  }
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
    public event: EventsService
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
    this.departmentForm = new FormGroup({
      'name': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'description': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
    })
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.params.title = 'Update Department';
          this.getDepartmentById(this.data?.id);
        };
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  getDepartmentById(Id: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_department_by_id?id=${Id}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.departmentForm.patchValue(res?.data);
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }
  submit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data?.id;
    };
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_department', formValue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      let check: any = await this.common.presentAlertWithPromise("Success", res?.message);;
      this.event.publish('reload_departments', 1);
      if (check) {
        this.router.navigate(['/admin-department-list']);
      }
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message);
      console.log('err', err);
    })
  }

}
