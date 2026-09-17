import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { IonicModule } from '@ionic/angular';
import { of } from 'rxjs';
import { AddEventsPage } from './add-events.page';

describe('AddEventsPage', () => {
  let component: AddEventsPage;
  let fixture: ComponentFixture<AddEventsPage>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [
        CommonModule,
        FormsModule,
        IonicModule.forRoot(),
        ReactiveFormsModule,
      ],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
      ],
      schemas: [NO_ERRORS_SCHEMA],
    });

    fixture = TestBed.createComponent(AddEventsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('does not require an event name so the API can auto-fill it', () => {
    const control = component.eventForm.get('event_name')!;

    control.setValue('');

    expect(control.hasError('required')).toBeFalse();
  });

  it('prefills the generated event name for new events', () => {
    (component as any).prefillEventNameForCreate({
      auto_event_name: 'event-123',
      event_name: 'event-123',
    });

    expect(component.eventForm.value.auto_event_name).toBe('event-123');
    expect(component.eventForm.value.event_name).toBe('event-123');
  });

  it('defaults new events to pending status', () => {
    expect(component.eventForm.value.event_status).toBe('0');
  });

  it('prefills edit venue and checkbox values from the event details response', () => {
    spyOn(component.common, 'showLoader');
    spyOn(component.common, 'dismissLoading');
    spyOn(component.apiService, 'getAuthData').and.callFake((endpoint: string) => {
      if (endpoint.startsWith('admin/event_billing_parties')) {
        return of({ data: [] });
      }
      return of({
        data: {
          event: {
            id: 42,
            event_name: 'Existing Event',
            event_location_id: '12',
            event_location_label: 'Main Street Venue',
            event_address: '123 Main St',
            latitude: '40.1',
            longitude: '-73.2',
            timezone: 'America/New_York',
            timezone_label: 'Eastern Time',
            city: 'New York',
            client_id: '7',
            department_id: '2',
            event_type_id: '3',
            manager_id: '6',
            distributor_id: '4',
            distributor: { id: 4, name: 'Distributor' },
            brand_id: '5',
            brand: { id: 5, brand_name: 'Brand' },
            market_id: '8',
            markets: [{ id: 8, name: 'Market' }],
            position: 1,
            start_time_raw: '2026-09-18 09:55:00',
            end_time_raw: '2026-09-18 10:55:00',
            booked_date: '2026-09-17',
            booked_by: '["admin@example.com"]',
            payment_type: 'Check',
            uniform: 'Black shirt',
            region: 'Southeast',
            age_restricted: 1,
            apply_event: '1',
            recap_needed: '0',
            receipt: '1',
            kit: '1',
            pinata_pay: 0,
            payment_status: '0',
            event_status: 0,
          },
        },
      });
    });
    component.formdata = { id: 42 };
    component.user = { token: 'token' };

    component.getSingleEventData();

    expect(component.venueInitialValue).toBe(12);
    expect(component.venueInitialLabel).toBe('Main Street Venue');
    expect(component.distributorInitialLabel).toBe('Distributor');
    expect(component.brandInitialLabel).toBe('Brand');
    expect(component.marketInitialLabel).toBe('Market');
    expect(component.eventForm.value.venue_name).toBe(12);
    expect(component.eventForm.value.age_restricted).toBeTrue();
    expect(component.eventForm.value.apply_event).toBeTrue();
    expect(component.eventForm.value.recap_needed).toBeFalse();
    expect(component.eventForm.value.receipt).toBeTrue();
    expect(component.eventForm.value.kit).toBeTrue();
    expect(component.eventForm.value.event_status).toBe(0);
    expect(component.is_kit_requied).toBeTrue();
    expect(component.eventForm.valid).toBeTrue();
  });

  it('keeps the update button enabled in edit mode so validation can run on submit', () => {
    component.formdata = { id: 42 };
    component.eventForm.patchValue({
      venue_name: null,
      department_id: null,
      distributor_id: null,
      brand_id: null,
      market_id: null,
      manager_id: null,
      payment_type: null,
      uniform: '',
      client_id: '',
      region: '',
      booked_by: '',
    });

    expect(component.eventForm.invalid).toBeTrue();
    expect(component.isSubmitDisabled()).toBeFalse();
  });

  it('keeps save disabled for invalid create forms', () => {
    component.formdata = null;
    component.eventForm.patchValue({ venue_name: null });

    expect(component.eventForm.invalid).toBeTrue();
    expect(component.isSubmitDisabled()).toBeTrue();
  });

  it('stores selected attachments until submit rebuilds FormData', () => {
    const file = new File(['hello'], 'test.txt', { type: 'text/plain' });
    spyOn(URL, 'createObjectURL').and.returnValue('blob:test');

    component.onChangeFile({ target: { files: [file] } });

    expect((component as any).selectedAttachmentFiles).toEqual([file]);
    expect(component.attachedImage).toBe('blob:test');
  });

  it('appends arrays and maps to FormData using Laravel field names', () => {
    const formData = new FormData();

    (component as any).appendToFormData(formData, 'billing_party_ids', [7, 9]);
    (component as any).appendToFormData(formData, 'labor_rates', { 7: '25', 9: '30' });

    expect(formData.getAll('billing_party_ids[]')).toEqual(['7', '9']);
    expect(formData.get('labor_rates[7]')).toBe('25');
    expect(formData.get('labor_rates[9]')).toBe('30');
  });

  it('keeps selected datetime values as event-local wall time', () => {
    component.setDateOnChange({ detail: { value: '2026-09-18T01:07:00-04:00' } }, 'start_time');

    expect(component.eventForm.value.start_time).toBe('2026-09-18T01:07:00');
    expect((component as any).formatDateTimeForPayload(component.eventForm.value.start_time)).toBe('2026-09-18 01:07');
  });

  it('defaults start and end from now in the selected event timezone', () => {
    jasmine.clock().install();
    try {
      jasmine.clock().mockDate(new Date('2026-09-17T13:54:10Z'));
      component.eventForm.patchValue({ timezone: 'America/New_York' });
      (component as any).timeDefaultsCanFollowTimezone = true;

      (component as any).initializeDefaultEventTimes('America/New_York');

      expect(component.eventForm.value.start_time).toBe('2026-09-17T09:55:00');
      expect(component.eventForm.value.end_time).toBe('2026-09-17T10:55:00');
    } finally {
      jasmine.clock().uninstall();
    }
  });

  it('corrects end time to one hour after start when needed', () => {
    spyOn(component.common, 'presentAlert');
    component.eventForm.patchValue({ start_time: '2026-09-17T10:15:00' });

    component.setDateOnChange({ detail: { value: '2026-09-17T10:00:00' } }, 'end_time');

    expect(component.eventForm.value.end_time).toBe('2026-09-17T11:15:00');
  });

  it('allows 03:00 AM to 04:00 AM in the selected timezone', () => {
    const alertSpy = spyOn(component.common, 'presentAlert');
    component.eventForm.patchValue({ timezone: 'America/New_York' });

    component.setDateOnChange({ detail: { value: '2026-09-18T03:00:00' } }, 'start_time');
    component.setDateOnChange({ detail: { value: '2026-09-18T04:00:00' } }, 'end_time');

    expect(component.eventForm.value.start_time).toBe('2026-09-18T03:00:00');
    expect(component.eventForm.value.end_time).toBe('2026-09-18T04:00:00');
    expect(alertSpy).not.toHaveBeenCalledWith('End time must be at least 1 hour after start time.');
  });

  it('sets end time exactly one hour after start when start changes', () => {
    component.eventForm.patchValue({
      timezone: 'America/New_York',
      end_time: '2026-09-18T08:00:00',
    });

    component.setDateOnChange({ detail: { value: '2026-09-18T02:00:00' } }, 'start_time');

    expect(component.eventForm.value.start_time).toBe('2026-09-18T02:00:00');
    expect(component.eventForm.value.end_time).toBe('2026-09-18T03:00:00');
  });

  it('allows an end time exactly one visible hour after start even when picker seconds differ', () => {
    const alertSpy = spyOn(component.common, 'presentAlert');
    component.setDateOnChange({ detail: { value: '2026-09-18T02:55:42' } }, 'start_time');

    component.setDateOnChange({ detail: { value: '2026-09-18T03:55:00' } }, 'end_time');

    expect(component.eventForm.value.start_time).toBe('2026-09-18T02:55:00');
    expect(component.eventForm.value.end_time).toBe('2026-09-18T03:55:00');
    expect(alertSpy).not.toHaveBeenCalledWith('End time must be at least 1 hour after start time.');
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
