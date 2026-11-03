@extends('layout.app', ['page_name' => 'Brands List'])
@section('content')
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Events: <span class="total-data">@if(!empty($events) && count($events) > 0) {{count($events)}} @endif</span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <small>Event Hours: <b>{{ $totalBrandEventHours }}</b>; Ambassador Hours: <b>{{ $totalBrandEventHours }}</b></small>
            <div class="d-flex gap-3 justify-content-end">
                <a href="mailto:bookwoodmans@araspromotions.com?subject=For Request Event&body=Your email body content here"><button type="button" class="btn btn-design grey-color">Request Event</button></a>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 col-xl-3">
                    <div class="mb-3">
                        <input class="form-control shadow-none" type="date" name="date" id="date">
                    </div>
                </div>
                {{-- <div class="col-md-6 col-xl-3">
                    <div class="mb-3">
                        <input class="form-control shadow-none" type="text" name="location" id="location" placeholder="Enter a location..">
                    </div>
                </div> --}}
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 20%;" style="width:10%;">Day</th>
                            <th style="width: 30%;">
                                <div>Date</div>
                            </th>
                            <th style="width: 40%;">
                                <div>Time</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($events) && count($events) > 0)
                            @foreach ($events as $event)
                                <tr>
                                    <td>{{$event->id}}</td>
                                    <td>{{$event->Dayname}}</td>
                                    <td>{{$event->FormattedDate}}</td>
                                    <td>{!!$event->FormattedTime!!}</td>                                    
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="14" class="text-center">Data not found.</td></tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3">
                    {{ $events->links('vendor.pagination.custom') }}
                </div>
            </div>     
        </div>
    </div>
@endsection
<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
<script>
    $(document).ready(function() {
        $('#date, #location').on('input', function() {
            let date = $('#date').val();
            
            $.ajax({
                url: "{{ route('events.getEvents') }}",
                type: 'POST',
                data: {
                    date: date,
                    // location: location,
                    _token:'{{csrf_token()}}'
                },
                success: function(response) {
                    console.log("response.events",response);
                    if (response && response.events) {
                        var eventsData = response.events || response.events;
                        
                        var rows = '';
                        if (eventsData && eventsData.length > 0) {
                            $.each(eventsData, function(index, event) {
                                rows += '<tr>';
                                rows += '<td>' + (index + 1) + '</td>';
                                rows += '<td>' + event.Dayname + '</td>';
                                rows += '<td>' + event.FormattedDate + '</td>';
                                rows += '<td>' + event.FormattedTime + '</td>';
                                rows += '</tr>';
                            });
                            $('.total-data').text(eventsData.length);
                        } else {
                            rows = '<tr><td colspan="4" class="text-center">Data not found</td></tr>';
                            $('.total-data').text('0');
                        }
                        $('table tbody').html(rows);
                    } else {
                        $('table tbody').html('<tr><td colspan="4" class="text-center">Error loading data</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        });
    });
</script>