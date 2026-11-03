@if (!empty($clients) && count($clients) > 0)
    @foreach ($clients as $client)
        <tr>
            <td>{{ $client->first_name }}</td>
            <td>{{ $client->last_name }}</td>
            <td>{{ $client->email }}</td>
            <td>{{ $client->phone }}</td>
            <td>
                <span class="span-text @if ($client->status == '1') success @else danger @endif">
                    @if ($client->status == '1')
                        Active
                    @else
                        Inactive
                    @endif
                </span>
            </td>
            <td>
                @php
                    $synced = !empty($client->quickbooks_customer_id);
                    $st = $client->qb_customer_sync_status;
                    $label = $st === 'failed' ? 'Failed' : ($synced ? 'Synced' : ($st ? ucfirst($st) : 'Not synced'));
                    $cls = $st === 'failed' ? 'danger' : ($synced ? 'success' : 'warning');
                @endphp
                <div class="d-flex flex-column">
                    <span class="span-text {{ $cls }}">{{ $label }}</span>
                    @if (!empty($client->qb_customer_last_sync_attempt_at))
                        <small class="text-muted">Last: {{ \Carbon\Carbon::parse($client->qb_customer_last_sync_attempt_at)->format('m/d/Y H:i') }}</small>
                    @endif
                    @if ($st === 'failed' && !empty($client->qb_customer_last_sync_error))
                        <small class="text-danger">{{ \Str::limit($client->qb_customer_last_sync_error, 80) }}</small>
                    @endif
                </div>
            </td>
            <td>
                <div>
                    <a data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"
                        href="{{ route($routePrefix . '.edit.client', ['id' => $client->id]) }}">
                        <button type="button" class="btn-ds btn-primary-outline btn-sm me-1">
                            <i class="fa fa-edit"></i>
                        </button>
                    </a>
                    <a data-bs-toggle="tooltip" data-bs-placement="top" title="View"
                        href="{{ route($routePrefix . '.show.client', ['client' => $client->id]) }}">
                        <button type="button" class="btn-ds btn-info-outline btn-sm me-1">
                            <i class="fa fa-eye"></i>
                        </button>
                    </a>
                    <a data-bs-toggle="tooltip" data-bs-placement="top" title="Transactions"
                        href="{{ route('admin.client.transactions', ['client' => $client->id]) }}">
                        <button type="button" class="btn-ds btn-secondary-outline btn-sm">
                            <i class="fa fa-file-invoice"></i>
                        </button>
                    </a>
                    @if (empty($client->quickbooks_customer_id) || $st === 'failed')
                        <form action="{{ route($routePrefix . '.client.sync-quickbooks', ['client' => $client->id]) }}"
                            method="POST" style="display:inline;" class="sync-qb-client-form">
                            @csrf
                            <button type="submit" class="btn-ds btn-warning-outline btn-sm sync-qb-client-btn"
                                title="Sync to QuickBooks">
                                <i class="fa fa-rotate-right"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="7" class="text-center">Data not found</td>
    </tr>
@endif
