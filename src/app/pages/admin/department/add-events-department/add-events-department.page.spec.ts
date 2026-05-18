import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddEventsDepartmentPage } from './add-events-department.page';

describe('AddEventsDepartmentPage', () => {
  let component: AddEventsDepartmentPage;
  let fixture: ComponentFixture<AddEventsDepartmentPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddEventsDepartmentPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
