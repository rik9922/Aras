import { ComponentFixture, TestBed } from '@angular/core/testing';
import { DateWiseListPage } from './date-wise-list.page';

describe('DateWiseListPage', () => {
  let component: DateWiseListPage;
  let fixture: ComponentFixture<DateWiseListPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(DateWiseListPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
