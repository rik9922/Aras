@php
    $roleId = (string) (Auth::user()->role_id ?? '');
    $prefix = $roleId === '6' ? 'manager' : 'admin';
    $recapId = $recapId ?? null;
    $eventId = $eventId ?? null;
@endphp
<div class="d-flex gap-1 flex-wrap">
    @if ($roleId === '3' && $eventId)
        <a href="{{ route('client.show-event', ['id' => $eventId]) }}">
            <button type="button" class="btn-ds btn-primary-ds">
                <span>View</span>
            </button>
        </a>
    @elseif (in_array($roleId, ['1', '6'], true) && $recapId)
        <a href="{{ route($prefix . '.show-recap', ['id' => $recapId]) }}">
            <button type="button" class="btn-ds btn-secondary-outline btn-sm">
                <span>View</span>
            </button>
        </a>
        <a href="{{ route($prefix . '.edit-recap', ['id' => $recapId]) }}">
            <button type="button" class="btn-ds btn-primary-outline btn-sm">Edit</button>
        </a>
    @elseif ($eventId)
        <a href="{{ route('admin.show-event', ['id' => $eventId]) }}">
            <button type="button" class="btn-ds btn-primary-ds">
                <span>View</span>
            </button>
        </a>
    @endif

    @if ($recapId)
        @include('admin.recap.partials.export-email-button', ['recapId' => $recapId])
    @endif
</div>
