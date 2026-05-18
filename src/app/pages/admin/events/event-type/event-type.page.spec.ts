import { ComponentFixture, TestBed } from '@angular/core/testing';
import { EventTypePage } from './event-type.page';

describe('EventTypePage', () => {
  let component: EventTypePage;
  let fixture: ComponentFixture<EventTypePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(EventTypePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
