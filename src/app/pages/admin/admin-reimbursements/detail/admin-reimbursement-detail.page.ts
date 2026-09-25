import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { FileViewer } from '@capacitor/file-viewer';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-admin-reimbursement-detail',
  templateUrl: './admin-reimbursement-detail.page.html',
  styleUrls: ['./admin-reimbursement-detail.page.scss'],
})
export class AdminReimbursementDetailPage implements OnInit {
  params: any = { title: 'Reimbursement Details', is_back: true };
  user: any;
  reimbursement: any;
  potentialDuplicates: any[] = [];
  reimbursementId: any;
  rejectReason = '';
  overrideDuplicateCheck = false;
  isLoading = false;
  isRejectOpen = false;
  isApproving = false;
  isRejecting = false;

  constructor(
    public route: ActivatedRoute,
    public router: Router,
    public nav: NavController,
    public apiService: ApiService,
    public common: CommonService,
    public storage: StorageService,
  ) {
    const state = this.router.getCurrentNavigation()?.extras?.state;
    this.reimbursementId = state?.['id'];
  }

  ngOnInit() {
    this.reimbursementId = this.reimbursementId || this.route.snapshot.paramMap.get('id');
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.reimbursementId) {
          this.loadReimbursement();
        }
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  loadReimbursement() {
    this.isLoading = true;
    this.apiService.getAuthData('admin/get_reimbursement_by_id?id=' + this.reimbursementId, this.user?.token).subscribe((res: any) => {
      const payload = res?.data || {};
      this.reimbursement = payload?.reimbursement || payload;
      this.potentialDuplicates = payload?.potential_duplicates || [];
      this.overrideDuplicateCheck = false;
      this.isLoading = false;
    }, err => {
      this.isLoading = false;
      this.common.presentAlert(err?.error?.message || 'Unable to load reimbursement.');
    });
  }

  approve() {
    if (this.potentialDuplicates.length > 0 && !this.overrideDuplicateCheck) {
      this.common.presentAlert('Please review and confirm the potential duplicate warning before approving.');
      return;
    }

    this.isApproving = true;
    this.apiService.sendAuthData('admin/approve_reimbursement', {
      id: this.reimbursement?.id,
      override_duplicate_check: this.overrideDuplicateCheck ? 1 : 0,
    }, this.user?.token).subscribe((res: any) => {
      this.isApproving = false;
      this.common.presentAlertWithPromise('Approved', res?.message || 'Reimbursement approved.');
      this.loadReimbursement();
    }, err => {
      this.isApproving = false;
      this.common.presentAlert(err?.error?.message || 'Unable to approve reimbursement.');
    });
  }

  openReject() {
    this.rejectReason = '';
    this.isRejectOpen = true;
  }

  closeReject() {
    this.isRejectOpen = false;
  }

  reject() {
    if (!this.rejectReason.trim()) {
      this.common.presentAlert('Please enter a rejection reason.');
      return;
    }

    this.isRejecting = true;
    this.apiService.sendAuthData('admin/reject_reimbursement', {
      id: this.reimbursement?.id,
      rejection_reason: this.rejectReason.trim(),
    }, this.user?.token).subscribe((res: any) => {
      this.isRejecting = false;
      this.isRejectOpen = false;
      this.common.presentAlertWithPromise('Rejected', res?.message || 'Reimbursement rejected.');
      this.loadReimbursement();
    }, err => {
      this.isRejecting = false;
      this.common.presentAlert(err?.error?.message || 'Unable to reject reimbursement.');
    });
  }

  isImageReceipt(): boolean {
    const url = this.reimbursement?.receipt_url || '';
    return /\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i.test(url);
  }

  async openReceipt() {
    if (!this.reimbursement?.receipt_url) {
      return;
    }

    await FileViewer.openDocumentFromUrl({
      url: this.reimbursement.receipt_url,
    });
  }

  getStatusClass(row: any = this.reimbursement): string {
    return `status-badge--${row?.status || 'pending'}`;
  }
}
