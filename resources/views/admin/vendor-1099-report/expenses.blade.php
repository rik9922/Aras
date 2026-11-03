@extends('layout.app', ['page_name' => '1099 Expense Transactions'])

@section('content')
@php
    $currentUser = auth()->user();
    $prefix = $currentUser && $currentUser->role_id == 6 ? 'manager' : 'admin';
    $ambassadorName = trim(($user->last_name ?? '').', '.($user->first_name ?? ''));
    $ambassadorName = $ambassadorName !== ',' ? $ambassadorName : ($user->name ?? 'Ambassador #'.$user->id);
    $exportUrl = route($prefix.'.vendor-1099.expenses.export', array_merge(['user' => $user->id], request()->only(['start_date', 'end_date'])));
@endphp
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
        <div>
            <h5>1099 Expense Transactions</h5>
            <div class="text-muted">{{ $ambassadorName }}</div>
        </div>
        <a href="{{ route($prefix.'.vendor-1099.index') }}" class="btn-ds btn-secondary-outline">Back to Report</a>
    </div>
    <div class="fixed-border"></div>

    <div class="content-box-grey d-flex align-items-center gap-2 p-2 mb-3">
        <i class="fa-solid fa-circle-info"></i>
        <div>Showing completed Event Pay expenses only. Reimbursements are excluded from this 1099 transaction history.</div>
    </div>

    <div class="page-card-design mb-3">
        <form method="GET" action="{{ route($prefix.'.vendor-1099.expenses', $user) }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="form-control shadow-none">
            </div>
            <div class="col-12 col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="form-control shadow-none">
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                <button type="submit" class="btn-ds btn-primary-ds">Filter</button>
                <a href="{{ route($prefix.'.vendor-1099.expenses', $user) }}" class="btn-ds btn-secondary-outline">Reset</a>
                <a href="{{ $exportUrl }}" class="btn-ds btn-primary-outline">
                    <i class="fas fa-download me-1"></i>Export
                </a>
            </div>
            <div class="col-12 col-md text-md-end">
                <div class="text-muted small">Filtered Total</div>
                <strong>${{ number_format((float) $filteredTotal, 2) }}</strong>
            </div>
        </form>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="page-card-design">
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Type</th>
                        <th>Status</th>
                        <th>Reference</th>
                        <th>Event</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        @php
                            $payout = $expense->payout;
                            $event = $payout?->event;
                            $status = $payout?->status ? ucfirst(str_replace('_', ' ', $payout->status)) : '-';
                        @endphp
                        <tr>
                            <td class="text-nowrap">{{ optional($expense->txn_date)->format('m/d/Y') ?? '-' }}</td>
                            <td class="text-nowrap">${{ number_format((float) $expense->total_amount, 2) }} {{ $expense->currency_code }}</td>
                            <td>
                                Event Pay
                                @if($expense->payment_type)
                                    <small class="text-muted d-block">via {{ $expense->payment_type }}</small>
                                @endif
                            </td>
                            <td>{{ $status }}</td>
                            <td>{{ $payout?->reference ?? '-' }}</td>
                            <td>
                                @if($event)
                                    {{ $event->brand?->brand_name ?? $event->event_name ?? 'Event #'.$event->id }}
                                    <small class="text-muted d-block">Event ID {{ $event->id }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($expense->description ?? $expense->private_note, 90) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No expense transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <div class="text-muted small">
                Showing {{ $expenses->firstItem() ?? 0 }}-{{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }} transactions
            </div>
            {{ $expenses->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>
@endsection
