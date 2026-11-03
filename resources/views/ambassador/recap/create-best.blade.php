@extends('layout.app', ['page_name' => 'Create BEST Recap'])

<style>
    .typeahead-container {
        position: relative;
    }
    .typeahead-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ced4da;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }
    .typeahead-item {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
    }
    .typeahead-item:hover,
    .typeahead-item.active {
        background-color: #e9ecef;
    }
    .typeahead-item:last-child {
        border-bottom: none;
    }
    .no-results {
        padding: 0.5rem 0.75rem;
        color: #6c757d;
        font-style: italic;
    }
    .loading {
        padding: 0.5rem 0.75rem;
        color: #6c757d;
    }
</style>

@php
    $bestData = json_decode($event->best_event_data ?? '{}', true) ?: [];
@endphp

@section('content')
    <div class="container py-3">
        <div class="h5">Submit BEST Recap</div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            @include('ambassador.recap.partials.recap-event-context', ['event' => $event, 'recapType' => 'best'])
            <form action="{{ route('ambassador.store-recap') }}" enctype="multipart/form-data" method="POST"> @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <input type="hidden" name="integrate_with_best" value="1">

                {{-- Standard Recap Fields --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="traffic_location" class="form-label text-uppercase">Describe the location's traffic
                                (Registers, Aisles, Other parts of location)</label>
                            <input type="text" name="traffic_location"
                                class="form-control shadow-none @error('traffic_location') is-invalid @enderror"
                                id="traffic_location" value="{{ old('traffic_location') }}">
                            @error('traffic_location')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="eventSuccesfull" class="form-label text-uppercase">
                                Was the timing of this event succesfull?
                            </label>
                            <input type="datetime-local" name="close_time"
                                class="form-control shadow-none @error('close_time') is-invalid @enderror"
                                id="eventSuccesfull" value="{{ old('close_time') }}">
                            @error('close_time')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="checkIn" class="form-label text-uppercase">
                                    With whom did you check in? Provide name, phone and/or email
                                </label>
                                <input type="text" class="form-control shadow-none" name="check_in_with_person"
                                    id="checkIn" value="{{ old('check_in_with_person') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_attachments1" class="form-label text-uppercase">
                                    Attach a photo of each product everywhere you see it on a display (not the shelf/not
                                    your setup). Show price/competitive set if available.
                                </label>
                                <input type="file" class="form-control shadow-none" name="product_attachments1"
                                    id="product_attachments1">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectShirtSize" class="form-label text-uppercase">
                                Is there a promotion running for this product?
                            </label>
                            <select class="form-select shadow-none @error('promotion_product_status') is-invalid @enderror"
                                name="promotion_product_status" id="selectShirtSize">
                                <option value="" selected>Select</option>
                                <option value="1" {{ old('promotion_product_status') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ old('promotion_product_status') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                            @error('promotion_product_status')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="everydayPriceTip" class="form-label text-uppercase">
                                Everyday Price Tip (line break) What is the Feature/Special Pricing?
                            </label>
                            <input type="text" class="form-control shadow-none" name="tip" id="everydayPriceTip" value="{{ old('tip') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="product_attachments2" class="form-label text-uppercase">
                                Attach a photo of each product everywhere you see it on the shelf (not a display) showing
                                surrounding. Show price/competitors if available.
                            </label>
                            <input type="file" class="form-control shadow-none" name="product_attachments2"
                                id="product_attachments2">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="competitorBrands" class="form-label text-uppercase">
                                If competitor brands were having demos on-site, list their names, setup, location and price
                                point.
                            </label>
                            <input type="text" class="form-control shadow-none" name="brand_dmeo_name"
                                id="competitorBrands" value="{{ old('brand_dmeo_name') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Backup brand</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="backup_brand_used"
                                        id="backup_brand_no" value="0"
                                        {{ old('backup_brand_used', '0') == '0' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="backup_brand_no">No</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="backup_brand_used"
                                        id="backup_brand_yes" value="1"
                                        {{ old('backup_brand_used') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="backup_brand_yes">Back up brand used</label>
                                </div>
                            </div>
                            @error('backup_brand_used')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6" id="backup_brand_name_wrap"
                        style="{{ old('backup_brand_used') == '1' ? '' : 'display: none;' }}">
                        <div class="mb-3">
                            <label for="backup_brand_name" class="form-label text-uppercase">What brand did you use?</label>
                            <input type="text" class="form-control shadow-none @error('backup_brand_name') is-invalid @enderror"
                                name="backup_brand_name" id="backup_brand_name" value="{{ old('backup_brand_name') }}"
                                maxlength="255" placeholder="Brand name">
                            @error('backup_brand_name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="brand_photo" class="form-label text-uppercase">
                                Add a photo of you with your setup (include table, products, etc.)
                            </label>
                            <input type="file" class="form-control shadow-none" name="brand_photo" id="brand_photo">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="predominantGender" class="form-label text-uppercase">
                                What was the predominant gender of consumers you interacted with?
                            </label>
                            <select class="form-select shadow-none @error('interaction_gender') is-invalid @enderror"
                                name="interaction_gender" id="predominantGender">
                                <option value="" selected>Select</option>
                                <option value="male" {{ old('interaction_gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('interaction_gender') == 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                            @error('interaction_gender')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="predominantAge" class="form-label text-uppercase">
                                What was the predominant age of consumers you interacted with?
                            </label>
                            <input type="text"
                                class="form-control shadow-none @error('interaction_age') is-invalid @enderror"
                                name="interaction_age" id="predominantAge" placeholder="21-25"
                                inputmode="numeric" maxlength="2" pattern="2[1-5]"
                                title="Enter an age from 21 to 25"
                                value="{{ old('interaction_age') }}">
                            @error('interaction_age')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="manyConsumers" class="form-label text-uppercase">
                                Approximately how many consumers did you interact with?
                            </label>
                            <select class="form-select shadow-none @error('total_interaction') is-invalid @enderror"
                                name="total_interaction" id="manyConsumers">
                                <option value="" selected>Select</option>
                                <option value="1-5" {{ old('total_interaction') == '1-5' ? 'selected' : '' }}>1-5</option>
                                <option value="5-10" {{ old('total_interaction') == '5-10' ? 'selected' : '' }}>5-10</option>
                                <option value="15-20" {{ old('total_interaction') == '15-20' ? 'selected' : '' }}>15-20</option>
                            </select>
                            @error('total_interaction')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="manySamples" class="form-label text-uppercase">
                                How many samples did you hand out?
                            </label>
                            <input type="number" min="0" step="1"
                                class="form-control shadow-none @error('samples') is-invalid @enderror"
                                name="samples" id="manySamples" placeholder="0"
                                value="{{ old('samples') }}">
                            @error('samples')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12" id="kit-giveaways-section" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="giveaway_count" class="form-label text-uppercase">How many giveaways/items were handed out?</label>
                                    <input type="number" min="0" step="1"
                                        class="form-control shadow-none @error('giveaway_count') is-invalid @enderror"
                                        name="giveaway_count" id="giveaway_count" value="{{ old('giveaway_count') }}">
                                    @error('giveaway_count')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="giveaway_comments" class="form-label text-uppercase">Comments on POS/giveaways</label>
                                    <textarea rows="3"
                                        class="form-control shadow-none @error('giveaway_comments') is-invalid @enderror"
                                        name="giveaway_comments" id="giveaway_comments"
                                        placeholder="Add any notes about POS materials and giveaways...">{{ old('giveaway_comments') }}</textarea>
                                    @error('giveaway_comments')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="consumer_photo" class="form-label text-uppercase">
                                Attach 2-4 photos of consumers with the product.
                            </label>
                            <input type="file" class="form-control shadow-none" name="consumer_photo[]"
                                id="consumer_photo" multiple>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="productSelling" class="form-label text-uppercase">
                                What type of product were you selling?
                            </label>
                            <select class="form-select shadow-none" name="brand_selling_id" id="productSelling">
                                <option selected>Select</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_selling_id') == $brand->id ? 'selected' : '' }}>{{ $brand->brand_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="productWereSold" class="form-label text-uppercase">
                                How many units of each product were sold during your task?
                            </label>
                            <input type="number" min="0" step="1"
                                class="form-control shadow-none @error('sold_unit') is-invalid @enderror"
                                name="sold_unit" id="productWereSold" placeholder="0"
                                value="{{ old('sold_unit') }}">
                            @error('sold_unit')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @include('ambassador.recap.partials.buyback-amount-field')
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="consumerFeedback" class="form-label text-uppercase">
                                Please list all relevant consumer feedback.
                            </label>
                            <input type="text" class="form-control shadow-none" name="consumer_feedback"
                                id="consumerFeedback" value="{{ old('consumer_feedback') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="ambassadorFeedback" class="form-label text-uppercase">
                                Ambassador Feedback
                            </label>
                            <input type="text" class="form-control shadow-none" name="ambassador_feedback"
                                id="ambassadorFeedback" value="{{ old('ambassador_feedback') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="receipt" class="form-label text-uppercase">
                                Receipt photo
                            </label>
                            <input type="file" class="form-control shadow-none" name="receipt" id="receipt">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="receipt" class="form-label text-uppercase"> State </label>
                            <select name="state_id" id="stateId"
                                class="form-control @error('state_id') is-invalid @enderror">
                                <option value="">Select State</option>
                                @foreach (getState() as $state)
                                    <option value="{{ $state->id }}" {{ old('state_id') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                @endforeach
                            </select>
                            @error('state_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="brand_count" class="form-label text-uppercase">How many people knew about the
                                brand?</label>
                            <input type="number"
                                class="form-control shadow-none @error('brand_count') is-invalid @enderror"
                                name="brand_count" id="brand_count" value="{{ old('brand_count') }}">
                            @error('brand_count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- BEST Integration Fields (prefilled from event setup) --}}
                <div class="d-flex justify-content-between">
                    <div class="h5">BEST Event Details</div>
                    <small class="text-info">Pre-filled from event setup. You may update values before submitting.</small>
                </div>
                <div class="fixed-border"></div>
                <div class="row my-3">
                    <div class="col-md-4 mb-3">
                        <label for="event_name" class="form-label text-uppercase">Event Name</label>
                        <input type="text" name="event_name" value="{{ old('event_name', $event->event_name) }}" readonly
                            class="form-control shadow-none @error('event_name') is-invalid @enderror" id="name" required>
                        @error('event_name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label text-uppercase">Status</label>
                        <select class="form-select shadow-none @error('status') is-invalid @enderror" name="status" required>
                            <option value="">---Select Status---</option>
                            <option value="Planned" {{ old('status', $bestData['status'] ?? '') == 'Planned' ? 'selected' : '' }}>Planned</option>
                            <option value="Completed" {{ old('status', $bestData['status'] ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                            <option value="Canceled" {{ old('status', $bestData['status'] ?? '') == 'Canceled' ? 'selected' : '' }}>Canceled</option>
                        </select>
                        @error('status')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="target_audience" class="form-label text-uppercase">Target Audience</label>
                        <select class="form-select shadow-none @error('target_audience') is-invalid @enderror"
                            name="target_audience" required>
                            <option value="">---Select Target Audience---</option>
                            <option value="Consumer" {{ old('target_audience', $bestData['target_audience'] ?? '') == 'Consumer' ? 'selected' : '' }}>Consumer</option>
                            <option value="Trade" {{ old('target_audience', $bestData['target_audience'] ?? '') == 'Trade' ? 'selected' : '' }}>Trade</option>
                        </select>
                        @error('target_audience')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="account_name" class="form-label text-uppercase">Account</label>
                        <div class="typeahead-container">
                            <input type="text" name="account_name" class="form-control shadow-none typeahead-input"
                                   id="account_name" data-api="https://aras.betaplanets.com/ambassador/best-get-account"
                                   placeholder="Start typing account name..." autocomplete="off" required
                                   value="{{ old('account_name', $bestData['account_name'] ?? '') }}">
                            <input type="hidden" name="account_id" id="account_id"
                                   value="{{ old('account_id', $bestData['account_id'] ?? '') }}">
                            <div class="typeahead-dropdown" id="account-dropdown"></div>
                        </div>
                        <div class="text-danger typeahead-error" style="display: none;"></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="Associated_with_Demand_Creation_Station" class="form-label text-uppercase">Associated with Demand Creation
                            Station</label>
                        <select name="Associated_with_Demand_Creation_Station" class="form-control shadow-none" id="Associated_with_Demand_Creation_Station" required>
                            <option>---Select Associated with Demand Creation Station---</option>
                            <option value="Yes" {{ old('Associated_with_Demand_Creation_Station', $bestData['Associated_with_Demand_Creation_Station'] ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                            <option value="No" {{ old('Associated_with_Demand_Creation_Station', $bestData['Associated_with_Demand_Creation_Station'] ?? '') == 'No' ? 'selected' : '' }}>No</option>
                        </select>
                        @error('Associated_with_Demand_Creation_Station')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-uppercase">Wet or Dry Event</label>
                        <div class="d-flex">
                            <div class="form-check me-5">
                                <input class="form-check-input" type="radio" name="wet_or_dry" id="wet"
                                    value="Wet" {{ old('wet_or_dry', $bestData['wet_or_dry'] ?? '') == 'Wet' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="wet">Wet</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="wet_or_dry" id="dry"
                                    value="Dry" {{ old('wet_or_dry', $bestData['wet_or_dry'] ?? '') == 'Dry' ? 'checked' : '' }}>
                                <label class="form-check-label" for="dry">Dry</label>
                            </div>
                        </div>
                        @error('wet_or_dry')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="objective" class="form-label text-uppercase">Objective</label>
                        <textarea name="objective" id="objective" class="form-control shadow-none @error('objective') is-invalid @enderror"
                            rows="3" placeholder="Your objective here" required>{{ old('objective', $bestData['objective'] ?? '') }}</textarea>
                        @error('objective')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="description" class="form-label text-uppercase">Description</label>
                        <textarea name="description" id="description" placeholder="description here"
                            class="form-control shadow-none @error('description') is-invalid @enderror" rows="3" required>{{ old('description', $bestData['description'] ?? '') }}</textarea>
                        @error('description')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="sampling_type" class="form-label text-uppercase">Sampling Type</label>
                        <select name="sampling_type" class="form-control shadow-none" id="sampling_type" required>
                            <option>---Sampling Type---</option>
                            <option value="Single Brand Sampling" {{ old('sampling_type', $bestData['sampling_type'] ?? '') == 'Single Brand Sampling' ? 'selected' : '' }}>Single Brand Sampling</option>
                            <option value="Multi Brand Sampling" {{ old('sampling_type', $bestData['sampling_type'] ?? '') == 'Multi Brand Sampling' ? 'selected' : '' }}>Multi Brand Sampling</option>
                        </select>
                        @error('sampling_type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="Attendees" class="form-label text-uppercase">Attendees</label>
                        <input type="number" name="attendees"
                            class="form-control shadow-none @error('attendees') is-invalid @enderror" id="attendees"
                            value="{{ old('attendees', $bestData['attendees'] ?? 0) }}" required>
                        @error('attendees')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="interactions" class="form-label text-uppercase">Interactions</label>
                        <input type="number" name="interactions"
                            class="form-control shadow-none @error('interactions') is-invalid @enderror"
                            value="{{ old('interactions', $bestData['interactions'] ?? 0) }}" id="interactions" required>
                        @error('interactions')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="event_results" class="form-label text-uppercase">Event Results</label>
                        <textarea name="event_results" id="event_results"
                            class="form-control shadow-none @error('event_results') is-invalid @enderror" rows="3" required>{{ old('event_results', $bestData['event_results'] ?? '') }}</textarea>
                        @error('event_results')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label text-uppercase">Key Learnings Future Considerations</label>
                        <textarea name="key_learnings_future_considerations" id="key_learnings_future_considerations"
                            class="form-control shadow-none @error('key_learnings_future_considerations') is-invalid @enderror"
                            rows="3" required>{{ old('key_learnings_future_considerations', $bestData['key_learnings_future_considerations'] ?? '') }}</textarea>
                        @error('key_learnings_future_considerations')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label text-uppercase">Consumer Feedback</label>
                        <textarea name="consumer_feedback" id="best_consumer_feedback"
                            class="form-control shadow-none @error('consumer_feedback') is-invalid @enderror" rows="3" required>{{ old('consumer_feedback', $bestData['consumer_feedback'] ?? '') }}</textarea>
                        @error('consumer_feedback')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label text-uppercase">Account Feedback</label>
                        <textarea name="account_feedback" id="account_feedback"
                            class="form-control shadow-none @error('account_feedback') is-invalid @enderror" rows="3" required>{{ old('account_feedback', $bestData['account_feedback'] ?? '') }}</textarea>
                        @error('account_feedback')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="h6">Consumer Event Samplings</div>
                <div class="fixed-border"></div>
                <div class="row my-3">
                    <div class="col-md-6 mb-3">
                        <label for="budget" class="form-label text-uppercase">Budget</label>
                        <input type="text" name="budget" value="{{ old('budget', $bestData['budget'] ?? 0) }}"
                            class="form-control shadow-none @error('budget') is-invalid @enderror" id="budget" required>
                        @error('budget')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="Account_Premise_Type" class="form-label text-uppercase">Account Premise Type</label>
                        <select name="Account_Premise_Type"
                            class="form-control shadow-none @error('Account_Premise_Type') is-invalid @enderror" id="Account_Premise_Type" required>
                            @foreach ($eventTypes as $type)
                                <option value="{{ $type->name }}" {{ old('Account_Premise_Type', $bestData['Account_Premise_Type'] ?? '') == $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('Account_Premise_Type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="program_name" class="form-label text-uppercase">Program Name</label>
                        <div class="typeahead-container">
                            <input type="text" name="program_name" class="form-control shadow-none typeahead-input"
                                data-api="https://aras.betaplanets.com/ambassador/best-get-program" id="program_name"
                                placeholder="Start typing program name..." autocomplete="off" required
                                value="{{ old('program_name', $bestData['program_name'] ?? '') }}">
                            <input type="hidden" name="program_id" id="program_id"
                                value="{{ old('program_id', $bestData['program_id'] ?? '') }}">
                            <div class="typeahead-dropdown" id="program-dropdown"></div>
                        </div>
                        <div class="text-danger typeahead-error" style="display: none;"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tag_name" class="form-label text-uppercase">Tag</label>
                        <div class="typeahead-container">
                            <input type="text" name="tag_name" class="form-control shadow-none typeahead-input"
                                   id="tag_name" data-api="https://aras.betaplanets.com/ambassador/best-get-tags"
                                   placeholder="Start typing tag name..." autocomplete="off" required
                                   value="{{ old('tag_name', $bestData['tag_name'] ?? '') }}">
                            <input type="hidden" name="tag_id" id="tag_id"
                                   value="{{ old('tag_id', $bestData['tag_id'] ?? '') }}">
                            <div class="typeahead-dropdown" id="tag-dropdown"></div>
                        </div>
                        <div class="text-danger typeahead-error" style="display: none;"></div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="1l_bottles" class="form-label text-uppercase">Total Bottles Sold 1L</label>
                        <input type="number" name="1l_bottles" placeholder="10"
                            class="form-control shadow-none @error('1l_bottles') is-invalid @enderror"
                            value="{{ old('1l_bottles', $bestData['1l_bottles'] ?? 0) }}" id="1l_bottles" required>
                        @error('1l_bottles')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="175l_bottles" class="form-label text-uppercase">Total Bottles Sold 1.75 L</label>
                        <input type="number" name="175l_bottles" placeholder="10"
                            value="{{ old('175l_bottles', $bestData['175l_bottles'] ?? 0) }}"
                            class="form-control shadow-none @error('175l_bottles') is-invalid @enderror"
                            id="175l_bottles" required>
                        @error('175l_bottles')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="750ml_bottles" class="form-label text-uppercase">Total Bottles Sold 750ML</label>
                        <input type="number" name="750ml_bottles" placeholder="10"
                            value="{{ old('750ml_bottles', $bestData['750ml_bottles'] ?? 0) }}"
                            class="form-control shadow-none @error('750ml_bottles') is-invalid @enderror"
                            id="750ml_bottles" required>
                        @error('750ml_bottles')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="375ml_bottles" class="form-label text-uppercase">Total Bottles Sold 375ML</label>
                        <input type="number" name="375ml_bottles" placeholder="10"
                            value="{{ old('375ml_bottles', $bestData['375ml_bottles'] ?? 0) }}"
                            class="form-control shadow-none @error('375ml_bottles') is-invalid @enderror"
                            id="375ml_bottles" required>
                        @error('375ml_bottles')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="total_samples_distributed" class="form-label text-uppercase">Total Samples Distributed</label>
                        <input type="number" name="total_samples_distributed" placeholder="10"
                            value="{{ old('total_samples_distributed', $bestData['total_samples_distributed'] ?? 0) }}"
                            class="form-control shadow-none @error('total_samples_distributed') is-invalid @enderror"
                            id="total_samples_distributed" required>
                        @error('total_samples_distributed')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="total_samples_distributed_cocktail" class="form-label text-uppercase">Total Samples Distributed Cocktail</label>
                        <input type="number" name="total_samples_distributed_cocktail" placeholder="10"
                            value="{{ old('total_samples_distributed_cocktail', $bestData['total_samples_distributed_cocktail'] ?? 0) }}"
                            class="form-control shadow-none @error('total_samples_distributed_cocktail') is-invalid @enderror"
                            id="total_samples_distributed_cocktail" required>
                        @error('total_samples_distributed_cocktail')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="total_samples_distributed_neat_shot" class="form-label text-uppercase">Total Samples Distributed Neat Shot</label>
                        <input type="number" name="total_samples_distributed_neat_shot" placeholder="10"
                            value="{{ old('total_samples_distributed_neat_shot', $bestData['total_samples_distributed_neat_shot'] ?? 0) }}"
                            class="form-control shadow-none @error('total_samples_distributed_neat_shot') is-invalid @enderror"
                            id="total_samples_distributed_neat_shot" required>
                        @error('total_samples_distributed_neat_shot')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="h6">Consumer Event Lengths</div>
                <div class="fixed-border"></div>
                <div class="row my-3">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="end_date" class="form-label text-uppercase">End Date</label>
                            <input type="date" name="end_date"
                                   value="{{ old('end_date', $bestData['end_date'] ?? '') }}"
                                   class="form-control shadow-none @error('end_date') is-invalid @enderror"
                                   id="end_date" required>
                            @error('end_date')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="start_time" class="form-label text-uppercase">Start Time</label>
                            <input type="time" name="start_time"
                                   value="{{ old('start_time', $bestData['start_time'] ?? '') }}"
                                   class="form-control shadow-none @error('start_time') is-invalid @enderror"
                                   id="start_time" required>
                            @error('start_time')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label text-uppercase">End Time</label>
                            <input type="time" name="end_time"
                                   value="{{ old('end_time', $bestData['end_time'] ?? '') }}"
                                   class="form-control shadow-none @error('end_time') is-invalid @enderror"
                                   id="end_time" required>
                            @error('end_time')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                @include('ambassador.recap.partials.quickbooks-requirements-card')
                @include('ambassador.recap.partials.expedited-pay', ['submitBtnId' => 'recapSubmitBtn'])

                <div class="d-flex gap-2">
                    <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-secondary px-4">Save as Draft</button>
                    <button type="submit" id="recapSubmitBtn" class="btn btn-design color-grey px-4" {{ isset($quickbooksRequirements) && ! ($quickbooksRequirements['ready'] ?? true) ? 'disabled' : '' }}>Complete</button>
                </div>
            </form>
        </div>
    </div>
@endsection

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function() {
    function syncBackupBrandFields() {
        const on = $('input[name="backup_brand_used"]:checked').val() === '1';
        $('#backup_brand_name_wrap').toggle(on);
        if (!on) {
            $('#backup_brand_name').val('');
        }
    }
    $('input[name="backup_brand_used"]').on('change', syncBackupBrandFields);
    syncBackupBrandFields();

    class UniversalTypeahead {
        constructor($input) {
            this.$input = $input;
            this.$hiddenInput = $(`#${$input.attr('id').replace('_name', '_id')}`);
            this.$dropdown = $input.siblings('.typeahead-dropdown');
            this.$error = $input.closest('.typeahead-container').siblings('.typeahead-error');
            this.apiUrl = $input.data('api');
            this.data = [];
            this.filteredData = [];
            this.activeIndex = -1;
            this.debounceTimer = null;
            this.init();
        }
        init() { this.loadData(); this.bindEvents(); }
        async loadData() {
            try {
                const response = await $.ajax({ url: this.apiUrl, method: 'GET' });
                this.data = response;
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }
        bindEvents() {
            this.$input
                .on('input', (e) => this.handleInput(e))
                .on('keydown', (e) => this.handleKeydown(e))
                .on('focus', () => this.handleFocus())
                .on('blur', () => { setTimeout(() => this.hideDropdown(), 200); });
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.typeahead-container').length) this.hideDropdown();
            });
        }
        handleInput(e) {
            const query = e.target.value.trim();
            this.$hiddenInput.val('');
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                if (query.length >= 1) this.filterData(query);
                else this.hideDropdown();
            }, 300);
        }
        handleKeydown(e) {
            const $items = this.$dropdown.find('.typeahead-item:not(.no-results)');
            switch (e.key) {
                case 'ArrowDown': e.preventDefault(); this.activeIndex = Math.min(this.activeIndex + 1, $items.length - 1); this.updateActiveItem($items); break;
                case 'ArrowUp': e.preventDefault(); this.activeIndex = Math.max(this.activeIndex - 1, -1); this.updateActiveItem($items); break;
                case 'Enter':
                    e.preventDefault();
                    if (this.activeIndex >= 0 && $items.eq(this.activeIndex).length) {
                        const $activeItem = $items.eq(this.activeIndex);
                        this.selectItem($activeItem.data('id'), $activeItem.data('name'));
                    }
                    break;
                case 'Escape': this.hideDropdown(); break;
            }
        }
        handleFocus() { if (this.$input.val().trim().length >= 1) this.filterData(this.$input.val().trim()); }
        filterData(query) {
            const lowerQuery = query.toLowerCase();
            this.filteredData = this.data.filter(item => item.Name.toLowerCase().includes(lowerQuery) && !item.IsDeleted);
            this.showDropdown();
            this.renderDropdown();
        }
        renderDropdown() {
            this.activeIndex = -1;
            if (this.filteredData.length === 0) {
                this.$dropdown.html('<div class="no-results">No results found</div>');
                return;
            }
            const html = this.filteredData.slice(0, 10).map(item =>
                `<div class="typeahead-item" data-id="${item.Id}" data-name="${item.Name}">${this.highlightMatch(item.Name, this.$input.val())}</div>`
            ).join('');
            this.$dropdown.html(html);
            this.$dropdown.find('.typeahead-item').on('click', (e) => {
                const $item = $(e.currentTarget);
                this.selectItem($item.data('id'), $item.data('name'));
            });
        }
        highlightMatch(text, query) {
            if (!query) return text;
            const regex = new RegExp(`(${this.escapeRegExp(query)})`, 'gi');
            return text.replace(regex, '<strong>$1</strong>');
        }
        escapeRegExp(string) { return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
        updateActiveItem($items) {
            $items.removeClass('active');
            if (this.activeIndex >= 0) {
                $items.eq(this.activeIndex).addClass('active');
                $items.eq(this.activeIndex)[0]?.scrollIntoView({ block: 'nearest' });
            }
        }
        selectItem(id, name) {
            this.$input.val(name);
            this.$hiddenInput.val(id);
            this.hideDropdown();
            this.$error.hide();
            this.$input.trigger('change');
        }
        showDropdown() { this.$dropdown.show(); }
        hideDropdown() { this.$dropdown.hide(); this.activeIndex = -1; }
    }

    $('.typeahead-input').each(function() { new UniversalTypeahead($(this)); });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kitRequired = {{ (int) ($event->kit ?? 0) }} === 1;
        const section = document.getElementById('kit-giveaways-section');
        if (!section) return;
        section.style.display = kitRequired ? 'block' : 'none';
    });
</script>
