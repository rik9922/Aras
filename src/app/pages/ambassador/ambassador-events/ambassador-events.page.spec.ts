import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AmbassadorEventsPage } from './ambassador-events.page';

describe('AmbassadorEventsPage', () => {
  let component: AmbassadorEventsPage;
  let fixture: ComponentFixture<AmbassadorEventsPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AmbassadorEventsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
