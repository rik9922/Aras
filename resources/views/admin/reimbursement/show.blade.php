@extends('layout.app', ['page_name' => 'Reimbursement Details'])

@section('content')
@php
    $isManager = request()->routeIs('manager.*');
    $indexRoute = $isManager ? 'manager.reimbursements.index' : 'admin.reimbursements.index';
    $approveRoute = $isManager ? 'manager.reimbursements.approve' : 'admin.reimbursements.approve';
    $rejectRoute = $isManager ? 'manager.reimbursements.reject' : 'admin.reimbursements.reject';
@endphp
<div class="container py-3">
    <a href="{{ route($indexRoute) }}" class="btn-ds btn-secondary-outline mb-3">&larr; Back</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="page-card-design">
        <h5>Reimbursement #{{ $reimbursement->id }}</h5>
        <div class="row mt-3">
            <div class="col-md-6">
                <p><strong>Ambassador:</strong> {{ $reimbursement->user->name ?? '-' }}</p>
                <p><strong>Expense Date:</strong> {{ $reimbursement->expense_date->format('M d, Y') }}</p>
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

        @if($reimbursement->status === 'pending')
            @if(isset($potentialDuplicates) && $potentialDuplicates->isNotEmpty())
                <div class="alert alert-warning mt-3">
                    <h6 class="mb-2">Potential duplicate reimbursement(s) found</h6>
                    <p class="mb-2">
                        Same ambassador has recent reimbursement(s) with the same amount and/or same event. Please review before approving.
                    </p>
                    <ul class="mb-0">
                        @foreach($potentialDuplicates as $dup)
                            <li class="mb-1">
                                #{{ $dup->id }} | ${{ number_format($dup->amount, 2) }} | {{ $dup->expense_date ? $dup->expense_date->format('M d, Y') : '-' }}
                                | {{ ucfirst($dup->status) }}
                                @if($dup->event_id)
                                    | Event ID {{ $dup->event_id }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <hr>
            <div class="d-flex gap-2">
                <form action="{{ route($approveRoute, $reimbursement) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    @if(isset($potentialDuplicates) && $potentialDuplicates->isNotEmpty())
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="override_duplicate_check" name="override_duplicate_check" required>
                            <label class="form-check-label" for="override_duplicate_check">
                                I reviewed the potential duplicate warning and still want to approve.
                            </label>
                        </div>
                    @endif
                    <button type="submit" class="btn-ds btn-success-ds">Approve</button>
                    <button type="button" class="btn-ds btn-danger-ds" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                </form>
                
            </div>

            <div class="modal fade" id="rejectModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route($rejectRoute, $reimbursement) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="modal-header">
                                <h5 class="modal-title">Reject Reimbursement</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label">Reason for rejection (required)</label>
                                <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-ds btn-secondary-ds" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn-ds btn-danger-ds">Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
