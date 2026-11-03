@extends('layout.app', ['page_name' => 'Recap'])

@section('content')
    @php
        $currentUser = Auth::user();
        $roleId = (string) ($currentUser->role_id ?? '');
    @endphp
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Recaps : <span class="total-data">{{ $recaps->total() }}</span></div>
        </div>
        <div class="fixed-border"></div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div class="page-card-design">
            <div class="d-flex justify-content-end gap-2">
                <button class="btn-ds btn-primary-ds" type="button" data-bs-toggle="collapse" data-bs-target="#search"
                    aria-expanded="false" aria-controls="search" id="searchToggleBtn">
                    <i class="fa-solid fa-caret-up" id="searchIcon"></i>
                    <span class="px-2">Search</span>
                </button>
                @if ($roleId === '1')
                    <a href="{{ route('admin.export-recap-events') }}" class="export-btn">
                        <button type="button" class="btn-ds btn-secondary-ds">Export</button>
                    </a>
                @elseif ($roleId === '6')
                    <a href="{{ route('manager.export-recap-events') }}" class="export-btn">
                        <button type="button" class="btn-ds btn-secondary-ds">Export</button>
                    </a>
                @elseif ($roleId === '3')
                    <a href="{{ route('client.export.recap-events') }}" class="export-btn">
                        <button type="button" class="btn-ds btn-secondary-ds">Export</button>
                    </a>
                @endif
            </div>
            <div class="collapse" id="search">
                <div class="card list-form-card mt-3">
                    <div class="row p-3">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="selectDivision" class="form-label">Select Departments</label>
                                        <select class="form-select shadow-none" id="selectDepartments"
                                            aria-label="Select Departments">
                                            <option value="">Select Departments</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="selectMarket" class="form-label">Select Market(s)</label>
                                        <select class="form-select shadow-none" id="selectMarket" name="market_id"
                                            aria-label="Select Market(s)">
                                            <option value="">Select Market</option>
                                            @foreach ($markets as $market)
                                                <option value="{{ $market->id }}">{{ $market->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12 col-xl-6">
                                    <div class="mb-3">
                                        <label for="selectDivision" class="form-label">Select Date Range</label>
                                        <div class="d-flex align-items-center">
                                            <input type="date" class="form-control" id="endDate"
                                                aria-label="selectDateRange">
                                            <div class="px-2">to</div>
                                            <input type="date" class="form-control" id="startDate"
                                                aria-label="selectDateRange">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="selectManagers" class="form-label">Manager</label>
                                        <select class="form-select shadow-none" id="selectManagers" name="manager_id"
                                            aria-label="Select Manager">
                                            <option value="">Select Manager</option>
                                            @foreach ($managers as $manager)
                                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="eventType" class="form-label">Event Type</label>
                                        <select class="form-select shadow-none"id="eventType" name="venue_id"
                                            aria-label="Event Type">
                                            <option value="">Select Event Type</option>
                                            @foreach ($venueTypes as $venueType)
                                                <option value="{{ $venueType->id }}">{{ $venueType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="selectDistributors" class="form-label">Distributors</label>
                                        <select class="form-select shadow-none" id="selectDistributors"
                                            name="distributor_id" aria-label="Distributors">
                                            <option value="">Select Distributor</option>
                                            @foreach ($distributors as $distributor)
                                                <option value="{{ $distributor->id }}">{{ $distributor->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="eventId" class="form-label">Event Id</label>
                                        <select class="form-select shadow-none" id="eventId" aria-label="Select Event Id"
                                            name="event_id">
                                            <option value="">Select Event Id</option>
                                            @foreach ($events as $event)
                                                <option value="{{ $event->id }}">
                                                    {{ $event->event_name . '(' . $event->id . ')' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="locationState" class="form-label">Location State</label>
                                        <select class="form-select shadow-none" id="locationState"
                                            aria-label="Location State" name="location_state">
                                            <option value="">Select Location State</option>
                                            @foreach ($states as $location)
                                                <option value="{{ $location->id }}">{{ $location->state }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="location_id" class="form-label">Select Location</label>
                                        <select class="form-select shadow-none" name="location_id" id="location_id">
                                            <option value="">Select Location</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="ambassadorName" class="form-label">Ambassador Name</label>
                                        <select class="form-select shadow-none" id="ambassadorName">
                                            <option value="">Select Ambassador Name</option>
                                            @foreach ($ambassadors as $ambassador)
                                                <option value="{{ $ambassador->id }}">{{ $ambassador->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="ambassadorEmail" class="form-label">Ambassador Email</label>
                                        <select class="form-select shadow-none" id="ambassadorEmail"
                                            aria-label="Ambassador Email">
                                            <option value="">Select Ambassador Email</option>
                                            @foreach ($ambassadors as $ambassador)
                                                <option value="{{ $ambassador->id }}">{{ $ambassador->email }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="brandId" class="form-label">Brand</label>
                                        <select class="form-select shadow-none" id="brandId" name="brand_id"
                                            aria-label="Select Types">
                                            <option value="">Select Brand</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="region" class="form-label">Region</label>
                                        <div class="d-flex gap-4">
                                            <select class="form-select shadow-none  @error('region') is-invalid @enderror"
                                                id="region" name="region" id="region">
                                                <option value="" selected>Select Region</option>
                                                <option value="Southeast"
                                                    {{ old('region') == 'Southeast' ? 'selected' : '' }}>Southeast</option>
                                                <option value="Midwest"
                                                    {{ old('region') == 'Midwest' ? 'selected' : '' }}>Midwest</option>
                                            </select>
                                        </div>
                                        @error('region')
                                            <div class="text-danger w-100">{{ $message }}</div>
                                        @enderror
                                    </div>
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
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check my-2">
                                <input class="form-check-input shadow-none" type="checkbox" value=""
                                    id="overviewNeeded">
                                <label class="form-check-label" for="overviewNeeded">
                                    Overview Needed
                                </label>
                            </div>
                            <div class="gap-2">
                                <button type="button" id="resetFilter"
                                    class="btn-ds btn-secondary-ds mt-3">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
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
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($recaps) && count($recaps) > 0)
                            @foreach ($recaps as $key => $recap)
                                <tr>
                                    <td>{{ $recap->event && $recap->event->brand ? $recap->event->brand->brand_name : '-' }}
                                    </td>
                                    <td>{{ $recap->event ? $recap->event->event_name ?? '-' : '-' }}</td>
                                    <td>{{ $recap->event && $recap->event->location ? $recap->event->location->city : '-' }}
                                    </td>
                                    <td>{{ $recap->event ? $recap->event->FormattedDate : '-' }}</td>
                                    <td class="text-bold">{!! ambassadorName($recap->event ? $recap->event->id : 'N/A') !!}</td>
                                    <td>
                                        @if ($recap->is_approved === false)
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($recap->is_approved === true)
                                            <span class="badge bg-success">Approved</span>
                                        @endif
                                    </td>

                                    <td>
                                        @include('admin.recap.partials.recap-list-actions', [
                                            'recapId' => $recap->id,
                                            'eventId' => $recap->event?->id,
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center">Data not found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3">
                    {{ $recaps->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {

            $("#selectDepartments, #selectMarket, #selectManagers, #eventType, #selectDistributors, #eventId, #locationState, #location_id, #ambassadorName, #ambassadorEmail, #brandId, #region, #billTo")
                .select2();

            const userRole = "{{ $role }}";
            let filterUrl = "";
            let filterType = "GET";

            if (userRole == '3') {
                filterUrl = "{{ route('client.filter.recap-events') }}";
                filterType = "POST";
            } else if (userRole == '6') {
                filterUrl = "{{ route('manager.recaps') }}";
            } else {
                filterUrl = "{{ route('admin.recaps') }}";
            }

            updateExportButton();

            function loadFilteredEvents() {
                $.ajax({
                    url: filterUrl,
                    type: filterType,
                    data: getFilterData(),
                    beforeSend: function() {},
                    success: function(response) {
                        $('.table-responsive').html(response.html);
                        $('.total-data').text(response.count);

                        updateExportButton();
                    },
                    error: function(xhr) {
                        console.error('Error filtering events:', xhr.responseText);
                    },
                    complete: function() {}
                });
            }

            function getFilterData() {
                return {
                    _token: "{{ csrf_token() }}",
                    department_id: $('#selectDepartments').val(),
                    market_id: $('#selectMarket').val(),
                    manager_id: $('#selectManagers').val(),
                    venue_id: $('#eventType').val(),
                    distributor_id: $('#selectDistributors').val(),
                    location_id: $('#location_id').val(),
                    location_state: $('#locationState').val(),
                    brand_id: $('#brandId').val(),
                    ambassador_name_id: $('#ambassadorName').val(),
                    ambassador_email_id: $('#ambassadorEmail').val(),
                    event_id: $('#eventId').val(),
                    startDate: $('#startDate').val(),
                    endDate: $('#endDate').val(),
                    overview_needed: $('#overviewNeeded').is(':checked') ? 1 : 0,
                    region: $('#region').val(),
                    bill_id: $('#billTo').val(),
                };
            }

            function updateExportButton() {
                let filters = getFilterData();
                let exportRoute = userRole == '3'
                    ? "{{ route('client.export.recap-events') }}"
                    : (userRole == '6'
                        ? "{{ route('manager.export-recap-events') }}"
                        : "{{ route('admin.export-recap-events') }}");

                let queryString = Object.keys(filters)
                    .filter(key => filters[key] !== '' && filters[key] !== null && key !== '_token')
                    .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(filters[key])}`)
                    .join('&');

                let exportUrl = exportRoute + (queryString ? '?' + queryString : '');
                $('.export-btn').attr('href', exportUrl);
            }

            $('#selectDepartments, #selectMarket, #selectManagers, #eventType, #selectDistributors, #location_id, #locationState, #brandId, #ambassadorEmail, #ambassadorName, #eventId, #startDate, #endDate, #overviewNeeded, #region, #billTo')
                .on('change keyup', function() {
                    loadFilteredEvents();
                });

            $('#resetFilter').click(function() {
                $('#selectDepartments').val('');
                $('#selectMarket').val('');
                $('#selectManagers').val('');
                $('#eventType').val('');
                $('#selectDistributors').val('');
                $('#location_id').val('');
                $('#locationState').val('');
                $('#brandId').val('');
                $('#ambassadorName').val('');
                $('#ambassadorEmail').val('');
                $('#eventId').val('');
                $('#startDate').val('');
                $('#endDate').val('');
                $('#region').val('');
                $('#billTo').val('');
                $('#overviewNeeded').prop('checked', false);

                loadFilteredEvents();
            });
        });
    </script>
@endsection
