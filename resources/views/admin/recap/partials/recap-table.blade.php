<table class="table table-striped align-middle">
    <thead>
        <tr>
            <th>Brand</th>
            <th>
                <div style="width: 150px;">Event Name</div>
            </th>
            <th>
                <div>City</div>
            </th>
            <th>
                <div style="width: 100px;">Date</div>
            </th>
            <th>
                <div style="width: 200px;">Ambassador(s)</div>
            </th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
            @forelse ($events as $key=>$event)
                <tr>
                    <td>{{ $event->brand ? $event->brand->brand_name : '-' }}</td>
                    <td>{{ $event->event_name ?? '-' }}</td>
                    <td>{{ $event->location ? $event->location->city : '-' }}</td>
                    <td>{{ $event->FormattedDate }}</td>
                    <td class="text-bold">{!! ambassadorName($event->id) !!}</td>

                    <td>
                        @include('admin.recap.partials.recap-list-actions', [
                            'recapId' => $event->recap?->id,
                            'eventId' => $event->id,
                        ])
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Event not found.</td></tr>
            @endforelse 
    </tbody>
</table>
<div class="d-flex justify-content-end mt-3">
    {{ $events->links('vendor.pagination.custom') }}
</div>
