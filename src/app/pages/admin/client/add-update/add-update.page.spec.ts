import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddUpdatePage } from './add-update.page';

describe('AddUpdatePage', () => {
  let component: AddUpdatePage;
  let fixture: ComponentFixture<AddUpdatePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddUpdatePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
