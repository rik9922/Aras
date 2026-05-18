import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BrandHomePage } from './brand-home.page';

describe('BrandHomePage', () => {
  let component: BrandHomePage;
  let fixture: ComponentFixture<BrandHomePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(BrandHomePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
