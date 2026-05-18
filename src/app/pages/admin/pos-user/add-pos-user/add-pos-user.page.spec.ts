import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddPosUserPage } from './add-pos-user.page';

describe('AddPosUserPage', () => {
  let component: AddPosUserPage;
  let fixture: ComponentFixture<AddPosUserPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddPosUserPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
