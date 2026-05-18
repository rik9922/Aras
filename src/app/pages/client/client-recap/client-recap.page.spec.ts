import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ClientRecapPage } from './client-recap.page';

describe('ClientRecapPage', () => {
  let component: ClientRecapPage;
  let fixture: ComponentFixture<ClientRecapPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(ClientRecapPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
