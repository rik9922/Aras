@extends('layout.app', ['page_name' => 'Pay Forecast'])

@section('content')

<div class="container py-3">
    <div class="card page-contents">
        <div class="h5 text-uppercase">Detailed Payroll Report: ${{$totalPay}} ({{$getEventCont}} Events - {{$totalHoursFormatted}})</div>
        <div class="content-box-grey d-flex align-items-center gap-2 p-2">
            <div class="fs-5"><i class="fa-solid fa-circle-info"></i></div>
            <div>Here you can view a detailed payroll estimate for all events assigned to you.</div>
        </div>
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
                        <th>Status</th>
                        <th>Event Pay</th>
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
                                @php
                                    $assignment  = $assignmentByEventId[$recap->id] ?? null;
                                    $checkedInAt = $assignment?->event_date_time;
                                    $startTime   = $recap->start_time;
                                    $lateLabel   = null;

                                    if ($assignment && $assignment->is_no_show) {
                                        $lateLabel = 'No Show';
                                    } elseif ($startTime) {
                                        $graceCutoff = \Carbon\Carbon::parse($startTime)->addMinutes($graceMinutes);
                                        if ($checkedInAt) {
                                            // Checked in — was it after the grace period?
                                            if (\Carbon\Carbon::parse($checkedInAt)->greaterThan($graceCutoff)) {
                                                $lateLabel = 'Late';
                                            }
                                        } elseif (\Carbon\Carbon::now()->greaterThan($graceCutoff)) {
                                            // Never checked in and grace period already passed
                                            $lateLabel = 'Late';
                                        }
                                    }
                                @endphp
                                @if ($lateLabel === 'No Show')
                                    <span class="badge bg-dark">No Show</span>
                                @elseif ($lateLabel === 'Late')
                                    <span class="badge bg-danger">Late</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                {{$recap->location?$recap->location->address:'-'}}
                            </td>
                            <td>
                                {{$recap->brand?$recap->brand->brand_name:'-'}}
                            </td>
                            <td>
                                @php
                                    $status = $recapStatusByEventId[$recap->id] ?? 'Not Submitted';
                                    $label = $status === 'Submitted' ? 'Pending' : $status;

                                    $badgeClass = match ($label) {
                                        'Rejected' => 'bg-danger',
                                        'Approved' => 'bg-success',
                                        'Pending' => 'bg-warning text-dark',
                                        default => 'bg-secondary', // Draft / Not Submitted
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                            </td>
                            <td>
                                @php
                                    // Calculate event duration in minutes
                                    $minutes = 0;
                                    if ($recap->start_time && $recap->end_time) {
                                        $start = \Carbon\Carbon::parse($recap->start_time);
                                        $end = \Carbon\Carbon::parse($recap->end_time);
                                        $minutes = $start->diffInMinutes($end);
                                    }
                            
                                    $overridePay = optional($recap->assignAmbassadors)->override_pay + optional($recap->assignAmbassadors)->pinata_amount;
                                    $wagePerMinute = $wage / 60;
                                    $wageForTime = $minutes * $wagePerMinute;
                            
                                    if ($overridePay) {
                                        $payAmount = $overridePay + $wageForTime;
                                    } else {
                                        $payAmount = $wageForTime;
                                    }
                                @endphp
                                ${{ number_format($payAmount, 2) }}
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
                        <tr><td class="text-center" colspan="9">Submitted Recap Not Found.</td></tr>
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
