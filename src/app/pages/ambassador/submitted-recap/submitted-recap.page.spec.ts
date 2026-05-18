import { ComponentFixture, TestBed } from '@angular/core/testing';
import { SubmittedRecapPage } from './submitted-recap.page';

describe('SubmittedRecapPage', () => {
  let component: SubmittedRecapPage;
  let fixture: ComponentFixture<SubmittedRecapPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(SubmittedRecapPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
