@forelse ($events as $event)
<tr>
    <td>{!! $event->FormattedTime !!}</td>
    <td>{{ $event->brand ? $event->brand->brand_name : '-' }}</td>
    <td>{{ $event->event_name ?? '-' }}</td>
    <td>{{ $event->location ? $event->location->city : '-' }}</td>
    <td>{!! ambassadorName($event->id) !!}</td>
    <td>
        @php($draft = $draftsByEventId[$event->id] ?? null)
        @if($draft)
            <a href="{{ route('ambassador.edit-recap', ['id' => $draft->id]) }}">
                <button class="btn btn-design">Continue Draft</button>
            </a>
        @else
            <a href="{{route('ambassador.create-recap',['id'=>$event->id])}}">
                <button class="btn btn-design">Incomplete</button>
            </a>
        @endif
    </td>
</tr>
@empty
<tr>
    <td class="text-center" colspan="6">Event Not Found</td>
</tr>
@endforelse