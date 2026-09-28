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
  params: any = { title: 'Managers List', is_back: true };
  user: any;
  managers: any[] = [];
  searchQuery: string = '';
  totalManagers: number = 0;
  isLoading: boolean = false;
  isInitialLoading: boolean = false;
  hasMore: boolean = true;
  showInactive: boolean = false;

  private page: number = 0;
  private readonly pageSize: number = 20;
  private requestVersion: number = 0;

  constructor(
    public event: EventsService,
    public router: Router,
    public apiService: ApiService,
    public common: CommonService,
    public storage: StorageService,
    public nav: NavController,
  ) {
    this.event.subscribe('reload_managers', res => {
      this.refresh();
    });
  }

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
    this.managers = [];
    this.totalManagers = 0;
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
    const start = this.page * this.pageSize;
    const params = [
      `start=${start}`,
      `length=${this.pageSize}`,
      `draw=${this.page + 1}`,
      `search[value]=${encodeURIComponent(this.searchQuery || '')}`,
      `is_inactive=${this.showInactive}`
    ];

    this.apiService.getAuthData(`admin/get_managers?${params.join('&')}`, this.user?.token).subscribe({
      next: (res: any) => {
        if (requestVersion !== this.requestVersion) return;

        const { payload, rows } = resolveDataTableResponse(res);
        const totalFiltered = Number(payload?.recordsFiltered ?? payload?.recordsTotal ?? rows.length);
        this.totalManagers = Number(payload?.recordsTotal ?? totalFiltered);
        this.managers = [...this.managers, ...rows];
        this.hasMore = rows.length > 0 && this.managers.length < totalFiltered;
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
        this.common.presentAlert(err?.error?.message || 'Unable to load managers.');
      }
    });
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  onSearch(event: any) {
    this.searchQuery = event?.detail?.value?.trim() ?? '';
    this.refresh();
  }

  edit(data: any) {
    this.router.navigate(['/admin-add-manager'], { state: { id: this.managerId(data) ?? data } });
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }

  view_inactive(event: any) {
    const isChecked = !!event?.detail?.checked;
    if (this.showInactive === isChecked) return;

    this.showInactive = isChecked;
    this.refresh();
  }

  toggleInactive() {
    this.showInactive = !this.showInactive;
    this.refresh();
  }

  view(manager: any) {
    const id = this.managerId(manager);
    if (!id) {
      this.common.presentAlert('Unable to open this manager.');
      return;
    }

    this.router.navigate(['/admin-view-manager', id], { state: { id } });
  }

  async deleteManager(manager: any) {
    const id = this.managerId(manager);
    if (!id) {
      this.common.presentAlert('Unable to delete this manager.');
      return;
    }

    if (!this.canDeleteManager(manager)) {
      const count = this.linkedEventsCount(manager);
      this.common.presentAlert(`This manager cannot be deleted because they are assigned to ${count} event${count === 1 ? '' : 's'}.`);
      return;
    }

    const confirmed = await this.common.presentConfirmationAlert(
      'Delete manager?',
      `Delete ${this.managerName(manager)}? This cannot be undone.`
    );
    if (!confirmed) return;

    this.common.showLoader('Deleting manager...');
    this.apiService.sendAuthData('admin/delete_manager', { id }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.common.presentToast(res?.message || 'Manager deleted successfully.');
        this.refresh();
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to delete this manager.');
      }
    });
  }

  managerId(manager: any) {
    if (manager?.id) return manager.id;
    const action = String(manager?.action ?? '');
    const match = action.match(/id=["']?([^"'\s>]+)/);
    return match?.[1] ?? null;
  }

  linkedEventsCount(manager: any): number {
    return Number(manager?.linked_events_count ?? manager?.managed_events_count ?? manager?.events_count ?? 0);
  }

  canDeleteManager(manager: any): boolean {
    if (manager?.can_delete !== undefined) {
      return manager.can_delete === true || manager.can_delete === 1 || manager.can_delete === '1';
    }

    return this.linkedEventsCount(manager) === 0;
  }

  managerName(manager: any): string {
    return this.cleanText(
      manager?.name ||
      `${manager?.first_name || ''} ${manager?.last_name || ''}`.trim() ||
      'Unnamed manager'
    );
  }

  managerEmail(manager: any): string {
    return this.cleanText(manager?.email || manager?.email_address || '-');
  }

  managerPhone(manager: any): string {
    return this.cleanText(manager?.phone || manager?.phone_number || '-');
  }

  managerAddress(manager: any): string {
    return this.cleanText(manager?.address || '-');
  }

  managerStatus(manager: any): string {
    const rawStatus = this.cleanText(manager?.status ?? manager?.is_active ?? manager?.active ?? '');
    const normalized = rawStatus.toLowerCase();

    if (normalized === '0' || normalized === 'inactive' || normalized === 'false') {
      return 'Inactive';
    }

    return 'Active';
  }

  isManagerActive(manager: any): boolean {
    return this.managerStatus(manager) === 'Active';
  }

  trackByManagerId = (index: number, manager: any) => {
    return this.managerId(manager) ?? index;
  };

  private cleanText(value: any): string {
    return String(value ?? '').replace(/<[^>]*>/g, '').trim();
  }

  private escapeHtml(value: string): string {
    return value.replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[char] || char));
  }
}
