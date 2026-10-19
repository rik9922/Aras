import { EventDetailsPage } from './event-details.page';

describe('EventDetailsPage', () => {
  let component: EventDetailsPage;

  beforeEach(() => {
    component = new EventDetailsPage(
      { getCurrentNavigation: () => null } as any,
      {} as any,
      {} as any,
      {} as any,
      {} as any,
    );
    component.user = { role_id: 5 };
  });

  it('shows the actual completed status for an assigned ambassador event', () => {
    component.eventDetail = { event: { event_status: 1, is_assigned: 1 } };

    expect(component.getStatusLabel(1)).toBe('Completed');
    expect(component.getStatusClass(1)).toBe('status-pill--success');
    expect(component.canShowCheckIn()).toBeFalse();
  });

  it('keeps active assignments labelled Assigned and eligible for check-in', () => {
    component.eventDetail = { event: { event_status: 0, is_assigned: 1 } };

    expect(component.getStatusLabel(0)).toBe('Assigned');
    expect(component.getStatusClass(0)).toBe('status-pill--primary');
    expect(component.canShowCheckIn()).toBeTrue();
  });

  it('maps event status 2 to Cancelled', () => {
    component.eventDetail = { event: { event_status: 2, is_assigned: 1 } };

    expect(component.getStatusLabel(2)).toBe('Cancelled');
    expect(component.getStatusClass(2)).toBe('status-pill--danger');
    expect(component.canShowCheckIn()).toBeFalse();
  });
});
