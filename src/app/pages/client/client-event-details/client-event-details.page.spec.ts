import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ClientEventDetailsPage } from './client-event-details.page';

describe('ClientEventDetailsPage', () => {
  let component: ClientEventDetailsPage;
  let fixture: ComponentFixture<ClientEventDetailsPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(ClientEventDetailsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
