import { Component, EventEmitter, Input, OnChanges, OnDestroy, Output, SimpleChanges, forwardRef } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { Subscription, timeout } from 'rxjs';
import { ApiService } from 'src/app/services/api.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-searchable-select',
  templateUrl: './searchable-select.component.html',
  styleUrls: ['./searchable-select.component.scss'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => SearchableSelectComponent),
      multi: true,
    },
  ],
})
export class SearchableSelectComponent implements ControlValueAccessor, OnChanges, OnDestroy {
  @Input() items: any[] = [];
  @Input() bindLabel = 'name';
  @Input() bindValue = 'id';
  @Input() placeholder = 'Select';
  @Input() fieldLabel = '';
  @Input() labelFn?: (item: any) => string;
  @Input() apiEndpoint = '';
  @Output() ionChange = new EventEmitter<any>();

  value: any = null;
  disabled = false;
  isOpen = false;
  searchText = '';
  filteredItems: any[] = [];
  isLoading = false;
  selectedItem: any = null;

  private onChange: (value: any) => void = () => {};
  private onTouched: () => void = () => {};
  private searchTimer: any;
  private token = '';
  private requestVersion = 0;
  private request?: Subscription;
  private destroyed = false;

  constructor(private apiService: ApiService, private storage: StorageService) {
    this.storage.get('apro_user').then((user: any) => {
      this.token = user?.token || '';
    });
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['items']) {
      if (this.isOpen) {
        this.filterList();
      } else if (!this.searchText.trim()) {
        this.filteredItems = [...(this.items || [])];
      }
    }
  }

  writeValue(value: any): void {
    this.value = value ?? null;
  }

  registerOnChange(fn: (value: any) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled = isDisabled;
  }

  getLabel(item: any): string {
    if (!item) {
      return '';
    }
    if (this.labelFn) {
      return this.labelFn(item);
    }
    const label = item[this.bindLabel];
    return label != null ? String(label) : '';
  }

  get selectedLabel(): string {
    if (this.selectedItem && String(this.selectedItem?.[this.bindValue]) === String(this.value)) {
      return this.getLabel(this.selectedItem);
    }
    const selected = this.findItem(this.value);
    return selected ? this.getLabel(selected) : '';
  }

  open(): void {
    if (this.disabled) {
      return;
    }
    this.searchText = '';
    this.filteredItems = this.apiEndpoint ? [] : [...(this.items || [])];
    this.isOpen = true;
    if (this.apiEndpoint) this.fetchRemoteOptions('');
    this.onTouched();
  }

  close(): void {
    clearTimeout(this.searchTimer);
    this.requestVersion++;
    this.request?.unsubscribe();
    this.isLoading = false;
    this.isOpen = false;
  }

  filterList(): void {
    const query = this.searchText.trim().toLowerCase();
    if (this.apiEndpoint) {
      clearTimeout(this.searchTimer);
      this.searchTimer = setTimeout(() => this.fetchRemoteOptions(query), 300);
      return;
    }

    if (!query) {
      this.filteredItems = [...(this.items || [])];
      return;
    }
    this.filteredItems = (this.items || []).filter((item) => {
      const label = this.getLabel(item).toLowerCase();
      const idText = String(item?.[this.bindValue] ?? '').toLowerCase();
      return label.includes(query) || idText.includes(query);
    });
  }

  select(item: any): void {
    this.value = item?.[this.bindValue] ?? null;
    this.selectedItem = item;
    this.onChange(this.value);
    this.ionChange.emit(this.value);
    this.close();
  }

  clear(): void {
    this.value = null;
    this.selectedItem = null;
    this.onChange(null);
    this.ionChange.emit(null);
    this.close();
  }

  isSelected(item: any): boolean {
    return String(item?.[this.bindValue]) === String(this.value);
  }

  private findItem(value: any): any {
    return (this.items || []).find((item) => String(item?.[this.bindValue]) === String(value));
  }

  ngOnDestroy(): void {
    this.destroyed = true;
    this.close();
  }

  private async fetchRemoteOptions(query: string): Promise<void> {
    if (!this.apiEndpoint) {
      return;
    }
    const version = ++this.requestVersion;
    this.request?.unsubscribe();
    const user = await this.storage.get('apro_user');
    if (this.destroyed || version !== this.requestVersion) return;
    this.token = user?.token || this.token;
    this.isLoading = true;
    const separator = this.apiEndpoint.includes('?') ? '&' : '?';
    const endpoint = `${this.apiEndpoint}${separator}q=${encodeURIComponent(query)}&limit=20`;
    this.request = this.apiService.getAuthData(endpoint, this.token).pipe(timeout(15000)).subscribe(
      (res: any) => {
        if (version !== this.requestVersion) return;
        this.filteredItems = res?.data ?? [];
        this.isLoading = false;
      },
      () => {
        if (version !== this.requestVersion) return;
        this.filteredItems = [];
        this.isLoading = false;
      }
    );
  }
}
