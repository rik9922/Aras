@php
    $value = fn (string $key) => ($mode ?? 'create') === 'edit'
        ? old($key, $recap->{$key} ?? '')
        : old($key);
@endphp
{{-- Hidden for On-Premise recap forms; retained for future use.
<div class="col-md-6">
    <div class="mb-3">
        <label for="drinksPurchasedAfterSampling" class="form-label text-uppercase">
            Drinks Purchased After Sampling (Consumer)
        </label>
        <input type="number" min="0" step="1"
            class="form-control shadow-none @error('drinks_purchased_after_sampling') is-invalid @enderror"
            name="drinks_purchased_after_sampling" id="drinksPurchasedAfterSampling"
            value="{{ $value('drinks_purchased_after_sampling') }}">
        @error('drinks_purchased_after_sampling')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
--}}
<div class="col-md-6">
    <div class="mb-3">
        <label for="consumerComments" class="form-label text-uppercase">Consumer Comments</label>
        <textarea rows="4" class="form-control shadow-none @error('consumer_feedback') is-invalid @enderror"
            name="consumer_feedback" id="consumerComments" minlength="200" maxlength="255">{{ $value('consumer_feedback') }}</textarea>
        @error('consumer_feedback')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label for="managerComments" class="form-label text-uppercase">Account Feedback</label>
        <textarea rows="4" class="form-control shadow-none @error('manager_comments') is-invalid @enderror"
            name="manager_comments" id="managerComments" minlength="200">{{ $value('manager_comments') }}</textarea>
        @error('manager_comments')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label for="consumerCommentsSuggestions" class="form-label text-uppercase">Consumer Suggestions</label>
        <textarea rows="4" class="form-control shadow-none @error('consumer_comments_suggestions') is-invalid @enderror"
            name="consumer_comments_suggestions" id="consumerCommentsSuggestions" minlength="200">{{ $value('consumer_comments_suggestions') }}</textarea>
        @error('consumer_comments_suggestions')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
