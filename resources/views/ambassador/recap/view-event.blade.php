@extends('layout.app', ['page_name' => 'View Event'])

@section('content')
    <div class="container py-3">
        <div class="card p-3 border-0 shadow">
            <div class="h5 text-uppercase mb-2">Event Details</div><hr>
            <div class="row">
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Event Name</div>
                    <div class="fw-medium">{{ $event->event_name?? '-' }}</div>
                </div>
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Day</div>
                    <div class="fw-medium">{{ $event->Dayname }}</div>
                </div>
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Date</div>
                    <div class="fw-medium">{{ $event->FormattedDate }}</div>
                </div>
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Time/Total Duration</div>
                    <div class="fw-medium">{!! $event->FormattedTime !!}</div>
                </div>

            </div>
            <div class="row">
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Brand</div>
                    <div class="fw-medium">{{ $event->brand ? $event->brand->brand_name : '-' }}</div>
                </div>
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Varietals</div>
                    <div class="fw-medium">{{ $event->varietals }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Kit</div>
                    <div class="fw-medium">{{ $event->kit == 1 ? 'Yes' : 'No' }}</div>
                </div>
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Department</div>
                    <div class="fw-medium">{{ $event->department ? $event->department->name : '-' }}</div>
                </div>
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Distributor</div>
                    <div class="fw-medium">{{ $event->distributor ? $event->distributor->name : '-' }}</div>
                </div>
                <div class="col-sm-4 col-xl-3 py-2">
                    <div class="fw-bold">Location</div>
                    <div class="fw-medium">
                        {{ $event->location ? ($event->location->name ?: ($event->location->address ?: '-')) : '-' }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4 py-2">
                    <div class="fw-bold">Assigned</div>
                    <div class="fw-medium">{!! ambassadorName($event->id) !!}</div>
                </div>
                <div class="col-sm-4 py-2">
                    <div class="fw-bold">Status</div>
                    <div class="fw-medium">
                        @if ($event->event_status == '1')
                            Completed
                        @else
                            Pending
                        @endif
                    </div>
                </div>
            </div>

            @if (!empty($event->eventAttachments) && count($event->eventAttachments) > 0)
                <div class="h5 text-uppercase mb-2">Event Attachements</div>
                <div class="row d-flex flex-wrap gap-2">
                    @foreach ($event->eventAttachments as $attachment)
                        @if (Str::startsWith($attachment->type, 'image/'))
                            <img src="{{ asset($attachment->filename) }}" class="preview-media"
                                style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                        @elseif (Str::startsWith($attachment->type, 'video/'))
                            <video class="preview-media" controls
                                style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                                <source src="{{ asset($attachment->filename) }}" type="{{ $attachment->type }}">
                            </video>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="d-flex gap-2 py-2">
                <button type="button" class="btn btn-design" onclick="window.history.go(-1)">
                    <span class="text-nowrap">Back</span>
                </button>
            </div>
        </div>
    </div>
@endsection
