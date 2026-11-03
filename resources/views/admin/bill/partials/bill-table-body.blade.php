@forelse ($billables as $row)
    @php
        $displayName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        if ($displayName === '') {
            $displayName = $row->name ?? '—';
        }
        $isActive   = (string) $row->status === '1' || (int) $row->status === 1;
        $contractOk = (int) $row->contract_recieved === 1 || (string) $row->contract_recieved === '1';
        $profile    = $row->clientProfile;
        $synced     = !empty($profile?->quickbooks_customer_id);
        $st         = $profile?->qb_customer_sync_status;
        $qbLabel    = $st === 'failed' ? 'Failed' : ($synced ? 'Synced' : ($st ? ucfirst($st) : 'Not synced'));
        $qbCls      = $st === 'failed' ? 'danger' : ($synced ? 'success' : 'warning');
    @endphp
    <tr>
        @if (!empty($canManage) && $canManage)
            <td><input type="checkbox" class="form-check-input bill-select" value="{{ $row->id }}" aria-label="Select {{ $displayName }}"></td>
        @endif
        <td>
            <div class="fw-medium">{{ $displayName }}</div>
            @if ($row->clientProfile?->childBillTos?->isNotEmpty())
                <small class="text-muted">{{ $row->clientProfile->childBillTos->count() }} child billing{{ $row->clientProfile->childBillTos->count() === 1 ? '' : 's' }}</small>
            @endif
        </td>
        <td>
            @if (!empty($canManage) && $canManage)
                <form method="POST" action="{{ route($routePrefix . '.bills.parent', ['client' => $row->id]) }}" class="bill-parent-form">
                    @csrf
                    <select class="form-select form-select-sm" aria-label="Parent Bill To for {{ $displayName }}" onchange="this.form.requestSubmit()">
                        <option value="">None (parent)</option>
                        @foreach (($parentOptions ?? []) as $option)
                            @if ((int) $option->id !== (int) $row->id)
                                @php $optionName = trim(($option->first_name ?? '').' '.($option->last_name ?? '')) ?: ($option->name ?? '—'); @endphp
                                <option value="{{ $option->id }}" @selected((int) ($profile?->parent_bill_to_id ?? 0) === (int) $option->id)>{{ $optionName }}</option>
                            @endif
                        @endforeach
                    </select>
                </form>
            @else
                {{ $profile?->parentBillTo?->name ?: '—' }}
            @endif
        </td>
        <td>{{ $row->email ?: '—' }}</td>
        <td>{{ $row->phone ?: '—' }}</td>
        <td>{{ $profile?->quickbooks_customer_id ?: '—' }}</td>
        <td>
            @if ($contractOk)
                <span class="badge bg-success">Yes</span>
            @else
                <span class="badge bg-secondary">No</span>
            @endif
        </td>
        <td>
            @if ($isActive)
                <span class="badge bg-success">Active</span>
            @else
                <span class="badge bg-secondary">Inactive</span>
            @endif
        </td>
        <td>
            <div class="d-flex flex-column">
                <span class="span-text {{ $qbCls }}">{{ $qbLabel }}</span>
                @if (!empty($profile?->qb_customer_last_sync_attempt_at))
                    <small class="text-muted">Last: {{ \Carbon\Carbon::parse($profile->qb_customer_last_sync_attempt_at)->format('m/d/Y H:i') }}</small>
                @endif
                @if ($st === 'failed' && !empty($profile?->qb_customer_last_sync_error))
                    <small class="text-danger">{{ \Str::limit($profile->qb_customer_last_sync_error, 80) }}</small>
                @endif
            </div>
        </td>
        <td>
            @if (!empty($canManage) && $canManage)
                <div class="d-flex flex-wrap gap-1">
                    <a data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Bill to" href="{{ route($routePrefix . '.edit.client', ['id' => $row->id]) }}"
                        class="btn-ds btn-primary-ds btn-sm text-uppercase" title="Edit"><i class="fa fa-pencil"></i></a>
                    @if (in_array($routePrefix, ['admin', 'manager']))
                        @if ($routePrefix === 'admin')
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="View Bill to" href="{{ route($routePrefix.'.show.client', ['client' => $row->id]) }}"
                            class="btn-ds btn-info-ds btn-sm text-uppercase" title="View"><i class="fa fa-eye"></i></a>
                        @endif
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Show Bill to Transactions" href="{{ route($routePrefix.'.client.transactions', ['client' => $row->id]) }}"
                            class="btn-ds btn-secondary-ds btn-sm text-uppercase" title="Transactions"><i class="fa fa-file-alt"></i></a>
                    @endif
                    <button data-bs-toggle="tooltip" data-bs-placement="top" title="Show Monthly Summary" type="button"
                        class="btn-ds btn-success-ds btn-sm text-uppercase btn-monthly-summary"
                        data-client-id="{{ $row->id }}"
                        data-client-name="{{ $displayName }}"
                        title="Monthly Summary"><i class="fa fa-calendar-alt"></i></button>
                    @if ((!$synced || $st === 'failed') && in_array($routePrefix, ['admin', 'manager']))
                        <form action="{{ route($routePrefix . '.bills.sync-client', ['client' => $row->id]) }}"
                            method="POST" class="sync-qb-bill-form d-inline">
                            @csrf
                            <button data-bs-toggle="tooltip" data-bs-placement="top" title="Sync to QuickBooks" type="submit" class="btn-ds btn-warning-ds btn-sm" title="Sync to QuickBooks">
                                <i class="fa fa-rotate-right"></i>
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <span class="text-muted small">—</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ !empty($canManage) && $canManage ? 10 : 9 }}" class="text-center text-muted py-4">No billables match your filters.</td>
    </tr>
@endforelse
