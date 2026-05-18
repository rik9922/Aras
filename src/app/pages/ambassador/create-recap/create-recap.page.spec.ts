import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CreateRecapPage } from './create-recap.page';

describe('CreateRecapPage', () => {
  let component: CreateRecapPage;
  let fixture: ComponentFixture<CreateRecapPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(CreateRecapPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
