import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BrandEventsPage } from './brand-events.page';

describe('BrandEventsPage', () => {
  let component: BrandEventsPage;
  let fixture: ComponentFixture<BrandEventsPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(BrandEventsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
