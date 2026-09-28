import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-manager-view',
  templateUrl: './view.page.html',
  styleUrls: ['./view.page.scss'],
  standalone: false
})
export class ViewPage implements OnInit {
  params: any = { title: 'Manager Details', is_back: true };
  user: any;
  managerId: number | null = null;
  manager: any;
  isLoading = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private nav: NavController,
    private apiService: ApiService,
    private common: CommonService,
    private storage: StorageService,
  ) {
    const state = this.router.getCurrentNavigation()?.extras?.state as any;
    const routeId = this.route.snapshot.paramMap.get('id');
    this.managerId = state?.id ? Number(state.id) : (routeId ? Number(routeId) : null);
  }

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (!data) {
        this.nav.navigateRoot('/login');
        return;
      }

      this.user = data;
      if (!this.managerId) {
        this.common.presentAlert('Manager not found.');
        this.nav.back();
        return;
      }

      this.loadManager();
    });
  }

  loadManager() {
    if (!this.managerId) return;

    this.isLoading = true;
    this.apiService.getAuthData(`admin/get_manager_by_id?id=${this.managerId}`, this.user?.token).subscribe({
      next: (response: any) => {
        this.manager = response?.status === 'success' ? response.data : response;
        this.isLoading = false;
      },
      error: (error: any) => {
        this.isLoading = false;
        this.common.presentAlert(error?.error?.message || 'Unable to load manager details.');
      }
    });
  }

  edit() {
    if (!this.managerId) return;
    this.nav.navigateForward('/admin-add-manager', { state: { id: this.managerId } });
  }

  managerName(): string {
    return this.valueOrDash(
      this.manager?.name ||
      `${this.manager?.first_name || ''} ${this.manager?.last_name || ''}`.trim()
    );
  }

  statusLabel(): string {
    const status = String(this.manager?.status ?? '').toLowerCase();
    return status === '0' || status === 'inactive' || status === 'false' ? 'Inactive' : 'Active';
  }

  marketNames(): string {
    const markets = this.manager?.manager_markets || this.manager?.managerMarkets || this.manager?.markets || [];
    if (!Array.isArray(markets) || markets.length === 0) return '-';

    return markets
      .map((market: any) => market?.name || market?.market_name || market)
      .filter(Boolean)
      .join(', ');
  }

  valueOrDash(value: any): string {
    return value === null || value === undefined || value === '' ? '-' : String(value);
  }
}
