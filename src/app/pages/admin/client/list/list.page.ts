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
  selected?: boolean;
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
  nameFilter = '';
  qbIdFilter = '';
  activeOnly = false;
  contractReceivedOnly = false;
  bulkField = 'status';
  bulkValue = '';
  isLoading = false;
  hasMore = true;
  totalClients = 0;
  totalBillables = 0;
  unsyncedCount = 0;
  qbConnected = false;

  bulkFields = [
    { value: 'status', label: 'Status (0/1)' },
    { value: 'phone', label: 'Phone' },
    { value: 'address', label: 'Address' },
    { value: 'city', label: 'City' },
    { value: 'state', label: 'State' },
    { value: 'zip_code', label: 'ZIP' },
    { value: 'company_name', label: 'Company' },
    { value: 'primary_billing_contact', label: 'Billing contact' },
    { value: 'default_labor_amount', label: 'Default labor' },
    { value: 'default_buyback_amount', label: 'Default buyback' },
    { value: 'default_cups_amount', label: 'Default cups' },
  ];

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
      'search[value]': this.nameFilter,
      name: this.nameFilter,
      qb_id: this.qbIdFilter,
      active_only: this.activeOnly ? '1' : '0',
      contract_received_only: this.contractReceivedOnly ? '1' : '0',
    });

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
              selected: false,
            }))
          : [];
        const totalRecords = Number(payload?.recordsTotal ?? 0);
        const totalFiltered = Number(payload?.recordsFiltered ?? totalRecords);
        this.qbConnected = Boolean(payload?.quickbooks_connected);
        this.unsyncedCount = Number(payload?.unsynced_count ?? 0);
        this.clients = [...this.clients, ...rows];
        this.totalBillables = totalRecords;
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

  async syncAllQuickBooks() {
    if (!this.qbConnected) {
      this.common.presentAlert('Please connect to QuickBooks before syncing Bill To records.');
      return;
    }

    if (this.unsyncedCount === 0) {
      this.common.presentAlert('All visible Bill To records are already synced with QuickBooks.');
      return;
    }

    const confirmed = await this.common.presentConfirmationAlert(
      'Sync all with QuickBooks?',
      'This will sync all unsynced Bill To records with QuickBooks.'
    );
    if (!confirmed) return;

    this.common.showLoader('Starting QuickBooks sync...');
    this.apiService.sendAuthData('admin/sync_all_clients_quickbooks', {}, this.user?.token).subscribe({
      next: (response: any) => {
        this.common.dismissLoading();
        this.common.presentToast(response?.message || 'QuickBooks sync started.');
        this.refresh();
      },
      error: (error: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(error?.error?.message || 'Unable to start QuickBooks sync.');
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

  onNameFilterInput(event: any) {
    this.nameFilter = event?.detail?.value ?? '';
  }

  onQbIdFilterInput(event: any) {
    this.qbIdFilter = event?.detail?.value ?? '';
  }

  applyFilters() {
    this.nameFilter = this.nameFilter.trim();
    this.qbIdFilter = this.qbIdFilter.trim();
    this.requestVersion++;
    this.isLoading = false;
    this.refresh();
  }

  resetFilters() {
    this.nameFilter = '';
    this.qbIdFilter = '';
    this.activeOnly = false;
    this.contractReceivedOnly = false;
    this.requestVersion++;
    this.isLoading = false;
    this.refresh();
  }

  toggleActiveOnly(event: any) {
    this.activeOnly = Boolean(event?.detail?.checked);
  }

  toggleActiveOnlyLabel() {
    this.activeOnly = !this.activeOnly;
  }

  toggleContractReceived(event: any) {
    this.contractReceivedOnly = Boolean(event?.detail?.checked);
  }

  toggleContractReceivedLabel() {
    this.contractReceivedOnly = !this.contractReceivedOnly;
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  get selectedClients(): ClientListItem[] {
    return this.clients.filter(client => client.selected);
  }

  get selectedCount(): number {
    return this.selectedClients.length;
  }

  get allVisibleSelected(): boolean {
    return this.clients.length > 0 && this.clients.every(client => client.selected);
  }

  toggleSelectAll(event: any) {
    const selected = Boolean(event?.detail?.checked);
    this.clients.forEach(client => client.selected = selected);
  }

  toggleSelectAllLabel() {
    const selected = !this.allVisibleSelected;
    this.clients.forEach(client => client.selected = selected);
  }

  async applyBulkEdit() {
    const selected = this.selectedClients;
    const value = this.bulkValue.trim();

    if (!selected.length) {
      this.common.presentAlert('Select at least one Bill To record first.');
      return;
    }

    if (value === '') {
      this.common.presentAlert('Enter a value before applying a bulk edit.');
      return;
    }

    if (this.bulkField === 'status' && !['0', '1'].includes(value)) {
      this.common.presentAlert('Status must be 0 for inactive or 1 for active.');
      return;
    }

    const confirmed = await this.common.presentConfirmationAlert(
      'Apply bulk edit?',
      `Update ${selected.length} selected Bill To record${selected.length === 1 ? '' : 's'}?`
    );
    if (!confirmed) return;

    const updates = selected.map(client => ({
      id: client.id,
      [this.bulkField]: value,
    }));

    this.common.showLoader('Applying update...');
    this.apiService.sendAuthData('admin/bulk_update_clients', { updates }, this.user?.token).subscribe({
      next: (response: any) => {
        this.common.dismissLoading();
        this.bulkValue = '';
        this.common.presentToast(response?.message || 'Selected Bill To records updated.');
        this.refresh();
      },
      error: (error: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(error?.error?.message || 'Unable to apply bulk update.');
      }
    });
  }

  exportClients() {
    const params = new URLSearchParams({
      name: this.nameFilter.trim(),
      qb_id: this.qbIdFilter.trim(),
      active_only: this.activeOnly ? '1' : '0',
      contract_received_only: this.contractReceivedOnly ? '1' : '0',
    });

    this.common.showLoader('Preparing export...');
    this.apiService.getAuthData(`admin/export_clients?${params.toString()}`, this.user?.token).subscribe({
      next: (response: any) => {
        this.common.dismissLoading();
        const payload = response?.status === 'success' ? response.data : response;
        const rows = Array.isArray(payload?.rows) ? payload.rows : [];
        if (!rows.length) {
          this.common.presentAlert('No Bill To records found for export.');
          return;
        }
        this.downloadCsv(rows);
      },
      error: (error: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(error?.error?.message || 'Unable to export Bill To records.');
      }
    });
  }

  private downloadCsv(rows: any[]) {
    const headers = Object.keys(rows[0]);
    const csv = [
      headers.join(','),
      ...rows.map(row => headers.map(header => this.csvCell(row?.[header])).join(',')),
    ].join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `bill-to-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(url);
  }

  private csvCell(value: any): string {
    const text = String(value ?? '');
    return `"${text.replace(/"/g, '""')}"`;
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
