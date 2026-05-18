import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-add-venue',
  templateUrl: './add-venue.page.html',
  styleUrls: ['./add-venue.page.scss'],
  standalone: false
})
export class AddVenuePage implements OnInit {
  params: any = { title: 'Add Venue', is_back: true };
  user: any;
  data: any;
  venueForm: FormGroup = new FormGroup({});
  validation_messages: any = {
    name: [
      { type: 'required', message: 'Venue name is required' },
      { type: 'pattern', message: 'Please enter a valid venue name' },
    ],
    description: [
      { type: 'required', message: 'Venue description is required' },
      { type: 'pattern', message: 'Please enter a valid description' },
    ],

  };

  constructor(
    public router: Router,
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public event: EventsService,
    public nav: NavController
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
    this.venueForm = new FormGroup({
      'name': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
      'description': new FormControl('', Validators.compose([Validators.required, Validators.pattern(/^\S(.*\S)?$/)])),
    })

  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.params.title = 'Update Venue';
          this.getVenueById(this.data?.id);
        }
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }
  getVenueById(Id: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_venue_by_id?id=${Id}`, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.venueForm.patchValue(res?.data);
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
      this.common.presentAlert(err?.error?.message);
    });
  }
  submit(formValue: any) {
    if (this.data?.id) {
      formValue.id = this.data.id;
    }
    this.common.showLoader();
    this.apiService.sendAuthData('admin/add_update_venue', formValue, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      let check:any = await this.common.presentAlertWithPromise("Success",res?.message);
      this.event.publish('reload_venues',1);
      if(check){
        this.router.navigate(['/admin-venue-list']);
      }      
      console.log('res', res);
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
      this.common.presentAlert(err?.error?.message);
    })
  }


}
