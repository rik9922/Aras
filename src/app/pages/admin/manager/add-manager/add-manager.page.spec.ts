import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddManagerPage } from './add-manager.page';

describe('AddManagerPage', () => {
  let component: AddManagerPage;
  let fixture: ComponentFixture<AddManagerPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddManagerPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
