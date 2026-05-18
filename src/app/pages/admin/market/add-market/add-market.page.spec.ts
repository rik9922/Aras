import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddMarketPage } from './add-market.page';

describe('AddMarketPage', () => {
  let component: AddMarketPage;
  let fixture: ComponentFixture<AddMarketPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddMarketPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
