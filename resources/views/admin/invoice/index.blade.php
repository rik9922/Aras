@extends('layout.app', ['page_name' => 'QuickBooks invoices'])
@section('content')
    <div class="container">
        <div class="page-card-design">
            @php
                $user = auth()->user();
                $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
            @endphp
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="h5 text-uppercase mb-0">QuickBooks invoices (synced): ({{ $invoices->total() }})</div>
                @if ($isConnected)
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route($prefix . '.event-invoicing') }}"
                            class="btn-ds btn-primary-ds text-uppercase btn-sm">
                            <i class="fas fa-calendar-alt"></i> Event invoicing (prepare)
                        </a>
                        <form method="POST" action="{{ route($prefix . '.invoices.sync') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn-ds btn-primary-outline text-uppercase btn-sm">
                                <i class="fas fa-sync"></i> Sync from QuickBooks
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="alert alert-light border small mb-3">
                These rows are <strong>invoices already in QuickBooks</strong> (read-only sync). To review recaps and
                <strong>publish new invoices</strong>, use
                <a href="{{ route($prefix . '.event-invoicing') }}" class="alert-link">Event invoicing (prepare)</a>.
            </div>

            @if (!$isConnected)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> {{ $message ?? 'QuickBooks is not connected.' }}
                    <a href="{{ route($prefix . '.quickbooks.auth') }}" class="btn-ds btn-primary-ds btn-sm ms-2">Connect
                        QuickBooks</a>
                </div>
            @else
                <!-- Filters -->
                <form method="GET" action="{{ route($prefix . '.invoices') }}">
                    <div class="row align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-uppercase">Customer</label>
                            <input type="text" name="customer" class="form-control"
                                value="{{ $filters['customer'] ?? '' }}" placeholder="Search customer...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-uppercase">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Paid" {{ ($filters['status'] ?? '') == 'Paid' ? 'selected' : '' }}>Paid
                                </option>
                                <option value="Unpaid" {{ ($filters['status'] ?? '') == 'Unpaid' ? 'selected' : '' }}>
                                    Unpaid
                                </option>
                                <option value="Draft" {{ ($filters['status'] ?? '') == 'Draft' ? 'selected' : '' }}>Draft
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-uppercase">From Date</label>
                            <input type="date" name="date_from" class="form-control"
                                value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-uppercase">To Date</label>
                            <input type="date" name="date_to" class="form-control"
                                value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn-ds btn-primary-ds text-uppercase">Filter</button>
                                <a href="{{ route($prefix . '.invoices') }}"
                                    class="btn-ds btn-secondary-outline text-uppercase">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($invoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->doc_number ?: '#' . $invoice->qb_invoice_id }}</td>
                                        <td>{{ $invoice->customer_name }}</td>
                                        <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('M d, Y') : '-' }}
                                        </td>
                                        <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '-' }}</td>
                                        <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                        <td>${{ number_format($invoice->balance, 2) }}</td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $invoice->status == 'Published' ? 'success' : ($invoice->balance > 0 ? 'warning' : 'secondary') }}">
                                                {{ $invoice->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route($prefix . '.invoices.show', $invoice->id) }}"
                                                class="btn-ds btn-primary-outline btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $invoices->appends($filters ?? [])->links() }}
                    </div>
                @else
                    <div class="content-box-grey mt-3">
                        <div class="h6 text-center text-uppercase my-4">
                            No Invoices Found
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
