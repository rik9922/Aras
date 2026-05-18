import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LocationHomePage } from './location-home.page';

describe('LocationHomePage', () => {
  let component: LocationHomePage;
  let fixture: ComponentFixture<LocationHomePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(LocationHomePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
