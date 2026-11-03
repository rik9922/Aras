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
            ${{ $wage + $recap->assignAmbassadors->pinata_amount + ($recap->assignAmbassadors->override_pay ?? 0) }}
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
    <tr><td class="text-center" colspan="10">Payment Not Found.</td></tr>
@endforelse
