@php
    $eventShowUrl = route('ambassador.show-event', ['id' => $event->id]);
@endphp
<tr data-href="{{ $eventShowUrl }}" tabindex="0" role="link" aria-label="View event {{ $event->id }} details">
    <td>
        <a href="{{ $eventShowUrl }}" class="text-decoration-underline" aria-label="Open event {{ $event->id }} details">
            {{ $event->id }}
        </a>
    </td>
    <td>
        {{ $event->day_name }}<br>
        {{ $event->formatted_date }}<br>
    </td>
    <td>{!! $event->formatted_time !!}</td>
    <td>
        <a href="{{ $eventShowUrl }}" class="text-decoration-none" aria-label="Open event {{ $event->id }} details">
            {{ $event->brand->brand_name ?? '--' }}
        </a>
    </td>
    <td>{{ $event->varietals ?? '--' }}</td>
    <td>
        @if ($event->kit)
            <span class="badge bg-success">Yes</span>
        @else
            <span class="badge bg-secondary">No</span>
        @endif
    </td>
    <td>{{ $event->department->name ?? '--' }}</td>
    <td>{{ $event->distributor->name ?? '--' }}</td>
    <td>
        {{ $event->location ? (filled($event->location->name) ? $event->location->name : ($event->location->address ?? '--')) : '--' }}<br>
        <small class="text-muted">{{ $event->location->city ?? '' }}</small>
    </td>
    <td>
        @php
            $assigned = assignedAmbassador($event->id) ?? null;
            $wage = getAmbassadorWage();
            $override = $assigned->override_pay ?? 0;
            $pinata = $assigned->pinata_amount ?? 0;
            $bonus = $override + $pinata;
            $total = number_format($event->getAmbassadorAmount($wage) + $bonus, 2);
        @endphp

        {{ '$' . $total }}
    </td>
    <td>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ $eventShowUrl }}" class="btn btn-sm btn-design grey-color">
                <span>View</span>
            </a>

            @if ($event->event_status == '1' && ($status === 'completed' || $status === 'all'))
                <button type="button" class="btn btn-sm btn-design btn-positive" disabled aria-disabled="true">
                    <span>Completed</span>
                </button>
            @elseif (in_array($event->id, $assignAmbasssadorIds) && ($status === 'assigned' || $status === 'all'))
                <button type="button" class="btn btn-sm btn-design grey-color" disabled aria-disabled="true">
                    <span>Assigned</span>
                </button>
                <div class="form-check d-flex align-items-center gap-1 mb-0">
                    <input class="form-check-input event-check-in" type="checkbox"
                        id="check-in-{{ $event->id }}"
                        onchange="handleCheckIn(this, {{ $event->id }})"
                        @checked(! empty($assigned?->event_date_time))
                        @disabled(! empty($assigned?->event_date_time))>
                    <label class="form-check-label" for="check-in-{{ $event->id }}">Check-in</label>
                </div>
            @elseif (in_array($event->id, $pendingAmbassadorIds) && ($status === 'pending' || $status === 'all'))
                <button type="button" class="btn btn-sm btn-design btnWarning" disabled aria-disabled="true">
                    <span>Pending</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="handleWithdraw({{ $event->id }})">
                    <span>Withdraw</span>
                </button>
            @else
                <button type="button" class="btn btn-sm btn-design" onclick="handleStatusChange({{ $event->id }})">
                    <span>Apply</span>
                </button>
            @endif
        </div>
    </td>
</tr>
