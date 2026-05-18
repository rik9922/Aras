import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AdminPayForecastPage } from './admin-pay-forecast.page';

describe('AdminPayForecastPage', () => {
  let component: AdminPayForecastPage;
  let fixture: ComponentFixture<AdminPayForecastPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AdminPayForecastPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
