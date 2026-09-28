import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-client-view',
  templateUrl: './view.page.html',
  styleUrls: ['./view.page.scss'],
})
export class ViewPage implements OnInit {
  params: any = { title: 'Bill To Details', is_back: true };
  user: any;
  clientId: number | null = null;
  client: any;
  isLoading = false;

  constructor(
    private router: Router,
    private nav: NavController,
    private apiService: ApiService,
    private common: CommonService,
    private storage: StorageService,
  ) {
    const state = this.router.getCurrentNavigation()?.extras?.state as any;
    this.clientId = state?.id ? Number(state.id) : null;
  }

  ngOnInit() {}

  ionViewWillEnter() {
    this.storage.get('apro_user').then((data: any) => {
      if (!data) {
        this.nav.navigateRoot('/login');
        return;
      }

      this.user = data;
      if (!this.clientId) {
        this.common.presentAlert('Bill To not found.');
        this.nav.back();
        return;
      }
      this.loadClient();
    });
  }

  loadClient() {
    if (!this.clientId) return;

    this.isLoading = true;
    this.apiService.getAuthData(`admin/get_client_details?client_id=${this.clientId}`, this.user?.token).subscribe({
      next: (response: any) => {
        this.client = response?.status === 'success' ? response.data : response;
        this.isLoading = false;
      },
      error: (error: any) => {
        this.isLoading = false;
        this.common.presentAlert(error?.error?.message || 'Unable to load Bill To details.');
      }
    });
  }

  edit() {
    if (!this.clientId) return;
    this.nav.navigateForward('admin-client-add-update', { state: { id: this.clientId } });
  }

  showTransactions() {
    if (!this.clientId) return;
    this.nav.navigateForward('admin-client-transactions', { state: { id: this.clientId } });
  }

  formatMoney(value: any, fallback = '-'): string {
    if (value === null || value === undefined || value === '') return fallback;
    return `$${Number(value).toFixed(2)}`;
  }

  valueOrDash(value: any): string {
    return value === null || value === undefined || value === '' ? '-' : String(value);
  }
}
