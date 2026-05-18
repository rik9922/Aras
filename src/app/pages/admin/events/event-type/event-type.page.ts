import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-event-type',
  templateUrl: './event-type.page.html',
  styleUrls: ['./event-type.page.scss'],
})
export class EventTypePage implements OnInit {
  params: any = { title: "Event Type List", is_back: true };
  eventType!: FormGroup;
  isModelOpen: boolean = false;
  is_ready: boolean = false;
  user: any;
  data: any = [];
  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public formBuilder: FormBuilder
  ) {
    this.eventType = this.formBuilder.group({
      id: new FormControl(""),
      name: new FormControl("", Validators.compose([Validators.required,])),
      description: new FormControl("", Validators.compose([Validators.required,])),
    });
  }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any = []) => {
      this.user = data;
      this.getEventTypes();
    })
  }

  getEventTypes() {
    this.is_ready = false;
    this.apiService.getAuthData('admin/get_event_types', this.user?.token).subscribe((res: any) => {
      console.log(res);
      this.is_ready = true;
      this.data = res?.data;
      jQuery(document).ready(function () {
        jQuery('#open').DataTable({
          searching: false, // Disables the search box
          paging: false
        });
      })
    }, err => {
      this.is_ready = true;
      this.common.presentAlert(err?.error?.message)
      console.log(err);
    })
  }

  model(type: any) {
    this.isModelOpen = type
    this.eventType.reset()
  }

  edit(formvalue: any) {
    this.isModelOpen = true
    this.eventType.patchValue(formvalue);
  }

  submit(formvalue: any) {
    this.common.showLoader();
    this.apiService.sendAuthData('admin/save_event_type', formvalue, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading()
      this.model(false);
      this.getEventTypes()
      this.common.presentAlert(res?.message)
    }, err => {
      this.common.dismissLoading()
      this.common.presentAlert(err?.error?.message);
      console.log(err)
    })
  }
  ionViewWillLeave() {
    if (jQuery('#open')) {
      jQuery('#open').DataTable().destroy();
    }
  }
}
