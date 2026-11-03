@php
    $ambRoutePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin';
@endphp
@forelse ($ambassadors as $ambassador)
    <tr>
        <td>
            @if (! $ambassador->hasVerifiedBankAccount())
                <input type="checkbox" class="form-check-input ambassador-select shadow-none"
                    name="ambassador_ids[]" value="{{ $ambassador->id }}"
                    form="bulk-banking-reminder-form">
            @endif
        </td>
        <td>
            <div class="d-flex align-items-center gap-2">
                <img src="{{ userProfileImageUrl($ambassador->getRawProfileImagePath()) }}"
                    alt="{{ $ambassador->name }}"
                    width="40" height="40"
                    style="object-fit: cover; border-radius: 50%; flex: 0 0 auto;">
                <div>
                    <div class="fw-semibold">{{ $ambassador->name }}</div>
                    <small class="text-muted">
                        @if($ambassador->lastEvent->first())
                            Last Event: {{ $ambassador->lastEvent->first()->event_name }}
                            ({{ \Carbon\Carbon::parse($ambassador->lastEvent->first()->end_time)->format('M d, Y') }})
                        @else
                            No events yet
                        @endif
                    </small>
                </div>
            </div>
        </td>


        <td>{{ $ambassador->email }}</td>
        <td>{{ $ambassador->phone }}</td>
        <td>{{ $ambassador->venmo }}</td>
        <td>
            @php
                $mkt = $ambassador->ambassadorProfile?->markets ?? collect();
                $marketsForModal = $mkt
                    ->filter(fn ($market) => filled($market->name))
                    ->map(fn ($market) => ['id' => $market->id, 'name' => $market->name])
                    ->values();
                $marketCount = $marketsForModal->count();
                $marketCountLabel = $marketCount >= 10
                    ? (floor($marketCount / 10) * 10) . '+ markets'
                    : $marketCount . ' ' . \Illuminate\Support\Str::plural('market', $marketCount);
            @endphp

            @if ($marketCount > 0)
                <button type="button"
                    class="btn btn-link p-0 text-decoration-none js-market-list-trigger"
                    data-ambassador-name="{{ $ambassador->name }}"
                    data-remove-url="{{ route($ambRoutePrefix . '.ambassadors.remove-markets', ['ambassador' => $ambassador->id]) }}"
                    data-markets='@json($marketsForModal)'>
                    {{ $marketCountLabel }}
                </button>
            @else
                -
            @endif
        </td>
        <td>{{ $ambassador->ambassadorProfile?->legal_address ?? '-' }}</td>
        <td>{{ $ambassador->ambassadorProfile?->legal_city ?? '-' }}</td>
        <td>{{ $ambassador->ambassadorProfile?->legal_state ?? '-' }}</td>
        <td><span class="span-text @if($ambassador->status=='1') success @else danger @endif">
            @if($ambassador->status=='1') Active @else Inactive @endif
        </span></td>
        <td>
            @if($ambassador->ambassadorLiquorLicense?->has_liquor_license)
                <span class="span-text success">Yes</span>
            @else
                <span class="span-text danger">No</span>
            @endif
        </td>
        <td>
            @include('admin.ambassador.partials.bank-setup-status-badge', ['ambassador' => $ambassador])
        </td>
        <td class="text-nowrap">
            <div class="d-inline-flex gap-1 align-items-center flex-nowrap">
                <a data-bs-toggle="tooltip" data-bs-placement="top" title="View"
                    href="{{ route($ambRoutePrefix . '.ambassadors.show', ['ambassador' => $ambassador->id]) }}"
                    class="btn-ds btn-info-ds btn-sm text-uppercase"
                    aria-label="View ambassador profile">
                    <i class="fa fa-eye" aria-hidden="true"></i>
                </a>
                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"
                    href="{{ route($ambRoutePrefix . '.edit.ambassador', ['id' => $ambassador->id]) }}"
                    class="btn-ds btn-primary-ds btn-sm text-uppercase"
                    aria-label="Edit ambassador">
                    <i class="fa fa-edit" aria-hidden="true"></i>
                </a>
                @if (! $ambassador->hasVerifiedBankAccount())
                    <form method="POST"
                        action="{{ route($ambRoutePrefix . '.send-banking-reminder', $ambassador) }}"
                        class="d-inline-flex mb-0"
                        onsubmit="return confirm('Send banking setup reminder to {{ $ambassador->name }}?');">
                        @csrf
                        <button type="submit"
                            class="btn-ds btn-warning-ds btn-sm text-uppercase"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Send Banking Reminder"
                            aria-label="Send banking setup reminder">
                            <i class="fa fa-bell" aria-hidden="true"></i>
                        </button>
                    </form>
                @endif
                <button type="button"
                    class="btn-ds btn-danger-ds btn-sm text-uppercase"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Remove"
                    aria-label="Remove ambassador"
                    onclick="deleteEvent({{ $ambassador->id }})">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td class="text-center" colspan="14">Data not found</td>
    </tr>
@endforelse
