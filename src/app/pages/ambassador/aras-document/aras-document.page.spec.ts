import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ArasDocumentPage } from './aras-document.page';

describe('ArasDocumentPage', () => {
  let component: ArasDocumentPage;
  let fixture: ComponentFixture<ArasDocumentPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(ArasDocumentPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
