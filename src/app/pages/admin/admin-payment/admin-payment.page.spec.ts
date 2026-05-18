import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AdminPaymentPage } from './admin-payment.page';

describe('AdminPaymentPage', () => {
  let component: AdminPaymentPage;
  let fixture: ComponentFixture<AdminPaymentPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AdminPaymentPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
