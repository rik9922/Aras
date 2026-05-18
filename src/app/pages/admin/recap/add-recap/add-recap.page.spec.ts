import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddRecapPage } from './add-recap.page';

describe('AddRecapPage', () => {
  let component: AddRecapPage;
  let fixture: ComponentFixture<AddRecapPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddRecapPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
