import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { unwrapApiPayload } from 'src/app/utils/api-response.util';

@Component({
  standalone: false,
  selector: 'app-reimbursement',
  templateUrl: './reimbursement.page.html',
  styleUrls: ['./reimbursement.page.scss'],
})
export class ReimbursementPage implements OnInit {
  params: any = { title: 'Reimbursements', is_back: true };
  user: any;
  hasVerifiedBank = true;
  reimbursements: any[] = [];
  loadError = '';

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
        this.loadReimbursements();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  loadReimbursements(onComplete?: () => void) {
    this.loadError = '';
    this.common.showLoader();
    this.apiService.getAuthData(
      'ambassador/get_reimbursements?draw=1&start=0&length=100',
      this.user?.token
    ).subscribe((res: any) => {
      this.common.dismissLoading();
      const payload = unwrapApiPayload(res);
      this.hasVerifiedBank = payload?.has_verified_bank !== false;
      this.reimbursements = payload?.data || [];
      onComplete?.();
    }, err => {
      this.common.dismissLoading();
      this.reimbursements = [];
      if (err?.status === 404) {
        this.loadError = 'Reimbursements are not available on the server yet. Please deploy the latest portal update and try again.';
      } else {
        this.loadError = err?.error?.message || 'Unable to load reimbursements. Please try again.';
      }
      onComplete?.();
    });
  }

  handleRefresh(event: any) {
    this.loadReimbursements(() => event?.target?.complete());
  }

  createReimbursement() {
    this.router.navigate(['/reimbursement-create']);
  }

  viewReimbursement(id: number) {
    this.nav.navigateForward('reimbursement-detail', { state: { id } });
  }

  editReimbursement(id: number) {
    this.nav.navigateForward('reimbursement-edit', { state: { id } });
  }
}
