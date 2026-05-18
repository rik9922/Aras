import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LocationAccountPage } from './location-account.page';

describe('LocationAccountPage', () => {
  let component: LocationAccountPage;
  let fixture: ComponentFixture<LocationAccountPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(LocationAccountPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
