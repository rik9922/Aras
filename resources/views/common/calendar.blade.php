@extends('layout.app', ['page_name' => 'Events'])
<style>
    .fc .fc-daygrid-day-number {
        font-size: var(--font-size-section-title) !important;
        text-decoration: none !important;
        font-weight: bold;
    }
</style>
@section('content')
    <div class="container py-5">
        @include('ambassador.recap.partials.quickbooks-requirements-card')
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div id="calendar"></div>

                <div class="modal fade" id="eventListModal" tabindex="-1" aria-labelledby="eventListModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="eventListModalLabel">Events for Selected Date</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="eventListContainer">
                                <p class="text-muted">Select an event to view details.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('common.install')
@endsection

<link rel="stylesheet" href="{{ asset('assets/css/fullcalendar.main.min.css') }}">
<script src="{{ asset('assets/js/fullcalendar.main.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userRole = "{{ $role }}";
        let showEventBaseUrl = "";
        let editEventBaseUrl = "";
        let viewEventBaseUrl = "";
        switch (userRole) {
            case '5':
                showEventBaseUrl = "{{ route('ambassador.show-event', ['id' => '__EVENT_ID__']) }}";
                viewEventBaseUrl = showEventBaseUrl;
                break;
            case '1':
                showEventBaseUrl = "{{ route('admin.edit.event', ['id' => '__EVENT_ID__']) }}";
                editEventBaseUrl = showEventBaseUrl;
                viewEventBaseUrl = "{{ route('admin.show-event', ['id' => '__EVENT_ID__']) }}";
                break;
            case '4':
                showEventBaseUrl = "{{ route('location.show-event', ['id' => '__EVENT_ID__']) }}";
                viewEventBaseUrl = showEventBaseUrl;
                break;
            case '3':
                showEventBaseUrl = "{{ route('client.show-event', ['id' => '__EVENT_ID__']) }}";
                viewEventBaseUrl = showEventBaseUrl;
                break;
            case '6':
                showEventBaseUrl = "{{ route('manager.show-event', ['id' => '__EVENT_ID__']) }}";
                viewEventBaseUrl = showEventBaseUrl;
                editEventBaseUrl = "{{ route('manager.edit.event', ['id' => '__EVENT_ID__']) }}";
                break;
            default:
                showEventBaseUrl = "{{ route('client.show-event', ['id' => '__EVENT_ID__']) }}" || "{{ route('ambassador.show-event', ['id' => '__EVENT_ID__']) }}";
                viewEventBaseUrl = showEventBaseUrl;
        }

        const calendarEl = document.getElementById('calendar');
        const allEvents = @json($events).map(event => ({
            id: event.id,
            title: event.event_name || (event.brand ? event.brand.brand_name : 'Untitled Event'),
            start: event.start || event.start_time,
            end: event.end || event.end_time,
            timeZone: event.timeZone || event.timezone || 'UTC',
            timeDisplay: event.time_display,
            startDisplay: event.start_display,
            endDisplay: event.end_display,
            timezoneLabel: event.timezone_label,
            positions: event.ambassador_id ? String(event.ambassador_id).split(',').length : 0,
            kit: event.kit ? 'Yes' : 'No',
            ageRestricted: event.age_restricted ? 'Yes' : 'No',
            eventStatus: event.event_status,
            backgroundColor: '#4a90e2',
            borderColor: '#357abd',
            textColor: '#ffffff'
        }));

        const groupedByDate = {};
        const countableEvents = ['3', '5'].includes(userRole)
            ? allEvents.filter(event => String(event.eventStatus) === '0')
            : allEvents;
        countableEvents.forEach(event => {
            const date = (event.start || '').split('T')[0].split(' ')[0];
            if (!groupedByDate[date]) groupedByDate[date] = [];
            groupedByDate[date].push(event);
        });

        const summaryEvents = Object.keys(groupedByDate).map(date => {
            const count = groupedByDate[date].length;
            return {
                id: `summary-${date}`,
                title: `${count} Events`,
                start: date,
                allDay: true,
                backgroundColor: '#f8f9fa',
                textColor: '#333'
            };
        });

        // Builds and shows the day-details modal for a given YYYY-MM-DD date.
        // Shared by dateClick (clicking the cell) and eventClick (clicking the
        // red count badge) so the dot is now clickable to see details.
        function showDayEvents(date) {
            const eventsOnDay = allEvents.filter(event => {
                const eventDate = (event.start || '').split('T')[0].split(' ')[0];
                return eventDate === date;
            });

            let html = '<ul class="list-group list-group-flush">';
            if (eventsOnDay.length === 0) {
                html += '<li class="list-group-item text-muted">No events scheduled for this date.</li>';
            } else {
                eventsOnDay.forEach(event => {
                    const viewUrl = viewEventBaseUrl.replace('__EVENT_ID__', event.id);
                    const editUrl = editEventBaseUrl ? editEventBaseUrl.replace('__EVENT_ID__', event.id) : '';
                    const timeLabel = event.timeDisplay
                        || ((event.startDisplay && event.endDisplay)
                            ? `${event.startDisplay} – ${event.endDisplay} ${event.timezoneLabel || ''}`.trim()
                            : (event.startDisplay ? `${event.startDisplay} ${event.timezoneLabel || ''}`.trim() : 'N/A'));
                    const editIcon = editUrl
                        ? `<a href="${editUrl}" data-bs-toggle="tooltip" title="Edit" class="badge bg-success text-decoration-none"><i class="fas fa-edit"></i></a>`
                        : '';
                    html += `
                        <li class="list-group-item shadow-sm border mb-2">
                            <div class="d-flex justify-content-between align-items-start text-dark">
                                <div>
                                    <strong>${event.title} - ${event.id}</strong>
                                    <div>
                                        <span class="text-muted">Time: ${timeLabel}</span><br>
                                        <span class="text-muted">Ambassadors: ${event.positions}</span>
                                    </div>
                                </div>
                                <div class="text-end text-muted">
                                    <span class="d-block">Kit: ${event.kit}</span>
                                    <span class="d-block">Age Restricted: ${event.ageRestricted}</span>
                                </div>
                                <div class="ms-2 d-flex gap-1 align-items-start flex-shrink-0">
                                    ${editIcon}
                                    <a href="${viewUrl}" data-bs-toggle="tooltip" title="View" class="badge bg-primary text-decoration-none"><i class="fas fa-eye"></i></a>
                                </div>
                            </div>
                        </li>`;
                });
            }
            html += '</ul>';

            document.getElementById('eventListContainer').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('eventListModal'));
            modal.show();
        }

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            dayMaxEvents: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,dayGridYear'
            },
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                day: 'Day',
                year: 'Year'
            },
            views: {
                dayGridYear: {
                    type: 'dayGrid',
                    duration: { years: 1 },
                    buttonText: 'Year'
                }
            },
            events: summaryEvents,
            eventDisplay: 'block',
            eventBorderColor: 'transparent',
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: 'short'
            },
            viewDidMount: function (arg) {
                const viewType = arg.view.type;
                calendar.removeAllEvents();
                if (viewType === 'dayGridMonth' || viewType === 'dayGridYear') {
                    calendar.addEventSource(summaryEvents);
                } else {
                    calendar.addEventSource(allEvents);
                }
            },
            dateClick: function (info) {
                const viewType = calendar.view.type;
                if (viewType === 'dayGridMonth' || viewType === 'dayGridYear') {
                    showDayEvents(info.dateStr);
                }
            },
            eventClick: function (info) {
                const viewType = calendar.view.type;
                if (viewType === 'dayGridMonth' || viewType === 'dayGridYear') {
                    // The month/year red badge is a summary event. Clicking it now
                    // opens the same day-details modal as clicking the cell — this
                    // fixes "unable to see details when clicking the dot".
                    info.jsEvent.preventDefault();
                    const date = (info.event.startStr || '').split('T')[0].split(' ')[0];
                    showDayEvents(date);
                } else if (!info.event.id.toString().startsWith('summary')) {
                    const url = showEventBaseUrl.replace('__EVENT_ID__', info.event.id);
                    window.location.href = url;
                }
            },
            eventContent: function (arg) {
                const viewType = calendar.view.type;
                if (viewType === 'dayGridMonth' || viewType === 'dayGridYear') {
                    const count = arg.event.title.match(/\d+/)?.[0];
                    return { html: `<span class="badge bg-danger rounded-pill position-absolute top-0 end-0 fs-6 fw-bold">${count}</span>` };
                } else {
                    return { html: `<div class="fc-content">${arg.event.title}</div>` };
                }
            }
        });

        calendar.render();
    });
</script>
