@if(($recapType ?? 'off_premise') === 'special_event')
    @include('ambassador.recap.partials.special-event-fields', compact('mode', 'recap'))
@endif

@if(($recapType ?? 'off_premise') === 'on_premise')
    @include('ambassador.recap.partials.on-premise-fields', compact('mode', 'recap'))
@else
    @include('ambassador.recap.partials.off-premise-fields', compact('mode', 'recap'))
@endif

@include('ambassador.recap.partials.recap-trailing-fields', compact('mode', 'recap'))
