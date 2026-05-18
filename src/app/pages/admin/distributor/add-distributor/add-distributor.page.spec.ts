import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddDistributorPage } from './add-distributor.page';

describe('AddDistributorPage', () => {
  let component: AddDistributorPage;
  let fixture: ComponentFixture<AddDistributorPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddDistributorPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
