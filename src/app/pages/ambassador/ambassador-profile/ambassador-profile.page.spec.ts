import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AmbassadorProfilePage } from './ambassador-profile.page';

describe('AmbassadorProfilePage', () => {
  let component: AmbassadorProfilePage;
  let fixture: ComponentFixture<AmbassadorProfilePage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AmbassadorProfilePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
