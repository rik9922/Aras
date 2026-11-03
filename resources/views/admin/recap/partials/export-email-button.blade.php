@php
    $roleId = (string) (Auth::user()->role_id ?? '');
    $exportRoute = match ($roleId) {
        '1' => 'admin.recap.export-email',
        '6' => 'manager.recap.export-email',
        '3' => 'client.recap.export-email',
        default => null,
    };
@endphp
@if ($exportRoute)
    <form method="POST" action="{{ route($exportRoute, $recapId) }}"
        class="{{ $formClass ?? 'd-inline-flex mb-0 align-items-center' }}">
        @csrf
        <button type="submit" class="{{ $buttonClass ?? 'btn-ds btn-success-outline btn-sm' }}"
            title="Email export to {{ Auth::user()->email }}">
            <span>Export</span>
        </button>
    </form>
@endif
