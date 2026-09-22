import { Component, OnInit } from '@angular/core';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { FileViewer } from "@capacitor/file-viewer";
import { Browser } from '@capacitor/browser';
import { unwrapApiPayload } from 'src/app/utils/api-response.util';

@Component({
  standalone: false,
  selector: 'app-aras-document',
  templateUrl: './aras-document.page.html',
  styleUrls: ['./aras-document.page.scss'],
})
export class ArasDocumentPage implements OnInit {
  params: any = { title: 'Aras Documents', is_back: true };
  user: any;
  isSpinner: boolean = false;
  documents: any[] = [];
  private readonly allowedDocumentTypes = ['image/jpeg', 'image/png', 'application/pdf'];
  private readonly allowedDocumentExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
  constructor(
    public storage: StorageService,
    public common: CommonService,
    public apiService: ApiService,
    public nav: NavController,
  ) { }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.documents = [];
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.getDocuments();
      } else {
        this.nav.navigateRoot('/login');
      }
    })
  }

  getDocuments(){
    this.isSpinner = true;
    this.apiService.getAuthData('admin/get_aras_documents', this.user?.token).subscribe({
      next: (res: any) => {
        const payload: any = unwrapApiPayload(res);
        this.documents = Array.isArray(payload?.data) ? payload.data : [];
        this.isSpinner = false;
      },
      error: (err: any) => {
        this.isSpinner = false;
        this.common.presentAlert(err?.error?.message || 'Unable to load documents.');
      }
    });
  }

  async view_document(url:any){
    if (!url) {
      this.common.presentAlert('Document URL is not available.');
      return;
    }

    try {
      await FileViewer.openDocumentFromUrl({ url });
    } catch {
      try {
        await Browser.open({ url });
      } catch {
        this.common.presentAlert('Unable to open this document.');
      }
    }
  }

  select_file(){
    document.querySelector<HTMLInputElement>('input[name="document"]')?.click();
  }

  onChange(event:any){
    if(event.target.files?.length>0){
      const file = event.target.files[0];

      if (!this.isAllowedDocument(file)) {
        this.common.presentAlert('Please upload a JPG, JPEG, PNG, or PDF file.');
        event.target.value = '';
        return;
      }

      this.common.showLoader();
      var formdata=new FormData();
      formdata.append('document', file);
      this.apiService.sendAuthData('admin/upload_aras_document',formdata,this.user?.token).subscribe((res:any)=>{
        this.common.dismissLoading()
        this.common.presentAlert(res?.message)
        this.getDocuments()
        event.target.value = '';
      },err=>{
        this.common.dismissLoading()
        this.common.presentAlert(err?.error?.message)
      })
    }
  }

  private isAllowedDocument(file: File): boolean {
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
    return this.allowedDocumentTypes.includes(file.type)
      || this.allowedDocumentExtensions.includes(extension);
  }

  async deleteDocument(documentItem: any) {
    const confirmed = await this.common.presentConfirmationAlert(
      'Delete document?',
      `Delete ${documentItem?.file_name || 'this document'}? This cannot be undone.`
    );
    if (!confirmed) return;

    this.common.showLoader('Deleting document...');
    this.apiService.sendAuthData('admin/delete_aras_document', { id: documentItem?.id }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.common.presentToast(res?.message || 'Document deleted successfully.');
        this.getDocuments();
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to delete this document.');
      }
    });
  }

  getFileExtension(documentItem: any): string {
    return (documentItem?.file_name || documentItem?.url || '')
      .split('?')[0]
      .split('.')
      .pop()
      ?.toUpperCase() || 'FILE';
  }

  formatFileSize(size: any): string {
    const bytes = Number(size);
    if (!bytes) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  }
}
