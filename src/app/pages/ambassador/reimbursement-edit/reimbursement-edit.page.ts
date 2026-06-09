import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

declare var jQuery: any;

@Component({
  standalone: false,
  selector: 'app-reimbursement-edit',
  templateUrl: './reimbursement-edit.page.html',
  styleUrls: ['./reimbursement-edit.page.scss'],
})
export class ReimbursementEditPage implements OnInit {
  params: any = { title: 'Edit Reimbursement', is_back: true };
  user: any;
  data: any;
  hasVerifiedBank = false;
  brands: any[] = [];
  events: any[] = [];
  receiptPreview: string | null = null;
  existingReceiptUrl: string | null = null;
  rejectionReason: string | null = null;

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
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.data = this.router.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((userData: any) => {
      if (userData) {
        this.user = userData;
        this.loadPageData();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  loadPageData() {
    this.common.showLoader();
    this.apiService.getAuthData('ambassador/get_reimbursement_form_data', this.user?.token).subscribe((formRes: any) => {
      this.hasVerifiedBank = formRes?.data?.has_verified_bank === true;
      this.brands = formRes?.data?.brands || [];
      this.events = formRes?.data?.events || [];

      this.apiService.getAuthData('ambassador/get_reimbursement_by_id?id=' + this.data.id, this.user?.token).subscribe((res: any) => {
        this.common.dismissLoading();
        const reimbursement = res?.data;
        if (!reimbursement?.can_edit) {
          this.common.presentAlert('Only rejected reimbursements can be edited.');
          this.router.navigate(['/reimbursement-detail'], { state: { id: this.data.id } });
          return;
        }

        this.rejectionReason = reimbursement.rejection_reason;
        this.existingReceiptUrl = reimbursement.receipt_url;
        this.form = {
          event_id: reimbursement.event_id ? String(reimbursement.event_id) : '',
          brand_id: reimbursement.brand_id ? String(reimbursement.brand_id) : '',
          brand_name: reimbursement.brand_name || '',
          expense_date: reimbursement.expense_date || '',
          amount: String(reimbursement.amount ?? ''),
          reason: reimbursement.reason || '',
        };
      }, err => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to load reimbursement.');
      });
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
    jQuery('#reimbursementReceiptEditUpload').trigger('click');
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
      this.common.presentAlert('You must verify your bank account before resubmitting reimbursement.');
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
    formData.append('id', String(this.data.id));
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

    const fileInput: any = document.getElementById('reimbursementReceiptEditUpload');
    if (fileInput?.files?.length) {
      formData.append('receipt', fileInput.files[0]);
    }

    this.common.showLoader();
    this.apiService.sendAuthData('ambassador/update_reimbursement', formData, this.user?.token).subscribe(async (res: any) => {
      this.common.dismissLoading();
      const confirmed = await this.common.presentAlertWithPromise('Success', res?.message);
      if (confirmed) {
        this.router.navigate(['/reimbursement-detail'], { state: { id: this.data.id } });
      }
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message || 'Unable to update reimbursement.');
    });
  }
}
