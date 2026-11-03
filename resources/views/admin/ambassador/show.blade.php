@extends('layout.app', ['page_name' => 'Ambassador Profile'])

@php
    $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin';
    $profile = $ambassador->ambassadorProfile;
    $license = $ambassador->ambassadorLiquorLicense;
    $w9 = $ambassador->w9Form;
    $markets = $profile?->markets ?? collect();
    $bankStatus = $ambassador->getBankSetupStatusLabel();
    $bankBadge = match ($ambassador->getBankSetupStatus()) {
        'verified' => 'success',
        'pending_verification' => 'warning',
        default => 'danger',
    };

    $statusBadge = function ($status) {
        return match ((string) $status) {
            'completed', 'paid', 'approved', '1' => 'bg-success',
            'processing', 'queued' => 'bg-info text-dark',
            'pending', '0', 'created' => 'bg-warning text-dark',
            'failed', 'job_failed', 'rejected', 'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
    };

    $money = fn($amount) => '$' . number_format((float) $amount, 2);
    $initials = collect(explode(' ', trim($ambassador->name)))
        ->filter()
        ->take(2)
        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
        ->join('');
@endphp

@push('styles')
    <style>
        .ambassador-profile-summary {
            padding: 1.25rem;
        }

        .ambassador-profile-summary-inner {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 1.25rem;
            align-items: center;
        }

        .ambassador-profile-avatar {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #eef2f7;
            color: #475569;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            overflow: hidden;
            position: relative;
            flex: 0 0 auto;
        }

        .ambassador-profile-avatar img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ambassador-profile-facts {
            display: grid;
            grid-template-columns: repeat(4, minmax(130px, 1fr));
            gap: 1rem;
            align-items: center;
        }

        .ambassador-profile-fact {
            min-height: 54px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .ambassador-profile-label {
            color: #6b7280;
            font-size: .72rem;
            line-height: 1;
            text-transform: uppercase;
            margin-bottom: .45rem;
        }

        .ambassador-action-row {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        @media (max-width: 991.98px) {
            .ambassador-profile-facts {
                grid-template-columns: repeat(2, minmax(130px, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .ambassador-profile-summary-inner {
                grid-template-columns: 1fr;
            }

            .ambassador-profile-facts {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div>
                <h5 class="mb-1">{{ $ambassador->name }}</h5>
                <div class="text-muted">{{ $ambassador->email }} @if ($ambassador->phone)
                        | {{ $ambassador->phone }}
                    @endif
                </div>
            </div>
            <div class="ambassador-action-row">
                <a href="{{ route($routePrefix . '.ambassadors') }}"
                    class="btn-ds btn-secondary-ds">Back</a>
                <a href="{{ route($routePrefix . '.edit.ambassador', ['id' => $ambassador->id]) }}"
                    class="btn-ds btn-success-ds">Edit</a>
            </div>
        </div>
        <div class="fixed-border"></div>

        <div class="page-card-design ambassador-profile-summary mb-3">
            <div class="ambassador-profile-summary-inner">
                <div class="ambassador-profile-avatar" aria-hidden="true">
                    <span>{{ $initials ?: 'BA' }}</span>
                    <img src="{{ userProfileImageUrl($ambassador->getRawProfileImagePath()) }}"
                        alt=""
                        onerror="this.style.display='none';">
                </div>
                <div class="ambassador-profile-facts">
                    <div class="ambassador-profile-fact">
                        <div class="ambassador-profile-label">Status</div>
                        <div>
                            <span class="span-text {{ $ambassador->status == '1' ? 'success' : 'danger' }}">
                                {{ $ambassador->status == '1' ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    <div class="ambassador-profile-fact">
                        <div class="ambassador-profile-label">Bank Setup</div>
                        <div>
                            <span class="span-text {{ $bankBadge }}">{{ $bankStatus }}</span>
                        </div>
                    </div>
                    <div class="ambassador-profile-fact">
                        <div class="ambassador-profile-label">Hired</div>
                        <div>
                            {{ $ambassador->hired ? \Carbon\Carbon::parse($ambassador->hired)->format('M d, Y') : '-' }}
                        </div>
                    </div>
                    <div class="ambassador-profile-fact">
                        <div class="ambassador-profile-label">Wage</div>
                        <div>{{ $ambassador->wage ? $money($ambassador->wage) : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="page-card-design">
                    <div class="text-muted text-uppercase small">Events</div>
                    <div class="h4 mb-1">{{ $stats['events_total'] }}</div>
                    <div class="small text-muted">{{ $stats['events_approved'] }} approved |
                        {{ $stats['events_pending'] }} pending</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="page-card-design">
                    <div class="text-muted text-uppercase small">Recaps Submitted</div>
                    <div class="h4 mb-1">{{ $stats['recaps_submitted'] }}</div>
                    <div class="small text-muted">{{ $stats['recaps_approved'] }} approved | {{ $stats['recaps_draft'] }}
                        drafts</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="page-card-design">
                    <div class="text-muted text-uppercase small">Reimbursements</div>
                    <div class="h4 mb-1">{{ $stats['reimbursements_total'] }}</div>
                    <div class="small text-muted">{{ $stats['reimbursements_paid'] }} paid |
                        {{ $money($stats['reimbursements_amount']) }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="page-card-design">
                    <div class="text-muted text-uppercase small">Transactions</div>
                    <div class="h4 mb-1">{{ $stats['transactions_total'] }}</div>
                    <div class="small text-muted">{{ $money($stats['transactions_amount']) }} total</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="page-card-design">
                    <h6 class="mb-3">Profile Details</h6>
                    <div class="row g-2">
                        <div class="col-sm-6"><strong>Nickname:</strong> {{ $ambassador->nickname ?? '-' }}</div>
                        <div class="col-sm-6"><strong>DOB:</strong>
                            {{ $ambassador->dob ? \Carbon\Carbon::parse($ambassador->dob)->format('M d, Y') : '-' }}</div>
                        <div class="col-sm-6"><strong>Venmo:</strong> {{ $ambassador->venmo ?? '-' }}</div>
                        <div class="col-sm-6"><strong>Region:</strong> {{ $ambassador->region ?? '-' }}</div>
                        <div class="col-sm-6"><strong>Shirt Size:</strong> {{ $ambassador->shirt_size ?? '-' }}</div>
                        <div class="col-sm-6"><strong>Markets:</strong>
                            {{ $markets->isNotEmpty() ? $markets->pluck('name')->join(', ') : '-' }}</div>
                        <div class="col-12"><strong>Legal Address:</strong>
                            {{ collect([$ambassador->legal_address, $ambassador->legal_city, $ambassador->legal_state, $ambassador->legal_zip_code])->filter()->join(', ') ?:'-' }}
                        </div>
                        <div class="col-12"><strong>Mailing Address:</strong>
                            {{ collect([$ambassador->mailing_address, $ambassador->mailing_city, $ambassador->mailing_state, $ambassador->mailing_zip_code])->filter()->join(', ') ?:'-' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="page-card-design">
                    <h6 class="mb-3">Compliance & Preferences</h6>
                    <div class="row g-2">
                        <div class="col-sm-6"><strong>Tito's Trained:</strong>
                            {{ $ambassador->getTitosTrained() ? 'Yes' : 'No' }}</div>
                        <div class="col-sm-6"><strong>THC Quiz Passed:</strong>
                            {{ $ambassador->thc_quiz_passed ? 'Yes' : 'No' }}</div>
                        <div class="col-sm-6"><strong>Tips Certified:</strong>
                            {{ $ambassador->tips_certified ? 'Yes' : 'No' }}</div>
                        <div class="col-sm-6"><strong>Has Permit:</strong> {{ $ambassador->has_permit ? 'Yes' : 'No' }}
                        </div>
                        <div class="col-sm-6"><strong>Liquor License:</strong>
                            {{ $license?->has_liquor_license ? 'Yes' : 'No' }}</div>
                        <div class="col-sm-6"><strong>License #:</strong> {{ $license?->license_number ?? '-' }}</div>
                        <div class="col-sm-6"><strong>W-9:</strong> {{ $w9 ? 'Submitted' : 'Not submitted' }}</div>
                        <div class="col-sm-6"><strong>QB Vendor:</strong> {{ $w9?->quickbooks_vendor_id ?? '-' }}</div>
                        <div class="col-sm-6"><strong>Coffee Shop:</strong> {{ $ambassador->favorite_coffee_shop ?? '-' }}
                        </div>
                        <div class="col-sm-6"><strong>Restaurant:</strong> {{ $ambassador->favorite_restaurant ?? '-' }}
                        </div>
                        <div class="col-sm-6"><strong>Shop:</strong> {{ $ambassador->favorite_place_to_shop ?? '-' }}</div>
                        <div class="col-sm-6"><strong>Charity:</strong> {{ $ambassador->favorite_charity ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-card-design mt-4 mb-4">
            <h6 class="mb-3">Recent Events</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Market</th>
                            <th>Status</th>
                            <th>Pay</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            @php $event = $assignment->event; @endphp
                            <tr>
                                <td>{{ $event?->event_name ?? '-' }}</td>
                                <td>{{ $event?->start_time ? \Carbon\Carbon::parse($event->start_time)->format('M d, Y g:i A') : '-' }}
                                </td>
                                <td>{{ $event?->markets?->isNotEmpty() ? $event->markets->pluck('name')->join(', ') : $event?->market?->name ?? '-' }}
                                </td>
                                <td><span
                                        class="badge {{ $statusBadge($assignment->status) }}">{{ $assignment->status == '1' ? 'Approved' : 'Pending' }}</span>
                                </td>
                                <td>{{ $money(($assignment->ambassador_price ?? 0) + ($assignment->override_pay ?? 0) + ($assignment->pinata_amount ?? 0)) }}
                                </td>
                                <td class="text-nowrap">
                                    @if ($event)
                                        <div class="ambassador-action-row">
                                            <a class="btn-ds btn-primary-outline btn-sm"
                                            href="{{ route($routePrefix . '.edit.event', ['id' => $event->id]) }}">View</a>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No events found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page-card-design mb-4">
            <h6 class="mb-3">Recent Recaps</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Samples</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recaps as $recap)
                            <tr>
                                <td>{{ $recap->event?->event_name ?? '-' }}</td>
                                <td>{{ $recap->created_at ? $recap->created_at->format('M d, Y') : '-' }}</td>
                                <td>
                                    @if ($recap->is_draft)
                                        <span class="badge bg-secondary">Draft</span>
                                    @elseif($recap->is_approved)
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Submitted</span>
                                    @endif
                                </td>
                                <td>{{ $recap->samples ?? ($recap->sampling_number ?? '-') }}</td>
                                <td class="text-nowrap">
                                    <div class="ambassador-action-row">
                                        <a class="btn-ds btn-primary-outline btn-sm"
                                        href="{{ route($routePrefix . '.show-recap', ['id' => $recap->id]) }}">View</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No recaps found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="page-card-design">
                    <h6 class="mb-3">Recent Reimbursements</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Brand/Event</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reimbursements as $reimbursement)
                                    <tr>
                                        <td>{{ $reimbursement->expense_date ? $reimbursement->expense_date->format('M d, Y') : '-' }}
                                        </td>
                                        <td>{{ $reimbursement->event?->brand?->brand_name ?? ($reimbursement->brand?->brand_name ?? ($reimbursement->brand_name ?? '-')) }}
                                        </td>
                                        <td>{{ $money($reimbursement->amount) }}</td>
                                        <td><span
                                                class="badge {{ $statusBadge($reimbursement->status) }}">{{ ucfirst($reimbursement->status) }}</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="ambassador-action-row">
                                                <a class="btn-ds btn-primary-outline btn-sm"
                                                href="{{ route($routePrefix . '.reimbursements.show', $reimbursement) }}">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No reimbursements found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="page-card-design">
                    <h6 class="mb-3">Recent Transactions</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Bank</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->occurred_at ? $transaction->occurred_at->format('M d, Y') : '-' }}
                                        </td>
                                        <td>{{ $transaction->type_label }}</td>
                                        <td>{{ $money($transaction->amount) }}</td>
                                        <td><span
                                                class="badge {{ $statusBadge($transaction->status) }}">{{ $transaction->status_label }}</span>
                                        </td>
                                        <td>{{ $transaction->bank_name ?? '-' }} @if ($transaction->bank_account_last4)
                                                | {{ $transaction->bank_account_last4 }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No transactions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-card-design mb-4">
            <h6 class="mb-3">Recent Payouts</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Event</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Approved</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payouts as $payout)
                            <tr>
                                <td>{{ $payout->reference ?? '#' . $payout->id }}</td>
                                <td>{{ $payout->event?->event_name ?? ($payout->event?->brand?->brand_name ?? '-') }}</td>
                                <td>{{ $money($payout->amount) }}</td>
                                <td><span
                                        class="badge {{ $statusBadge($payout->status) }}">{{ ucfirst(str_replace('_', ' ', $payout->status)) }}</span>
                                </td>
                                <td>{{ $payout->approved_at ? $payout->approved_at->format('M d, Y') : '-' }}</td>
                                <td>{{ $payout->completed_at ? $payout->completed_at->format('M d, Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No payouts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
