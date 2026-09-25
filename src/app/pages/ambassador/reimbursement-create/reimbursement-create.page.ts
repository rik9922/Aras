import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-reimbursement-create',
  templateUrl: './reimbursement-create.page.html',
  styleUrls: ['./reimbursement-create.page.scss'],
})
export class ReimbursementCreatePage implements OnInit {
  params: any = { title: 'Request Reimbursement', is_back: true };
  user: any;
  hasVerifiedBank = false;
  brands: any[] = [];
  events: any[] = [];
  receiptPreview: string | null = null;

  form = {
    event_id: '',
    brand_id: '',
    brand_name: '',
    expense_date: '',
    amount: '',
    reason: '',
  };

  constructor(
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
    public router: Router,
  ) { }

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.loadFormData();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  loadFormData() {
    this.common.showLoader();
    this.apiService.getAuthData('ambassador/get_reimbursement_form_data', this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.hasVerifiedBank = res?.data?.has_verified_bank === true;
      this.brands = res?.data?.brands || [];
      this.events = res?.data?.events || [];
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message || 'Unable to load reimbursement form.');
    });
  }

  onEventChange() {
    if (this.form.event_id) {
      this.form.brand_id = '';
      this.form.brand_name = '';
    }
  }

  openReceiptUpload() {
    jQuery('#reimbursementReceiptUpload').trigger('click');
  }

  onReceiptSelected(event: any) {
    const file = event?.target?.files?.[0];
    if (!file) {
      return;
    }
    this.receiptPreview = URL.createObjectURL(file);
  }

  submit() {
    if (!this.hasVerifiedBank) {
      this.common.presentAlert('You must verify your bank account before submitting reimbursement.');
      return;
    }

    if (!this.form.expense_date || !this.form.amount || !this.form.reason) {
      this.common.presentAlert('Please fill in expense date, amount, and reason.');
      return;
    }

    if (!this.form.event_id && !this.form.brand_id && !this.form.brand_name) {
      this.common.presentAlert('Please provide either an event, brand, or brand name.');
      return;
    }

    const formData = new FormData();
    if (this.form.event_id) {
      formData.append('event_id', String(this.form.event_id));
    }
    if (this.form.brand_id) {
      formData.append('brand_id', String(this.form.brand_id));
    }
    if (this.form.brand_name) {
      formData.append('brand_name', this.form.brand_name);
    }
    formData.append('expense_date', this.form.expense_date);
    formData.append('amount', String(this.form.amount));
    formData.append('reason', this.form.reason);
    formData.append('is_expedited_requested', '0');

    const fileInput: any = document.getElementById('reimbursementReceiptUpload');
    if (fileInput?.files?.length) {
      formData.append('receipt', fileInput.files[0]);
    }

    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/store_reimbursement', formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      const confirmed = await this.common.presentAlertWithPromise('Success', res?.message);
      if (confirmed) {
        this.router.navigate(['/reimbursements']);
      }
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message || 'Unable to submit reimbursement.');
    });
  }
}
