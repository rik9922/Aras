@extends('layout.app', ['page_name' => 'Bill To'])

@section('content')
    @php
        $clientsCreate = $canManage
            ? route($routePrefix . '.clients.create')
            : null;
        $f = $filters ?? [
            'name' => '',
            'qb_id' => '',
            'active_only' => false,
            'contract_received_only' => false,
        ];
    @endphp
    <div class="container">
        <div class="page-card-design">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="h5 text-uppercase mb-0">
                    Bill To:
                    <span class="text-muted" id="bill-total-showing">{{ $billables->total() }}</span>
                    <span class="text-muted small fw-normal">/ {{ $totalBillables ?? $billables->total() }} total</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if ($canManage && $clientsCreate)
                        <a href="{{ $clientsCreate }}" class="btn-ds btn-secondary-ds grey-color btn-sm text-uppercase">Add client</a>
                    @endif
                    @if ($canManage && in_array($routePrefix, ['admin', 'manager']))
                        <form action="{{ route($routePrefix . '.bills.sync-all') }}" method="POST" class="sync-all-qb-form d-inline">
                            @csrf
                            <button type="submit" class="btn-ds btn-warning-ds btn-sm sync-all-qb-btn"
                                @if (($unsyncedCount ?? 0) == 0) disabled @endif>
                                Sync with QuickBooks ({{ $unsyncedCount ?? 0 }})
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($canManage && in_array($routePrefix, ['admin', 'manager']))
                <form id="billBulkForm" class="border rounded p-2 my-3" data-url="{{ route($routePrefix . '.bills.bulk-update') }}">
                    @csrf
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div><label class="form-label small mb-0">Bulk edit selected</label>
                            <select id="billBulkField" class="form-select form-select-sm">
                                <option value="status">Status (0/1)</option><option value="phone">Phone</option><option value="address">Address</option>
                                <option value="city">City</option><option value="state">State</option><option value="zip_code">ZIP</option>
                                <option value="company_name">Company</option><option value="primary_billing_contact">Billing contact</option>
                                <option value="default_labor_amount">Default labor</option><option value="default_buyback_amount">Default buyback</option><option value="default_cups_amount">Default cups</option>
                            </select>
                        </div>
                        <div><label class="form-label small mb-0">Value</label><input id="billBulkValue" class="form-control form-control-sm" required></div>
                        <button class="btn-ds btn-primary-ds btn-sm" type="submit">Apply to selected</button>
                        <small class="text-muted">Select records below. QuickBooks ID and email cannot be changed here.</small>
                    </div>
                </form>
            @endif

            <div class="alert alert-info small my-3">
                <strong>Adding a new Bill To?</strong> Create a <strong>Bill To</strong>@if ($clientsCreate)
                    (<a href="{{ $clientsCreate }}">Add client</a>)@endif. Similar existing billings are flagged on save to reduce QuickBooks duplicates.
            </div>

            <div id="bill-ajax-error" class="alert alert-danger small d-none" role="alert"></div>

            <div class="row align-items-end g-2">
                <div class="col-md-4 col-lg-4">
                    <label class="form-label text-uppercase small mb-0">Name / Email / Phone</label>
                    <input type="text" class="form-control shadow-none" id="billFilterName" name="name"
                        value="{{ $f['name'] }}" placeholder="Name, email or phone…" autocomplete="off">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label text-uppercase small mb-0">QuickBooks Customer ID</label>
                    <input type="text" class="form-control shadow-none" id="billFilterQbId" name="qb_id"
                        value="{{ $f['qb_id'] }}" placeholder="QuickBooks customer ID" autocomplete="off">
                </div>
                <div class="col-md-12 col-lg-5">
                    <div class="d-flex flex-wrap gap-3 pt-2">
                        <div class="form-check">
                            <input class="form-check-input shadow-none" type="checkbox" id="billFilterActiveOnly"
                                {{ !empty($f['active_only']) ? 'checked' : '' }}>
                            <label class="form-check-label text-uppercase" for="billFilterActiveOnly">Active only</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input shadow-none" type="checkbox" id="billFilterContractOnly"
                                {{ !empty($f['contract_received_only']) ? 'checked' : '' }}>
                            <label class="form-check-label text-uppercase" for="billFilterContractOnly">Contract received</label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                    <button type="button" class="btn-ds btn-primary-ds text-uppercase" id="billFilterApply">Filter</button>
                    <button type="button" class="btn-ds btn-secondary-outline text-uppercase" id="billFilterReset">Reset</button>
                    @if ($exportUrl)
                        <button type="button" class="btn-ds btn-primary-outline text-uppercase" id="billExportBtn">Export</button>
                    @endif
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            @if ($canManage && in_array($routePrefix, ['admin', 'manager']))<th><input type="checkbox" class="form-check-input" id="billSelectAll" aria-label="Select all"></th>@endif
                            <th>Name</th>
                            <th>Parent Bill To</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th class="text-nowrap">QuickBooks ID</th>
                            <th>Contract</th>
                            <th>Status</th>
                            <th class="text-nowrap">QB Sync</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bill-table-body">
                        @include('admin.bill.partials.bill-table-body', [
                            'billables' => $billables,
                            'routePrefix' => $routePrefix,
                            'canManage' => $canManage,
                            'quickbooksConnected' => $quickbooksConnected,
                            'parentOptions' => $parentOptions,
                        ])
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3" id="bill-pagination">
                {{ $billables->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>

    {{-- Monthly Summary Modal --}}
    <div class="modal fade" id="monthlySummaryModal" tabindex="-1" aria-labelledby="monthlySummaryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="monthlySummaryModalLabel">Monthly Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-uppercase small mb-1">Month</label>
                        <input type="month" class="form-control shadow-none" id="summaryMonthPicker"
                            value="{{ now()->format('Y-m') }}">
                    </div>
                    <div id="summaryLoading" class="text-center d-none py-3">
                        <span class="spinner-border spinner-border-sm"></span> Loading…
                    </div>
                    <div id="summaryContent" class="d-none">
                        <p class="text-muted small mb-2" id="summaryMonthLabel"></p>
                        <table class="table table-sm table-bordered">
                            <tbody>
                                <tr><td>Submitted</td><td id="statSubmitted" class="fw-medium"></td></tr>
                                <tr><td>Not Recapped</td><td id="statNotRecapped" class="fw-medium"></td></tr>
                                <tr><td>No Shows</td><td id="statNoShows" class="fw-medium"></td></tr>
                                <tr><td>Late</td><td id="statLate" class="fw-medium"></td></tr>
                                <tr><td>Cancelled</td><td id="statCancelled" class="fw-medium"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="summaryError" class="alert alert-danger small d-none"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const billsUrl  = @json($billsIndexUrl);
            const exportUrl = @json($exportUrl);
            const qbConnected = @json($quickbooksConnected ?? false);

            document.getElementById('billSelectAll')?.addEventListener('change', function () {
                document.querySelectorAll('.bill-select').forEach(cb => cb.checked = this.checked);
            });
            document.addEventListener('submit', function (e) {
                const form = e.target.closest('.bill-parent-form');
                if (!form) return;
                e.preventDefault();
                fetch(form.action, { method: 'POST', credentials: 'same-origin', headers: {'Accept':'application/json','X-CSRF-TOKEN': form.querySelector('[name=_token]').value, 'Content-Type':'application/json'}, body: JSON.stringify({parent_bill_to_id: form.querySelector('select').value || null}) })
                    .then(r => r.json().then(data => ({ok:r.ok,data})))
                    .then(({ok,data}) => { if (!ok) throw new Error(data.message || 'Parent update failed'); Swal.fire({icon:'success',title:'Parent updated',timer:1400,showConfirmButton:false}); loadBills(null); })
                    .catch(err => Swal.fire('Parent update failed', err.message, 'error'));
            });

            document.getElementById('billBulkForm')?.addEventListener('submit', function (e) {
                e.preventDefault();
                const ids = [...document.querySelectorAll('.bill-select:checked')].map(cb => Number(cb.value));
                if (!ids.length) { Swal.fire('Select Bill To records', 'Choose at least one record first.', 'warning'); return; }
                const field = document.getElementById('billBulkField').value;
                const value = document.getElementById('billBulkValue').value;
                const updates = ids.map(id => ({ id, [field]: value }));
                fetch(this.dataset.url, { method: 'POST', credentials: 'same-origin', headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': this.querySelector('[name=_token]').value}, body: JSON.stringify({updates}) })
                    .then(r => r.json().then(data => ({ok:r.ok,data})))
                    .then(({ok,data}) => { if (!ok) throw new Error(data.message || 'Update failed'); Swal.fire({icon:'success',title:'Updated',timer:1800,showConfirmButton:false}); loadBills(null); })
                    .catch(err => Swal.fire('Bulk update failed', err.message, 'error'));
            });

            // ── QB sync guards ──────────────────────────────────────────────
            document.querySelector('.sync-all-qb-form')?.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!qbConnected) {
                    Swal.fire('QuickBooks not connected', 'Please connect to QuickBooks before syncing Bill To records.', 'warning');
                    return;
                }
                Swal.fire({
                    title: 'Sync all with QuickBooks?',
                    text: 'This will sync all unsynced Bill To records with QuickBooks.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, sync all',
                }).then(result => { if (result.isConfirmed) e.target.submit(); });
            });

            document.addEventListener('submit', function (e) {
                if (!e.target.classList.contains('sync-qb-bill-form')) return;
                if (!qbConnected) {
                    e.preventDefault();
                    Swal.fire('QuickBooks not connected', 'Please connect to QuickBooks before syncing this Bill To record.', 'warning');
                }
            });

            @if (session('error'))
                Swal.fire('Unable to sync', @json(session('error')), 'warning');
            @endif
            @if (session('success'))
                Swal.fire({ icon: 'success', title: @json(session('success')), timer: 3000, showConfirmButton: false });
            @endif

            // ── AJAX list ───────────────────────────────────────────────────
            function filterPayloadObject() {
                return {
                    name: (document.getElementById('billFilterName') || {}).value || '',
                    qb_id: (document.getElementById('billFilterQbId') || {}).value || '',
                    active_only: document.getElementById('billFilterActiveOnly')?.checked ? '1' : '0',
                    contract_received_only: document.getElementById('billFilterContractOnly')?.checked ? '1' : '0',
                    bills_ajax: '1',
                };
            }

            function applyPayloadToUrl(baseUrl) {
                const u = new URL(baseUrl, window.location.href);
                const p = filterPayloadObject();
                Object.keys(p).forEach(k => u.searchParams.set(k, p[k]));
                return u.toString();
            }

            function loadBills(pageUrl) {
                const fullUrl = pageUrl ? applyPayloadToUrl(pageUrl) : applyPayloadToUrl(billsUrl);
                const errEl = document.getElementById('bill-ajax-error');

                fetch(fullUrl, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                    .then(data => {
                        if (errEl) { errEl.classList.add('d-none'); errEl.textContent = ''; }
                        if (data.html) document.getElementById('bill-table-body').innerHTML = data.html;
                        if (data.pagination) document.getElementById('bill-pagination').innerHTML = data.pagination;
                        if (typeof data.total !== 'undefined') {
                            const el = document.getElementById('bill-total-showing');
                            if (el) el.textContent = data.total;
                        }
                    })
                    .catch(() => {
                        if (errEl) {
                            errEl.textContent = 'Could not refresh the list. Try reloading the page.';
                            errEl.classList.remove('d-none');
                        }
                    });
            }

            document.getElementById('billFilterApply')?.addEventListener('click', () => loadBills(null));
            document.getElementById('billFilterReset')?.addEventListener('click', function () {
                ['billFilterName', 'billFilterQbId'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                ['billFilterActiveOnly', 'billFilterContractOnly'].forEach(id => { const el = document.getElementById(id); if (el) el.checked = false; });
                loadBills(null);
            });

            document.getElementById('billExportBtn')?.addEventListener('click', function () {
                if (!exportUrl) return;
                const p = new URLSearchParams();
                const o = filterPayloadObject();
                delete o.bills_ajax;
                Object.keys(o).forEach(k => p.set(k, o[k]));
                window.location.href = exportUrl + (exportUrl.indexOf('?') >= 0 ? '&' : '?') + p.toString();
            });

            document.getElementById('bill-pagination')?.addEventListener('click', function (e) {
                const a = e.target.closest('a');
                if (!a || !a.getAttribute('href')) return;
                e.preventDefault();
                loadBills(a.href);
            });

            let t;
            document.getElementById('billFilterName')?.addEventListener('keyup', function () {
                clearTimeout(t); t = setTimeout(() => loadBills(null), 450);
            });
            ['billFilterQbId', 'billFilterActiveOnly', 'billFilterContractOnly'].forEach(id => {
                document.getElementById(id)?.addEventListener('change', () => loadBills(null));
            });

            // ── Monthly Summary Modal ───────────────────────────────────────
            let currentClientId = null;

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-monthly-summary');
                if (!btn) return;
                currentClientId = btn.dataset.clientId;
                document.getElementById('summaryContent').classList.add('d-none');
                document.getElementById('summaryError').classList.add('d-none');
                document.getElementById('summaryLoading').classList.add('d-none');
                document.getElementById('monthlySummaryModalLabel').textContent =
                    'Monthly Summary — ' + (btn.dataset.clientName || '');
                const modal = new bootstrap.Modal(document.getElementById('monthlySummaryModal'));
                modal.show();
                loadSummary();
            });

            document.getElementById('summaryMonthPicker')?.addEventListener('change', loadSummary);

            function loadSummary() {
                if (!currentClientId) return;
                const month = document.getElementById('summaryMonthPicker').value;
                const statsUrl = '/' + @json($routePrefix) + '/bill-to/' + currentClientId + '/monthly-stats?month=' + month;

                document.getElementById('summaryLoading').classList.remove('d-none');
                document.getElementById('summaryContent').classList.add('d-none');
                document.getElementById('summaryError').classList.add('d-none');

                fetch(statsUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                    .then(data => {
                        document.getElementById('summaryLoading').classList.add('d-none');
                        document.getElementById('summaryMonthLabel').textContent = data.month;
                        document.getElementById('statSubmitted').textContent   = data.submitted;
                        document.getElementById('statNotRecapped').textContent = data.not_recapped;
                        document.getElementById('statNoShows').textContent     = data.no_shows;
                        document.getElementById('statLate').textContent        = data.late;
                        document.getElementById('statCancelled').textContent   = data.cancelled;
                        document.getElementById('summaryContent').classList.remove('d-none');
                    })
                    .catch(() => {
                        document.getElementById('summaryLoading').classList.add('d-none');
                        const errEl = document.getElementById('summaryError');
                        errEl.textContent = 'Could not load stats. Please try again.';
                        errEl.classList.remove('d-none');
                    });
            }
        });
    </script>
@endpush
