import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';
import { resolveDataTableResponse } from 'src/app/utils/api-response.util';

@Component({
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
  standalone: false
})
export class ListPage implements OnInit {
  user: any;
  params: any = { title: 'Unapprove Ambassador for Events List ', is_back: true };
  assignments: any[] = [];
  totalAssignments: number = 0;
  isLoading: boolean = false;
  isInitialLoading: boolean = false;
  hasMore: boolean = true;

  private page: number = 0;
  private readonly pageSize: number = 20;
  private requestVersion: number = 0;

  constructor(
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    public apiService: ApiService,
    public storage: StorageService,
    public event: EventsService
  ) { }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (data) {
        this.user = data;
        this.refresh();
      } else {
        this.nav.navigateRoot('/login');
      }
    });
  }

  refresh() {
    this.assignments = [];
    this.totalAssignments = 0;
    this.page = 0;
    this.hasMore = true;
    this.isInitialLoading = true;
    this.requestVersion++;
    this.loadPage();
  }

  loadPage(infiniteScrollEvent?: any) {
    if (this.isLoading) {
      infiniteScrollEvent?.target?.complete();
      return;
    }

    if (!this.hasMore && infiniteScrollEvent) {
      infiniteScrollEvent.target?.complete();
      return;
    }

    this.isLoading = true;
    const requestVersion = this.requestVersion;
    const params = [
      `start=${this.page * this.pageSize}`,
      `length=${this.pageSize}`,
      `draw=${this.page + 1}`
    ];

    this.apiService.getAuthData(`admin/get_unapprove_ambassador_list?${params.join('&')}`, this.user?.token).subscribe({
      next: (res: any) => {
        if (requestVersion !== this.requestVersion) return;

        const { payload, rows } = resolveDataTableResponse(res);
        const totalFiltered = Number(payload?.recordsFiltered ?? payload?.recordsTotal ?? rows.length);
        this.totalAssignments = Number(payload?.recordsTotal ?? totalFiltered);
        this.assignments = [...this.assignments, ...rows];
        this.hasMore = rows.length > 0 && this.assignments.length < totalFiltered;
        this.page++;
        this.isLoading = false;
        this.isInitialLoading = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: (err: any) => {
        if (requestVersion !== this.requestVersion) return;

        this.isLoading = false;
        this.isInitialLoading = false;
        infiniteScrollEvent?.target?.complete();
        this.common.presentAlert(err?.error?.message || 'Unable to load ambassadors.');
      }
    });
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  assign(assignment: any) {
    const id = this.assignmentId(assignment);
    if (!id) {
      this.common.presentAlert('Unable to approve this ambassador.');
      return;
    }

    this.common.showLoader('Approving ambassador...');
    this.apiService.sendAuthData('admin/assign_ambassabor_event', { id }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.common.presentToast(res?.message || 'Ambassador assigned to event successfully.');
        this.refresh();
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to approve this ambassador.');
      }
    });
  }

  async reject(assignment: any) {
    const id = this.assignmentId(assignment);
    if (!id) {
      this.common.presentAlert('Unable to reject this ambassador.');
      return;
    }

    const confirmed = await this.common.presentConfirmationAlert(
      'Reject request?',
      `Reject ${this.ambassadorName(assignment)} for ${this.eventName(assignment)}?`
    );
    if (!confirmed) return;

    this.common.showLoader('Rejecting ambassador...');
    this.apiService.sendAuthData('admin/reject_ambassabor_event', { id }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.common.presentToast(res?.message || 'Ambassador request rejected successfully.');
        this.refresh();
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to reject this ambassador.');
      }
    });
  }

  assignmentId(assignment: any) {
    if (assignment?.id) return assignment.id;
    const action = String(assignment?.action ?? '');
    const match = action.match(/id=["']?([^"'\s>]+)/);
    return match?.[1] ?? null;
  }

  eventName(assignment: any): string {
    return this.cleanText(assignment?.event_name || assignment?.event || '-');
  }

  ambassadorName(assignment: any): string {
    return this.cleanText(assignment?.ambassador_name || assignment?.name || '-');
  }

  ambassadorEmail(assignment: any): string {
    return this.cleanText(assignment?.email || '-');
  }

  ambassadorPhone(assignment: any): string {
    return this.cleanText(assignment?.phone || '-');
  }

  eventDate(assignment: any): string {
    return this.cleanText(assignment?.event_date || '-');
  }

  eventTime(assignment: any): string {
    return this.cleanText(assignment?.event_time || '');
  }

  eventLocation(assignment: any): string {
    return this.cleanText(assignment?.location || '-');
  }

  eventBrand(assignment: any): string {
    return this.cleanText(assignment?.brand || '-');
  }

  trackByAssignmentId = (index: number, assignment: any) => {
    return this.assignmentId(assignment) ?? index;
  };

  private cleanText(value: any): string {
    return String(value ?? '').replace(/<[^>]*>/g, '').trim();
  }
}
