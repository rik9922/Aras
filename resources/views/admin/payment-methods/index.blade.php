@extends('layout.app', ['page_name' => 'Payment Methods'])

@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
    @endphp
    <div class="container py-3">
        <h5>Payment Methods</h5>
        <div class="fixed-border"></div>

        <div class="page-card-design mb-3">
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                    style="width: 32px; padding: 5px;">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                        <h6 class="mb-0">How payment accounts work with QuickBooks</h6>
                        <span class="badge bg-light text-muted fw-normal">Read-only · Synced from QuickBooks</span>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-2 h-100 bg-light">
                                <div class="fw-semibold small mb-1">
                                    <i class="fa-solid fa-link me-1 text-primary"></i>
                                    Source of truth
                                </div>
                                <p class="small mb-0">
                                    These rows are <strong>Bank / Credit Card accounts from QuickBooks</strong>.
                                    Create or edit accounts <strong>only in QuickBooks</strong>, then sync to refresh this
                                    list.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-2 h-100 bg-light">
                                <div class="fw-semibold small mb-1">
                                    <i class="fa-solid fa-arrows-rotate me-1 text-success"></i>
                                    How to sync
                                </div>
                                <p class="small mb-0">
                                    After changing accounts in QuickBooks, click
                                    <strong>“Sync from QuickBooks (run first)”</strong> below to import the latest
                                    Bank &amp; Credit Card accounts.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-2 h-100 bg-light">
                                <div class="fw-semibold small mb-1">
                                    <i class="fa-solid fa-circle-check me-1 text-warning"></i>
                                    Default for payouts
                                </div>
                                <p class="small mb-1">
                                    The account marked <strong>Default</strong> is used for
                                    <strong>ambassador payouts</strong> expenses.
                                </p>
                                <p class="small mb-0">
                                    Balances (Opening Balance / As of) come <strong>directly from QuickBooks</strong>
                                    and <strong>cannot be edited here</strong>. Update them in QuickBooks, then sync again.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="page-card-design mb-3 d-flex flex-wrap align-items-center gap-2">
            <form method="POST" action="{{ route($prefix . '.payment-methods.sync') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn-ds btn-success-ds">
                    <i class="fa-solid fa-rotate"></i> Sync from QuickBooks (run first)
                </button>
            </form>
        </div>

        <div class="page-card-design">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Account Name</th>
                            <th>Account Type</th>
                            <th>Detail Type</th>
                            <th>Opening Balance</th>
                            <th>As of</th>
                            <th>QuickBooks Account ID</th>
                            <th>Default</th>
                            <th>Active</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentMethods as $i => $pm)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $pm->name }}</td>
                                <td>{{ $pm->account_type ?? '–' }}</td>
                                <td>{{ $pm->detail_type ?? '–' }}</td>
                                <td>{{ $pm->opening_balance !== null ? number_format($pm->opening_balance, 2) : '–' }}</td>
                                <td>{{ $pm->as_of_date?->format('Y-m-d') ?? '–' }}</td>
                                <td>{{ $pm->quickbooks_account_id ?? '–' }}</td>
                                <td>
                                    @if ($pm->is_default)
                                        <span class="btn-ds btn-success-rounded">Default</span>
                                    @else
                                        <form method="POST"
                                            action="{{ route($prefix . '.payment-methods.set-default', $pm) }}"
                                            class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-ds btn-secondary-outline-rounded">Set
                                                default</button>
                                        </form>
                                    @endif
                                </td>
                                <td>{{ $pm->active ? 'Yes' : 'No' }}</td>
                                <td>
                                    {{-- Read-only; only sync & set default are allowed --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    No payment accounts yet. Click <strong>Sync from QuickBooks (run first)</strong> to pull
                                    Bank & Credit Card accounts.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
