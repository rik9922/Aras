@extends('layout.app', ['page_name' => 'Event List'])
@section('content')
    @php
        $eventStatusPostUrl =
            (int) (auth()->user()->role_id ?? 0) === 6
                ? route('manager.update-event-status.event')
                : route('admin.update-event-status.event');
        $eventsListRoute = (int) (auth()->user()->role_id ?? 0) === 6 ? route('manager.events') : route('admin.events');
        $exportEventsRoute =
            (int) (auth()->user()->role_id ?? 0) === 6 ? route('manager.export.events') : route('admin.export.events');
        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
    @endphp
    @push('styles')
        <style>
            select option {
                background: #fff;
                color: #000;
            }

            .stats-card {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 1.5rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .search-section {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                margin-bottom: 1rem;
            }

            .search-header {
                padding: 1rem;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                border-radius: 8px 8px 0 0;
            }

            .search-content {
                padding: 1.5rem;
            }

            .filter-section {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1.5rem;
                height: fit-content;
            }

            .filter-section h6 {
                margin-bottom: 1rem;
                color: #495057;
                font-weight: 600;
            }

            .form-check {
                margin-bottom: 0.75rem;
            }

            .form-check-label {
                font-size: var(--font-size-body);
                color: #495057;
            }

            .table-section {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 1.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .table th {
                background: #f8f9fa;
                border-color: #dee2e6;
                font-weight: 600;
                font-size: var(--font-size-table-header);
                padding: 0.5rem 0.4rem;
                vertical-align: middle;
            }

            .table td {
                padding: 0.5rem 0.4rem;
                vertical-align: middle;
                font-size: var(--font-size-table-cell);
            }

            .status-select {
                border: none;
                background: transparent;
                font-weight: 500;
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
            }

            .statusCompleted {
                background: #d4edda !important;
                color: #155724 !important;
            }

            .statusCancelled {
                background: #f8d7da !important;
                color: #721c24 !important;
            }

            .statusPending {
                background: #fff3cd !important;
                color: #856404 !important;
            }

            .action-buttons {
                display: flex;
                gap: 0.25rem;
                flex-wrap: wrap;
            }

            .action-buttons .btn {
                padding: 0.25rem 0.5rem;
                font-size: var(--font-size-button);
                border-radius: 4px;
            }

            .varietals-cell {
                max-width: 150px;
                word-wrap: break-word;
            }

            .read-more-toggle {
                color: #007bff;
                font-size: var(--font-size-caption);
                text-decoration: none;
                cursor: pointer;
            }

            .read-more-toggle:hover {
                text-decoration: underline;
            }

            .btn-design {
                padding: 0.5rem 1rem;
                border-radius: 6px;
                font-weight: 500;
                font-size: var(--font-size-button);
                border: 1px solid #dee2e6;
                transition: all 0.2s;
            }

            .btn-design:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .grey-color {
                background: #6c757d;
                color: #fff;
                border-color: #6c757d;
            }

            .btn-negative {
                background: #dc3545;
                color: #fff;
                border-color: #dc3545;
            }

            .total-data {
                font-weight: 600;
                color: #007bff;
            }

            .date-range-inputs {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .date-range-inputs input {
                flex: 1;
            }

            .date-separator {
                padding: 0 0.5rem;
                color: #6c757d;
                font-weight: 500;
            }

            @media (max-width: 768px) {
                .action-buttons {
                    flex-direction: column;
                }

                .table-responsive {
                    font-size: var(--font-size-table-cell);
                }

                .search-content .row {
                    margin: 0;
                }

                .search-content .col-md-9,
                .search-content .col-md-3 {
                    padding: 0;
                }

                .filter-section {
                    margin-top: 1rem;
                }
            }
        </style>
    @endpush

    <div class="container py-3">
        <!-- Page Header -->
        <x-page-header title="Events Management"
            subtitle="Total Events: <span class='total-data'>{{ $events->total() }}</span>" />

        <!-- Stats Card -->
        <div class="stats-card">
            <div class="row">
                <div class="col-md-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Event Hours</h6>
                            <h4 class="mb-0 text-primary">{{ $getEventHours['event_hours'] }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Ambassador Hours</h6>
                            <h4 class="mb-0 text-success">{{ $getEventHours['total_hours'] }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="d-flex gap-2 justify-content-end align-items-center">
                        <a href="{{ route($routePrefix . '.event.create') }}">
                            <button type="button" class="btn-ds btn-primary-rounded">
                                <i class="fas fa-plus me-1"></i>Add Event
                            </button>
                        </a>
                        <a href="{{ route($routePrefix . '.eventtypes') }}">
                            <button type="button" class="btn-ds btn-theme-rounded">
                                <i class="fas fa-list me-1"></i>Event Types
                            </button>
                        </a>
                        <a href="{{ route($routePrefix . '.export.events') }}">
                            <button type="button" class="btn-ds btn-warning-rounded">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </a>
                        <!-- Replace your current Export button with this -->
                        {{-- Add --}}
                        <button type="button" class="btn-ds btn-success-rounded" data-bs-toggle="modal"
                            data-bs-target="#importModal">
                            <i class="fas fa-upload me-1"></i> Import Excel
                        </button>

                        <button type="button" class="btn-ds btn-info-rounded" id="toggleCompleted">
                            <i class="fas fa-check-double me-1"></i>
                            <span class="btn-text">Show Completed</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-header">
                <button class="btn-ds btn-primary-ds w-100" type="button" data-bs-toggle="collapse"
                    data-bs-target="#search" aria-expanded="false" aria-controls="search" id="searchToggleBtn">
                    <i class="fa-solid fa-caret-down me-2" id="searchIcon"></i>
                    <span>Search & Filter Options</span>
                </button>
            </div>

            <div class="collapse" id="search">
                <div class="search-content">
                    <div class="row">
                        <div class="col-lg-9">
                            <div class="row g-3">
                                <div class="col-md-6 col-xl-3">
                                    <label for="selectDepartments" class="form-label">Departments</label>
                                    <select class="form-select" id="selectDepartments" name="department_id"
                                        aria-label="Select Departments">
                                        <option value="">Select Departments</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="selectMarket" class="form-label">Market</label>
                                    <select class="form-select" id="selectMarket" name="market_id"
                                        aria-label="Select Market">
                                        <option value="">Select Market</option>
                                        @foreach ($markets as $market)
                                            <option value="{{ $market->id }}">{{ $market->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-12 col-xl-6">
                                    <label for="dateRange" class="form-label">Date Range</label>
                                    <div class="date-range-inputs">
                                        <input type="date" class="form-control" id="startDate" name="startDate"
                                            aria-label="Start Date">
                                        <span class="date-separator">to</span>
                                        <input type="date" class="form-control" id="endDate" name="endDate"
                                            aria-label="End Date">
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="selectManagers" class="form-label">Manager</label>
                                    <select class="form-select" id="selectManagers" name="manager_id"
                                        aria-label="Select Managers">
                                        <option value="">Select Manager</option>
                                        @foreach ($managers as $manager)
                                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="eventType" class="form-label">Event Type</label>
                                    <select class="form-select" id="eventType" name="event_type_id"
                                        aria-label="Event Type">
                                        <option value="">Select Event Type</option>
                                        @foreach ($eventTypes as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="selectDistributors" class="form-label">Distributors</label>
                                    <select class="form-select" id="selectDistributors" name="distributor_id"
                                        aria-label="Distributors">
                                        <option value="">Select Distributor</option>
                                        @foreach ($distributors as $distributor)
                                            <option value="{{ $distributor->id }}">{{ $distributor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="eventId" class="form-label">Event ID</label>
                                    <select class="form-select" id="eventId" aria-label="Select Event Id"
                                        name="event_id">
                                        <option value="">Select Event ID</option>
                                        @foreach ($eventFilterOptions as $event)
                                            <option value="{{ $event->id }}">
                                                {{ $event->event_name . '(' . $event->id . ')' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="location_state" class="form-label">Location State</label>
                                    <select class="form-select" id="location_state" aria-label="Location State"
                                        name="location_state">
                                        <option value="">Location State</option>
                                        @foreach ($states as $item)
                                            <option value="{{ $item->id }}">{{ $item->state }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="location" class="form-label">Location</label>
                                    <select class="form-select" id="location" aria-label="location" name="location">
                                        <option value="">Select Location</option>
                                        @foreach ($locations as $item)
                                            <option value="{{ $item->id }}">
                                                {{ $item->name ? $item->name . ' - ' : '' }}{{ trim($item->address . ' ' . $item->city . ' ' . $item->state) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="ambassadorName" class="form-label">Ambassador Name</label>
                                    <select class="form-select" id="ambassadorName">
                                        <option value="">Select Ambassador Name</option>
                                        @foreach ($ambassadors as $ambassador)
                                            <option value="{{ $ambassador->id }}">{{ $ambassador->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="ambassadorEmail" class="form-label">Ambassador Email</label>
                                    <select class="form-select" id="ambassadorEmail" aria-label="Ambassador Email">
                                        <option value="">Select Ambassador Email</option>
                                        @foreach ($ambassadors as $ambassador)
                                            <option value="{{ $ambassador->id }}">{{ $ambassador->email }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="varietals" class="form-label">Varietals</label>
                                    <input type="text" class="form-control" id="varietals" name="varietals"
                                        placeholder="Enter varietals">
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="paymentType" class="form-label">Payment Type</label>
                                    <select class="form-select" id="paymentType" aria-label="Select Payment Type">
                                        <option value="">Select Payment Type</option>
                                        <option value="No purchase">No purchase</option>
                                        <option value="Product on site">Product on site</option>
                                        <option value="Comdata">Comdata</option>
                                        <option value="Check">Check</option>
                                        <option value="Reimbursement">Reimbursement</option>
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="brandId" class="form-label">Brand</label>
                                    <select class="form-select" id="brandId" name="brand_id"
                                        aria-label="Select Brand">
                                        <option value="">Select Brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="region" class="form-label">Region</label>
                                    <select class="form-select @error('region') is-invalid @enderror" id="region"
                                        name="region">
                                        <option value="" selected>Select Region</option>
                                        <option value="Southeast" {{ old('region') == 'Southeast' ? 'selected' : '' }}>
                                            Southeast</option>
                                        <option value="Midwest" {{ old('region') == 'Midwest' ? 'selected' : '' }}>Midwest
                                        </option>
                                    </select>
                                    @error('region')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="billTo" class="form-label">Bill To</label>
                                    <select class="form-select" id="billTo" aria-label="Bill To">
                                        <option value="">Select Bill To</option>
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="bestSync" class="form-label">BEST Sync</label>
                                    <select class="form-select" id="bestSync" aria-label="BEST Sync">
                                        <option value="">All</option>
                                        <option value="synced">Synced</option>
                                        <option value="not_synced">Not Synced</option>
                                        <option value="failed">Failed</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <label for="billable_name" class="form-label">Billable By Name</label>
                                    <input type="text" class="form-control" id="billable_name" name="billable_name"
                                        placeholder="Search by name">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="filter-section">
                                <h6>Filter Options</h6>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value=""
                                        id="includeNearbyEvents">
                                    <label class="form-check-label" for="includeNearbyEvents">
                                        Include Nearby Events
                                    </label>
                                    {{-- <small class="text-muted d-block ms-4">Not a location-radius search; this filter is not connected to live data.</small> --}}
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" id="overviewNeeded">
                                    <label class="form-check-label" for="overviewNeeded">
                                        Overview Needed
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" id="notFullyStaffed">
                                    <label class="form-check-label" for="notFullyStaffed">
                                        Not Fully Staffed
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="kitRequired"
                                        name="kit_required">
                                    <label class="form-check-label" for="kitRequired">
                                        Kit Required
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value=""
                                        id="excludeCancellations">
                                    <label class="form-check-label" for="excludeCancellations">
                                        Exclude Cancellations
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="filterBackupBrandUsed" name="backup_brand_used">
                                    <label class="form-check-label" for="filterBackupBrandUsed">
                                        Back up brand used (recap)
                                    </label>
                                </div>

                                <div class="mb-2">
                                    <label for="backupBrandNameSearch" class="form-label small mb-0">Backup brand name
                                        contains</label>
                                    <input type="text" class="form-control form-control-sm" id="backupBrandNameSearch"
                                        placeholder="Search recap text" autocomplete="off">
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="2" id="showCancellations"
                                        name="eventStatus">
                                    <label class="form-check-label" for="showCancellations">
                                        Show Cancellations Only
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="0" id="pendingEventsOnly"
                                        name="eventStatus">
                                    <label class="form-check-label" for="pendingEventsOnly">
                                        Pending Events Only
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="1" id="showCompletedOnly"
                                        name="eventStatus">
                                    <label class="form-check-label" for="showCompletedOnly">
                                        Show Completed Only
                                    </label>
                                </div>

                                <button class="btn-ds btn-secondary-ds w-100 mt-3" id="resetFilters">
                                    <i class="fas fa-undo me-1"></i>Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk BEST Action -->
        <form method="POST"
            action="{{ (int) (auth()->user()->role_id ?? 0) === 6 ? route('manager.events.best.send-selected') : route('admin.events.best.send-selected') }}"
            id="bestBulkForm">
            @csrf
            <div class="d-flex align-items-center gap-2 mb-2">
                <button type="submit" class="btn-ds btn-primary-ds text-nowrap">
                    <i class="fas fa-paper-plane me-1"></i> Send Selected Events to BEST
                </button>
                <small class="text-muted">Synced events are automatically skipped.</small>
            </div>

            <input type="hidden" name="event_ids" id="bestSelectedIds" value="">

            <!-- Events Table -->
            <div class="table-section">
                <div class="table-responsive">
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
                            @if ($events->total() > 0)
                                @foreach ($events as $key => $event)
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

                                            {{-- Kit Delivered (only if kit required) --}}
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
                                                    data-was="{{ $event->event_status }}" name="status_id"
                                                    onchange="handleStatusChange(this, {{ $event->id }})">
                                                    <option value="1"
                                                        @if ($event->event_status == '1') selected @endif>
                                                        Completed</option>
                                                    <option value="0"
                                                        @if ($event->event_status == '0') selected @endif>
                                                        Pending</option>
                                                    <option value="2"
                                                        @if ($event->event_status == '2') selected @endif>
                                                        Cancelled</option>
                                                </select>
                                            </div>
                                            @if ($event->budget_approval_status === 'pending')
                                                <div class="small text-warning fw-semibold mt-1">Budget approval pending
                                                </div>
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
                                                    <option value=""
                                                        @if (empty($adminStatus)) selected @endif>Ops: —</option>
                                                    <option value="no_show"
                                                        @if ($adminStatus === 'no_show') selected @endif>
                                                        No Show</option>
                                                    <option value="cancelled_no_fee"
                                                        @if ($adminStatus === 'cancelled_no_fee') selected @endif>
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
                                                            <button type="submit"
                                                                class="btn-ds btn-danger-outline btn-sm">Retry</button>
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
                                                {{-- <a data-bs-toggle="tooltip" title="Generate Invoice"
                                                href="{{ route('admin.event.generate-invoice', ['event' => $event->id]) }}"
                                                class="btn-ds btn-secondary-outline btn-sm"><i
                                                    class="fas fa-file-invoice"></i></a> --}}
                                                <a data-bs-toggle="tooltip" title="Edit"
                                                    href="{{ route($routePrefix . '.edit.event', ['id' => $event->id]) }}"
                                                    class="btn-ds btn-success-ds btn-sm btn-icon-only">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a data-bs-toggle="tooltip" title="View"
                                                    href="{{ route($routePrefix . '.show-event', ['id' => $event->id]) }}"
                                                    class="btn-ds btn-primary-ds btn-sm btn-icon-only">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <span data-bs-toggle="tooltip" title="Remove"
                                                    class="btn-ds btn-danger-ds btn-sm btn-icon-only"
                                                    style="cursor: pointer;" onclick="deleteEvent({{ $event->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="17" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                            <br>No events found.
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-end mt-3 pagination-container">
                    {{ $events->links('vendor.pagination.custom') }}
                </div>
            </div>
        </form>
    </div>


    {{-- Add --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.events.import.process') }}" method="POST" enctype="multipart/form-data"
                class="modal-content" id="importForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Events from Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">Excel File</label>
                        <input class="form-control" type="file" id="excelFile" name="excel_file"
                            accept=".xls,.xlsx,.csv" required>
                        <small class="text-muted">Accepted formats: .xlsx, .xls, .csv (Max: 50MB)</small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="sendToBest"
                            name="send_to_best">
                        <label class="form-check-label" for="sendToBest">
                            Send Events to BEST
                        </label>
                        <div class="small text-muted">If checked, events will be queued and sent after import completes.
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <strong>Expected Columns:</strong> Event Id, Day, Start, End, Hours, Timezone, Brand, Varietals, Location,
                        Address, City, State, Zip, Store Number, Kit Required, Distributor, Ambassador, Email, Phone, Department,
                        Payment Type, Event Type, Market, Region, Manager
                        <div class="mt-1 text-muted" style="font-size:0.85em;">Ambassador &amp; Manager are optional and will not block import. Duplicate events (same Event Id) are automatically skipped.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-ds btn-secondary-ds" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ds btn-primary-ds" id="uploadBtn">
                        <i class="fas fa-upload me-1"></i> Upload and import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Excel is Being Imported...</h5>
                    <p class="text-muted">Please wait, the data is being saved. This process may take some time.</p>

                    <div class="progress mt-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                            style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
            <div class="toast-body" id="successMessage">
                Events successfully imported!
            </div>
        </div>

        <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
            <div class="toast-body" id="errorMessage">
                Import failed! Please try again.
            </div>
        </div>
    </div>
    {{-- add --}}


    @include('common.install')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        function deleteEvent(eventId) {
            Swal.fire({
                title: "Are you sure?",
                text: "You want to delete this Event.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = "POST";
                    form.action = "{{ route($routePrefix . '.delete.event', '') }}/" + eventId;

                    let csrfToken = document.createElement('input');
                    csrfToken.type = "hidden";
                    csrfToken.name = "_token";
                    csrfToken.value = "{{ csrf_token() }}";

                    let methodField = document.createElement('input');
                    methodField.type = "hidden";
                    methodField.name = "_method";
                    methodField.value = "POST";

                    form.appendChild(csrfToken);
                    form.appendChild(methodField);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            let showCompleted = false;

            $("#selectDepartments, #selectMarket, #selectManagers, #eventType, #selectDistributors, #eventId, #location_state, #location, #ambassadorName, #ambassadorEmail, #paymentType, #brandId, #region, #billTo")
                .select2();

            const filterSelectors =
                '#selectDepartments, #selectMarket, #selectManagers, #eventType, #selectDistributors, #location_state, #location, #varietals, #brandId, #ambassadorEmail, #ambassadorName, #billTo, #billable_name, #eventId, #paymentType,#startDate,#endDate,#region,#backupBrandNameSearch,#bestSync';

            let resetting = false;

            function loadFilteredEvents() {

                let kit_required = $('#kitRequired').is(':checked') ? 1 : 0;
                let include_nearby_events = $('#includeNearbyEvents').is(':checked') ? 1 : 0;
                let overview_needed = $('#overviewNeeded').is(':checked') ? 1 : 0;
                let not_fully_staffed = $('#notFullyStaffed').is(':checked') ? 1 : 0;
                let exclude_cancellations = $('#excludeCancellations').is(':checked') ? 1 : 0;
                let backup_brand_used = $('#filterBackupBrandUsed').is(':checked') ? 1 : 0;

                // Get date values
                let startDate = $('#startDate').val();
                let endDate = $('#endDate').val();

                $.ajax({
                    url: @json($eventsListRoute),
                    type: "GET",
                    data: {
                        _token: "{{ csrf_token() }}",
                        department_id: $('#selectDepartments').val(),
                        market_id: $('#selectMarket').val(),
                        manager_id: $('#selectManagers').val(),
                        event_type_id: $('#eventType').val(),
                        distributor_id: $('#selectDistributors').val(),
                        location: $('#location').val(),
                        varietals: $('#varietals').val(),
                        brand_id: $('#brandId').val(),
                        ambassador_name: $('#ambassadorName').val(),
                        ambassador_email: $('#ambassadorEmail').val(),
                        bill_id: $('#billTo').val(),
                        billable_name: $('#billable_name').val(),
                        payment_type: $('#paymentType').val(),
                        event_id: $('#eventId').val(),
                        location_state: $('#location_state').val(),

                        include_nearby_events: include_nearby_events,
                        overview_needed: overview_needed,
                        not_fully_staffed: not_fully_staffed,
                        kit_required: kit_required,
                        exclude_cancellations: exclude_cancellations,
                        backup_brand_used: backup_brand_used,
                        backup_brand_name: $('#backupBrandNameSearch').val(),
                        event_status: $('input[name="eventStatus"]:checked').val() || '',
                        best_sync: $('#bestSync').val(),
                        startDate: startDate,
                        endDate: endDate,
                        region: $('#region').val(),
                        show_completed: showCompleted ? 1 : 0,
                    },
                    success: function(response) {
                        $('.table-responsive').html(response.table_html);
                        $('.pagination-container').html(response.pagination);
                        $('.total-data').text(response.total);
                    },
                    error: function(xhr) {
                        console.error('Error filtering events:', xhr.responseText);
                    }
                });
            }


            // Handle pagination clicks
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();

                // Extract page number from link
                const page = $(this).attr('href').split('page=')[1];

                // Collect all filter values again
                let kit_required = $('#kitRequired').is(':checked') ? 1 : 0;
                let include_nearby_events = $('#includeNearbyEvents').is(':checked') ? 1 : 0;
                let overview_needed = $('#overviewNeeded').is(':checked') ? 1 : 0;
                let not_fully_staffed = $('#notFullyStaffed').is(':checked') ? 1 : 0;
                let exclude_cancellations = $('#excludeCancellations').is(':checked') ? 1 : 0;
                let backup_brand_used = $('#filterBackupBrandUsed').is(':checked') ? 1 : 0;
                let startDate = $('#startDate').val();
                let endDate = $('#endDate').val();

                $.ajax({
                    url: @json($eventsListRoute) + '?page=' + page,
                    type: "GET",
                    data: {
                        _token: "{{ csrf_token() }}",
                        department_id: $('#selectDepartments').val(),
                        market_id: $('#selectMarket').val(),
                        manager_id: $('#selectManagers').val(),
                        event_type_id: $('#eventType').val(),
                        distributor_id: $('#selectDistributors').val(),
                        location: $('#location').val(),
                        varietals: $('#varietals').val(),
                        brand_id: $('#brandId').val(),
                        ambassador_name: $('#ambassadorName').val(),
                        ambassador_email: $('#ambassadorEmail').val(),
                        bill_id: $('#billTo').val(),
                        billable_name: $('#billable_name').val(),
                        payment_type: $('#paymentType').val(),
                        event_id: $('#eventId').val(),
                        location_state: $('#location_state').val(),
                        include_nearby_events: include_nearby_events,
                        overview_needed: overview_needed,
                        not_fully_staffed: not_fully_staffed,
                        kit_required: kit_required,
                        exclude_cancellations: exclude_cancellations,
                        backup_brand_used: backup_brand_used,
                        backup_brand_name: $('#backupBrandNameSearch').val(),
                        event_status: $('input[name="eventStatus"]:checked').val() || '',
                        best_sync: $('#bestSync').val(),
                        startDate: startDate,
                        endDate: endDate,
                        region: $('#region').val(),
                        show_completed: showCompleted ? 1 : 0,
                    },
                    success: function(response) {
                        $('.table-responsive').html(response.table_html);
                        $('.pagination-container').html(response.pagination);
                        $('.total-data').text(response.total);
                        $('html, body').animate({
                            scrollTop: 0
                        }, 500); // optional: scroll to top
                    },
                    error: function(xhr) {
                        console.error('Pagination error:', xhr.responseText);
                    }
                });
            });


            // Toggle show/hide completed events (syncs with "Show Completed Only" radio)
            $(document).on('click', '#toggleCompleted', function() {
                showCompleted = !showCompleted;
                $('input[name="eventStatus"]').prop('checked', false);
                if (showCompleted) {
                    $('#showCompletedOnly').prop('checked', true);
                }
                const btnText = showCompleted ? 'Hide Completed' : 'Show Completed';
                $(this).find('.btn-text').text(btnText);

                loadFilteredEvents();
            });

            $(document).on('change', 'input[name="eventStatus"]', function() {
                showCompleted = $('#showCompletedOnly').is(':checked');
                const btnText = showCompleted ? 'Hide Completed' : 'Show Completed';
                $('#toggleCompleted .btn-text').text(btnText);
            });



            // Event listeners
            $(filterSelectors +
                    ', #includeNearbyEvents, #overviewNeeded, #notFullyStaffed, #kitRequired, #excludeCancellations, #filterBackupBrandUsed, #showCancellations, #pendingEventsOnly, #showCompletedOnly, input[name="eventStatus"]'
                )
                .on('change keyup', function() {
                    if (resetting) return;
                    // Add small delay for date inputs to ensure proper value capture
                    if ($(this).attr('type') === 'date') {
                        setTimeout(loadFilteredEvents, 100);
                    } else {
                        loadFilteredEvents();
                    }
                });

            $('#resetFilters').on('click', function() {
                resetting = true;
                $(filterSelectors).each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).val(null).trigger('change');
                    } else {
                        $(this).val('');
                    }
                });
                $('#includeNearbyEvents, #overviewNeeded, #notFullyStaffed, #kitRequired, #excludeCancellations, #filterBackupBrandUsed')
                    .prop('checked', false);

                $('input[name="eventStatus"]').prop('checked', false);
                showCompleted = false;
                $('#toggleCompleted .btn-text').text('Show Completed');
                resetting = false;
                loadFilteredEvents();
            });

        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hidden = document.getElementById('bestSelectedIds');
            const form = document.getElementById('bestBulkForm');

            function syncSelected() {
                const ids = Array.from(document.querySelectorAll('.event-select:checked')).map(el => el.value);
                hidden.value = ids.join(',');
            }

            document.addEventListener('change', function(e) {
                if (e.target && e.target.id === 'selectAllEvents') {
                    document.querySelectorAll('.event-select').forEach(cb => {
                        cb.checked = e.target.checked;
                    });
                    syncSelected();
                    return;
                }
                if (e.target && e.target.classList.contains('event-select')) {
                    syncSelected();
                }
            });

            if (form) {
                form.addEventListener('submit', function(e) {
                    syncSelected();
                    if (!hidden.value) {
                        e.preventDefault();
                        showToast('error', 'Please select at least one event.');
                    }
                });
            }
        });
    </script>

    <script>
        function handleStatusChange(selectEl, eventId) {
            const previousValue = selectEl.getAttribute('data-was') || '0';
            const selectedValue = selectEl.value;
            const statusUrl = @json($eventStatusPostUrl);

            if (selectedValue == '1') {
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to Complete this Event.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: statusUrl,
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                event_id: eventId,
                                status: selectedValue
                            },
                            success: function(response) {
                                if (response.budget_pending) {
                                    Swal.fire('Budget approval', response.message ||
                                        'Management has been notified. Event stays Pending until approved.',
                                        'info');
                                    selectEl.value = '0';
                                    selectEl.setAttribute('data-was', '0');
                                    return;
                                }
                                Swal.fire(
                                    'Updated!',
                                    'Event has been Completed Successfully.',
                                    'success'
                                );
                                selectEl.setAttribute('data-was', selectedValue);
                                setTimeout(function() {
                                    location.reload(true);
                                }, 2000);
                            },
                            error: function(xhr) {
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', msg, 'error');
                                selectEl.value = previousValue || '0';
                            }
                        });
                    } else {
                        selectEl.value = previousValue || '0';
                    }
                });
            } else if (selectedValue == '2') {
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to Cancel this Event.",
                    icon: "warning",
                    input: 'textarea',
                    inputLabel: 'Cancellation reason',
                    inputPlaceholder: 'Enter the cancellation reason...',
                    inputAttributes: {
                        maxlength: 2000,
                    },
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'Cancellation reason is required.';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes,"
                }).then((result) => {
                    if (result.isConfirmed) {
                        const reason = (result.value || '').trim();
                        $.ajax({
                            url: statusUrl,
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                event_id: eventId,
                                status: selectedValue,
                                cancellation_reason: reason,
                            },
                            success: function(response) {
                                selectEl.setAttribute('data-was', selectedValue);
                                Swal.fire(
                                    'Updated!',
                                    'Event has been cancelled successfully.',
                                    'success'
                                );
                                setTimeout(function() {
                                    location.reload(true);
                                }, 2000);
                            },
                            error: function(xhr) {
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', msg, 'error');
                                selectEl.value = previousValue || '0';
                            }
                        });
                    } else {
                        selectEl.value = previousValue || '0';
                    }
                });
            } else {
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to keep Pending this Event.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes,"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: statusUrl,
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                event_id: eventId,
                                status: selectedValue
                            },
                            success: function(response) {
                                selectEl.setAttribute('data-was', selectedValue);
                                Swal.fire(
                                    'Updated!',
                                    'Event has been Pending successfully.',
                                    'success'
                                );
                                setTimeout(function() {
                                    location.reload(true);
                                }, 2000);
                            },
                            error: function(xhr) {
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', msg, 'error');
                                selectEl.value = previousValue || '0';
                            }
                        });
                    } else {
                        selectEl.value = previousValue || '0';
                    }
                });
            }
        }
    </script>

    <script>
        function handleAdminStatusChange(selectEl, eventId) {
            const selectedValue = selectEl.value;
            const userRoleId = '{{ auth()->user()->role_id ?? '' }}';
            const updateUrl = userRoleId === '6' ?
                '{{ route('manager.update-event-status.event') }}' :
                '{{ route('admin.update-event-status.event') }}';

            const isCancellation = typeof selectedValue === 'string' && selectedValue.startsWith('cancelled_');

            Swal.fire({
                title: "Are you sure?",
                text: "You want to update the operational status for this event?",
                icon: "warning",
                input: isCancellation ? 'textarea' : undefined,
                inputLabel: isCancellation ? 'Cancellation reason' : undefined,
                inputPlaceholder: isCancellation ? 'Enter the cancellation reason...' : undefined,
                inputAttributes: isCancellation ? {
                    maxlength: 2000
                } : undefined,
                inputValidator: isCancellation ? (value) => {
                    if (!value || !value.trim()) {
                        return 'Cancellation reason is required.';
                    }
                } : undefined,
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes",
            }).then((result) => {
                if (!result.isConfirmed) {
                    // Reload to restore previous selection
                    location.reload(true);
                    return;
                }

                const reason = isCancellation ? ((result.value || '').trim()) : '';

                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        event_id: eventId,
                        admin_status: selectedValue,
                        cancellation_reason: reason,
                    },
                    success: function() {
                        Swal.fire('Updated!', 'Operational status updated.', 'success');
                        setTimeout(function() {
                            location.reload(true);
                        }, 1200);
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                        setTimeout(function() {
                            location.reload(true);
                        }, 1200);
                    }
                });
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            $('.btn-design.grey-color').filter(function() {
                return $(this).text().trim() === 'Export';
            }).on('click', function(e) {
                e.preventDefault();

                let exportForm = $('<form></form>');
                exportForm.attr('method', 'GET');
                exportForm.attr('action', @json($exportEventsRoute));

                const filters = {
                    'department_id': $('#selectDepartments').val(),
                    'market_id': $('#selectMarket').val(),
                    'manager_id': $('#selectManagers').val(),
                    'event_type_id': $('#eventType').val(),
                    'distributor_id': $('#selectDistributors').val(),
                    'location': $('#location').val(),
                    'varietals': $('#varietals').val(),
                    'brand_id': $('#brandId').val(),
                    'ambassador_name': $('#ambassadorName').val(),
                    'ambassador_email': $('#ambassadorEmail').val(),
                    'bill_id': $('#billTo').val(),
                    'billable_name': $('#billable_name').val(),
                    'payment_type': $('#paymentType').val(),
                    'event_id': $('#eventId').val(),
                    'startDate': $('#startDate').val(),
                    'endDate': $('#endDate').val(),
                    'include_nearby_events': $('#includeNearbyEvents').is(':checked') ? 1 : 0,
                    'overview_needed': $('#overviewNeeded').is(':checked') ? 1 : 0,
                    'not_fully_staffed': $('#notFullyStaffed').is(':checked') ? 1 : 0,
                    'kit_required': $('#kitRequired').is(':checked') ? 1 : 0,
                    'exclude_cancellations': $('#excludeCancellations').is(':checked') ? 1 : 0,
                    'backup_brand_used': $('#filterBackupBrandUsed').is(':checked') ? 1 : 0,
                    'backup_brand_name': $('#backupBrandNameSearch').val()
                };

                if ($('input[name="eventStatus"]:checked').length > 0) {
                    filters['event_status'] = $('input[name="eventStatus"]:checked').val();
                }

                for (const [key, value] of Object.entries(filters)) {
                    if (value !== null && value !== '' && value !== undefined) {
                        exportForm.append($('<input>').attr({
                            type: 'hidden',
                            name: key,
                            value: value
                        }));
                    }
                }

                $('body').append(exportForm);
                exportForm.submit();
                exportForm.remove();
            });
        });

        $(document).ready(function() {
            $(document).on('click', '.read-more-toggle', function() {
                var $this = $(this); // The clicked 'Read More' link
                var $parent = $this.closest('td'); // Find the parent <td> (or container)

                // Find the preview and full text using siblings() instead of prev() and next()
                var $preview = $parent.find('.varietals-preview');
                var $full = $parent.find('.varietals-full');

                console.log("Preview Text: ", $preview.text());
                console.log("Full Text: ", $full.text());
                console.log("Preview Display: ", $preview.css('display'));
                console.log("Full Display: ", $full.css('display'));

                if ($full.is(':visible')) {
                    $full.hide();
                    $preview.show();
                    $this.text('Read More');
                } else {
                    $full.show();
                    $preview.hide();
                    $this.text('Read Less');
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const importForm = document.getElementById('importForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const excelFile = document.getElementById('excelFile');
            const importModal = new bootstrap.Modal(document.getElementById('importModal'));
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                backdrop: 'static',
                keyboard: false
            });

            // File size validation
            excelFile.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const fileSize = file.size / 1024 / 1024; // Convert to MB
                    if (fileSize > 50) {
                        showToast('error', 'File size 50MB se kam honi chahiye!');
                        this.value = '';
                        return false;
                    }
                }
            });

            // Form submission
            importForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate file
                if (!excelFile.files[0]) {
                    showToast('error', 'Please select an Excel file!');
                    return;
                }

                // Show loading modal and hide import modal
                importModal.hide();
                loadingModal.show();

                // Disable submit button
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Importing...';

                // Create FormData and submit via AJAX
                const formData = new FormData(this);

                fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        loadingModal.hide();

                        if (data.success) {
                            let parts = [];
                            if (typeof data.total_processed !== 'undefined') {
                                parts.push(`Processed: ${data.total_processed}`);
                            }
                            if (typeof data.imported !== 'undefined')        parts.push(`Imported: ${data.imported}`);
                            if (typeof data.skipped  !== 'undefined' && data.skipped  > 0) parts.push(`Duplicates skipped: ${data.skipped}`);
                            if (typeof data.failed   !== 'undefined' && data.failed   > 0) parts.push(`Failed rows: ${data.failed}`);
                            if (typeof data.manager_missing     !== 'undefined' && data.manager_missing     > 0) parts.push(`Manager missing: ${data.manager_missing}`);
                            if (typeof data.missing_brand       !== 'undefined' && data.missing_brand       > 0) parts.push(`Missing brand: ${data.missing_brand}`);
                            if (typeof data.missing_department  !== 'undefined' && data.missing_department  > 0) parts.push(`Missing department: ${data.missing_department}`);
                            if (typeof data.missing_distributor !== 'undefined' && data.missing_distributor > 0) parts.push(`Missing distributor: ${data.missing_distributor}`);
                            if (typeof data.missing_event_type  !== 'undefined' && data.missing_event_type  > 0) parts.push(`Missing event type: ${data.missing_event_type}`);
                            if (typeof data.missing_market      !== 'undefined' && data.missing_market      > 0) parts.push(`Missing market: ${data.missing_market}`);
                            if (typeof data.missing_location    !== 'undefined' && data.missing_location    > 0) parts.push(`Missing location: ${data.missing_location}`);
                            if (typeof data.best_queued         !== 'undefined' && data.best_queued         > 0) parts.push(`BEST queued: ${data.best_queued}`);
                            const summaryMessage = parts.length > 0
                                ? 'Import complete. ' + parts.join(' | ')
                                : (data.message || 'Events successfully imported!');
                            showToast('success', summaryMessage);
                            // Reset form
                            importForm.reset();
                            // Reload page after 2 seconds
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showToast('error', data.message || 'Import failed! Please try again.');
                        }
                    })
                    .catch(error => {
                        loadingModal.hide();
                        console.error('Error:', error);
                        showToast('error',
                            'Network error! Please check your connection and try again.');
                    })
                    .finally(() => {
                        // Re-enable submit button
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i> Upload and import';
                    });
            });

            // Toast function
            function showToast(type, message) {
                const toastElement = document.getElementById(type + 'Toast');
                const messageElement = document.getElementById(type + 'Message');

                messageElement.textContent = message;

                const toast = new bootstrap.Toast(toastElement, {
                    delay: 5000
                });

                toast.show();
            }

            // Handle modal close
            document.getElementById('importModal').addEventListener('hidden.bs.modal', function() {
                importForm.reset();
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i> Upload and import';
            });
        });
    </script>

    <script>
        function updateKitDelivered(eventId, currentStatus) {
            const pathSegments = window.location.pathname.split('/');
            const prefix = pathSegments[1];

            let message = "";

            if (currentStatus == 1) {
                message = "This kit is already delivered. Do you want to change the delivery status?";
            } else {
                message = "This kit is not delivered yet. Do you want to mark it as delivered?";
            }

            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, change it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/${prefix}/events/${eventId}/toggle-kit-delivered`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Updated!',
                                    text: 'Kit delivery status has been changed.'
                                }).then(() => {
                                    location.reload(); // reload or dynamically update
                                });
                            } else {
                                Swal.fire('Error', data.error || 'Something went wrong', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error', 'Server error occurred', 'error');
                        });
                }
            });
        }
    </script>
@endpush
