@extends('layout.app', ['page_name' => 'Reimbursements'])

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">My Reimbursements</h5>
        <a href="{{ route('ambassador.reimbursements.create') }}" class="btn btn-primary">Request Reimbursement</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (! $hasVerifiedBank)
        <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
            <i class="fa-solid fa-circle-info mt-1"></i>
            <div>
                <strong>Before requesting reimbursement:</strong> Add your bank account under <strong>My Account → Bank Account</strong>, complete verification, and wait for approval. Once your bank is verified, you can submit reimbursement requests and receive payments there.
                <a href="{{ route('ambassador.dwolla.bank-account') }}" class="alert-link">Add bank account</a>
            </div>
        </div>
    @endif

    <div class="page-card-design">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Brand/Event</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reimbursements as $r)
                        <tr>
                            <td>{{ $r->expense_date->format('M d, Y') }}</td>
                            <td>
                                @if($r->event)
                                    {{ $r->event->brand->brand_name ?? '-' }} (Event #{{ $r->event_id }})
                                @else
                                    {{ $r->brand->brand_name ?? $r->brand_name ?? '-' }}
                                @endif
                            </td>
                            <td>${{ number_format($r->amount, 2) }}</td>
                            <td>{{ Str::limit($r->reason, 40) }}</td>
                            <td>
                                @switch($r->status)
                                    @case('pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-info">Approved</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                        @break
                                    @case('paid')
                                        <span class="badge bg-success">Paid</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $r->status }}</span>
                                @endswitch
                            </td>
                            <td>{{ $r->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('ambassador.reimbursements.show', $r) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No reimbursements yet. <a href="{{ route('ambassador.reimbursements.create') }}">Request one</a></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $reimbursements->links() }}
    </div>
</div>
@endsection
