import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AmbassadorHomePage } from './ambassador-home.page';

describe('AmbassadorHomePage', () => {
  let component: AmbassadorHomePage;
  let fixture: ComponentFixture<AmbassadorHomePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AmbassadorHomePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
