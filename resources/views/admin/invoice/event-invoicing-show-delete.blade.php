@extends('layout.app', ['page_name' => 'Event invoicing'])
@section('content')
    <div class="container">
        <div class="page-card-design">
            @php
                $user = auth()->user();
                $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
            @endphp

            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <a href="{{ route($prefix . '.event-invoicing', ['month' => $month]) }}"
                        class="btn-ds btn-secondary-outline btn-sm mb-2 text-uppercase">&larr; Back</a>
                    <div class="h5 text-uppercase mb-0">{{ $client->name }}</div>
                    <small class="text-muted">Event month: <strong>{{ $monthLabel }}</strong> (by event start date)</small>
                </div>
                <div>
                    <div class="d-flex flex-wrap justify-content-end align-items-center gap-2">
                        @if ($isConnected)
                            <span class="badge bg-success">QuickBooks connected</span>
                        @else
                            <a href="{{ route($prefix . '.quickbooks.auth') }}" class="btn-ds btn-primary-ds btn-sm text-uppercase">
                                Connect QuickBooks
                            </a>
                        @endif

                        <form method="GET" action="{{ route($prefix . '.export-event-invoicing-month') }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <button type="submit" class="btn-ds btn-secondary-outline btn-sm text-uppercase">
                                Export invoicing (this supplier)
                            </button>
                        </form>

                        @if ($isConnected)
                            <form method="POST"
                                action="{{ route($prefix . '.event-invoicing.create-month-invoice', ['client' => $client->id, 'month' => $month]) }}">
                                @csrf
                                <button type="submit" class="btn-ds btn-primary-outline btn-sm text-uppercase">
                                    Create month invoice (remaining events)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row g-2 mb-4">
                <div class="col-6 col-md-auto">
                    <div class="border rounded p-2 text-center small">
                        <div class="text-muted text-uppercase">Submitted</div>
                        <div class="fw-bold">{{ $stats['submitted'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-auto">
                    <div class="border rounded p-2 text-center small">
                        <div class="text-muted text-uppercase">Not recapped</div>
                        <div class="fw-bold">{{ $stats['not_recapped'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-auto">
                    <div class="border rounded p-2 text-center small">
                        <div class="text-muted text-uppercase">No shows</div>
                        <div class="fw-bold">{{ $stats['no_shows'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-auto">
                    <div class="border rounded p-2 text-center small">
                        <div class="text-muted text-uppercase">Late</div>
                        <div class="fw-bold">{{ $stats['late'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-auto">
                    <div class="border rounded p-2 text-center small">
                        <div class="text-muted text-uppercase">Cancelled</div>
                        <div class="fw-bold">{{ $stats['cancelled'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-auto">
                    <div class="border rounded p-2 text-center small">
                        <div class="text-muted text-uppercase">Month status</div>
                        <div class="fw-bold">
                            <span class="badge bg-{{ $stats['complete'] ? 'success' : 'warning' }}">
                                {{ $stats['complete'] ? 'Complete' : 'Pending' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Brand</th>
                            <th>Ops status</th>
                            <th>Recap</th>
                            <th>Publish to QB</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            @php
                                $blocked = in_array($event->admin_status, ['no_show', 'cancelled_no_fee'], true);
                                $qb = $event->qb_publish_status;
                            @endphp
                            <tr>
                                <td><strong>{{ $event->id }}</strong></td>
                                <td>{{ $event->FormattedDate ?? '-' }}</td>
                                <td>{{ $event->brand->brand_name ?? '-' }}</td>
                                <td>
                                    @if ($event->admin_status)
                                        <code class="small">{{ $event->admin_status }}</code>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($event->recap)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($qb === 'success')
                                        <span class="badge bg-success">Success</span>
                                    @elseif ($qb === 'failed')
                                        <span class="badge bg-danger" title="{{ $event->qb_publish_error }}">Failed</span>
                                        @if ($event->qb_publish_error)
                                            <div class="small text-danger">{{ Str::limit($event->qb_publish_error, 80) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($blocked)
                                        <span class="badge bg-secondary">No invoice (blocked)</span>
                                    @elseif ($event->is_invoice_created)
                                        <a href="{{ route($prefix . '.invoices.show', $event->invoice_id) }}"
                                            class="btn-ds btn-success-outline btn-sm">View invoice</a>
                                    @elseif ($isConnected)
                                        <a href="{{ route($prefix . '.event.generate-invoice', $event) }}"
                                            class="btn-ds btn-primary-ds btn-sm text-uppercase">Prepare &amp; publish</a>
                                    @else
                                        <span class="text-muted small">Connect QB</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No events this month.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
