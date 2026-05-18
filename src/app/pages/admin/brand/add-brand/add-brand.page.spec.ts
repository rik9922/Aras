import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddBrandPage } from './add-brand.page';

describe('AddBrandPage', () => {
  let component: AddBrandPage;
  let fixture: ComponentFixture<AddBrandPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddBrandPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
