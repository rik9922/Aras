@extends('layout.app',['page_name' => 'Event List'])
@section('content')
    <div class="container py-3">
        <div class="d-grid">
            <div class="h5">Events : <span class="total-data">@if(!empty($events) && count($events) > 0) {{count($events)}} @endif</span> </div>
            <div class="fixed-border"></div>
            <small>Event Hours: <b>{{ $getTotalHoursClient }}</b>; Ambassador Hours: <b>{{ $getTotalHoursClient }}</b></small>
        </div>
        <div class="page-card-design">
            
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-design" type="button" data-bs-toggle="collapse" data-bs-target="#search"
                    aria-expanded="false" aria-controls="search" id="searchToggleBtn">
                    <i class="fa-solid fa-caret-up" id="searchIcon"></i>
                    <span class="px-2">Search</span>
                </button>
                <a href="{{ route('client.export.events') }}" class="client-export-btn">
                    <button type="button" class="btn btn-design grey-color">Export</button>
                </a>
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
                                                <option value="{{$department->id}}">{{$department->name}}</option>
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
                                                <option value="{{$market->id}}">{{$market->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="eventType" class="form-label">Event Type</label>
                                        <select class="form-select shadow-none"id="eventType"  name="venue_id"
                                            aria-label="Event Type">
                                            <option value="">Select Event Type</option>
                                            @foreach ($venueTypes as $venueType)
                                                <option value="{{$venueType->id}}">{{$venueType->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="selectDistributors" class="form-label">Distributors</label>
                                        <select class="form-select shadow-none" id="selectDistributors" name="distributor_id"
                                            aria-label="Distributors">
                                            <option value="">Select Distributor</option>
                                            @foreach ($distributors as $distributor)
                                                <option value="{{$distributor->id}}">{{$distributor->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="eventId" class="form-label">Event Id</label>
                                        <select class="form-select shadow-none" id="eventId"
                                            aria-label="Select Event Id" name="event_id">
                                            <option value="">Select Event Id</option>
                                            @foreach ($events as $event)
                                                <option value="{{$event->id}}">{{$event->event_name. '('.$event->id.')'}}</option>
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
                                            @foreach ($locations as $location)
                                                <option value="{{$location->id}}">{{$location->name}}</option>
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
                                                <option value="{{$location->id}}">{{$location->name}}</option>
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
                                                <option value="{{$ambassador->id}}">{{$ambassador->name}}</option>
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
                                                <option value="{{$ambassador->id}}">{{$ambassador->email}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="varietals" class="form-label">Varietals</label>
                                        <input type="text" class="form-control shadow-none" id="varietals" name="varietals"
                                            id="varietals">
                                    </div>
                                </div>
                                
                                <div class="col-md-6 col-xl-3">
                                    <div class="mb-3">
                                        <label for="brandId" class="form-label">Brand</label>
                                        <select class="form-select shadow-none" id="brandId" name="brand_id"
                                            aria-label="Select Types">
                                            <option value="">Select Brand</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{$brand->id}}">{{$brand->brand_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="overviewNeeded">
                                    <label class="form-check-label" for="overviewNeeded">
                                        Overview Needed
                                    </label>
                                </div>
                                
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="showCancellations">
                                    <label class="form-check-label" for="showCancellations">
                                        Show Cancellations
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="pendingEventsOnly">
                                    <label class="form-check-label" for="pendingEventsOnly">
                                        Pending Events Only
                                    </label>
                                </div>
                                <div class="gap-2">
                                    <button type="button" id="resetFilter" class="btn btn-design grey-color mt-3">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Day</th>
                            <th>
                                <div style="width: 80px;">Date</div>
                            </th>
                            <th>
                                <div style="width: 100px;">Time</div>
                            </th>
                            <th>Brand</th>
                            <th>
                                <div style="width: 200px;">Varietals</div>
                            </th>
                            <th>Department</th>
                            <th>Distributor</th>
                            <th>Location</th>
                            <th>Ambassadors</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($events) && count($events) > 0)
                            @foreach ($events as $key=>$event)
                                <tr>
                                    <td>{{$key+1}}</td>
                                    <td>{{$event->event_name ?? '-'}}</td>
                                    <td>{{$event->Dayname}}</td>
                                    <td>{{$event->FormattedDate}}</td>
                                    <td>{!!$event->FormattedTime!!}</td>
                                    <td>{{$event->brand?$event->brand->brand_name:'-'}}</td>
                                    <td>{{$event->varietals}}</td>
                                    <td>{{$event->department?$event->department->name:'-'}}</td>
                                    <td>{{$event->distributor?$event->distributor->name:'-'}}</td>
                                    <td>{{$event->location ? ($event->location->address ?? $event->location->venue_name ?? '-') : '-'}}</td>
                                    <td class="text-bold">{!!ambassadorName($event->id)!!}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{route('client.show-event',['id'=>$event->id])}}">
                                                <button type="button" class="btn btn-design">
                                                    <span>View</span>
                                                </button>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                
                            @endforeach
                        @else
                            <tr><td colspan="13" class="text-center">Data not found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
    <script>
        $(document).ready(function() {
            updateExportButton();
            
            function loadFilteredEvents() {
                $.ajax({
                    url: "{{ route('client.filter.events') }}",
                    type: "POST",
                    data: getFilterData(),
                    beforeSend: function() {
                    },
                    success: function(response) {
                        $('.table-responsive').html(response.html);
                        
                        updateExportButton();
                    },
                    error: function(xhr) {
                        console.error('Error filtering events:', xhr.responseText);
                    },
                    complete: function() {
                    }
                });
            }
            
            function getFilterData() {
                return {
                    _token: "{{ csrf_token() }}",
                    department_id: $('#selectDepartments').val(),
                    market_id: $('#selectMarket').val(),
                    venue_id: $('#eventType').val(),
                    distributor_id: $('#selectDistributors').val(),
                    location_id: $('#location_id').val(),
                    varietals: $('#varietals').val(),
                    brand_id: $('#brandId').val(),
                    ambassador_name_id: $('#ambassadorName').val(),
                    ambassador_email_id: $('#ambassadorEmail').val(),
                    event_id: $('#eventId').val()
                };
            }
            
            function updateExportButton() {
                let filters = getFilterData();
                let exportRoute = "{{ route('client.export.events') }}";
                
                let queryString = Object.keys(filters)
                    .filter(key => filters[key] !== '' && filters[key] !== null && key !== '_token')
                    .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(filters[key])}`)
                    .join('&');
                
                let exportUrl = exportRoute + (queryString ? '?' + queryString : '');
                $('.client-export-btn').attr('href', exportUrl);
            }

            $('#selectDepartments, #selectMarket, #eventType, #selectDistributors, #location_id, #varietals, #brandId, #ambassadorEmail, #ambassadorName, #eventId').on('change keyup', function() {
                loadFilteredEvents();
            });

            $('#resetFilter').click(function() {
                $('#selectDepartments').val('');
                $('#selectMarket').val('');
                $('#eventType').val('');
                $('#selectDistributors').val('');
                $('#location_id').val('');
                $('#varietals').val('');
                $('#brandId').val('');
                $('#ambassadorName').val('');
                $('#ambassadorEmail').val('');
                $('#eventId').val('');
                
                loadFilteredEvents();
            });
        });
    </script>
@endsection