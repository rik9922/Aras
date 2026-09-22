import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { unwrapApiPayload } from 'src/app/utils/api-response.util';

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
  modalTitle: string = 'Add Event Type';
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
      recap_type: new FormControl(""),
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
      this.is_ready = true;
      const payload: any = unwrapApiPayload(res);
      this.data = Array.isArray(payload) ? payload : [];
    }, err => {
      this.is_ready = true;
      this.common.presentAlert(err?.error?.message)
    })
  }

  model(type: any) {
    this.isModelOpen = type;
    if (type) {
      this.modalTitle = 'Add Event Type';
      this.eventType.reset({
        id: '',
        name: '',
        description: '',
        recap_type: '',
      });
    }
  }

  edit(formvalue: any) {
    this.modalTitle = 'Edit Event Type';
    this.isModelOpen = true;
    this.eventType.patchValue({
      ...formvalue,
      recap_type: formvalue?.recap_type ?? '',
    });
  }

  recapFormTypeLabel(recapType?: string | null): string {
    switch (recapType) {
      case 'on_premise':
        return 'On Premise Recap';
      case 'special_event':
        return 'Special Event Recap';
      default:
        return 'Off Premise Recap';
    }
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
    })
  }

  trackByEventTypeId(index: number, item: any) {
    return item?.id ?? index;
  }
}
