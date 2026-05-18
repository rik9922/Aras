import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddEventsPage } from './add-events.page';

describe('AddEventsPage', () => {
  let component: AddEventsPage;
  let fixture: ComponentFixture<AddEventsPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddEventsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
