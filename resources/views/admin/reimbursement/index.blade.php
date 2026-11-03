@extends('layout.app', ['page_name' => 'Reimbursements'])

@section('content')
@php
    $isManager = request()->routeIs('manager.*');
    $createRoute = $isManager ? 'manager.reimbursements.create' : null;
@endphp
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Reimbursement Requests</h5>
        @if($createRoute)
            <a href="{{ route($createRoute) }}" class="btn-ds btn-primary-ds">Request Reimbursement</a>
        @endif
    </div>
    <div class="fixed-border"></div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="page-card-design mb-3">
        <form method="GET" class="row g-2">
            <div class="col-auto">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn-ds btn-primary-ds">Filter</button>
            </div>
        </form>
    </div>

    <div class="page-card-design">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ambassador</th>
                        <th>Date</th>
                        <th>Brand/Event</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reimbursements as $r)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->user->name ?? '-' }}</td>
                            <td>{{ $r->expense_date->format('M d, Y') }}</td>
                            <td>
                                @if($r->event)
                                    {{ $r->event->brand->brand_name ?? '-' }} (Event #{{ $r->event_id }})
                                @else
                                    {{ $r->brand->brand_name ?? $r->brand_name ?? '-' }}
                                @endif
                            </td>
                            <td>${{ number_format($r->amount, 2) }}</td>
                            <td>
                                @switch($r->status)
                                    @case('pending') <span class="badge bg-warning">Pending</span> @break
                                    @case('approved') <span class="badge bg-info">Approved</span> @break
                                    @case('rejected') <span class="badge bg-danger">Rejected</span> @break
                                    @case('paid') <span class="badge bg-success">Paid</span> @break
                                    @default <span class="badge bg-secondary">{{ $r->status }}</span>
                                @endswitch
                            </td>
                            <td>{{ $r->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route($showRoute ?? 'admin.reimbursements.show', $r) }}" class="btn-ds btn-primary-outline btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">No reimbursements found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $reimbursements->links() }}
    </div>
</div>
@endsection
