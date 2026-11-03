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
                @if ($event->recap->canBeEditedBy(auth()->user()))
<a href="{{ route('ambassador.edit-recap', ['id' => $event->recap->id]) }}">
                    <button class="btn btn-design">Edit</button>
                </a>
@endif
            @else
                <span class="text-muted small">Recap not submitted yet</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td class="text-center" colspan="6">Submitted Recap Not Found.</td>
    </tr>
@endforelse
