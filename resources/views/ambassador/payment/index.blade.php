@extends('layout.app', ['page_name' => 'Payment'])

@section('content')

<div class="container py-3">
    <div class="card page-contents">
        <div class="h5 text-uppercase">Payments</div>
    </div>

    <div class="page-card-design">
        <div class="col-md-12 col-xl-8">
            <div class="mb-3">
                <label for="selectDateRange" class="form-label fw-bold py-2">Search for a Date Range or View all Below</label>
                <div class="d-flex align-items-center">
                    <input type="date" class="form-control" id="startDate" aria-label="selectDateRange"> 
                    <div class="px-2 px-4 fw-medium">to</div>
                    <input type="date" class="form-control" id="endDate" aria-label="selectDateRange"> 
                    <button class="btn btn-design ms-3" id="resetButton">Reset</button>
                </div>
            </div>
        </div>


        <div class="table-responsive px-0">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Start</th>
                        <th>End</th>
                        <th>Late</th>
                        <th>Venue</th>
                        <th>Brand</th>
                        <th>Event Pay</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Created/Saved</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody id="pay-forecast">
                    @forelse ($recaps as $recap)
                        <tr>
                            <td>
                                {{ $recap->start_time }}
                            </td>
                            <td>
                                {{ $recap->end_time }}
                            </td>
                            <td>
                                -
                            </td>
                            <td>
                                {{$recap->location?$recap->location->address:'-'}}
                            </td>
                            <td>
                                {{$recap->brand?$recap->brand->brand_name:'-'}}
                            </td>
                            <td>
                                @if($recap->assignAmbassadors)
                                ${{ $wage + $recap->assignAmbassadors->pinata_amount + ($recap->assignAmbassadors->override_pay ?? 0) }}
                                @endif
                            </td>
                            <td>
                                {{$recap->client?$recap->client->first_name:'-'}}
                            </td>
                            <td>
                                {{$recap->client?$recap->client->last_name:'-'}}
                            </td>
                            <td>
                                {{$recap->recap?$recap->recap->created_at:'-'}}
                            </td>
                            <td>
                                @if($recap->recap)
                                    <a href="{{route('ambassador.view-recap',['id'=>$recap->id])}}">
                                        <button class="btn btn-design">View</button>
                                    </a>
                                @else
                                    <span class="text-muted small">Recap not submitted yet</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center" colspan="10">Submitted Recap Not Found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Trigger AJAX request when search or date range is changed
        $('#search, #marketId, #startDate, #endDate').on('change keyup', function() {
            let startDate = $('#startDate').val();
            let endDate = $('#endDate').val();
    
            // Fetch filtered data with AJAX
            $.ajax({
                url: '{{ route("ambassador.pay-forecast") }}',
                method: 'GET',
                data: {
                    startDate: startDate,
                    endDate: endDate
                },
                success: function(data) {
                    // Update the table with the new filtered data
                    $('#pay-forecast').html(data.html);
                }
            });
        });
    
        // Reset button functionality
        $('#resetButton').on('click', function() {
            // Clear the date inputs and trigger AJAX to reset table
            $('#startDate').val('');
            $('#endDate').val('');
    
            // Fetch all data without filters
            $.ajax({
                url: '{{ route("ambassador.pay-forecast") }}',
                method: 'GET',
                data: {
                    reset: 'all',
                },
                success: function(data) {
                    // Reset the table content
                    $('#pay-forecast').html(data.html);
                }
            });
        });
    });
</script>

@endsection
