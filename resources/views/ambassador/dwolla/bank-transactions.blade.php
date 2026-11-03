@extends('layout.app', ['page_name' => 'Transactions'])
<link rel="stylesheet" href="{{ asset('assets/select2/select2.css') }}">
@section('content')
    <div class="container p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="h4">Transactions</div>
                <div class="fixed-border"></div>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn-dark btn-sm">Back</a>
        </div>
        <div class="page-card-design">
            @if ($transactions->count())
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="fw-semibold text-muted">
                        Total Earnings
                    </div>
                    <div class="h5 mb-0 text-success">
                        ${{ number_format($totalEarning, 2) }}
                    </div>
                </div>

                <hr>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Transaction ID</th>
                                <th>Bank</th>
                                <th>Event</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $index => $transaction)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $index }}</td>
                                    <td>{{ $transaction->type_label }}</td>
                                    <td>${{ number_format($transaction->amount, 2) }}</td>
                                    <td>
                                        @php
                                            $status = $transaction->status;
                                            $statusClass = in_array($status, ['completed', 'paid']) ? 'success' : 'warning';
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ $transaction->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->dwolla_transfer_id ?? '-' }}</td>
                                    <td>
                                        {{ $transaction->bank_name ?? '-' }}
                                        @if ($transaction->bank_account_last4)
                                            - ****{{ $transaction->bank_account_last4 }}
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($transaction->meta['event_id']))
                                            {{ $transaction->meta['event_name'] ?? ('Event #'.$transaction->meta['event_id']) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $transaction->occurred_at?->format('d M Y, h:i A') ?? $transaction->created_at->format('d M Y, h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-end mt-3">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center text-muted py-4">
                    No transactions found.
                </div>
            @endif
        </div>


    </div>

    </div>
@endsection
