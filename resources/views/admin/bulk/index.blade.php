@extends('layout.app', ['page_name' => 'Bulk'])
@section('content')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush
    <div class="container pb-3">
        <div class="page-card-design"> 
            <form method="POST" action="{{ route('admin.bulk.preview') }}">
                @csrf
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3 content-box-grey">
                            <p class="p-3 mb-0">Type or paste a list of event IDs you wish to update.
                                The list can be space, comma, or new line separated.</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="note" class="form-label text-uppercase">Events Ids <span class="text-danger">*</span></label>
                            <textarea class="form-control shadow-none" name="notes" id="note"
                                placeholder="Enter a list of event IDs separated by common, space or new line">{{ old('notes', $notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="d-grid mx-3 mb-3">
                    <button type="submit" class="btn-ds btn-primary-ds text-uppercase">Step 1.) Preview Events</button>
                </div>
            </form>
            
            
           @if(!empty($events) && $events->count())
            <div class="mt-4 p-3">
                <h5>Previewed Events:</h5>
        
                <form method="POST" action="{{ route('admin.bulk.update') }}">
                    @csrf
                    <input type="hidden" name="notes" value="{{ $notes ?? '' }}">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Event ID</th>
                                <th>Name</th>
                                <th>Positions</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Pinata Pay</th>
                                <th>Booked By</th>
                                <th>Booked Date</th>
                                <th>Event Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($events as $event)
                                <tr>
                                    <td>
                                        {{ $event->id }}
                                        <input type="hidden" name="events[{{ $event->id }}][id]" value="{{ $event->id }}">
                                    </td>
                                    <td>
                                        <input type="text" name="events[{{ $event->id }}][event_name]" value="{{ $event->event_name }}" class="form-control shadow-none">
                                    </td>
                                    <td>
                                        <input type="text" name="events[{{ $event->id }}][position]" value="{{ $event->position }}" class="form-control shadow-none">
                                    </td>
                                    <td>
                                        <input type="text" name="events[{{ $event->id }}][start_time]"
                                               value="{{ \Carbon\Carbon::parse($event->start_time)->format('Y-m-d\TH:i') }}"
                                               class="form-control shadow-none startDateTime">
                                    </td>
                                    <td>
                                        <input type="text" name="events[{{ $event->id }}][end_time]"
                                               value="{{ \Carbon\Carbon::parse($event->end_time)->format('Y-m-d\TH:i') }}"
                                               class="form-control shadow-none endDateTime">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="events[{{ $event->id }}][pinata_pay]" value="1" {{ $event->pinata_pay ? 'checked' : '' }}>
                                    </td>
                                    <td>{{ $event->booked_by_display }}</td>
                                    <td>{{ $event->booked_date }}</td>
                                    <td>
                                        <select name="events[{{ $event->id }}][event_status]" class="form-select shadow-none">
                                            <option value="0" {{ $event->event_status == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $event->event_status == 1 ? 'selected' : '' }}>Completed</option>
                                            <option value="2" {{ $event->event_status == 2 ? 'selected' : '' }}>Canceled</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
        
                    <div class="d-flex gap-3 mt-3">
                        <button type="submit" class="btn-ds btn-success-ds text-uppercase">Step 2.) Update All Events</button>
                    </div>
                </form>
        
                <form method="POST" action="{{ route('admin.bulk.cancel') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="event_ids" value="{{ implode(',', $events->pluck('id')->toArray()) }}">
                    <button onclick="return confirm('Are you sure you want to cancel these events?')" type="submit" class="btn-ds btn-danger-ds text-uppercase">Step 2.) Cancel All Events</button>
                </form>
            </div>
        @endif


        </div> 
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
          const startInputs = document.querySelectorAll(".startDateTime");
          const endInputs = document.querySelectorAll(".endDateTime");
        
          startInputs.forEach((startInput, index) => {
            const endInput = endInputs[index];
            let startPicker, endPicker;
        
            function roundToNearest5(date) {
              const ms = 1000 * 60 * 5;
              return new Date(Math.ceil(date.getTime() / ms) * ms);
            }
        
            function getTimes() {
              const now = new Date();
              const rounded = roundToNearest5(now);
              const oneHourLater = new Date(rounded.getTime() + 60 * 60 * 1000);
              return { start: rounded, end: oneHourLater };
            }
        
            const { start, end } = getTimes();
        
            startPicker = flatpickr(startInput, {
              enableTime: true,
              dateFormat: "Y-m-d H:i",
              minuteIncrement: 5,
              minDate: "today",
              defaultDate: startInput.value || start,
              onChange: function (selectedDates) {
                const startDate = selectedDates[0];
                const now = new Date();
        
                if (startDate < now) {
                  alert("Start time cannot be in the past.");
                  startPicker.setDate(start, true);
                  return;
                }
        
                const newEnd = new Date(startDate.getTime() + 60 * 60 * 1000);
                endPicker.set("minDate", startDate);
                endPicker.setDate(newEnd, true);
              },
            });
        
            endPicker = flatpickr(endInput, {
              enableTime: true,
              dateFormat: "Y-m-d H:i",
              minuteIncrement: 5,
              minDate: start,
              defaultDate: endInput.value || end,
              onChange: function (selectedDates) {
                const endDate = selectedDates[0];
                const startDate = startPicker.selectedDates[0];
                if (startDate && endDate <= startDate) {
                  alert("End time must be after start time.");
                  const newEnd = new Date(startDate.getTime() + 60 * 60 * 1000);
                  endPicker.setDate(newEnd, true);
                }
              },
            });
          });
        });
        </script>


@endpush
