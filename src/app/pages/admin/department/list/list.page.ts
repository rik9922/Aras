import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FormBuilder, FormControl } from '@angular/forms';
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
  hasMore: boolean = true;
  isLoading: boolean = false;

  constructor(
    public storage: StorageService,
    public apiService: ApiService,
    public common: CommonService,
    public router: Router,
    public event: EventsService,
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
    });
  }

  refresh() {
    this.departments = [];
    this.page = 0;
    this.hasMore = true;
    this.isLoading = true;
    this.loadPage();
  }

  loadPage(infiniteScrollEvent?: any) {
    // Only block infinite scroll triggers, not initial load
    if (this.isLoading && infiniteScrollEvent) {
      infiniteScrollEvent.target?.complete();
      return;
    }
    if (!this.hasMore && infiniteScrollEvent) {
      infiniteScrollEvent.target?.complete();
      return;
    }
    
    this.isLoading = true;

    const params = new URLSearchParams();
    params.set('start', String(this.page * this.pageSize));
    params.set('length', String(this.pageSize));
    params.set('draw', String(this.page + 1));

    if (this.searchQuery) {
      params.set('search', this.searchQuery);
    }

    const url = `admin/get_event_department?${params.toString()}`;

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
        
        this.departments = [...this.departments, ...rows];
        this.hasMore = rows.length === this.pageSize;
        this.page++;
        this.isLoading = false;
        infiniteScrollEvent?.target?.complete();
      },
      error: (err: any) => {
        console.error("Error fetching departments", err);
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
    this.router.navigate(['/add-events-department'], { state: { id: data } });
  }

  goToPage(url: any) {
    this.router.navigate([url]);
  }
}
