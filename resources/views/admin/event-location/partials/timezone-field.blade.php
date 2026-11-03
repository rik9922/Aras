@php
    $tzService = app(\App\Services\EventTimezoneService::class);
    $iana = $tzService->normalizeIana($location->timezone ?? '');
    $abbrev = $location->timezone_label ?? null;
    $display = ($iana !== 'UTC' || $abbrev)
        ? $tzService->displayLabel($iana, $abbrev)
        : '';
@endphp
<div class="col-12">
    <div class="mb-3">
        <label for="venue_timezone_display" class="form-label text-uppercase">Time Zone</label>
        <input type="text"
            class="form-control shadow-none"
            id="venue_timezone_display"
            value="{{ $display }}"
            placeholder="Set address coordinates to detect time zone"
            readonly>
        <input type="hidden" name="timezone" id="venue_timezone" value="{{ $location->timezone ?? '' }}">
        <input type="hidden" name="timezone_label" id="venue_timezone_label" value="{{ $abbrev ?? '' }}">
        <small class="text-muted">Detected automatically from the venue address (not editable).</small>
    </div>
</div>
