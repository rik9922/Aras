{{-- forecast-table.blade.php --}}
@php
    $roleId = (string) (Auth::user()->role_id ?? '');
    $prefix = $roleId === '6' ? 'manager' : 'admin';
@endphp
<table class="table table-striped table-bordered align-middle">
    <thead>
        <tr>
            <th>Start</th>
            <th>End</th>
            <th>Late</th>
            <th>Venue</th>
            <th>Brand</th>
            <th>Status</th>
            <th>Event Pay</th>
            <th>Buyback/Barspend</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Created/Saved</th>
            <th>Submitted</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($recaps as $recap)
            @php
                $raw = is_string($recap->status ?? null) ? strtolower(trim($recap->status)) : null;
                $status =
                    $recap->is_draft ? 'Draft'
                    : ((!empty($recap->denied_at) || !empty($recap->deny_reason)) ? 'Rejected'
                        : ($recap->is_approved ? 'Approved' : 'Submitted'));

                if (in_array($raw, ['draft', 'submitted', 'approved', 'rejected'], true)) {
                    $status = ucfirst($raw);
                }

                // Normalize wording to match Recaps tab UX
                $label = $status === 'Submitted' ? 'Pending' : $status;
                $canEditRecap = $label !== 'Approved';

                $badgeClass = match ($label) {
                    'Rejected' => 'bg-danger',
                    'Approved' => 'bg-success',
                    'Pending' => 'bg-warning text-dark',
                    default => 'bg-secondary',
                };
            @endphp
            <tr>
                <td>{{ $recap->event?->start_time ?? '-' }}</td>
                <td>{{ $recap->event?->end_time ?? '-' }}</td>
                <td>-</td>
                <td>{{ $recap->event?->location?->venue_name ?? '-' }}</td>
                <td>{{ $recap->event?->brand?->brand_name ?? '-' }}</td>
                <td>
                    <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                </td>
                <td>
                    @php
                        $wage = (float) (optional($recap->ambassador)->wage ?? 0);
                        $overridePay = (float) ($recap->assignment_override_pay ?? 0);
                        $pinata = (float) ($recap->assignment_pinata_amount ?? 0);
                        $pay = $wage + $overridePay + $pinata;
                    @endphp
                    {{ $pay > 0 ? ('$' . number_format($pay, 2)) : '-' }}
                </td>
                <td>-</td>
                <td>{{ $recap->ambassador?->first_name ?? '-' }}</td>
                <td>{{ $recap->ambassador?->last_name ?? '-' }}</td>
                <td>{{ $recap->created_at }}</td>
                <td>{{ $recap->is_draft ? 'N' : 'Y' }}</td>
                <td class="d-flex gap-1 flex-wrap">
                    <a href="{{ route($prefix . '.show-recap', $recap->id) }}" class="btn-ds btn-primary-ds btn-sm" style="min-width: 0px;">View</a>
                    @if ($canEditRecap)
                        <a href="{{ route($prefix . '.edit-recap', $recap->id) }}" class="btn-ds btn-primary-ds btn-sm text-nowrap" style="min-width: 0px; background-color: #007e42!important">Edit</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="12" class="text-center">No data found</td></tr>
        @endforelse
    </tbody>
</table>