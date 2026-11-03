@extends('layout.app', ['page_name' => 'Calendor List'])
<link rel="stylesheet" href="{{ asset('assets/css/fullcalendar.main.min.css') }}">
@section('content')
    <div class="container pb-3">
        <div class="page-card-design">
            <div id="calendar"></div>
        </div>
        <div class="modal fade" id="eventListModal" tabindex="-1" aria-labelledby="eventListModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventListModalLabel">Events</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="eventListContainer">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="{{ asset('assets/js/fullcalendar.main.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const showEventBaseUrl = "{{ route('ambassador.show-event', ['id' => '__EVENT_ID__']) }}";
        const calendarEl = document.getElementById('calendar');

        const allEvents = [{
                id: 1,
                title: 'Meeting with John',
                start: '2025-04-15T10:00:00',
                end: '2025-04-15T11:00:00'
            },
            {
                id: 2,
                title: 'Project Kickoff',
                start: '2025-04-15T12:00:00',
                end: '2025-04-15T13:30:00'
            },
            {
                id: 3,
                title: 'Client Call',
                start: '2025-04-16T14:00:00',
                end: '2025-04-16T15:00:00'
            },
            {
                id: 4,
                title: 'Demo Presentation',
                start: '2025-04-15T16:00:00',
                end: '2025-04-15T17:00:00'
            },
            {
                id: 5,
                title: 'Internal Review',
                start: '2025-04-17T09:00:00',
                end: '2025-04-17T10:00:00'
            }
        ];

        const groupedByDate = {};
        allEvents.forEach(event => {
            const date = event.start.split('T')[0];
            if (!groupedByDate[date]) groupedByDate[date] = [];
            groupedByDate[date].push(event);
        });

        const summaryEvents = Object.keys(groupedByDate).map(date => {
            const count = groupedByDate[date].length;
            return {
                id: `summary-${date}`,
                title: `${count} Events`,
                start: date,
                allDay: true
            };
        });

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
                    duration: {
                        years: 1
                    },
                    buttonText: 'Year'
                }
            },
            events: summaryEvents,

            viewDidMount: function(arg) {
                const viewType = arg.view.type;
                calendar.removeAllEvents();
                if (viewType === 'dayGridMonth' || viewType === 'dayGridYear') {
                    calendar.addEventSource(summaryEvents);
                } else {
                    calendar.addEventSource(allEvents);
                }
            },

            eventClick: function(info) {
                const viewType = calendar.view.type;

                if (viewType === 'dayGridMonth' || viewType === 'dayGridYear') {
                    const date = info.event.startStr.split('T')[0];
                    const eventsOnDay = allEvents.filter(event => event.start.startsWith(date));

                    let html = '<ul class="list-group">';
                    eventsOnDay.forEach(event => {
                        const url = showEventBaseUrl.replace('__EVENT_ID__', event.id);
                        html += `<li class="list-group-item">
                                    <a href="${url}" class="event-link">${event.title}</a>
                                </li>`;
                    });
                    html += '</ul>';

                    document.getElementById('eventListContainer').innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById('eventListModal'));
                    modal.show();
                } else {
                    if (!info.event.id.toString().startsWith('summary')) {
                        const url = showEventBaseUrl.replace('__EVENT_ID__', event.id);
                        window.location.href = url;
                    }
                }
            },

            eventContent: function(arg) {
                const viewType = calendar.view.type;
                if (viewType === 'dayGridMonth' || viewType === 'dayGridYear') {
                    const count = arg.event.title.match(/\d+/)?.[0];
                    return {
                        html: `<div class="event-badge">${count}</div>`
                    };
                }
            }
        });

        calendar.render();
    });
</script>
