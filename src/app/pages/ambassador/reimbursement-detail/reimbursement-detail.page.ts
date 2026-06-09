import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { FileViewer } from '@capacitor/file-viewer';

@Component({
  standalone: false,
  selector: 'app-reimbursement-detail',
  templateUrl: './reimbursement-detail.page.html',
  styleUrls: ['./reimbursement-detail.page.scss'],
})
export class ReimbursementDetailPage implements OnInit {
  params: any = { title: 'Reimbursement Details', is_back: true };
  user: any;
  data: any;
  reimbursement: any;

  constructor(
    public router: Router,
    public apiService: ApiService,
    public common: CommonService,
    public storage: StorageService,
  ) {
    if (this.router.getCurrentNavigation()?.extras?.state) {
      this.data = this.router.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.loadReimbursement();
        }
      }
    });
  }

  loadReimbursement() {
    this.common.showLoader();
    this.apiService.getAuthData('ambassador/get_reimbursement_by_id?id=' + this.data.id, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading();
      this.reimbursement = res?.data;
    }, err => {
      this.common.dismissLoading();
      this.common.presentAlert(err?.error?.message || 'Unable to load reimbursement.');
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

  editReimbursement() {
    this.router.navigate(['/reimbursement-edit'], { state: { id: this.reimbursement.id } });
  }
}
