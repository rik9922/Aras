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
                        if (\Carbon\Carbon::parse($checkedInAt)->greaterThan($graceCutoff)) {
                            $lateLabel = 'Late';
                        }
                    } elseif (\Carbon\Carbon::now()->greaterThan($graceCutoff)) {
                        $lateLabel = 'Late';
                    }
                }
            @endphp
            @if ($lateLabel === 'No Show')
                <span class="badge bg-dark">No Show</span>
            @elseif ($lateLabel === 'Late')
                <span class="badge bg-danger">Late</span>
            @else
                &mdash;
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
    <tr><td class="text-center" colspan="9">Pay Forecast Not Found.</td></tr>
@endforelse
