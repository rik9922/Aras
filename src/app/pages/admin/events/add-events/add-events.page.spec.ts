import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AddEventsPage } from './add-events.page';

describe('AddEventsPage', () => {
  let component: AddEventsPage;
  let fixture: ComponentFixture<AddEventsPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(AddEventsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('validates one or more comma-separated email addresses', () => {
    const control = component.eventForm.get('booked_by')!;

    control.setValue(' FIRST@example.com, second@example.com ');
    expect(control.valid).toBeTrue();

    control.setValue('first@example.com, not-an-email');
    expect(control.hasError('emailList')).toBeTrue();

    control.setValue('');
    expect(control.hasError('required')).toBeTrue();
  });

  it('normalizes booked-by values to lowercase email arrays', () => {
    const normalize = (component as any).normalizeBookedByEmails.bind(component);

    expect(normalize(' FIRST@example.com; second@example.com ')).toEqual([
      'first@example.com',
      'second@example.com',
    ]);
    expect(normalize('["FIRST@example.com", "second@example.com"]')).toEqual([
      'first@example.com',
      'second@example.com',
    ]);
  });
});
