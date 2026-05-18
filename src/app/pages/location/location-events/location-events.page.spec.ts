import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LocationEventsPage } from './location-events.page';

describe('LocationEventsPage', () => {
  let component: LocationEventsPage;
  let fixture: ComponentFixture<LocationEventsPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(LocationEventsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
