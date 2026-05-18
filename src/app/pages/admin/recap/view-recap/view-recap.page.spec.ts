import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ViewRecapPage } from './view-recap.page';

describe('ViewRecapPage', () => {
  let component: ViewRecapPage;
  let fixture: ComponentFixture<ViewRecapPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(ViewRecapPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
