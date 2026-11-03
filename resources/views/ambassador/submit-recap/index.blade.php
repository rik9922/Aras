@extends('layout.app', ['page_name' => 'Submit Recap'])

@section('content')
    <div class="container">
        <div class="h5 mt-2">Submitted Recaps :
            <span class="total-data">
                @if (!empty($events) && count($events) > 0)
                    {{ count($events) }}
                @endif
            </span>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row pt-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <input type="text" class="form-control field-style" name="search" id="search"
                            placeholder="Search for a Brand, Location or City.">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <select class="form-select field-style" name="brand_id" id="marketId" aria-label="Select Market">
                            <option selected value="">Select Brands</option>
                            @foreach ($markets as $market)
                                <option value="{{$market->id}}">{{$market->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-responsive px-0">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div>Date</div>
                            </th>
                            <th>
                                <div style="width: 180px">Brand</div>
                            </th>
                            <th>
                                <div class="text-nowrap">Event Name</div>
                            </th>
                            <th>
                                <div>City</div>
                            </th>
                            <th>
                                <div>Ambassador(s)</div>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="submitted-recap">
                        @forelse ($events as $event)
                            <tr>
                                <td>
                                    <div>{{ $event->Dayname }}</div>
                                    <div>{{ $event->FormattedDate }}</div>
                                </td>
                                <td>
                                    {{$event->brand?$event->brand->brand_name:'-'}}
                                </td>
                                <td>
                                    {{$event->event_name??'-'}}
                                </td>
                                <td>
                                    {{$event->location?$event->location->city:'-'}}
                                </td>
                                <td>
                                    {!!ambassadorName($event->id)!!}
                                </td>
                                <td>
                                    @if($event->recap)
                                        <a href="{{route('ambassador.view-recap',['id'=>$event->id])}}">
                                            <button class="btn btn-design">View</button>
                                        </a>
                                        <a href="{{ route('ambassador.edit-recap', ['id' => $event->recap->id]) }}">
                                            <button class="btn btn-design">Edit</button>
                                        </a>
                                    @else
                                        <span class="text-muted small">Recap not submitted yet</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center" colspan="6">Submitted Recap Not Found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
    <script>
        $(document).ready(function() {
            $('#search, #marketId').on('change keyup', function() {
                let search = $('#search').val();
                let marketId = $('#marketId').val();

                // Fetch filtered data with AJAX
                $.ajax({
                    url: '{{ route("ambassador.submitted-recap") }}',
                    method: 'GET',
                    data: {
                        search: search,
                        marketId: marketId
                    },
                    success: function(data) {
                        // Update the table with the new filtered data
                        $('#submitted-recap').html(data.html);
                    }
                });
            });
        });
    </script>
@endsection
