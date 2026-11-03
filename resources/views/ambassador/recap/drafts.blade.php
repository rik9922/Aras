@extends('layout.app', ['page_name' => 'Saved Drafts'])

@section('content')
    <div class="container py-3">
        <div class="h5 mt-2">Saved Drafts:
            <span class="total-data">
                @if (!empty($drafts) && count($drafts) > 0)
                    {{ count($drafts) }}
                @endif
            </span>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="mb-2 small">
                Looking for recaps you already submitted?
                <a href="{{ route('ambassador.submitted-recap') }}"><strong>View Submitted Recaps</strong></a>.
            </div>

            <div class="table-responsive px-0">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th><div>Date</div></th>
                            <th><div style="width: 180px">Brand</div></th>
                            <th><div class="text-nowrap">Event Name</div></th>
                            <th><div>City</div></th>
                            <th><div>Last Updated</div></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($drafts as $draft)
                            <tr>
                                <td>
                                    @if($draft->event)
                                        <div>{{ $draft->event->Dayname }}</div>
                                        <div>{{ $draft->event->FormattedDate }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $draft->event && $draft->event->brand ? $draft->event->brand->brand_name : '-' }}
                                </td>
                                <td>
                                    {{ $draft->event ? ($draft->event->event_name ?? '-') : '-' }}
                                </td>
                                <td>
                                    {{ $draft->event && $draft->event->location ? $draft->event->location->city : '-' }}
                                </td>
                                <td>
                                    {{ optional($draft->updated_at)->format('M d, Y h:i A') }}
                                </td>
                                <td>
                                    <a href="{{ route('ambassador.edit-recap', ['id' => $draft->id]) }}">
                                        <button class="btn btn-design">Continue</button>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="6">No drafts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

