@extends('layout.app', ['page_name' => 'Payment'])

@section('content')
    <div class="container py-3">
        <div class="d-grid">
            <div class="h5">Payments</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="card list-form-card mt-3">
                <div class="row p-3">
                    <div class="col-md-9">
                        <div class="row">
                            <div class="h4">Search</div>
                        
                            <!-- Date Range -->
                            <div class="col-md-12 col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Date Range</label>
                                    <div class="d-flex align-items-center">
                                        <input type="date" class="form-control shadow-none" id="startDate" name="startDate">
                                        <div class="px-2">to</div>
                                        <input type="date" class="form-control shadow-none" id="endDate" name="endDate">
                                    </div>
                                </div>
                            </div>
                        
                            <!-- Ambassador -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="ambassadorSelect" class="form-label">Ambassador</label>
                                    <select class="form-select" id="ambassadorSelect">
                                        <option value="">All Ambassadors</option>
                                        @foreach ($ambassadors as $ambassador)
                                            <option value="{{ $ambassador->id }}">{{ $ambassador->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 mt-2 d-flex gap-2">
                                <button type="button" class="btn-ds btn-primary-ds" id="searchBtn">Search</button>
                                <button type="button" class="btn-ds btn-secondary-outline" id="resetFilters">Reset</button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="h4">Generate</div>
                            <!-- Date Range -->
                            <div class="col-md-12 col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Date Range</label>
                                    <div class="d-flex align-items-center">
                                        <input type="date" class="form-control shadow-none" name="start_date" id="generateStartDate">
                                        <div class="px-2">to</div>
                                        <input type="date" class="form-control shadow-none" name="end_date" id="generateEndDate">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <div>
                            <div class="form-check my-2">
                                <input class="form-check-input shadow-none status-checkbox" type="checkbox" value="all" id="allcheckbox">
                                <label class="form-check-label" for="allcheckbox">All</label>
                            </div>
                            <div class="form-check my-2">
                                <input class="form-check-input shadow-none status-checkbox" type="checkbox" value="created" id="cb_created">
                                <label class="form-check-label" for="cb_created">Created</label>
                            </div>
                            <div class="form-check my-2">
                                <input class="form-check-input shadow-none status-checkbox" type="checkbox" value="processing" id="cb_processing">
                                <label class="form-check-label" for="cb_processing">Processing</label>
                            </div>
                            <div class="form-check my-2">
                                <input class="form-check-input shadow-none status-checkbox" type="checkbox" value="completed" id="cb_completed">
                                <label class="form-check-label" for="cb_completed">Completed</label>
                            </div>
                            <div class="form-check my-2">
                                <input class="form-check-input shadow-none status-checkbox" type="checkbox" value="failed" id="cb_failed">
                                <label class="form-check-label" for="cb_failed">Failed</label>
                            </div>
                            <div class="form-check my-2">
                                <input class="form-check-input shadow-none status-checkbox" type="checkbox" value="cancelled" id="cb_cancelled">
                                <label class="form-check-label" for="cb_cancelled">Cancelled</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                @include('admin.payment.partials.payment-table')
            </div>
            <!-- Pagination -->
            <div class="d-flex justify-content-end mt-3 pagination-container">
                {{ $rows->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $("#ambassadorSelect").select2();

    function getCheckedStatuses() {
        var checked = [];
        $('.status-checkbox:checked').each(function() {
            checked.push($(this).val());
        });
        // 'all' selected or nothing selected → no status filter
        if (checked.length === 0 || checked.includes('all')) return [];
        return checked;
    }

    function loadPayments(page) {
        page = page || 1;
        var statuses = getCheckedStatuses();
        var data = {
            startDate:      $('#startDate').val(),
            endDate:        $('#endDate').val(),
            ambassador_id:  $('#ambassadorSelect').val(),
        };
        if (statuses.length) {
            data.statuses = statuses;
        }

        $.ajax({
            url: "{{ route('admin.payments') }}?page=" + page,
            type: 'GET',
            data: data,
            beforeSend: function() {
                $('.table-responsive').html('<div class="text-center p-5">Loading...</div>');
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

    // "All" checkbox toggles the rest off
    $('#allcheckbox').on('change', function() {
        if ($(this).is(':checked')) {
            $('.status-checkbox').not(this).prop('checked', false);
        }
    });
    $('.status-checkbox').not('#allcheckbox').on('change', function() {
        if ($(this).is(':checked')) {
            $('#allcheckbox').prop('checked', false);
        }
    });

    // Search button
    $('#searchBtn').on('click', function() {
        loadPayments(1);
    });

    // Ambassador select change
    $('#ambassadorSelect').on('change', function() {
        loadPayments(1);
    });

    // Date inputs — trigger on change
    $('#startDate, #endDate').on('change', function() {
        loadPayments(1);
    });

    // Status checkboxes — trigger on change
    $('.status-checkbox').on('change', function() {
        loadPayments(1);
    });

    // Pagination
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        var page = $(this).attr('href').split('page=')[1];
        loadPayments(page);
    });

    // Reset
    $('#resetFilters').on('click', function() {
        $('#startDate, #endDate').val('');
        $('#ambassadorSelect').val(null).trigger('change');
        $('.status-checkbox').prop('checked', false);
        loadPayments(1);
    });
});
</script>
@endpush
