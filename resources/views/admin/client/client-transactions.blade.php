@extends('layout.app', ['page_name' => 'Bill To Transactions'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
@section('content')
    <div class="container">
        <div class="page-card-design">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">{{ $client->name }} - Transactions</h4>
                    <p class="text-muted mb-0">QuickBooks Customer ID: {{ $client->quickbooks_customer_id ?? 'Not Synced' }}
                    </p>
                </div>
                <div>
                    @if ($client->quickbooks_customer_id)
                        <form action="{{ route($routePrefix.'.client.transactions.sync', $client->id) }}" method="POST"
                            class="d-inline">
                            @csrf
                            <button type="submit" class="btn-ds btn-success-ds me-2">
                                <i class="fas fa-sync"></i> Sync Transactions
                            </button>
                        </form>
                    @endif
                    <a href="{{ route($routePrefix.'.show.client', $client->id) }}" class="btn-ds btn-secondary-outline">
                        <i class="fas fa-arrow-left"></i> Back to Bill To
                    </a>
                </div>
            </div>

            @if ($client->quickbooks_customer_id)
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">From Date</label>
                            <input type="date" name="date_from" class="form-control"
                                value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">To Date</label>
                            <input type="date" name="date_to" class="form-control"
                                value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn-ds btn-primary-ds me-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="{{ route($routePrefix.'.client.transactions', $client->id) }}" class="btn-ds btn-secondary-ds">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            @endif

            @if (isset($error))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                </div>
            @elseif(empty($transactions))
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No transactions found for this client.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->DocNumber ?? $transaction->Id }}</td>
                                    <td>{{ isset($transaction->TxnDate) ? \Carbon\Carbon::parse($transaction->TxnDate)->format('M d, Y') : '-' }}
                                    </td>
                                    <td>{{ isset($transaction->DueDate) ? \Carbon\Carbon::parse($transaction->DueDate)->format('M d, Y') : '-' }}
                                    </td>
                                    <td>${{ number_format($transaction->TotalAmt ?? 0, 2) }}</td>
                                    <td>${{ number_format($transaction->Balance ?? 0, 2) }}</td>
                                    <td>
                                        <span
                                            class="badge bg-{{ ($transaction->Balance ?? 0) == 0 ? 'success' : 'secondary' }}">
                                            {{ ($transaction->Balance ?? 0) == 0 ? 'Paid' : 'Unpaid' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $localInvoice = \App\Models\Invoice::where(
                                                'qb_invoice_id',
                                                $transaction->Id,
                                            )->first();
                                        @endphp
                                        @if ($localInvoice)
                                            <a href="{{ route($routePrefix.'.invoices.show', $localInvoice->id) }}"
                                                class="btn-ds btn-primary-ds btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
