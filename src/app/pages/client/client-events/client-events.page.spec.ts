import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ClientEventsPage } from './client-events.page';

describe('ClientEventsPage', () => {
  let component: ClientEventsPage;
  let fixture: ComponentFixture<ClientEventsPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(ClientEventsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
