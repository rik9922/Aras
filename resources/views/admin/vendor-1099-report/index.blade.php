@extends('layout.app', ['page_name' => '1099 Vendor Tracking Report'])

@section('content')
@php
    $user = auth()->user();
    $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
@endphp
<div class="container py-3">
    <h5>1099 Vendor Tracking Report</h5>
    <div class="fixed-border"></div>
    <div class="content-box-grey d-flex align-items-center gap-2 p-2 mb-3">
        <i class="fa-solid fa-circle-info"></i>
        <div>Use this report to cross off ambassadors as you enable "Track payments for 1099" in QuickBooks for each vendor. Expenses include completed Event Pay expenses only; reimbursements are excluded.</div>
    </div>
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <form action="{{ route($prefix.'.quickbooks.sync-vendors') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn-ds btn-primary-outline">Sync Existing QB Vendors to Portal</button>
        </form>
        <small class="text-muted">Run before launch to link existing QuickBooks vendors and avoid duplicates.</small>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="page-card-design">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ambassador</th>
                        <th>QB Vendor ID</th>
                        <th>Expenses</th>
                        <th>W9 Submitted</th>
                        <th>1099 Tracked in QB</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ambassadors as $i => $a)
                        @php
                            $expensesTotal = (float) ($a->reportable_expenses_total ?? 0);
                            $vendorId = $a->w9Form?->quickbooks_vendor_id ?? $a->quickbooks_vendor_id ?? null;
                            $expensesUrl = route($prefix.'.vendor-1099.expenses', $a);
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <a href="{{ $expensesUrl }}">
                                    {{ trim($a->last_name . ', ' . $a->first_name) }}
                                </a>
                            </td>
                            <td>{{ $vendorId ?? '-' }}</td>
                            <td>${{ number_format($expensesTotal, 2) }}</td>
                            <td>
                                @if($a->w9Form)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-warning">No</span>
                                @endif
                            </td>
                            <td>
                                @if($vendorId && $a->w9Form)
                                    <form action="{{ route($prefix.'.vendor-1099.toggle', $a) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="tracked"
                                                {{ $a->w9Form && $a->w9Form->is_1099_vendor ? 'checked' : '' }}
                                                onchange="this.form.submit()">
                                            <label class="form-check-label">Mark when done</label>
                                        </div>
                                    </form>
                                @else
                                    <span class="text-muted">No vendor</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
