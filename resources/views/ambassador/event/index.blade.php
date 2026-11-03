@extends('layout.app', ['page_name' => 'Event List'])
<style>
    .events-tab {
        display: inline-block;
        overflow: hidden;
    }

    .events-tab .nav-item .nav-link {
        color: var(--primary-color);
        border-radius: 0.5rem 0.5rem 0 0;
        border: 1px solid #dee2e6;
        background: #fff;
    }

    .events-tab .nav-item .nav-link.active {
        background: var(--primary-color);
        color: #fff;
    }

    .events-tab .nav-item .nav-link:hover {
        background: var(--primary-color);
        opacity: 0.8;
        color: #fff;
    }

    .pagination-wrapper {
        margin-top: 20px;
        display: flex;
        justify-content: center;
    }

    #loading {
        text-align: center;
        padding: 20px;
        display: none;
    }
</style>
@section('content')
    <div class="container">
        <div class="h5 mt-2">Events: <span class="total-data">0</span></div>
        <div class="fixed-border"></div>

        <div class="page-card-design">
            <div class="row pt-3">
                <div class="col-md-6">
                    <div class="m-1">
                        <div class="events-tab">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link status-event active" id="all-tab" data-id="all"
                                        data-bs-toggle="tab" data-bs-target="#all-tab-pane" type="button" role="tab"
                                        aria-controls="all-tab-pane" aria-selected="true">All</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link status-event" id="applied-tab" data-id="pending"
                                        data-bs-toggle="tab" data-bs-target="#applied-tab-pane" type="button"
                                        role="tab" aria-controls="applied-tab-pane"
                                        aria-selected="false">Pending</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link status-event" id="assigned-tab" data-id="assigned"
                                        data-bs-toggle="tab" data-bs-target="#assigned-tab-pane" type="button"
                                        role="tab" aria-controls="assigned-tab-pane"
                                        aria-selected="false">Assigned</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link status-event" id="completed-tab" data-id="completed"
                                        data-bs-toggle="tab" data-bs-target="#completed-tab-pane" type="button"
                                        role="tab" aria-controls="completed-tab-pane"
                                        aria-selected="false">Completed</button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3 position-relative">
                        <span class="position-absolute px-3 py-2 fs-6" style="color: grey"><i
                                class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control field-style ps-5" name="search" id="search"
                            placeholder="Search for a Brand, Location or City.">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <select class="form-select field-style" aria-label="Select Market" name="market_id" id="marketId">
                            <option value="" selected>Select Market</option>
                            @foreach ($markets as $market)
                                <option value="{{ $market->id }}">{{ $market->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div id="loading">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Event Id</th>
                            <th class="amb-sort-toggle" style="cursor:pointer; user-select:none;"
                                onclick="toggleEventSort()" title="Sort by date & time">
                                Date - Time <span id="amb-sort-arrow">&#8593;</span>
                            </th>
                            <th>Duration</th>
                            <th>Brand</th>
                            <th>Varietals</th>
                            <th>Kit Required</th>
                            <th>Department</th>
                            <th>Distributor</th>
                            <th>Location</th>
                            <th>Event Pay</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="amb-event-table">
                        <!-- Events will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrapper" id="pagination-wrapper">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let status = 'all'; // default
        let currentPage = 1;
        let sortOrder = 'asc'; // date/time sort direction (matches the app)

        function fetchEvents(page = 1) {
            let search = $('#search').val();
            let marketId = $('#marketId').val();
            status = $('.status-event.active').data('id') || 'all';

            // Show loading
            $('#loading').show();
            $('#amb-event-table').empty();
            $('#pagination-wrapper').empty();

            $.ajax({
                url: "{{ route('ambassador.events') }}",
                method: 'GET',
                data: {
                    search: search,
                    marketId: marketId,
                    status: status,
                    page: page,
                    sort_order: sortOrder
                },
                success: function(data) {
                    $('#loading').hide();
                    $('#amb-event-table').html(data.html);
                    $('#pagination-wrapper').html(data.pagination);

                    if (data.count !== undefined) {
                        $('.total-data').text(data.count);
                    }

                    currentPage = data.current_page;
                },
                error: function(xhr, status, error) {
                    $('#loading').hide();
                    console.log('Error: ' + error);
                }
            });
        }


        function handleStatusChange(eventId) {
            if (eventId) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to Apply for this Event.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('ambassador.apply-event') }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                event_id: eventId,
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Applied!',
                                    'Event Applied Successfully.',
                                    'success'
                                );
                                setTimeout(function() {
                                    fetchEvents(currentPage || 1);
                                }, 700);
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    'Something went wrong.',
                                    'error'
                                );
                            }

                        });
                    }
                });
            }
        }

        function handleWithdraw(eventId) {
            if (!eventId) return;

            Swal.fire({
                title: "Withdraw from this event?",
                text: "You will be removed from this event.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, withdraw"
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route('ambassador.withdraw-event') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        event_id: eventId,
                    },
                    success: function(response) {
                        Swal.fire('Withdrawn!', 'You have been removed from the event.', 'success');
                        setTimeout(function() {
                            fetchEvents(currentPage || 1);
                        }, 700);
                    },
                    error: function(xhr) {
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong.';
                        Swal.fire('Error!', msg, 'error');
                    }
                });
            });
        }

        function handleCheckIn(checkbox, eventId) {
            if (!checkbox.checked || !eventId) return;

            checkbox.disabled = true;

            if (!navigator.geolocation) {
                checkbox.checked = false;
                checkbox.disabled = false;
                Swal.fire('Location unavailable', 'Your browser does not support location services.', 'error');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    submitCheckIn(checkbox, eventId, position.coords.latitude, position.coords.longitude, false);
                },
                function() {
                    checkbox.checked = false;
                    checkbox.disabled = false;
                    Swal.fire('Location required', 'Please allow location access to check in.', 'error');
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        }

        function submitCheckIn(checkbox, eventId, latitude, longitude, forceCheckIn) {
            $.ajax({
                url: '{{ route('ambassador.check-in-event') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    event_id: eventId,
                    latitude: latitude,
                    longitude: longitude,
                    force_check_in: forceCheckIn ? 1 : 0
                },
                success: function(response) {
                    checkbox.checked = true;
                    checkbox.disabled = true;
                    Swal.fire('Checked in!', response.message || 'Check-in recorded successfully.', 'success');
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};

                    if (response.requires_confirmation && !forceCheckIn) {
                        Swal.fire({
                            title: 'Outside event location',
                            text: response.message || 'You appear to be outside the event location. Check in anyway?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Check in anyway'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitCheckIn(checkbox, eventId, latitude, longitude, true);
                            } else {
                                checkbox.checked = false;
                                checkbox.disabled = false;
                            }
                        });
                        return;
                    }

                    checkbox.checked = false;
                    checkbox.disabled = false;
                    Swal.fire('Check-in failed', response.message || 'Something went wrong.', 'error');
                }
            });
        }

        // Flip the Date - Time sort direction and reload (mirrors the app toggle).
        function toggleEventSort() {
            sortOrder = (sortOrder === 'asc') ? 'desc' : 'asc';
            const arrow = document.getElementById('amb-sort-arrow');
            if (arrow) arrow.innerHTML = (sortOrder === 'asc') ? '&#8593;' : '&#8595;';
            fetchEvents(1);
        }

        $(document).ready(function() {
            // Load events on page load
            fetchEvents();

            // Handle tab clicks
            $('.status-event').on('click', function(e) {
                e.preventDefault();
                $('.status-event').removeClass('active');
                $(this).addClass('active');
                currentPage = 1;
                fetchEvents(1);
            });

            // Handle search input
            $('#search').on('keyup', function() {
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    fetchEvents(1);
                }, 500); // Debounce search
            });

            // Handle market filter change
            $('#marketId').on('change', function() {
                currentPage = 1;
                fetchEvents(1);
            });

            // Handle pagination clicks
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');
                if (url) {
                    let page = url.split('page=')[1];
                    if (page) {
                        fetchEvents(page);
                    }
                }
            });

            // Make entire row keyboard/mouse navigable
            $(document).on('click', 'tr[data-href]', function(e) {
                // Don't hijack clicks from actual interactive elements
                if ($(e.target).closest('a, button, input, select, textarea, label').length) return;
                const href = $(this).data('href');
                if (href) window.location.href = href;
            });

            $(document).on('keydown', 'tr[data-href]', function(e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                if ($(e.target).closest('a, button, input, select, textarea, label').length) return;
                e.preventDefault();
                const href = $(this).data('href');
                if (href) window.location.href = href;
            });
        });
    </script>
@endsection
