import {
  Component,
  EventEmitter,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  Output,
  SimpleChanges,
} from '@angular/core';
import { Subject, Subscription } from 'rxjs';
import { debounceTime, takeUntil, timeout } from 'rxjs/operators';
import { ApiService } from 'src/app/services/api.service';
import { StorageService } from 'src/app/services/storage.service';

export interface TypeaheadSelection {
  id: any;
  label: string;
  item: any;
}

@Component({
  standalone: false,
  selector: 'app-typeahead-select',
  templateUrl: './typeahead-select.component.html',
  styleUrls: ['./typeahead-select.component.scss'],
})
export class TypeaheadSelectComponent implements OnInit, OnDestroy, OnChanges {
  /** API endpoint relative to auth_url (e.g. 'admin/search_venues') */
  @Input() apiEndpoint: string = '';
  /** Key of the label field in result objects (e.g. 'name', 'brand_name') */
  @Input() labelKey: string = 'name';
  /** Key of the value/id field in result objects */
  @Input() valueKey: string = 'id';
  @Input() placeholder: string = 'Search…';
  @Input() required: boolean = false;
  /** Pre-populate on edit: pass the numeric id */
  @Input() initialValue: any = null;
  /** Pre-populate on edit: pass the display label */
  @Input() initialLabel: string = '';

  @Output() selectionChange = new EventEmitter<TypeaheadSelection | null>();

  searchText: string = '';
  results: any[] = [];
  isOpen: boolean = false;
  isLoading: boolean = false;

  selectedId: any = null;
  selectedLabel: string = '';

  private searchSubject = new Subject<string>();
  private destroy$ = new Subject<void>();
  private token: string = '';
  private requestVersion = 0;
  private request?: Subscription;
  private destroyed = false;

  constructor(private apiService: ApiService, private storage: StorageService) {}

  ngOnInit() {
    this.storage.get('apro_user').then((user: any) => {
      this.token = user?.token || '';
    });

    this.searchSubject
      .pipe(debounceTime(300), takeUntil(this.destroy$))
      .subscribe((query) => this.fetchResults(query));
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['initialValue'] || changes['initialLabel']) {
      if (this.initialValue != null && this.initialValue !== '') {
        this.selectedId = this.initialValue;
        this.selectedLabel = this.initialLabel || String(this.initialValue);
      }
    }
  }

  ngOnDestroy() {
    this.destroyed = true;
    this.requestVersion++;
    this.request?.unsubscribe();
    this.destroy$.next();
    this.destroy$.complete();
  }

  openDropdown() {
    this.isOpen = true;
    this.searchSubject.next(this.searchText);
  }

  onSearchInput(event: any) {
    const q = event?.detail?.value ?? '';
    this.searchText = q;
    this.searchSubject.next(q);
  }

  private async fetchResults(q: string) {
    const version = ++this.requestVersion;
    this.request?.unsubscribe();
    const user = await this.storage.get('apro_user');
    if (this.destroyed || version !== this.requestVersion) return;
    this.token = user?.token || this.token;
    if (!this.apiEndpoint) return;
    this.isLoading = true;
    const endpoint = `${this.apiEndpoint}?q=${encodeURIComponent(q)}&limit=20`;
    this.request = this.apiService.getAuthData(endpoint, this.token).pipe(timeout(15000), takeUntil(this.destroy$)).subscribe(
      (res: any) => {
        if (version !== this.requestVersion) return;
        this.isLoading = false;
        this.results = res?.data ?? [];
        this.isOpen = true;
      },
      () => {
        if (version !== this.requestVersion) return;
        this.isLoading = false;
        this.results = [];
      }
    );
  }

  getLabel(item: any): string {
    return item?.[this.labelKey] != null ? String(item[this.labelKey]) : '';
  }

  select(item: any) {
    this.requestVersion++;
    this.request?.unsubscribe();
    this.isLoading = false;
    this.selectedId = item[this.valueKey];
    this.selectedLabel = this.getLabel(item);
    this.searchText = '';
    this.results = [];
    this.isOpen = false;
    this.selectionChange.emit({ id: this.selectedId, label: this.selectedLabel, item });
  }

  clear() {
    this.requestVersion++;
    this.request?.unsubscribe();
    this.isLoading = false;
    this.selectedId = null;
    this.selectedLabel = '';
    this.searchText = '';
    this.results = [];
    this.isOpen = false;
    this.selectionChange.emit(null);
  }

  closeDropdown() {
    setTimeout(() => {
      this.isOpen = false;
    }, 200);
  }
}
