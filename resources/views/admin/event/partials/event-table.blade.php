<table class="table table-striped table-hover align-middle">
    <thead>
        <tr>
            <th style="width: 3%">
                <input type="checkbox" id="selectAllEvents">
            </th>
            <th style="width: 3%">ID</th>
            <!--<th style="width: 10%">Event Name</th>-->
            <th style="width: 5%">Day</th>
            <th style="width: 7%;">Date</th>
            <th style="width: 23%">Time</th>
            <th style="width: 8%">Billable Hours</th>
            <th style="width: 10%;">Brand</th>
            <th style="width: 10%">Varietals</th>
            <th style="width: 2%">Kit</th>
            <th style="width: 6%">Department</th>
            <th style="width: 8%">Distributor</th>
            <th style="width: 10%">Location</th>
            <th>Assigned</th>
            <th>Pending</th>
            <th>Status</th>
            <th>BEST</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($events as $key => $event)
            <tr>
                <td>
                    @if ((int) ($event->integrate_with_best ?? 0) === 1)
                        <input type="checkbox" class="event-select" value="{{ $event->id }}">
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td><strong>{{ $event->id }}</strong></td>
                <!--<td>-->
                <!--    <div class="fw-medium">{{ $event->event_name ?? '' }}</div>-->
                <!--    @if ($event->eventType)
-->
                <!--        <small class="text-muted">({{ $event->eventType->name }})</small>-->
                <!--
@endif-->
                <!--    <small style="white-space: nowrap" class="text-muted">(EventId: {{ $event->id }})</small>-->
                <!--</td>-->
                <td>{{ $event->Dayname }}</td>
                <td>{{ $event->FormattedDate }}</td>
                <td>{!! $event->FormattedTime !!}</td>
                <td>{{ $event->billable_hours_formatted }}</td>
                <td>{{ $event->brand ? $event->brand->brand_name : '-' }}</td>
                <td class="varietals-cell">
                    <span class="varietals-preview">
                        {{ \Str::limit($event->varietals, 25) }}
                    </span>
                    <span class="varietals-full" style="display: none;">
                        {{ $event->varietals }}
                    </span>
                    @if (strlen($event->varietals) > 25)
                        <br><a href="javascript:void(0);" class="read-more-toggle">Read More</a>
                    @endif
                </td>
                <td>
                    {{-- Kit Required --}}
                    <span class="badge bg-{{ $event->kit == 1 ? 'success' : 'secondary' }}">
                        {{ $event->kit == 1 ? 'Yes' : 'No' }}
                    </span>

                    {{-- Kit Delivered --}}
                    @if ($event->kit == 1)
                        <a href="javascript:void(0)"
                            onclick="updateKitDelivered({{ $event->id }}, {{ $event->kit_delivered }})"
                            class="text-{{ $event->kit_delivered == 1 ? 'success' : 'danger' }}">
                            <i class="fas fa-truck"></i>
                        </a>
                    @endif
                </td>
                <td>{{ $event->department ? $event->department->name : '-' }}</td>
                <td>{{ $event->distributor ? $event->distributor->name : '-' }}</td>
                <td>{{ $event->location ? (filled($event->location->name) ? $event->location->name : $event->location->address) : '-' }}</td>
                <td class="fw-medium">{!! ambassadorName($event->id) !!}</td>
                <td class="text-center">{{ (int) ($event->pending_applicants_count ?? 0) }}</td>
                <td>
                    {{-- BA-facing status (what ambassadors see) --}}
                    <div class="mb-1">
                        <select
                            class="status-select @if ($event->event_status == '1') statusCompleted @elseif($event->event_status == '2') statusCancelled @else statusPending @endif"
                            data-was="{{ $event->event_status }}"
                            name="status_id" onchange="handleStatusChange(this, {{ $event->id }})">
                            <option value="1" @if ($event->event_status == '1') selected @endif>
                                Completed</option>
                            <option value="0" @if ($event->event_status == '0') selected @endif>
                                Pending</option>
                            <option value="2" @if ($event->event_status == '2') selected @endif>
                                Cancelled</option>
                        </select>
                    </div>
                    @if ($event->budget_approval_status === 'pending')
                        <div class="small text-warning fw-semibold mt-1">Budget approval pending</div>
                    @elseif ($event->budget_approval_status === 'approved')
                        <div class="small text-success mt-1">Budget approved</div>
                    @elseif ($event->budget_approval_status === 'denied')
                        <div class="small text-danger mt-1">Budget denied</div>
                    @endif

                    {{-- Admin/Manager operational status (impacts QB + metrics) --}}
                    @if (Auth::check() && in_array(Auth::user()->role_id, [1, 6], true))
                        @php
                            $adminStatus = $event->admin_status;
                        @endphp
                        <select class="form-select form-select-sm" name="admin_status"
                            onchange="handleAdminStatusChange(this, {{ $event->id }})">
                            <option value="" @if (empty($adminStatus)) selected @endif>Ops: —</option>
                            <option value="no_show" @if ($adminStatus === 'no_show') selected @endif>
                                No Show</option>
                            <option value="cancelled_no_fee" @if ($adminStatus === 'cancelled_no_fee') selected @endif>
                                Cancelled - No Fee</option>
                            <option value="cancelled_2_3_business_days_fee_15"
                                @if ($adminStatus === 'cancelled_2_3_business_days_fee_15') selected @endif>
                                Cancelled - 2-3 Business Days - $15 Fee</option>
                            <option value="cancelled_24_hours_or_less_full_labor"
                                @if ($adminStatus === 'cancelled_24_hours_or_less_full_labor') selected @endif>
                                Cancelled - 24 Hours or Less - Full Labor</option>
                        </select>
                    @endif
                </td>
                <td>
                    @if ((int) ($event->integrate_with_best ?? 0) !== 1)
                        <span class="text-muted">—</span>
                    @else
                        @if (!is_null($event->best_synced_at))
                            <span title="Synced" class="text-success fw-semibold">✅</span>
                        @elseif (($event->best_status ?? 'pending') === 'failed')
                            <div class="d-flex align-items-center gap-2">
                                <span title="Failed" class="text-danger fw-semibold">❌</span>
                                <form method="POST"
                                    action="{{ (int) (auth()->user()->role_id ?? 0) === 6 ? route('manager.events.best.retry', $event) : route('admin.events.best.retry', $event) }}">
                                    @csrf
                                    <button type="submit" class="btn-ds btn-danger-outline btn-sm">Retry</button>
                                </form>
                            </div>
                        @elseif (($event->best_status ?? 'pending') === 'processing')
                            <span title="Processing" class="text-warning fw-semibold">⏳</span>
                        @else
                            <span title="Pending" class="text-muted fw-semibold">⏳</span>
                        @endif
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-1">
                        @php
                            $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
                        @endphp
                        @if ($event->is_invoice_created)
                            <a data-bs-toggle="tooltip" title="Show Invoice"
                                href="{{ route($routePrefix . '.invoices.show', $event->invoice_id) }}"
                                class="btn-ds btn-success-outline btn-sm"><i class="fas fa-file-invoice"></i></a>
                        @else
                            {{-- <a data-bs-toggle="tooltip" title="Generate Invoice"
                                href="{{ route('admin.event.generate-invoice', ['event' => $event->id]) }}"
                                class="btn-ds btn-secondary-outline btn-sm"><i class="fas fa-file-invoice"></i></a> --}}
                        @endif
                        <a data-bs-toggle="tooltip" title="Edit"
                            href="{{ route($routePrefix . '.edit.event', ['id' => $event->id]) }}"
                            class="btn-ds btn-success-ds btn-sm btn-icon-only">
                            <i class="fas fa-edit"></i>
                        </a>

                        <a data-bs-toggle="tooltip" title="Duplicate"
                            href="{{ route($routePrefix . '.duplicate.event', ['id' => $event->id]) }}"
                            class="btn-ds btn-primary-ds btn-sm btn-icon-only">
                            <i class="fas fa-copy"></i>
                        </a>

                        <a data-bs-toggle="tooltip" title="View"
                            href="{{ route($routePrefix . '.show-event', ['id' => $event->id]) }}"
                            class="btn-ds btn-orange-ds btn-sm btn-icon-only">
                            <i class="fas fa-eye"></i>
                        </a>

                        <span data-bs-toggle="tooltip" title="Remove"
                            class="btn-ds btn-success-ds btn-sm btn-icon-only"
                            style="cursor: pointer;"
                            onclick="deleteEvent({{ $event->id }})">
                            <i class="fas fa-trash"></i>
                        </span>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="18" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                        <br>No events found.
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
