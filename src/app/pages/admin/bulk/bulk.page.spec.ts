import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BulkPage } from './bulk.page';

describe('BulkPage', () => {
  let component: BulkPage;
  let fixture: ComponentFixture<BulkPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(BulkPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
