import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';

interface BrandCategoryItem {
  id: number;
  name: string;
}

@Component({
  standalone:false,
  selector: 'app-category',
  templateUrl: './category.page.html',
  styleUrls: ['./category.page.scss'],
})
export class CategoryPage implements OnInit {
  params: any = { title: "Brand Categories",'is_back':true };
  user:any;
  categories: BrandCategoryItem[] = [];
  isLoading = false;
  hasMore = true;
  totalCategories = 0;
  isModelOpen: boolean = false;
  categoryForm!:FormGroup;
  heading:any='Add ';
  private page = 0;
  private readonly pageSize = 25;
  private requestVersion = 0;
  constructor(
    public storage : StorageService,
    public apiService : ApiService,
    public common : CommonService,
    public router : Router,
    public formBuilder : FormBuilder
  ) {
    this.categoryForm = this.formBuilder.group({
      id: new FormControl(""),
      name: new FormControl("", Validators.compose([Validators.required,])),
    });
   }

  ngOnInit() {
  }

  ionViewWillEnter(){
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
    this.categories = [];
    this.page = 0;
    this.hasMore = true;
    this.totalCategories = 0;
    this.loadPage();
  }

  loadPage(infiniteScrollEvent?: any) {
    if (this.isLoading || !this.hasMore) {
      infiniteScrollEvent?.target?.complete();
      return;
    }

    this.isLoading = true;
    const requestVersion = ++this.requestVersion;
    const url = `admin/get_brand_categories?start=${this.page * this.pageSize}&length=${this.pageSize}&draw=${this.page + 1}`;

    this.apiService.getAuthData(url, this.user?.token).subscribe({
      next: (response: any) => {
        if (requestVersion !== this.requestVersion) return;

        const payload = response?.status === 'success' ? response.data : response;
        const rows = Array.isArray(payload?.data)
          ? payload.data.map((category: any): BrandCategoryItem => ({
              id: Number(category?.id),
              name: String(category?.name ?? 'Unnamed category'),
            }))
          : [];
        const totalRecords = Number(payload?.recordsTotal ?? 0);
        const totalFiltered = Number(payload?.recordsFiltered ?? totalRecords);

        this.categories = [...this.categories, ...rows];
        this.totalCategories = totalRecords;
        this.hasMore = rows.length > 0 && this.categories.length < totalFiltered;
        this.page++;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: () => {
        if (requestVersion !== this.requestVersion) return;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
        this.common.presentAlert('Unable to load brand categories. Please try again.');
      }
    });
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  edit(category: BrandCategoryItem) {
    this.heading='Update ';
    this.isModelOpen = true;
    this.categoryForm.patchValue(category);
  }

  async deleteCategory(category: BrandCategoryItem) {
    const confirmed = await this.common.presentConfirmationAlert(
      'Delete category?',
      `Delete ${category.name}? This cannot be undone.`
    );
    if (!confirmed) return;

    this.common.showLoader('Deleting category...');
    this.apiService.sendAuthData('admin/delete_brand_category', { id: category.id }, this.user?.token).subscribe({
      next: (response: any) => {
        this.common.dismissLoading();
        this.common.presentToast(response?.message || 'Brand category deleted successfully.');
        this.refresh();
      },
      error: (error: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(error?.error?.message || 'Unable to delete this category.');
      }
      });
  }

  model(type: any) {
    this.heading='Add ';
    this.isModelOpen = type
    this.categoryForm.reset()
  }

  submit(formvalue: any) {
    this.common.showLoader();
    this.apiService.sendAuthData('admin/save_brand_category', formvalue, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading()
      this.model(false);
      this.refresh()
      this.common.presentAlert(res?.message)
    }, err => {
      this.common.dismissLoading()
      this.common.presentAlert(err?.error?.message);
      console.log(err)
    })
  }
}
  
