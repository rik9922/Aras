import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LocationEventDetailsPage } from './location-event-details.page';

describe('LocationEventDetailsPage', () => {
  let component: LocationEventDetailsPage;
  let fixture: ComponentFixture<LocationEventDetailsPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(LocationEventDetailsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
