import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddVenuePage } from './add-venue.page';

describe('AddVenuePage', () => {
  let component: AddVenuePage;
  let fixture: ComponentFixture<AddVenuePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddVenuePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
