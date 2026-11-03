@php
    $locationService = app(\App\Services\EventLocationService::class);
    $tzService = app(\App\Services\EventTimezoneService::class);
    $selectedVenueId = old('event_location_id', $event->event_location_id ?? '');
    $selectedVenue = $selectedVenueId ? $states->firstWhere('id', (int) $selectedVenueId) : null;
    $defaultAddress = old('event_address', $event->event_address ?? ($selectedVenue ? $locationService->formatVenueAddress($selectedVenue) : ''));
@endphp
<div class="col-md-12">
    <div class="mb-3">
        <label for="event_location_id" class="form-label text-uppercase field-required">Venue</label>
        <select class="form-select shadow-none @error('event_location_id') is-invalid @enderror"
            id="venue_name" aria-label="Venue" name="event_location_id">
            <option value="">Select venue</option>
            @foreach ($states as $item)
                @php
                    $venueAddress = $locationService->formatVenueAddress($item);
                    $iana = $tzService->normalizeIana($item->timezone ?? '');
                    $abbrev = $item->timezone_label ?? '';
                    $tzDisplay = ($iana !== 'UTC' || $abbrev)
                        ? $tzService->displayLabel($iana, $abbrev)
                        : '';
                @endphp
                <option value="{{ $item->id }}"
                    data-address="{{ e($venueAddress) }}"
                    data-latitude="{{ $item->latitude }}"
                    data-longitude="{{ $item->longitude }}"
                    data-timezone="{{ $item->timezone ?: ($iana !== 'UTC' ? $iana : '') }}"
                    data-timezone-label="{{ $abbrev }}"
                    data-timezone-display="{{ $tzDisplay }}"
                    data-city="{{ $item->city }}"
                    {{ (string) $selectedVenueId === (string) $item->id ? 'selected' : '' }}>
                    {{ $item->name ? $item->name . ' — ' : '' }}{{ $venueAddress }}
                </option>
            @endforeach
        </select>
        @error('event_location_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
{{-- Venue Type not used on events
<div class="col-md-6">
    <div class="mb-3">
        <label for="selectType" class="form-label text-uppercase">Venue Type</label>
        <select class="form-select shadow-none @error('venue_type_id') is-invalid @enderror"
            id="selectType" aria-label="Venue Type" name="venue_type_id">
            <option value="">Select</option>
            @foreach ($venueTypes as $venueType)
                <option value="{{ $venueType->id }}"
                    {{ (string) old('venue_type_id', $event->venue_type_id ?? '') === (string) $venueType->id ? 'selected' : '' }}>
                    {{ $venueType->name }}
                </option>
            @endforeach
        </select>
        @error('venue_type_id')
            <div class="text-danger w-100">{{ $message }}</div>
        @enderror
    </div>
</div>
--}}
<div class="col-md-12">
    <div class="mb-3">
        <label for="event_address" class="form-label text-uppercase">Event Address</label>
        <input type="text" class="form-control shadow-none @error('event_address') is-invalid @enderror"
            name="event_address" value="{{ $defaultAddress }}" id="event_address"
            placeholder="Select a venue" readonly>
        @error('event_address')
            <div class="text-danger w-100">{{ $message }}</div>
        @enderror
        <small class="text-muted">Copied from the selected venue (stored on the event).</small>
    </div>
</div>
<input type="hidden" name="latitude" id="event_latitude" value="{{ old('latitude', $event->latitude ?? '') }}">
<input type="hidden" name="longitude" id="event_longitude" value="{{ old('longitude', $event->longitude ?? '') }}">
