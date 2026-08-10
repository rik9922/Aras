import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FormBuilder, FormControl } from '@angular/forms';
import { ApiService } from 'src/app/services/api.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  selector: 'app-list',
  templateUrl: './list.page.html',
  styleUrls: ['./list.page.scss'],
  standalone: false
})
export class ListPage implements OnInit {
  params: any = { title: 'Distributors list', is_back: true };
  user: any;
  searchForm: any;
  searchQuery: string = '';

  distributors: any[] = [];

  private page: number = 0;
  private pageSize: number = 20;
  hasMore: boolean = true;
  isLoading: boolean = false;

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public router: Router,
    public event: EventsService,
    private fb: FormBuilder
  ) {
    this.event.subscribe('reload_distributor', res => {
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
    this.distributors = [];
    this.page = 0;
    this.hasMore = true;
    this.isLoading = true;
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

    // Build params as DataTables-style nested arrays (Laravel expects [value] format)
    const searchVal = this.searchQuery || '';
    const paramsStr = `start=${this.page * this.pageSize}&length=${this.pageSize}&draw=${this.page + 1}&search[value]=${encodeURIComponent(searchVal)}&columns[0][data]=name&columns[1][data]=phone&columns[2][data]=action`;

    const url = `admin/get_distributors?${paramsStr}`;

    this.apiService.getAuthData(url, this.user?.token).subscribe({
      next: (res: any) => {
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

        this.distributors = [...this.distributors, ...rows];
        this.hasMore = rows.length === this.pageSize;
        this.page++;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: (err: any) => {
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

  edit(data: any) {
    this.router.navigate(['/admin-add-distributor'], { state: { id: data } });
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }
}
