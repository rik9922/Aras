import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddUserLocationPage } from './add-user-location.page';

describe('AddUserLocationPage', () => {
  let component: AddUserLocationPage;
  let fixture: ComponentFixture<AddUserLocationPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddUserLocationPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
