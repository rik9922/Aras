@php
    $value = fn (string $key) => ($mode ?? 'create') === 'edit'
        ? old($key, $recap->{$key} ?? '')
        : old($key);
@endphp
<div class="col-md-6">
    <div class="mb-3">
        <label for="consumerInteractions" class="form-label text-uppercase">Consumer Interactions</label>
        <input type="number" min="0" step="1"
            class="form-control shadow-none @error('consumer_interactions') is-invalid @enderror"
            name="consumer_interactions" id="consumerInteractions"
            value="{{ $value('consumer_interactions') }}">
        @error('consumer_interactions')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label for="consumerSampled" class="form-label text-uppercase">Consumer Sampled</label>
        <input type="number" min="0" step="1"
            class="form-control shadow-none @error('consumer_sampled') is-invalid @enderror"
            name="consumer_sampled" id="consumerSampled"
            value="{{ $value('consumer_sampled') }}">
        @error('consumer_sampled')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="col-md-12">
    <div class="mb-3">
        <label for="eventSummary" class="form-label text-uppercase">Event Summary</label>
        <textarea rows="4" class="form-control shadow-none @error('event_summary') is-invalid @enderror"
            name="event_summary" id="eventSummary" minlength="200">{{ $value('event_summary') }}</textarea>
        @error('event_summary')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
