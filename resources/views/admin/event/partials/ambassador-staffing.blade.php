@php
    $staffingRows = $staffingRows ?? [];
    $eventPinataPay = (bool) old('pinata_pay', $eventPinataPay ?? false);
    $staffingEventId = $staffingEventId ?? null;
    $assignAmbUrl = $routePrefix === 'manager'
        ? route('manager.assign-amb-event')
        : route('ambassador.assign-amb-event');
    $removeAmbUrl = route($routePrefix . '.remove-ambassador-assignment');
@endphp

<div class="d-flex justify-content-between align-items-center py-3">
    <div class="text-uppercase fw-bold">
        Ambassadors Assigned / Pending Approval
    </div>
    <div class="form-check m-0">
        <input class="form-check-input shadow-none @error('pinata_pay') is-invalid @enderror"
               type="checkbox" id="pinata_pay" value="1" name="pinata_pay"
               {{ $eventPinataPay ? 'checked' : '' }}>
        <label class="form-check-label text-uppercase" for="pinata_pay">
            Pinata Event
        </label>
        @error('pinata_pay')
            <div class="text-danger w-100">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle" id="ambassadorStaffingTable">
        <thead>
            <tr class="text-uppercase small">
                <th>Profile</th>
                <th>Assigned</th>
                <th>Pay</th>
                <th>Overbooked</th>
                <th>Modifiers</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="ambassadorStaffingBody">
            @foreach ($staffingRows as $row)
                @include('admin.event.partials.ambassador-staffing-row', ['row' => $row])
            @endforeach
        </tbody>
    </table>
</div>

<input type="hidden" name="ambassador_staffing_data" id="ambassadorStaffingData" value="">
<input type="hidden" id="selectedAmbassadorId" name="selectedAmbassadorIds">
<input type="hidden" id="removeAmbassadorIds" name="removeAmbassadorIds">
<input type="hidden" name="overridePayData" id="overridePayData">
<input type="hidden" name="additionalPayReasonData" id="additionalPayReasonData">
<input type="hidden" name="additionalPayNoteData" id="additionalPayNoteData">

<div class="row my-3">
    <div class="col-md-5">
        <input type="text" class="form-control shadow-none" id="searchAmbassador"
            name="search ambassadors" placeholder="Search Ambassadors">
        <div id="searchResults" class="bg-white w-100 d-none position-relative" style="z-index: 1000;"></div>
    </div>
    <div class="col-md-3 pb-3">
        <input type="text" class="form-control shadow-none" id="overridePay" name="override_pay"
            placeholder="Override Pay">
    </div>
    <div class="col-md-3 pb-3">
        <select class="form-select shadow-none" id="additionalPayReason">
            <option value="">Select reason (optional)</option>
            <option value="drive_time">Drive time</option>
            <option value="incentive">Incentive</option>
            <option value="other">Other</option>
        </select>
    </div>
    <div class="col-md-1 d-flex justify-center pb-3">
        <button class="btn-ds btn-success-ds btn-icon-only" title="Add" type="button" id="addAmbassador">
            <i class="fas fa-plus"></i>
        </button>
    </div>
</div>

@push('scripts')
<script>
    window.AmbassadorStaffingConfig = {
        searchUrl: @json(route($routePrefix . '.search.ambassador')),
        assignUrl: @json($assignAmbUrl),
        removeUrl: @json($removeAmbUrl),
        csrfToken: @json(csrf_token()),
        eventId: @json($staffingEventId),
        eventPinataPay: @json($eventPinataPay),
        ageRestrictedSelector: '#ageRestrictedCheckbox',
        positionSelector: '#selectPosition',
        initialRows: @json($staffingRows),
        oldStaffingRows: @json(
            old('ambassador_staffing_data')
                ? json_decode(old('ambassador_staffing_data'), true)
                : []
        ),
    };
</script>
<script src="{{ asset('assets/js/ambassador-staffing.js') }}"></script>
@endpush
