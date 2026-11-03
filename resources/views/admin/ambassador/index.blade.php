@extends('layout.app', ['page_name' => 'Ambassador List'])
@php
    $ambRoutePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin';
@endphp
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@section('content')
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Ambassador: <span class="total-data">
                    {{ $totalAmbassadors ?? 0 }}
                </span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row">
                <div class="d-flex justify-content-end align-items-center gap-2">
                    <button
                        class="btn-ds btn-secondary-ds"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#search"
                        aria-expanded="false"
                        aria-controls="search"
                        id="searchToggleBtn">
                        <i class="fa-solid fa-caret-up" id="searchIcon"></i>
                        <span class="px-2">Search</span>
                    </button>
                
                    <form
                        id="bulk-banking-reminder-form"
                        method="POST"
                        action="{{ route($ambRoutePrefix . '.send-bulk-banking-reminder') }}"
                        class="mb-0"
                        onsubmit="return confirm('Send banking setup reminder to selected ambassadors?');">
                        @csrf
                        <button
                            type="submit"
                            class="btn-ds btn-info-ds"
                            id="bulk-banking-reminder-btn"
                            disabled>
                            Send Banking Setup Reminder
                        </button>
                    </form>
                
                    <a href="{{ route($ambRoutePrefix . '.ambassador.create') }}">
                        <button type="button" class="btn-ds btn-primary-ds">
                            Add Ambassador
                        </button>
                    </a>
                </div>
                <div class="collapse" id="search">
                    <div class="card list-form-card mt-3">
                        <div class="row p-3">
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="firstName" class="form-label">First Name</label>
                                            <input type="text" class="form-control shadow-none filter-input" name="first_name" id="first_name"
                                                id="firstName" placeholder="first name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="lastName" class="form-label">Last Name</label>
                                            <input type="text" class="form-control shadow-none filter-input" name="last_name" id="last_name"
                                                placeholder="last name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control shadow-none filter-input" name="email"
                                                id="email" placeholder="email address">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control shadow-none filter-input" name="phone"
                                            placeholder="phone number">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control shadow-none filter-input" name="city"
                                                id="city" placeholder="city">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="state" class="form-label">State</label>
                                            <input type="text" class="form-control shadow-none filter-input" name="state"
                                                id="state" placeholder="state">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="selectMarket" class="form-label">Markets</label>
                                            <select class="form-select shadow-none filter-input" id="market_id" name="market_id">
                                                <option value="" selected>select</option>
                                                @foreach ($markets as $market)
                                                    <option value="{{$market->id}}">{{$market->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="region" class="form-label">Region</label>
                                            <div class="d-flex gap-4">
                                                <select class="form-select shadow-none filter-input" id="region" name="region" id="region">
                                                    <option value="" selected>Select Region</option>
                                                    <option value="Southeast">Southeast</option>
                                                    <option value="Midwest">Midwest</option>
                                                </select>
                                            </div>
                                            @error('region')
                                                <div class="text-danger w-100">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-6">
                                        <div class="mb-3">
                                            <label for="selectDivision" class="form-label">Select Hired Date or Date
                                                Range</label>
                                            <div class="d-flex align-items-center">
                                                <input type="date" class="form-control shadow-none filter-input" name="start_date" id="start_date">
                                                <div class="px-2">to</div>
                                                <input type="date" class="form-control shadow-none filter-input" name="end_date" id="end_date">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="status" value="1" id="activeRadio">
                                        <label class="form-check-label" for="activeRadio">Active</label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="status" value="0" id="inactiveRadio">
                                        <label class="form-check-label" for="inactiveRadio">Inactive</label>
                                    </div>

                                    <div class="mt-3 small text-muted text-uppercase">Liquor license</div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="liquor_license" value="" id="liquorLicenseAll" checked>
                                        <label class="form-check-label" for="liquorLicenseAll">All</label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="liquor_license" value="1" id="liquorLicenseYes">
                                        <label class="form-check-label" for="liquorLicenseYes">Has license</label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="liquor_license" value="0" id="liquorLicenseNo">
                                        <label class="form-check-label" for="liquorLicenseNo">No license</label>
                                    </div>

                                    <div class="mt-3 small text-muted text-uppercase">Bank setup</div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="bank_setup" value="all" id="bankSetupAll" checked>
                                        <label class="form-check-label" for="bankSetupAll">All</label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="bank_setup" value="verified" id="bankSetupVerified">
                                        <label class="form-check-label" for="bankSetupVerified">Verified</label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="bank_setup" value="pending_verification" id="bankSetupPending">
                                        <label class="form-check-label" for="bankSetupPending">Pending Verification</label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="radio" name="bank_setup" value="not_started" id="bankSetupNotStarted">
                                        <label class="form-check-label" for="bankSetupNotStarted">Not Started</label>
                                    </div>

                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="checkbox" value=""
                                            id="documents">
                                        <label class="form-check-label" for="documents">
                                            All Documents Received
                                        </label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="checkbox" value=""
                                            id="videoWatched">
                                        <label class="form-check-label" for="videoWatched">
                                            Videos Watched
                                        </label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="checkbox" value=""
                                            id="hasUnwatchedVideos">
                                        <label class="form-check-label" for="hasUnwatchedVideos">
                                            Has Unwatched Videos
                                        </label>
                                    </div>
                                    <div class="form-check my-2">
                                        <input class="form-check-input shadow-none filter-input" type="checkbox" value=""
                                            id="comdataCardSent">
                                        <label class="form-check-label" for="comdataCardSent">
                                            Comdata Card Sent
                                        </label>
                                    </div><button class="btn-ds btn-secondary-ds" id="resetFilters">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-2">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th style="width: 36px;">
                                <input type="checkbox" class="form-check-input shadow-none" id="select-all-ambassadors"
                                    title="Select all on this page">
                            </th>
                            <th>
                                <div class="text-nowrap">Ambassador</div>
                            </th>
                            <th>
                                <div>Email</div>
                            </th>
                            <th>
                                <div>Phone</div>
                            </th>
                            <th>
                                <div>Venmo</div>
                            </th>
                            {{-- <th>
                                <div>Instagram</div>
                            </th> --}}
                            <th>
                                <div>Markets</div>
                            </th>

                            <th>
                                <div>Address</div>
                            </th>
                            <th>
                                <div>City</div>
                            </th>
                            <th>
                                <div>State</div>
                            </th>
                            <th>
                                <div>Status</div>
                            </th>
                            <th>
                                <div>Liquor license</div>
                            </th>
                            <th>
                                <div>Bank Setup</div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="ambassador-table">
                        @include('admin.ambassador.ambassador_table', ['ambassadors' => $ambassadors])
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3" id="pagination-wrapper">
                    {{ $ambassadors->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ambassadorMarketsModal" tabindex="-1" aria-labelledby="ambassadorMarketsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ambassadorMarketsModalLabel">Markets</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush mb-0" id="ambassadorMarketsList"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ds btn-secondary-ds" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn-ds btn-danger-ds" id="removeSelectedMarketsBtn" disabled>Remove Selected</button>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function deleteEvent(id) {
        Swal.fire({
            title: "Are you sure?",
            text: "You want to delete this Ambassador.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            console.log('result',result);
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.method = "POST";
                form.action = "{{ route($ambRoutePrefix . '.delete.ambassador', '') }}/" + id;

                let csrfToken = document.createElement('input');
                csrfToken.type = "hidden";
                csrfToken.name = "_token";
                csrfToken.value = "{{ csrf_token() }}";

                let methodField = document.createElement('input');
                methodField.type = "hidden";
                methodField.name = "_method";
                methodField.value = "POST";

                form.appendChild(csrfToken);
                form.appendChild(methodField);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
<script>
    $(document).ready(function () {
        var activeMarketTrigger = null;
        var activeMarketRemoveUrl = '';
        var activeMarkets = [];
        var activeMarketAmbassadorName = 'Ambassador';

        function getMarketCountLabel(count) {
            if (count >= 10) {
                return (Math.floor(count / 10) * 10) + '+ markets';
            }

            return count + ' ' + (count === 1 ? 'market' : 'markets');
        }

        function renderMarketsModal(markets) {
            $('#ambassadorMarketsModalLabel').text(
                activeMarketAmbassadorName + ' Markets (' + markets.length + ')'
            );

            $('#ambassadorMarketsList').html(
                markets.length
                    ? markets.map(function (market) {
                        return $('<li/>', {
                            class: 'list-group-item px-0'
                        }).append(
                            $('<div/>', {
                                class: 'form-check'
                            }).append(
                                $('<input/>', {
                                    class: 'form-check-input js-modal-market-checkbox',
                                    type: 'checkbox',
                                    value: market.id,
                                    id: 'modalMarket' + market.id
                                }),
                                $('<label/>', {
                                    class: 'form-check-label',
                                    for: 'modalMarket' + market.id,
                                    text: market.name
                                })
                            )
                        ).prop('outerHTML');
                    }).join('')
                    : '<li class="list-group-item px-0 text-muted">No markets assigned</li>'
            );

            $('#removeSelectedMarketsBtn').prop('disabled', true).toggle(markets.length > 0);
        }

        function initializeAmbassadorTooltips() {
            document.querySelectorAll('#ambassador-table [data-bs-toggle="tooltip"]').forEach(function (el) {
                if (!bootstrap.Tooltip.getInstance(el)) {
                    new bootstrap.Tooltip(el);
                }
            });
        }
        
        $("#market_id, #region").select2();
        initializeAmbassadorTooltips();
        
        $('.filter-input').on('change keyup', function () {
            fetchFilteredData();
        });

        function fetchFilteredData(page = 1) {
            $.ajax({
                url: '{{ route($ambRoutePrefix . ".filter-ambassador") }}',
                method: 'POST',
                data: {
                    first_name: $('#first_name').val(),
                    last_name: $('#last_name').val(),
                    email: $('#email').val(),
                    phone: $('[name="phone"]').val(),
                    city: $('#city').val(),
                    state: $('#state').val(),
                    region: $('#region').val(),
                    market_id: $('#market_id').val(),
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    status: $('input[name="status"]:checked').val(),
                    liquor_license: $('input[name="liquor_license"]:checked').val(),
                    bank_setup: $('input[name="bank_setup"]:checked').val(),
                    documents: $('#documents').is(':checked') ? 1 : '',
                    videoWatched: $('#videoWatched').is(':checked') ? 1 : '',
                    hasUnwatchedVideos: $('#hasUnwatchedVideos').is(':checked') ? 1 : '',
                    comdataCardSent: $('#comdataCardSent').is(':checked') ? 1 : '',
                    page: page,
                    _token:'{{ csrf_token() }}'},
                success: function (response) {
                    $('#ambassador-table').html(response.tableHtml);
                    $('#pagination-wrapper').html(response.paginationHtml);
                    initializeAmbassadorTooltips();
                    updateBulkReminderButton();
                    $('#select-all-ambassadors').prop('checked', false);
                }
            });
        }

        function updateBulkReminderButton() {
            var selectedCount = $('#ambassador-table .ambassador-select:checked').length;
            $('#bulk-banking-reminder-btn').prop('disabled', selectedCount === 0);
        }

        $(document).on('change', '.ambassador-select', updateBulkReminderButton);

        $(document).on('click', '.js-market-list-trigger', function () {
            activeMarketTrigger = $(this);
            activeMarketAmbassadorName = $(this).data('ambassador-name') || 'Ambassador';
            activeMarketRemoveUrl = $(this).data('remove-url') || '';
            activeMarkets = [];

            try {
                activeMarkets = JSON.parse($(this).attr('data-markets') || '[]');
            } catch (error) {
                activeMarkets = [];
            }

            renderMarketsModal(activeMarkets);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('ambassadorMarketsModal')).show();
        });

        $(document).on('change', '.js-modal-market-checkbox', function () {
            $('#removeSelectedMarketsBtn').prop(
                'disabled',
                $('.js-modal-market-checkbox:checked').length === 0
            );
        });

        $('#removeSelectedMarketsBtn').on('click', function () {
            var marketIds = $('.js-modal-market-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            if (!marketIds.length || !activeMarketRemoveUrl) {
                return;
            }

            Swal.fire({
                title: 'Remove selected markets?',
                text: 'This will remove the selected markets from this ambassador.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, remove'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: activeMarketRemoveUrl,
                    method: 'POST',
                    data: {
                        market_ids: marketIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        activeMarkets = response.markets || [];

                        if (activeMarketTrigger && activeMarketTrigger.length) {
                            if (activeMarkets.length) {
                                activeMarketTrigger
                                    .text(getMarketCountLabel(activeMarkets.length))
                                    .attr('data-markets', JSON.stringify(activeMarkets));
                            } else {
                                activeMarketTrigger.closest('td').text('-');
                            }
                        }

                        renderMarketsModal(activeMarkets);
                    },
                    error: function () {
                        Swal.fire('Unable to remove markets', 'Please try again.', 'error');
                    }
                });
            });
        });

        $('#select-all-ambassadors').on('change', function () {
            var isChecked = $(this).is(':checked');
            $('#ambassador-table .ambassador-select').prop('checked', isChecked);
            updateBulkReminderButton();
        });
        
        $(document).on('click', '.pagination a', function (e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            fetchFilteredData(page);
        });


        $('#resetFilters').on('click', function () {
            $('#first_name').val('');
            $('#last_name').val('');
            $('#email').val('');
            $('[name="phone"]').val('');
            $('#city').val('');
            $('#state').val('');
            $('#market_id').val('');
            $('#start_date').val('');
            $('#end_date').val('');
            $('#region').val('');
            
            $('input[name="status"]').prop('checked', false);
            $('input[name="liquor_license"]').prop('checked', false);
            $('#liquorLicenseAll').prop('checked', true);
            $('input[name="bank_setup"]').prop('checked', false);
            $('#bankSetupAll').prop('checked', true);
            $('#documents').prop('checked', false);
            $('#videoWatched').prop('checked', false);
            $('#hasUnwatchedVideos').prop('checked', false);
            $('#comdataCardSent').prop('checked', false);

            fetchFilteredData();
        });
    });
</script>
