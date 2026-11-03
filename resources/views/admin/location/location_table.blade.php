@if(!empty($locations) && count($locations) >0 )
    @foreach ($locations as $location)
        <tr>
            <td>
                <div>{{ $location->venue_name ?: $location->name }}</div>
                @if(!empty($location->venue_name) && $location->name !== $location->venue_name)
                    <small class="text-muted">Contact: {{ $location->name }}</small>
                @endif
            </td>
            <td>{{$location->email}}</td>
            <td>{{$location->phone}}</td>
            <td>{{$location->address}}</td>
            <td>{{$location->city}}</td>
            <td>{{$location->state?$location->state:'-'}}</td>
            <td>
                <span class="span-text @if($location->status == '1') success @else danger @endif">{{ ($location->status == '1') ? 'Active' : 'Inactive' }}</span>
            </td>
            <td>
                <div>
                    <a href="{{ route('admin.edit.location', ['id' => $location->id]) }}">
                        <button type="button" class="btn-ds btn-primary-ds">
                            <span>Edit</span>
                        </button>
                    </a>
                </div>
            </td>
        </tr>
    @endforeach
@else 
    <tr><td class="text-center" colspan="8">Data not found</td></tr>
@endif