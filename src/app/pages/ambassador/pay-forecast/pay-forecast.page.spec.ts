import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PayForecastPage } from './pay-forecast.page';

describe('PayForecastPage', () => {
  let component: PayForecastPage;
  let fixture: ComponentFixture<PayForecastPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(PayForecastPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
