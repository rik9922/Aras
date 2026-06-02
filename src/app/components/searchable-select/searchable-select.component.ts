import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges, forwardRef } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';

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
export class SearchableSelectComponent implements ControlValueAccessor, OnChanges {
  @Input() items: any[] = [];
  @Input() bindLabel = 'name';
  @Input() bindValue = 'id';
  @Input() placeholder = 'Select';
  @Input() fieldLabel = '';
  @Input() labelFn?: (item: any) => string;
  @Output() ionChange = new EventEmitter<any>();

  value: any = null;
  disabled = false;
  isOpen = false;
  searchText = '';
  filteredItems: any[] = [];

  private onChange: (value: any) => void = () => {};
  private onTouched: () => void = () => {};

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
    const selected = this.findItem(this.value);
    return selected ? this.getLabel(selected) : '';
  }

  open(): void {
    if (this.disabled) {
      return;
    }
    this.searchText = '';
    this.filteredItems = [...(this.items || [])];
    this.isOpen = true;
    this.onTouched();
  }

  close(): void {
    this.isOpen = false;
  }

  filterList(): void {
    const query = this.searchText.trim().toLowerCase();
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
    this.onChange(this.value);
    this.ionChange.emit(this.value);
    this.close();
  }

  clear(): void {
    this.value = null;
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
}
