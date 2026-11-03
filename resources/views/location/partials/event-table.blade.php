<table class="table table-striped align-middle">
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
                            <a href="{{route('location.show-event',['id'=>$event->id])}}">
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