@extends('layout.app', ['page_name' => 'Create Recap'])

@section('content')
    <div class="container py-3">
        <div class="h5 mt-2">Create Recaps: <span class="total-data">
                @if (!empty($events) && count($events) > 0)
                    {{ count($events) }}
                @endif
            </span>
        </div>
        <div class="fixed-border"></div>
        @include('ambassador.recap.partials.quickbooks-requirements-card')
        <div class="page-card-design">
            <div class="row">
                <div class="col-8">
                    <div class="content-box-grey p-3">
                        <div class="h5">Instructions</div>
                        <div>To begin, select an unsubmitted recap from below.</div>
                        <div class="mt-2 small text-muted">
                            Recaps are expected to be submitted within
                            <strong>24 hours</strong> after your event ends. Late recaps may result in your payout
                            being reduced to <strong>$0</strong>.
                        </div>
                        {{-- Bug 10 (MC cmon1a7u4005d3r1h5cdct2vh): testers were confused
                            because submitted recaps don't appear here — they live on
                            "Submitted Recap". Make that crystal clear. --}}
                        <div class="mt-2 small">
                            Already submitted a recap? View it under
                            <a href="{{ route('ambassador.submitted-recap') }}"><strong>Submitted Recap</strong></a>.
                        </div>
                        <div class="mt-2 small">
                            Saved a recap draft? Continue it under
                            <a href="{{ route('ambassador.recap-drafts') }}"><strong>Saved Drafts</strong></a>.
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="mb-3">
                        <select class="form-select field-style" id="recap-select">
                            <option selected value="all">Select unsubmitted recap</option>
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}">{{ $event->event_name ?? $event->brand->brand_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-responsive px-0">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div>Date</div>
                            </th>
                            <th>
                                <div style="width: 180px">Brand</div>
                            </th>
                            <th>
                                <div style="width: 180px">Event Name</div>
                            </th>
                            <th>
                                <div class="text-nowrap">City</div>
                            </th>
                            <th>
                                <div>Ambassador(s)</div>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="recap-table">
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
                                            <button class="btn btn-design create-recap">Continue Draft</button>
                                        </a>
                                    @else
                                        <a href="{{ route('ambassador.create-recap', ['id' => $event->id]) }}">
                                            <button class="btn btn-design create-recap">Incomplete</button>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="6">
                                    No unsubmitted recaps. Looking for a recap you already submitted?
                                    <a href="{{ route('ambassador.submitted-recap') }}">View Submitted Recaps</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#recap-select').on('change', function() {
                let eventId = $(this).val();

                if (eventId) {
                    $.ajax({
                        url: '{{ route("ambassador.recaps") }}',
                        method: 'GET',
                        data: {
                            id: eventId,
                        },
                        success: function(response) {
                            $('#recap-table').html(response.html);
                        },
                    });
                }
            });
        });
    </script>
@endsection
