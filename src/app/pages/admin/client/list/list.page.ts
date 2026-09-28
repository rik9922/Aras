import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

interface ClientListItem {
  id: number;
  name: string;
  parent_bill_to_id: number | null;
  parent_bill_to: string | null;
  email: string | null;
  phone: string | null;
  quickbooks_id: string | null;
  contract: 'Yes' | 'No';
  status: 'Active' | 'Inactive';
  qb_sync: string;
  qb_sync_status: string | null;
  qb_synced: boolean;
}

interface ParentBillToOption {
  id: number;
  name: string;
}

@Component({
  standalone: false,
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
})
export class ListPage implements OnInit {
  params: any = { title: "Bill To", is_back:true };
  user: any;
  clients: ClientListItem[] = [];
  parentOptions: ParentBillToOption[] = [];
  searchQuery = '';
  showInactive = false;
  isLoading = false;
  hasMore = true;
  totalClients = 0;
  qbConnected = false;

  private page = 0;
  private readonly pageSize = 20;
  private requestVersion = 0;

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public nav: NavController,
    public event: EventsService
  ) {
    this.event.subscribe('reload_clients', res => {
      this.refresh();
    });
  };

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.storage.get("apro_user").then((data) => {
      if (data != null) {
        this.user = data;
        this.loadParentOptions();
        this.refresh();
      } else {
        this.router.navigate(["/login"]);
      }
    });
  }

  refresh() {
    this.clients = [];
    this.page = 0;
    this.hasMore = true;
    this.totalClients = 0;
    this.loadPage();
  }

  loadPage(infiniteScrollEvent?: any) {
    if (this.isLoading || !this.hasMore) {
      infiniteScrollEvent?.target?.complete();
      return;
    }

    this.isLoading = true;
    const requestVersion = ++this.requestVersion;
    const params = new URLSearchParams({
      start: String(this.page * this.pageSize),
      length: String(this.pageSize),
      draw: String(this.page + 1),
      'search[value]': this.searchQuery,
    });

    if (this.showInactive) {
      params.set('is_inactive', 'true');
    }

    this.apiService.getAuthData(`admin/get_clients?${params.toString()}`, this.user?.token).subscribe({
      next: (response: any) => {
        if (requestVersion !== this.requestVersion) return;

        const payload = response?.status === 'success' ? response.data : response;
        const rows = Array.isArray(payload?.data)
          ? payload.data.map((client: any): ClientListItem => ({
              id: Number(client?.id),
              name: String(client?.name || `${client?.first_name ?? ''} ${client?.last_name ?? ''}`.trim() || 'Unnamed Bill To'),
              parent_bill_to_id: client?.parent_bill_to_id ? Number(client.parent_bill_to_id) : null,
              parent_bill_to: client?.parent_bill_to ? String(client.parent_bill_to) : null,
              email: client?.email ? String(client.email) : null,
              phone: client?.phone ? String(client.phone) : null,
              quickbooks_id: client?.quickbooks_id ? String(client.quickbooks_id) : null,
              contract: client?.contract === 'Yes' ? 'Yes' : 'No',
              status: client?.status === 'Inactive' ? 'Inactive' : 'Active',
              qb_sync: String(client?.qb_sync ?? 'Not synced'),
              qb_sync_status: client?.qb_sync_status ? String(client.qb_sync_status) : null,
              qb_synced: Boolean(client?.qb_synced),
            }))
          : [];
        const totalRecords = Number(payload?.recordsTotal ?? 0);
        const totalFiltered = Number(payload?.recordsFiltered ?? totalRecords);
        this.qbConnected = Boolean(payload?.quickbooks_connected);
        this.clients = [...this.clients, ...rows];
        this.totalClients = totalFiltered;
        this.hasMore = rows.length > 0 && this.clients.length < totalFiltered;
        this.page++;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: () => {
        if (requestVersion !== this.requestVersion) return;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
        this.common.presentAlert('Unable to load Bill To records. Please try again.');
      }
    });
  }

  loadParentOptions() {
    this.apiService.getAuthData('admin/get_client_parent_options', this.user?.token).subscribe({
      next: (response: any) => {
        const payload = response?.status === 'success' ? response.data : response;
        this.parentOptions = Array.isArray(payload)
          ? payload.map((option: any) => ({
              id: Number(option?.id),
              name: String(option?.name ?? 'Unnamed Bill To'),
            }))
          : [];
      },
      error: () => {
        this.common.presentAlert('Unable to load Parent Bill To options. Please try again.');
      }
    });
  }

  edit(data: any) {
    this.nav.navigateForward('admin-client-add-update', { state: { id: data } })
  }

  view(client: ClientListItem) {
    this.nav.navigateForward('admin-client-view', { state: { id: client.id } });
  }

  transactions(client: ClientListItem) {
    this.nav.navigateForward('admin-client-transactions', { state: { id: client.id } });
  }

  async showMonthlySummary(client: ClientListItem) {
    const month = new Date().toISOString().slice(0, 7);
    this.apiService.getAuthData(`admin/client_monthly_stats?client_id=${client.id}&month=${month}`, this.user?.token).subscribe({
      next: (response: any) => {
        const stats = response?.status === 'success' ? response.data : response;
        this.common.presentAlert(
          `<strong>${this.escapeHtml(client.name)}</strong><br>${this.escapeHtml(stats?.month ?? '')}<br><br>` +
          `Submitted: ${stats?.submitted ?? 0}<br>` +
          `Not Recapped: ${stats?.not_recapped ?? 0}<br>` +
          `No Shows: ${stats?.no_shows ?? 0}<br>` +
          `Late: ${stats?.late ?? 0}<br>` +
          `Cancelled: ${stats?.cancelled ?? 0}`
        );
      },
      error: (error: any) => {
        this.common.presentAlert(error?.error?.message || 'Unable to load monthly summary.');
      }
    });
  }

  async syncQuickBooks(client: ClientListItem) {
    if (!this.qbConnected) {
      this.common.presentAlert('Please connect to QuickBooks before syncing this Bill To record.');
      return;
    }

    const confirmed = await this.common.presentConfirmationAlert(
      'Sync with QuickBooks?',
      `${client.qb_sync_status === 'failed' ? 'Retry' : 'Queue'} QuickBooks sync for ${client.name}?`
    );
    if (!confirmed) return;

    this.common.showLoader('Queueing sync...');
    this.apiService.sendAuthData('admin/sync_client_quickbooks', { client_id: client.id }, this.user?.token).subscribe({
      next: (response: any) => {
        this.common.dismissLoading();
        this.common.presentToast(response?.message || 'Client QuickBooks sync queued.');
        this.refresh();
      },
      error: (error: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(error?.error?.message || 'Unable to queue QuickBooks sync.');
      }
    });
  }

  updateParentBillTo(client: ClientListItem, event: any) {
    const rawValue = event?.detail?.value;
    const parentId = rawValue === '' || rawValue === null || rawValue === undefined ? null : Number(rawValue);

    this.apiService.sendAuthData('admin/update_client_parent', {
      client_id: client.id,
      parent_bill_to_id: parentId,
    }, this.user?.token).subscribe({
      next: (response: any) => {
        const parent = this.parentOptions.find(option => option.id === parentId);
        client.parent_bill_to_id = parentId;
        client.parent_bill_to = parent?.name ?? null;
        this.common.presentToast(response?.message || 'Parent Bill To updated.');
      },
      error: (error: any) => {
        this.common.presentAlert(error?.error?.message || 'Unable to update Parent Bill To.');
        this.refresh();
      }
    });
  }

  onSearch(event: any) {
    this.searchQuery = event?.detail?.value?.trim() ?? '';
    this.requestVersion++;
    this.isLoading = false;
    this.refresh();
  }

  view_inactive(event: any) {
    this.showInactive = Boolean(event?.detail?.checked);
    this.requestVersion++;
    this.isLoading = false;
    this.refresh();
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  private escapeHtml(value: any): string {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
}
