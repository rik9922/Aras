import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';

interface DetailRow {
  label: string;
  value: any;
}

interface AttachmentGroup {
  label: string;
  attachments: string[];
}

@Component({
  selector: 'app-view-recap',
  template: `
<appHeader [params]="params"> </appHeader>

<ion-content [fullscreen]="true">
    <ion-card>
        <ion-card-header>
            <ion-card-title>Event Details</ion-card-title>
        </ion-card-header>
        <ion-card-content>
            <ng-container *ngIf="eventDetails.length > 0; else fallbackEventDetails">
                <ion-item *ngFor="let detail of eventDetails">
                    <ion-label position="stacked" class="text-bold">{{ detail.label }}:</ion-label>
                    <ion-text>{{ displayValue(detail.value) }}</ion-text>
                </ion-item>
            </ng-container>

            <ng-template #fallbackEventDetails>
                <ion-item *ngIf="recapData?.state?.name">
                    <ion-label>Store:</ion-label>
                    <ion-text>{{ recapData?.state?.name }}</ion-text>
                </ion-item>

                <ion-item *ngIf="recapData?.close_time">
                    <ion-label>Date:</ion-label>
                    <ion-text>{{ recapData?.close_time | date: 'shortDate' }}</ion-text>
                </ion-item>

                <ion-item *ngIf="recapData?.event?.brand?.brand_name">
                    <ion-label>Brand:</ion-label>
                    <ion-text>{{ recapData?.event?.brand?.brand_name }}</ion-text>
                </ion-item>

                <ion-item *ngIf="recapData?.event_id">
                    <ion-label>Event ID:</ion-label>
                    <ion-text>{{ recapData?.event_id }}</ion-text>
                </ion-item>

                <ion-item *ngIf="recapData?.ambassador?.name">
                    <ion-label>Ambassadors:</ion-label>
                    <ion-text>{{ recapData?.ambassador?.name }}</ion-text>
                </ion-item>
            </ng-template>
        </ion-card-content>
    </ion-card>

    <ion-card>
        <ion-card-header>
            <ion-card-title>Recap Details</ion-card-title>
        </ion-card-header>
        <ion-card-content>
            <ng-container *ngIf="recapDetails.length > 0; else fallbackRecapDetails">
                <div class="recap-detail-grid">
                    <div class="recap-detail-card" *ngFor="let detail of recapDetails">
                        <div class="recap-detail-label">{{ detail.label }}</div>
                        <div class="recap-detail-value">{{ displayValue(detail.value) }}</div>
                    </div>
                </div>
            </ng-container>

            <ng-template #fallbackRecapDetails>
                <div class="recap-detail-grid">
                    <div class="recap-detail-card">
                        <div class="recap-detail-label">Everyday Price Tip</div>
                        <div class="recap-detail-value">{{ displayValue(recapData?.tip) }}</div>
                    </div>
                </div>
            </ng-template>
        </ion-card-content>
    </ion-card>

    <ion-card *ngIf="attachmentGroups.length > 0">
        <ion-card-header>
            <ion-card-title>Uploaded Files</ion-card-title>
        </ion-card-header>
        <ion-card-content>
            <div class="attachment-group" *ngFor="let group of attachmentGroups">
                <div class="attachment-label">{{ group.label }}</div>
                <div class="attachment-grid">
                    <ng-container *ngFor="let attachment of group.attachments">
                        <a class="attachment-tile" [href]="attachmentUrl(attachment)" target="_blank" rel="noopener">
                            <img *ngIf="isImage(attachment); else fileAttachment"
                                [src]="attachmentUrl(attachment)"
                                [alt]="group.label"
                                onerror="this.onerror=null;this.src='assets/icon/pdf.png';" />
                            <ng-template #fileAttachment>
                                <img src="assets/icon/pdf.png" [alt]="group.label" />
                            </ng-template>
                        </a>
                    </ng-container>
                </div>
            </div>
        </ion-card-content>
    </ion-card>
</ion-content>
`,
  styleUrls: ['./view-recap.page.scss'],
  standalone: false
})
export class ViewRecapPage implements OnInit {
  params: any = { title: 'View Recap', is_back: true };
  data: any;
  user: any;
  recapData: any = {};
  eventDetails: DetailRow[] = [];
  recapDetails: DetailRow[] = [];
  attachmentGroups: AttachmentGroup[] = [];

  constructor(
    public common: CommonService,
    public router: Router,
    public storage: StorageService,
    public apiService: ApiService,
    public event: EventsService,
    public nav: NavController
  ) {
    if (this.router?.getCurrentNavigation()?.extras?.state) {
      this.data = this.router?.getCurrentNavigation()?.extras?.state;
    }
  }

  ngOnInit() {
  }
  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        if (this.data?.id) {
          this.getRecapById(this.data?.id);
        }
      } else {
        this.nav.navigateRoot(['/login']);
      }
    })
  }
  getRecapById(recapId: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_recap_by_id?id=${recapId}`, this.user?.token).subscribe((res: any) => {
      this.recapData = res?.data?.recap || {};
      this.eventDetails = this.normalizeDetails(res?.data?.event_details);
      this.recapDetails = this.normalizeDetails(res?.data?.recap_details);
      this.attachmentGroups = this.buildAttachmentGroups(this.recapData);
      this.common.dismissLoading();
    }, err => {
      this.common.dismissLoading();
      console.log('err', err);
    })
  }

  normalizeDetails(details: any): DetailRow[] {
    return Array.isArray(details) ? details.filter((detail: any) => detail?.label) : [];
  }

  displayValue(value: any): string {
    if (value === null || value === undefined || value === '') {
      return 'Not available';
    }

    if (Array.isArray(value)) {
      return value.length ? value.map((item) => this.displayValue(item)).join(', ') : 'Not available';
    }

    if (typeof value === 'object') {
      return value?.name || value?.brand_name || value?.label || value?.value || JSON.stringify(value);
    }

    return String(value);
  }

  buildAttachmentGroups(recap: any): AttachmentGroup[] {
    const groups: AttachmentGroup[] = [
      { label: 'Product Display Photo', attachments: this.toAttachmentList(recap?.product_attachments1) },
      { label: 'Product Shelf Photo', attachments: this.toAttachmentList(recap?.product_attachments2) },
      { label: 'Setup Photo', attachments: this.toAttachmentList(recap?.brand_photo) },
      { label: 'Consumers With Product', attachments: this.toAttachmentList(recap?.consumer_photos || recap?.consumer_photo) },
      { label: 'Event Photos', attachments: this.toAttachmentList(recap?.op_event_photos) },
      { label: 'Receipt', attachments: this.toAttachmentList(recap?.receipt) },
      { label: 'Off-Premise Receipts', attachments: this.toAttachmentList(recap?.op_receipts || recap?.op_event_receipts) },
      { label: 'Additional Attachments', attachments: this.toAttachmentList(recap?.recap_attachments) },
    ];

    return groups.filter((group) => group.attachments.length > 0);
  }

  toAttachmentList(value: any): string[] {
    if (!value) {
      return [];
    }

    if (Array.isArray(value)) {
      return value.reduce((attachments: string[], item: any) => {
        return attachments.concat(this.toAttachmentList(item));
      }, []);
    }

    if (typeof value === 'object') {
      return this.toAttachmentList(value?.filename || value?.file || value?.path || value?.url);
    }

    const text = String(value).trim();
    if (!text) {
      return [];
    }

    if ((text.startsWith('[') && text.endsWith(']')) || (text.startsWith('{') && text.endsWith('}'))) {
      try {
        return this.toAttachmentList(JSON.parse(text));
      } catch (error) {
        return [];
      }
    }

    return text.split(',').map((item) => item.trim()).filter(Boolean);
  }

  attachmentUrl(path: string): string {
    if (/^https?:\/\//i.test(path) || path.startsWith('blob:') || path.startsWith('data:')) {
      return path;
    }

    return environment.base_url + path.replace(/^\/+/, '');
  }

  isImage(path: string): boolean {
    return /\.(jpe?g|png|gif|webp|bmp|svg)(\?.*)?$/i.test(path);
  }

}
