@extends('layout.app', ['page_name' => 'Expenses'])

@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
    @endphp
    <div class="container py-3">
        <h5>Expenses</h5>
        <div class="fixed-border"></div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="content-box-grey d-flex align-items-center gap-2 p-2 mb-0">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    QuickBooks expenses linked to ambassador event pay and reimbursements.
                </div>
            </div>
            <form method="POST" action="{{ route($prefix.'.expenses.sync') }}">
                @csrf
                <button type="submit" class="btn-ds btn-success-rounded">
                    <i class="fas fa-refresh"></i>
                    Sync from QuickBooks
                </button>
            </form>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="page-card-design">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>VendorID</th>
                            <th>Ambassador</th>
                            <th>Amount</th>
                            <th>Payment Via</th>
                            <th>Description</th>
                            <th>Private Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ optional($expense->txn_date)->format('m/d/Y') }}</td>
                                <td>{{ $expense->vendor_id ?? '-' }}</td>
                                <td>
                                    @if ($expense->user)
                                        <div>{{ $expense->user->name }}</div>
                                    @else
                                        <span class="text-muted">Unlinked</span>
                                    @endif

                                    <div class="small mt-1">
                                        @if ($expense->payout_id)
                                            <span class="badge bg-info text-dark">Event Pay</span>
                                        @endif
                                        @if ($expense->reimbursement_id)
                                            <span class="badge bg-secondary">Reimbursement</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ number_format($expense->total_amount, 2) }} {{ $expense->currency_code }}</td>
                                <td>{{ $expense->payment_type }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($expense->description, 80) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($expense->private_note, 80) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No expenses found. Try syncing from QuickBooks.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $expenses->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
@endsection

