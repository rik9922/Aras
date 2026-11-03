@php
    $value = fn (string $key) => ($mode ?? 'create') === 'edit'
        ? old($key, $recap->{$key} ?? '')
        : old($key);
@endphp
<div class="col-md-6">
    <div class="mb-3">
        <label for="consumerFeedback" class="form-label text-uppercase">
            Please list all relevant consumer feedback.
        </label>
        <input type="text" class="form-control shadow-none @error('consumer_feedback') is-invalid @enderror" name="consumer_feedback"
            id="consumerFeedback" value="{{ $value('consumer_feedback') }}">
        @error('consumer_feedback')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label for="ambassadorFeedback" class="form-label text-uppercase">
            Ambassador Feedback
        </label>
        <input type="text" class="form-control shadow-none @error('ambassador_feedback') is-invalid @enderror" name="ambassador_feedback"
            id="ambassadorFeedback" value="{{ $value('ambassador_feedback') }}">
        @error('ambassador_feedback')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
