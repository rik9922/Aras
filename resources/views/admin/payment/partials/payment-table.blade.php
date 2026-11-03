<table class="table table-striped table-bordered align-middle">
    <thead>
        <tr>
            <th>Id</th>
            <th style="width: 220px;">Event</th>
            <th>Ambassador</th>
            <th>Amount</th>
            <th class="text-nowrap">Is Late</th>
            <th class="text-nowrap">Creation Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            @php
                // $row is a PayrollBatchItem — status and creation date come directly from it.
                $event      = $row->recap?->event;
                $ambassador = $row->ambassador;

                // Amount stored directly on the batch item (calculated at generation time)
                $amount = '$' . number_format((float) $row->amount, 2);

                // Status from payroll_batch_items (not AmbassadorPayout)
                $batchStatus = $row->status ?? 'created';
                $badgeClass  = match ($batchStatus) {
                    'completed'  => 'bg-success',
                    'processing' => 'bg-info text-dark',
                    'created'    => 'bg-warning text-dark',
                    'failed'     => 'bg-danger',
                    'removed'    => 'bg-secondary',
                    default      => 'bg-secondary',
                };

                // Late: based on the assignment's late_checkin_notification_sent
                $assignment = $event
                    ? \App\Models\AssignAmbassador::where('event_id', $event->id)
                        ->where('ambassador_id', $row->ambassador_id)
                        ->first()
                    : null;
                $isLate = $assignment?->late_checkin_notification_sent ? 'Yes' : 'No';
            @endphp
            <tr>
                <td>{{ $row->id }}</td>

                <td>
                    @if ($event)
                        <strong>{{ strtoupper($event->brand->brand_name ?? $event->event_name) }}</strong>
                        @if ($event->event_name)
                            <br><small>({{ $event->event_name }}):</small>
                        @endif
                        <br>
                        <small>
                            {{ \Carbon\Carbon::parse($event->start_time)->format('n/j/Y') }},
                            {{ \Carbon\Carbon::parse($event->start_time)->format('g:i A') }}&ndash;{{ \Carbon\Carbon::parse($event->end_time)->format('g:i A') }}
                            {{ $event->timezone_label ?? '' }}
                        </small>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>

                <td>{{ $ambassador?->name ?? '—' }}</td>

                <td>{{ $amount }}</td>

                {{-- Late: driven by check-in notification flag on the assignment --}}
                <td>{{ $isLate }}</td>

                {{-- Creation Date: when this payment was added to a payroll batch --}}
                <td class="text-nowrap">
                    {{ $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '—' }}
                </td>

                {{-- Status: payroll_batch_items.status (not AmbassadorPayout status) --}}
                <td>
                    <span class="badge {{ $badgeClass }}">{{ ucfirst($batchStatus) }}</span>
                </td>

                <td>
                    {{-- Actions reserved for Process Payroll phase --}}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-3">No payroll batch items found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
