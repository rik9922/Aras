import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PayForecastDetailPage } from './pay-forecast-detail.page';

describe('PayForecastDetailPage', () => {
  let component: PayForecastDetailPage;
  let fixture: ComponentFixture<PayForecastDetailPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(PayForecastDetailPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
