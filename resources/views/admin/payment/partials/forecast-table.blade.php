{{-- forecast-table.blade.php — Unified payment forecast --}}
<table class="table table-striped table-bordered align-middle">
    <thead>
        <tr>
            <th>Ambassador</th>
            <th>Event</th>
            <th>Payment Type</th>
            <th>Gross Amount</th>
            <th>Fee</th>
            <th>Net Amount</th>
            <th>Approval Date</th>
            <th>Payroll Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($recaps as $row)
            @php
                $event = $row->event ?? null;
                $eventDate = $event?->start_time
                    ? \Carbon\Carbon::parse($event->start_time)->format('m/d/Y g:i A')
                    : null;
                $venue = $event?->location?->address;
                $brand = $event?->brand?->brand_name;
                $approvalDate = $row->approval_date ? \Carbon\Carbon::parse($row->approval_date)->format('m/d/Y') : '-';
            @endphp
            <tr>
                <td class="text-nowrap">{{ $row->ambassador_name }}</td>
                <td>
                    <div>{{ $row->event_name }}</div>
                    @if ($brand || $venue || $eventDate)
                        <small class="text-muted">
                            {{ collect([$brand, $venue, $eventDate])->filter()->join(' | ') }}
                        </small>
                    @endif
                </td>
                <td>
                    <span class="fw-semibold">{{ $row->payment_type_label }}</span>
                    <br>
                    {{-- <small class="text-muted">{{ $row->payment_type }}</small> --}}
                </td>
                <td class="text-nowrap">${{ number_format((float) $row->gross_amount, 2) }}</td>
                <td class="text-nowrap">
                    {{ (float) $row->fee > 0 ? '$' . number_format((float) $row->fee, 2) : '-' }}
                </td>
                <td class="text-nowrap">${{ number_format((float) $row->net_amount, 2) }}</td>
                <td class="text-nowrap">{{ $approvalDate }}</td>
                <td>
                    <span class="badge {{ $row->status_badge_class }}">{{ $row->payroll_status }}</span>
                </td>
                <td>
                    <a href="{{ $row->action_url }}" class="btn-ds btn-primary-ds btn-sm" style="min-width: 0px;">
                        {{ $row->action_label }}
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">No data found</td>
            </tr>
        @endforelse
    </tbody>
</table>
