import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FormBuilder, FormControl } from '@angular/forms';
import { AlertController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
})
export class ListPage implements OnInit {
  params: any = { title: "Event Department", is_back: true };
  user: any;
  searchForm: any;
  searchQuery: string = '';

  departments: any[] = [];

  private page: number = 0;
  private pageSize: number = 20;
  private loadToken: number = 0;
  hasMore: boolean = true;
  isLoading: boolean = false;

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public event: EventsService,
    private alertCtrl: AlertController,
    private fb: FormBuilder
  ) {
    this.event.subscribe('reload_departments', res => {
      this.refresh();
    });
  }

  ngOnInit() {
    this.searchForm = this.fb.group({
      search: new FormControl('')
    });
  }

  ionViewWillEnter() {
    this.storage.get("apro_user").then((data) => {
      if (data != null) {
        this.user = data;
        this.refresh();
      } else {
        this.router.navigate(["/login"]);
      }
    }).catch((err) => {
      console.error("Storage error:", err);
    });
  }

  refresh() {
    this.departments = [];
    this.page = 0;
    this.hasMore = true;
    this.loadToken++;
    this.loadPage();
  }

  loadPage(infiniteScrollEvent?: any) {
    if (this.isLoading && infiniteScrollEvent) {
      infiniteScrollEvent.target?.complete();
      return;
    }
    if (!this.hasMore && infiniteScrollEvent) {
      infiniteScrollEvent.target?.complete();
      return;
    }
    
    this.isLoading = true;
    const requestToken = this.loadToken;
    const requestPage = this.page;

    // Build params as DataTables-style nested arrays (Laravel expects [value] format)
    const searchVal = this.searchQuery || '';
    const paramsStr = `start=${requestPage * this.pageSize}&length=${this.pageSize}&draw=${requestPage + 1}&search[value]=${encodeURIComponent(searchVal)}&columns[0][data]=name&columns[1][data]=description&columns[2][data]=action`;

    const url = `admin/get_event_department?${paramsStr}`;

    this.apiService.getAuthData(url, this.user?.token).subscribe({
      next: (res: any) => {
        if (requestToken !== this.loadToken) {
          infiniteScrollEvent?.target?.complete();
          return;
        }

        let rows: any[] = [];
        
        if (res?.data?.data && Array.isArray(res.data.data)) {
          rows = res.data.data;
        }
        else if (Array.isArray(res?.data)) {
          rows = res.data;
        }
        else if (Array.isArray(res)) {
          rows = res;
        }
        
        this.departments = requestPage === 0 ? rows : [...this.departments, ...rows];
        this.hasMore = rows.length === this.pageSize;
        this.page = requestPage + 1;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: (err: any) => {
        if (requestToken !== this.loadToken) {
          infiniteScrollEvent?.target?.complete();
          return;
        }

        console.error("API error:", err);
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
      }
    });
  }

  onInfiniteScroll(event: any) {
    this.loadPage(event);
  }

  onSearch(event: any) {
    const val = event?.detail?.value?.trim() ?? '';
    this.searchQuery = val;
    this.refresh();
  }

  edit(department: any) {
    const id = this.departmentId(department);
    if (!id) {
      return;
    }

    this.router.navigate(['/update-events-department'], {
      state: { id, department }
    });
  }

  private departmentId(department: any): number | null {
    if (typeof department === 'number') {
      return department;
    }

    if (typeof department === 'string' && /^\d+$/.test(department)) {
      return Number(department);
    }

    if (department?.id) {
      return Number(department.id);
    }

    const actionId = String(department?.action ?? '').match(/id="(\d+)"/)?.[1];
    return actionId ? Number(actionId) : null;
  }

  async confirmDelete(department: any) {
    const id = this.departmentId(department);
    if (!id) {
      return;
    }

    const name = department?.name ? String(department.name) : 'this department';
    const alert = await this.alertCtrl.create({
      header: 'Delete Department',
      message: `Are you sure you want to delete ${name}?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Delete',
          role: 'destructive',
          cssClass: 'alert-delete-btn',
          handler: () => this.deleteDepartment(id),
        },
      ],
    });
    await alert.present();
  }

  private deleteDepartment(id: number) {
    this.common.showLoader();
    this.apiService.sendAuthData('admin/delete_department', { id }, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        this.departments = this.departments.filter(department => this.departmentId(department) !== id);
        this.common.presentToast(res?.message ?? 'Department deleted successfully.');
      },
      error: (err: any) => {
        this.common.dismissLoading();
        const message = err?.error?.message ?? 'Unable to delete department.';
        if (err?.status === 422) {
          this.common.presentAlert(message);
          return;
        }
        this.common.presentToast(message);
      }
    });
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }
}
