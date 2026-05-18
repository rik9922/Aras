import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AmbassadorEditRecapPage } from './ambassador-edit-recap.page';

describe('AmbassadorEditRecapPage', () => {
  let component: AmbassadorEditRecapPage;
  let fixture: ComponentFixture<AmbassadorEditRecapPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AmbassadorEditRecapPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
