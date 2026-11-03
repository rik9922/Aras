@extends('layout.app', ['page_name' => 'Dwolla Webhooks'])

@section('content')
    <div class="container">
        <div class="page-contents">
            <div class="d-grid">
                <div class="h5 text-uppercase">Dwolla Webhook Subscriptions
                    @if(!empty($subscriptions) && count($subscriptions) > 0)
                        ({{ count($subscriptions) }})
                    @else
                        (0)
                    @endif
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row align-items-center mt-2">
                <div class="col-md-8 pt-3">
                    <a href="{{ route('admin.webhooks.index', ['sync' => 1]) }}" class="btn-ds btn-secondary-outline btn-sm">
                        Sync from Dwolla
                    </a>
                </div>
                <div class="col-md-4 pt-3 text-end">
                    <a href="{{ route('admin.webhooks.create') }}">
                        <button type="button" class="btn-ds btn-secondary-ds">Add Webhook Subscription</button>
                    </a>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Dwolla ID</th>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($subscriptions) && count($subscriptions) > 0)
                            @foreach ($subscriptions as $sub)
                                <tr>
                                    <td><code class="small">{{ $sub->dwolla_subscription_id }}</code></td>
                                    <td>{{ $sub->url }}</td>
                                    <td>
                                        @if($sub->paused)
                                            <span class="badge bg-warning">Paused</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td>{{ $sub->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <form action="{{ route('admin.webhooks.toggle-paused', $sub->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-ds btn-primary-ds btn-sm">
                                                {{ $sub->paused ? 'Resume' : 'Pause' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.webhooks.destroy', $sub->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this webhook subscription?');">
                                            @csrf
                                            <button type="submit" class="btn-ds btn-danger-outline btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center py-4">No webhook subscriptions. Add one or sync from Dwolla.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
