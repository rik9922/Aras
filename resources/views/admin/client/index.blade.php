@extends('layout.app', ['page_name' => 'Bill To'])
@section('content')
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Bill To : <span class="total-data">
                    {{ $totalClients ?? 0 }}
                </span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="d-flex justify-content-end gap-3">
                <div class="d-grid">
                    <a href="{{ route($routePrefix . '.clients.create') }}">
                        <button type="button" class="btn-ds btn-secondary-ds grey-color">Create Bill To</button>
                    </a>
                </div>

                <div class="d-grid">
                    <form action="{{ route($routePrefix . '.sync-clients-quickbooks') }}" method="POST" style="display: inline;" class="sync-all-qb-form">
                        @csrf
                        <button type="submit" class="btn-ds btn-warning-ds sync-all-qb-btn"
                            @if ($unsyncedCount == 0) disabled @endif>
                            Sync with QuickBooks ({{ $unsyncedCount }})
                        </button>
                    </form>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6 col-lg-8 pt-3">
                    <input type="search" class="form-control shadow-none" id="search" name="serach"
                        placeholder="Search for a Name, Email or Phone">
                </div>
                <div class="col-md-3 col-lg-2 pt-3">
                    <div class="form-check">
                        <input class="form-check-input shadow-none" type="checkbox" value="0" name="inactive"
                            id="viewInactiveCheckbox">
                        <label class="form-check-label text-uppercase" for="viewInactiveCheckbox">
                            View Inactive
                        </label>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div class="text-nowrap">First Name</div>
                            </th>
                            <th>
                                <div class="text-nowrap">Last Name</div>
                            </th>
                            <th>
                                <div>Email</div>
                            </th>
                            <th>
                                <div>Phone</div>
                            </th>
                            <th>
                                <div>Status</div>
                            </th>
                            <th>
                                <div class="text-nowrap">QB Sync</div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="client-table">
                        @if (!empty($clients) && count($clients) > 0)
                            @foreach ($clients as $client)
                                <tr>
                                    <td>{{ $client->first_name }}</td>
                                    <td>{{ $client->last_name }}</td>
                                    <td>{{ $client->email }}</td>
                                    <td>{{ $client->phone }}</td>
                                    <td>
                                        <span
                                            class="span-text @if ($client->status == '1') success @else danger @endif">
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
                                                    <button data-bs-toggle="tooltip" data-bs-placement="top" title="Sync to QuickBooks" type="submit" class="btn-ds btn-warning-outline btn-sm sync-qb-client-btn">
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
                                <td class="text-center" colspan="7">Data not found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3">
                    {{ $clients->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        const quickbooksConnected = @json($quickbooksConnected ?? false);

        $(document).on('submit', '.sync-all-qb-form', function(e) {
            if (!quickbooksConnected) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire('QuickBooks not connected', 'Please connect to QuickBooks before syncing Bill To records.', 'warning');
                }
                return false;
            }
            if (!confirm('This will sync all Bill To records with QuickBooks. Continue?')) {
                e.preventDefault();
                return false;
            }
        });

        $(document).on('click', '.sync-qb-client-btn', function(e) {
            if (!quickbooksConnected) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire('QuickBooks not connected', 'Please connect to QuickBooks before syncing this Bill To record.', 'warning');
                }
                return false;
            }
        });

        @if (session('error'))
            if (typeof Swal !== 'undefined') {
                Swal.fire('Unable to sync', @json(session('error')), 'warning');
            }
        @endif

        function loadFilteredClients() {
            let search = $('#search').val();
            let inactive = $('#viewInactiveCheckbox').prop('checked') ? '0' : '';

            $.ajax({
                url: '{{ route($routePrefix . '.clients') }}',
                method: 'GET',
                data: {
                    search: search,
                    inactive: inactive
                },
                success: function(data) {
                    $('#client-table').html(data.html);
                    if (typeof data.totalCount !== 'undefined') {
                        $('.total-data').text(data.totalCount);
                    }
                }
            });
        }

        $('#search, #viewInactiveCheckbox').on('change keyup', loadFilteredClients);

        $('#search').on('input', function() {
            if ($(this).val() === '') {
                loadFilteredClients();
            }
        });

    });
</script>
