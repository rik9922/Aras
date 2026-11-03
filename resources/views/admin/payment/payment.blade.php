@extends('layout.app', ['page_name' => 'Payments'])
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

@php
    $roleId = (string) (Auth::user()->role_id ?? '');
    $routePrefix = $roleId === '6' ? 'manager' : 'admin';
@endphp

@section('content')
    <div class="container py-3">

        <x-page-header title="Batch Payroll" subtitle="Manage and process ambassador payments" />

        {{-- ═══════════════════════════════════════════════════════════
         SEARCH / FILTER PANEL
    ═══════════════════════════════════════════════════════════ --}}
        <div class="page-card-design mb-3" style="background: #d79e8d17;border: 1px solid #d79e8d40;">
            <div class="row g-3 align-items-start">

                {{-- ── LEFT: Search filters ──────────────────────────────────── --}}
                <div class="col-md-5 col-xl-5">
                    <div class="fw-semibold text-uppercase small text-muted mb-2">Search</div>

                    {{-- Search Date Range (flatpickr range — defaults to last 30 days) --}}
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <input type="text" class="form-control shadow-none" id="searchDateRange"
                            placeholder="Select date range" autocomplete="off" readonly>
                        <input type="hidden" id="startDate" name="startDate">
                        <input type="hidden" id="endDate" name="endDate">
                    </div>

                    {{-- Ambassador Last Name --}}
                    <div class="mb-3">
                        <label for="ambassadorSelect" class="form-label">Ambassador</label>
                        <select class="form-select shadow-none" id="ambassadorSelect" name="ambassador_id">
                            <option value="">All Ambassadors</option>
                            @foreach ($ambassadors as $ambassador)
                                <option value="{{ $ambassador->id }}"
                                    {{ request('ambassador_id') == $ambassador->id ? 'selected' : '' }}>
                                    {{ $ambassador->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn-ds btn-primary-ds" id="searchBtn">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                        <button type="button" class="btn-ds btn-secondary-ds" id="resetFilters">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>

                {{-- ── CENTER: Payroll Status ───────────────────────────────── --}}
                <div class="col-md-3 col-xl-3">
                    <div class="fw-semibold text-uppercase small text-muted mb-2">Payroll Status</div>

                    <div class="d-flex flex-column gap-1">
                        @php
                            $statusOptions = [
                                'all' => 'All',
                                'created' => 'Created',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Error',
                                'cancelled' => 'Removed',
                            ];
                            $activeStatuses = (array) request('statuses', []);
                        @endphp
                        @foreach ($statusOptions as $value => $label)
                            <div class="form-check my-1">
                                <input class="form-check-input shadow-none status-checkbox" type="checkbox"
                                    value="{{ $value }}" id="cb_{{ $value }}"
                                    {{ in_array($value, $activeStatuses) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="cb_{{ $value }}">
                                    {{ $label }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── RIGHT: Payroll Generation ────────────────────────────── --}}
                <div class="col-md-4 col-xl-4">
                    <div class="fw-semibold text-uppercase small text-muted mb-2">Payroll Generation</div>

                    <div class="mb-3">
                        <label class="form-label">Payroll Date Range</label>
                        <input type="text" class="form-control shadow-none" id="payrollDateRange"
                            placeholder="Select date range" autocomplete="off" readonly>
                        {{-- Hidden fields populated by flatpickr --}}
                        <input type="hidden" id="generateStartDate" name="generate_start_date">
                        <input type="hidden" id="generateEndDate" name="generate_end_date">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn-ds btn-primary-ds" id="generatePayrollBtn">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Generate
                        </button>
                        <button type="button" class="btn-ds btn-success-ds" id="processPayrollBtn">
                            <i class="fa-solid fa-circle-play"></i> Process
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
         PAYMENTS TABLE
    ═══════════════════════════════════════════════════════════ --}}
        <div class="page-card-design">
            <div class="table-responsive">
                @include('admin.payment.partials.payment-table')
            </div>
            <div class="d-flex justify-content-end mt-3 pagination-container">
                {{ $rows->links('vendor.pagination.custom') }}
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {

            $('#ambassadorSelect').select2({
                width: '100%'
            });

            // ── Date helpers ────────────────────────────────────────────────
            function fmtDate(d) {
                return d.getFullYear() + '-' +
                    String(d.getMonth() + 1).padStart(2, '0') + '-' +
                    String(d.getDate()).padStart(2, '0');
            }

            function addDays(d, n) {
                var r = new Date(d);
                r.setDate(r.getDate() + n);
                return r;
            }

            // ── Search date range default: last 30 days ending today ────────
            var today = new Date();
            var search30 = addDays(today, -30);

            var searchPicker = flatpickr('#searchDateRange', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'm/d/Y',
                defaultDate: [fmtDate(search30), fmtDate(today)],
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        $('#startDate').val(fmtDate(selectedDates[0]));
                        $('#endDate').val(fmtDate(selectedDates[1]));
                    } else {
                        $('#startDate').val('');
                        $('#endDate').val('');
                    }
                },
            });
            // Populate hidden fields for the initial default
            $('#startDate').val(fmtDate(search30));
            $('#endDate').val(fmtDate(today));

            // ── Payroll date range default: dynamic rolling window ────────
            // Rule:
            //   End Date   = Today - 2 days
            //   Start Date = End Date - 15 days
            var payrollEnd   = addDays(today, -2);
            var payrollStart = addDays(payrollEnd, -15);

            var payrollPicker = flatpickr('#payrollDateRange', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'm/d/Y',
                defaultDate: [fmtDate(payrollStart), fmtDate(payrollEnd)],
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        $('#generateStartDate').val(fmtDate(selectedDates[0]));
                        $('#generateEndDate').val(fmtDate(selectedDates[1]));
                    } else {
                        $('#generateStartDate').val('');
                        $('#generateEndDate').val('');
                    }
                },
            });
            // Populate hidden fields for the initial default
            $('#generateStartDate').val(fmtDate(payrollStart));
            $('#generateEndDate').val(fmtDate(payrollEnd));

            // ── Collect active filter values ────────────────────────────────
            function getFilters(page) {
                page = page || 1;
                var statuses = [];
                $('.status-checkbox:checked').each(function() {
                    statuses.push($(this).val());
                });
                // If 'all' is checked or nothing is checked → no status filter
                if (statuses.includes('all')) statuses = [];

                return {
                    startDate: $('#startDate').val(),
                    endDate: $('#endDate').val(),
                    ambassador_id: $('#ambassadorSelect').val(),
                    statuses: statuses,
                    page: page,
                };
            }

            // ── Load payments via AJAX ──────────────────────────────────────
            function loadPayments(page) {
                var data = getFilters(page);
                $.ajax({
                    url: "{{ route($routePrefix . '.payments') }}",
                    type: 'GET',
                    data: data,
                    beforeSend: function() {
                        $('.table-responsive').html(
                            '<div class="text-center p-5"><i class="fa-solid fa-spinner fa-spin fa-2x text-muted"></i></div>'
                        );
                    },
                    success: function(response) {
                        $('.table-responsive').html(response.table_html);
                        $('.pagination-container').html(response.pagination);
                    },
                    error: function(xhr) {
                        console.error('Payment filter error:', xhr.responseText);
                    }
                });
            }

            // ── "All" checkbox toggles the rest off ────────────────────────
            $('#cb_all').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.status-checkbox').not(this).prop('checked', false);
                }
            });
            $('.status-checkbox').not('#cb_all').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#cb_all').prop('checked', false);
                }
                loadPayments(1);
            });
            $('#cb_all').on('change', function() {
                loadPayments(1);
            });

            // ── Filter triggers ─────────────────────────────────────────────
            $('#searchBtn').on('click', function() {
                loadPayments(1);
            });

            $('#ambassadorSelect').on('change', function() {
                loadPayments(1);
            });

            // Search date range change is handled inside flatpickr onChange above

            // ── Pagination ──────────────────────────────────────────────────
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                var page = href ? (href.split('page=')[1] || 1) : 1;
                loadPayments(page);
            });

            // ── Reset — restore pickers to their defaults ──────────────────
            $('#resetFilters').on('click', function() {
                // Restore search range to last 30 days
                searchPicker.setDate([fmtDate(search30), fmtDate(today)], true);
                $('#startDate').val(fmtDate(search30));
                $('#endDate').val(fmtDate(today));
                $('#ambassadorSelect').val(null).trigger('change');
                $('.status-checkbox').prop('checked', false);
                loadPayments(1);
            });

            // ── Generate Payroll ──────────────────────────────────────────
            $('#generatePayrollBtn').on('click', function() {
                var start = $('#generateStartDate').val();
                var end   = $('#generateEndDate').val();

                if (!start || !end) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Range Required',
                        text: 'Please select a payroll date range before generating.',
                        confirmButtonColor: '#1c4975',
                    });
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Generating...');

                $.ajax({
                    url:  "{{ route($routePrefix . '.payments.generate-payroll') }}",
                    type: 'POST',
                    data: {
                        _token:              "{{ csrf_token() }}",
                        generate_start_date: start,
                        generate_end_date:   end,
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Payroll Batch Generated Successfully.',
                                html:
                                    '<div class="text-start mt-2">'
                                    + '<p><strong>Batch:</strong><br>' + res.batch_number + '</p>'
                                    + '<p><strong>Payroll Period:</strong><br>' + res.period_start + ' - ' + res.period_end + '</p>'
                                    + '<p><strong>Payments:</strong><br>' + res.total_records + '</p>'
                                    + '<p><strong>Total:</strong><br>$' + res.total_amount + '</p>'
                                    + '</div>',
                                confirmButtonColor: '#1c4975',
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.message, confirmButtonColor: '#1c4975' });
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON ? xhr.responseJSON.message : 'An unexpected error occurred.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonColor: '#1c4975' });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fa-solid fa-file-invoice-dollar"></i> Generate');
                    },
                });
            });

            // ── Process — SweetAlert confirmation ────────────────────────────
            $('#processPayrollBtn').on('click', function() {
                Swal.fire({
                    title: 'Process Payroll?',
                    text: 'This will process any payments in the Created status, proceed?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#1c4975',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'OK',
                    cancelButtonText: 'Cancel',
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var $btn = $('#processPayrollBtn');
                        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Processing...');

                        $.ajax({
                            url:  "{{ route($routePrefix . '.payments.process-payroll') }}",
                            type: 'POST',
                            data: { _token: "{{ csrf_token() }}" },
                            success: function(res) {
                                if (res.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Processing Started',
                                        text: res.message,
                                        confirmButtonColor: '#1c4975',
                                    }).then(function() {
                                        loadPayments(1);
                                    });
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error', text: res.message, confirmButtonColor: '#1c4975' });
                                }
                            },
                            error: function(xhr) {
                                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'An unexpected error occurred.';
                                Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonColor: '#1c4975' });
                            },
                            complete: function() {
                                $btn.prop('disabled', false).html('<i class="fa-solid fa-circle-play"></i> Process');
                            },
                        });
                    }
                });
            });

        });
    </script>
@endpush
