@php
    use App\Services\RecapFormService;

    $eventModel = $event ?? ($recap->event ?? null);
    $resolvedRecapType = $recapType
        ?? (isset($recap) ? RecapFormService::resolveForRecap($recap) : RecapFormService::resolveFromEvent($eventModel));
@endphp
<div class="alert alert-light border mb-3 recap-event-context" role="status">
    <div class="row g-2">
        <div class="col-md-4">
            <span class="text-uppercase text-muted small d-block">Event</span>
            <strong>{{ $eventModel->event_name ?? '—' }}</strong>
        </div>
        <div class="col-md-4">
            <span class="text-uppercase text-muted small d-block">Event Type</span>
            <strong>{{ $eventModel?->eventType?->name ?? 'Not set' }}</strong>
        </div>
        <div class="col-md-4">
            <span class="text-uppercase text-muted small d-block">Recap Form</span>
            <strong>{{ RecapFormService::displayLabel($resolvedRecapType) }}</strong>
        </div>
        <div class="col-md-6"><span class="text-uppercase text-muted small d-block">Location</span><strong>{{ $eventModel?->location?->name ?: ($eventModel?->location?->address ?? '—') }}</strong></div>
        <div class="col-md-6"><span class="text-uppercase text-muted small d-block">Event date and time</span><strong>{{ $eventModel?->formatted_date }} {{ strip_tags($eventModel?->formatted_time ?? '') }}</strong></div>
    </div>
    @if (RecapFormService::receiptRequired($eventModel))
        <p class="mb-0 mt-2">A receipt is required for this Comdata event.</p>
    @endif
</div>
