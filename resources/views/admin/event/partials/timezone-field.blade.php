@php
    $tzService = app(\App\Services\EventTimezoneService::class);
    $iana = $tzService->normalizeIana(old('timezone', $event->timezone ?? ''));
    $abbrev = old('timezone_label', $event->timezone_label ?? null);
    $display = $iana !== 'UTC' || $abbrev
        ? $tzService->displayLabel($iana, $abbrev, old('start_time', $event->start_time ?? null))
        : '';
@endphp
<div class="col-md-4">
    <div class="mb-3">
        <label for="timezone_display" class="form-label text-uppercase">Time Zone</label>
        <input type="text"
            class="form-control shadow-none @error('timezone') is-invalid @enderror"
            id="timezone_display"
            value="{{ $display }}"
            placeholder="Select a venue to detect time zone"
            readonly>
        <input type="hidden" name="timezone" id="timezone" value="{{ old('timezone', $iana !== 'UTC' ? $iana : '') }}">
        <input type="hidden" name="timezone_label" id="timezone_label" value="{{ $abbrev ?? '' }}">
        @error('timezone')
            <div class="text-danger w-100">{{ $message }}</div>
        @enderror
    </div>
</div>
