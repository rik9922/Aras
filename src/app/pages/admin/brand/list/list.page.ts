import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

interface BrandListItem {
  id: number;
  name: string;
  parent_name: string | null;
  categories: string[];
  categoryLabel: string;
  status: 'Active' | 'Inactive';
}

@Component({
  standalone: false,
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
})
export class ListPage implements OnInit {
  params: any = { title: "Brands" };
  user: any;
  brands: BrandListItem[] = [];
  searchQuery = '';
  isLoading = false;
  hasMore = true;

  private page = 0;
  private readonly pageSize = 20;
  private requestVersion = 0;
  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public event: EventsService
  ) {
    this.event.subscribe('reload_brands', res => {
      this.refresh();
    });
  }

  ngOnInit() {
  }

  ionViewWillEnter() {
    this.storage.get("apro_user").then((data) => {
      if (data != null) {
        this.user = data;
        this.refresh();
      } else {
        this.router.navigate(["/login"]);
      }
    });
  }

  refresh() {
    this.brands = [];
    this.page = 0;
    this.hasMore = true;
    this.loadPage();
  }

  loadPage(infiniteScrollEvent?: any) {
    if (this.isLoading || !this.hasMore) {
      infiniteScrollEvent?.target?.complete();
      return;
    }

    this.isLoading = true;
    const requestVersion = ++this.requestVersion;
    const search = encodeURIComponent(this.searchQuery);
    const url = `admin/get_brands?start=${this.page * this.pageSize}&length=${this.pageSize}&draw=${this.page + 1}&search[value]=${search}`;

    this.apiService.getAuthData(url, this.user?.token).subscribe({
      next: (response: any) => {
        if (requestVersion !== this.requestVersion) return;

        const payload = response?.status === 'success' ? response.data : response;
        const rows = Array.isArray(payload?.data)
          ? payload.data.map((brand: any): BrandListItem => {
              const categories = this.resolveCategories(brand);

              return {
                id: Number(brand?.id),
                name: String(brand?.name ?? 'Unnamed brand'),
                parent_name: brand?.parent_name ? String(brand.parent_name) : null,
                categories,
                categoryLabel: categories.join(', ') || 'Uncategorized',
                status: brand?.status === 'Inactive' ? 'Inactive' : 'Active',
              };
            })
          : [];
        this.brands = [...this.brands, ...rows];
        this.hasMore = this.brands.length < Number(payload?.recordsFiltered ?? 0);
        this.page++;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: () => {
        if (requestVersion !== this.requestVersion) return;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
        this.common.presentAlert('Unable to load brands. Please try again.');
      }
    });
  }

  onSearch(event: any) {
    this.searchQuery = event?.detail?.value?.trim() ?? '';
    this.requestVersion++;
    this.isLoading = false;
    this.refresh();
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  private resolveCategories(brand: any): string[] {
    const rawCategories = brand?.categories ?? brand?.category_names ?? brand?.brand_categories ?? brand?.category;
    let parsedCategories = rawCategories;

    if (typeof rawCategories === 'string' && rawCategories.trim().startsWith('[')) {
      try {
        parsedCategories = JSON.parse(rawCategories);
      } catch {
        parsedCategories = rawCategories;
      }
    }

    if (Array.isArray(parsedCategories)) {
      return parsedCategories
        .map((category: any) => category?.name ?? category?.category_name ?? category?.brand_category_name ?? category)
        .filter(Boolean)
        .map((category: any) => String(category).trim())
        .filter(Boolean);
    }

    return typeof parsedCategories === 'string' && parsedCategories.trim()
      ? parsedCategories.split(',').map((category: string) => category.trim()).filter(Boolean)
      : [];
  }

  edit(data: any) {
    this.router.navigate(['/add-brand'], { state: { id: data } });
  }

  async deleteBrand(brand: BrandListItem) {
    const confirmed = await this.common.presentConfirmationAlert(
      'Delete brand?',
      `Delete ${brand.name}? This cannot be undone.`
    );
    if (!confirmed) return;

    this.common.showLoader('Deleting brand...');
    this.apiService.sendAuthData('admin/delete_brand', { id: brand.id }, this.user?.token).subscribe({
      next: (response: any) => {
        this.common.dismissLoading();
        this.common.presentToast(response?.message || 'Brand deleted successfully.');
        this.refresh();
      },
      error: (error: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(error?.error?.message || 'Unable to delete this brand.');
      }
    });
  }
  goToPage(url: any) {
    this.router.navigate([url]);
  }
}
