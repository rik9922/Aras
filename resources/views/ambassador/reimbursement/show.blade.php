@extends('layout.app', ['page_name' => 'Reimbursement Details'])

@section('content')
<div class="container py-3">
    <a href="{{ route('ambassador.reimbursements.index') }}" class="btn btn-outline-secondary mb-3">&larr; Back</a>

    <div class="page-card-design">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Reimbursement #{{ $reimbursement->id }}</h5>
            @if($reimbursement->status === 'rejected')
                <a href="{{ route('ambassador.reimbursements.edit', $reimbursement) }}" class="btn btn-sm btn-primary">
                    Edit &amp; Resubmit
                </a>
            @endif
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <p><strong>Date:</strong> {{ $reimbursement->expense_date->format('M d, Y') }}</p>
                <p><strong>Amount:</strong> ${{ number_format($reimbursement->amount, 2) }}</p>
                <p><strong>Brand/Event:</strong>
                    @if($reimbursement->event)
                        {{ $reimbursement->event->brand->brand_name ?? '-' }} (Event ID {{ $reimbursement->event_id }})
                    @else
                        {{ $reimbursement->brand->brand_name ?? $reimbursement->brand_name ?? '-' }}
                    @endif
                </p>
                <p><strong>Status:</strong>
                    @switch($reimbursement->status)
                        @case('pending') <span class="badge bg-warning">Pending</span> @break
                        @case('approved') <span class="badge bg-info">Approved</span> @break
                        @case('rejected') <span class="badge bg-danger">Rejected</span> @break
                        @case('paid') <span class="badge bg-success">Paid</span> @break
                        @default <span class="badge bg-secondary">{{ $reimbursement->status }}</span>
                    @endswitch
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Submitted:</strong> {{ $reimbursement->created_at->format('M d, Y H:i') }}</p>
                @if($reimbursement->rejection_reason)
                    <p><strong>Rejection Reason:</strong> {{ $reimbursement->rejection_reason }}</p>
                @endif
                @if($reimbursement->paid_at)
                    <p><strong>Paid:</strong> {{ $reimbursement->paid_at->format('M d, Y') }}</p>
                @endif
            </div>
        </div>
        <p><strong>Reason:</strong> {{ $reimbursement->reason }}</p>
        @include('common.partials.reimbursement-receipt')
    </div>
</div>
@endsection
