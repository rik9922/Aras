import { CommonModule } from '@angular/common';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ComponentFixture, TestBed, fakeAsync, flushMicrotasks, tick } from '@angular/core/testing';
import { Subject } from 'rxjs';
import { ApiService } from 'src/app/services/api.service';
import { StorageService } from 'src/app/services/storage.service';
import { TypeaheadSelectComponent } from './typeahead-select.component';

describe('Event form venue picker', () => {
  let fixture: ComponentFixture<TypeaheadSelectComponent>;
  let component: TypeaheadSelectComponent;
  let api: jasmine.SpyObj<ApiService>;
  let responses: Subject<any>[];

  beforeEach(async () => {
    responses = [];
    api = jasmine.createSpyObj('ApiService', ['getAuthData']);
    api.getAuthData.and.callFake(() => {
      const response = new Subject<any>();
      responses.push(response);
      return response.asObservable();
    });
    await TestBed.configureTestingModule({
      declarations: [TypeaheadSelectComponent], imports: [CommonModule], schemas: [NO_ERRORS_SCHEMA],
      providers: [{ provide: ApiService, useValue: api }, { provide: StorageService, useValue: { get: () => Promise.resolve({ token: 'test-token' }) } }],
    }).compileComponents();
    fixture = TestBed.createComponent(TypeaheadSelectComponent);
    component = fixture.componentInstance;
    component.apiEndpoint = 'admin/search_venues';
    fixture.detectChanges();
  });

  it('loads choices on focus and selects a result using a touch-generated click', fakeAsync(() => {
    flushMicrotasks();
    component.openDropdown(); tick(300); flushMicrotasks();
    expect(api.getAuthData).toHaveBeenCalledWith('admin/search_venues?q=&limit=20', 'test-token');
    responses[0].next({ data: [{ id: 7, name: 'New Venue' }] }); responses[0].complete();
    fixture.detectChanges();
    const selected = spyOn(component.selectionChange, 'emit');
    fixture.nativeElement.querySelector('.typeahead-option').click();
    expect(selected).toHaveBeenCalledWith({ id: 7, label: 'New Venue', item: { id: 7, name: 'New Venue' } });
    expect(component.isOpen).toBeFalse();
  }));

  it('ignores stale search results and releases the spinner when a request times out', fakeAsync(() => {
    flushMicrotasks();
    component.onSearchInput({ detail: { value: 'old' } }); tick(300); flushMicrotasks();
    component.onSearchInput({ detail: { value: 'new' } }); tick(300); flushMicrotasks();
    responses[0].next({ data: [{ id: 1, name: 'Old Venue' }] });
    expect(component.results).toEqual([]);
    tick(15001);
    expect(component.isLoading).toBeFalse();
    expect(component.results).toEqual([]);
  }));

  it('reloads options after a selection is cleared', fakeAsync(() => {
    flushMicrotasks();
    component.openDropdown(); tick(300); flushMicrotasks();
    responses[0].next({ data: [{ id: 1, name: 'Venue' }] }); responses[0].complete();
    component.select({ id: 1, name: 'Venue' }); component.clear(); component.openDropdown(); tick(300); flushMicrotasks();
    expect(api.getAuthData).toHaveBeenCalledTimes(2);
    responses[1].complete();
  }));
});
