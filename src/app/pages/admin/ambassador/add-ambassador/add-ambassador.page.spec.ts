import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddAmbassadorPage } from './add-ambassador.page';

describe('AddAmbassadorPage', () => {
  let component: AddAmbassadorPage;
  let fixture: ComponentFixture<AddAmbassadorPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddAmbassadorPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
