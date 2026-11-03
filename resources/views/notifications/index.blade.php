@extends('layout.app', ['page_name' => 'Notifications'])

@section('content')
    <div class="container py-3">
        <div class="page-card-design">
            <div class="row mt-3 px-2">
                <div class="col-12 pb-3 px-0 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <div class="h5 fw-bold text-uppercase">Notifications</div>
                        @if ($unreadCount > 0)
                            <div class="text-muted" style="font-size: var(--font-size-body);">{{ $unreadCount }} unread</div>
                        @else
                            <div class="text-muted" style="font-size: var(--font-size-body);">You're all caught up</div>
                        @endif
                    </div>
                    <div>
                        <a class="btn btn-sm btn-design text-uppercase" href="{{ route('notifications.settings') }}">Settings</a>
                    </div>
                </div>
            </div>

            @if ($notifications->count() === 0)
                <div class="p-3 text-muted">No notifications yet.</div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($notifications as $notification)
                        <a href="{{ route('notifications.seen', $notification->id) }}"
                            class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold">
                                        {{ $notification->title ?? 'Notification' }}
                                        @if (! (bool) $notification->is_seen)
                                            <span class="badge bg-primary ms-2">New</span>
                                        @endif
                                    </div>
                                    <div class="text-muted" style="white-space: pre-wrap;">
                                        {{ \Illuminate\Support\Str::limit($notification->message ?? '', 180) }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted" style="font-size: var(--font-size-caption);">
                                        {{ $notification->created_at?->diffForHumans() ?? '' }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-3 px-2">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

