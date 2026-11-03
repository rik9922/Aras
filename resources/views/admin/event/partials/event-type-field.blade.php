@php
    $selectedEventTypeId = old('event_type_id', $event->event_type_id ?? '');
@endphp
<div class="col-md-6">
    <div class="mb-3">
        <label for="event_type_id" class="form-label text-uppercase field-required">Event Type</label>
        <select class="form-select shadow-none @error('event_type_id') is-invalid @enderror" id="event_type_id"
            name="event_type_id" aria-label="Event Type" required>
            <option value="">Select Event Type</option>
            @foreach ($eventTypes as $type)
                <option value="{{ $type->id }}"
                    {{ (string) $selectedEventTypeId === (string) $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Determines which recap form ambassadors complete. Configure recap forms under Event
            Types.</small>
        @error('event_type_id')
            <div class="text-danger w-100">{{ $message }}</div>
        @enderror
    </div>
</div>
