import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RecapPage } from './recap.page';

describe('RecapPage', () => {
  let component: RecapPage;
  let fixture: ComponentFixture<RecapPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(RecapPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
