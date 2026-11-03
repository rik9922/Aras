@extends('layout.app', ['page_name' => 'Payment Forecast'])

@php
    $roleId = (string) (Auth::user()->role_id ?? '');
    $routePrefix = $roleId === '6' ? 'manager' : 'admin';
@endphp

@section('content')
    <div class="container">
        <div class="d-grid">
            <div class="h5">Payment Forecast</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="d-flex justify-content-end mx-3">
                <button class="btn-ds btn-primary-ds" type="button" data-bs-toggle="collapse" data-bs-target="#search"
                    aria-expanded="false" aria-controls="search" id="searchToggleBtn">
                    <i class="fa-solid fa-caret-up" id="searchIcon"></i>
                    <span class="px-2">Search</span>
                </button>
            </div>
            <div class="collapse" id="search">
                <div class="card list-form-card mt-3">
                    <div class="row p-3">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6 col-xl-4 mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <select class="form-select" id="location" aria-label="location"
                                        name="location">
                                        <option value="">Select Location</option>
                                        @foreach ($locations as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-4 mb-3">
                                    <label for="selectMarket" class="form-label">Market</label>
                                    <select class="form-select" id="selectMarket" name="market_id"
                                        aria-label="Select Market">
                                        <option value="">Select Market</option>
                                        @foreach ($markets as $market)
                                            <option value="{{ $market->id }}">{{ $market->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-4 mb-3">
                                    <label for="brandId" class="form-label">Brand</label>
                                    <select class="form-select" id="brandId" name="brand_id"
                                        aria-label="Select Brand">
                                        <option value="">Select Brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-4 mb-3">
                                    <label for="selectManagers" class="form-label">Manager</label>
                                    <select class="form-select" id="selectManagers" name="manager_id"
                                        aria-label="Select Managers">
                                        <option value="">Select Manager</option>
                                        @foreach ($managers as $manager)
                                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-xl-4 mb-3">
                                    <label for="ambassadorName" class="form-label">Ambassador Name</label>
                                    <select class="form-select" id="ambassadorName">
                                        <option value="">Select Ambassador Name</option>
                                        @foreach ($ambassadors as $ambassador)
                                            <option value="{{ $ambassador->id }}">{{ $ambassador->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-xl-4 mb-3">
                                    <label for="ambassadorEmail" class="form-label">Ambassador Email</label>
                                    <select class="form-select" id="ambassadorEmail" aria-label="Ambassador Email">
                                        <option value="">Select Ambassador Email</option>
                                        @foreach ($ambassadors as $ambassador)
                                            <option value="{{ $ambassador->id }}">{{ $ambassador->email }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 col-xl-3">
                                        <div class="mb-3">
                                            <label for="selectYear" class="form-label">Select Year</label>
                                            <select class="form-select shadow-none" id="selectYear">
                                                <option value="">Select Year</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="mb-3">
                                            <label for="selectMonth" class="form-label">Select Month</label>
                                            <select class="form-select shadow-none" id="selectMonth">
                                                <option value="">Select Month</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="mb-3">
                                            <label for="startDay" class="form-label">Start Day</label>
                                            <select class="form-select shadow-none" id="startDay">
                                                <option value="">Select Start Day</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="mb-3">
                                            <label for="endDay" class="form-label">End Day</label>
                                            <select class="form-select shadow-none" id="endDay">
                                                <option value="">Select End Day</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="noShow">
                                    <label class="form-check-label" for="noShow">
                                        No Show
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="onlyEventsWithRecaps">
                                    <label class="form-check-label" for="onlyEventsWithRecaps">
                                        Only Events With Recaps
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="onlyEventsWithNoRecaps">
                                    <label class="form-check-label" for="onlyEventsWithNoRecaps">
                                        Only Events With No Recaps
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="excludeUnpaidEvents">
                                    <label class="form-check-label" for="excludeUnpaidEvents">
                                        Exclude Unpaid Events
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="pinataPay">
                                    <label class="form-check-label" for="pinataPay">
                                        Pinata Pay
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <button type="button" id="resetFilters" class="btn-ds btn-primary-ds text-uppercase">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                @include('admin.payment.partials.forecast-table')
            </div>
            <!-- Pagination -->
            <div class="d-flex justify-content-end mt-3 pagination-container">
                {{ $recaps->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#selectMarket, #selectManagers, #location, #ambassadorName, #ambassadorEmail, #brandId").select2();
        });
    </script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const yearSelect = document.getElementById("selectYear");
        const monthSelect = document.getElementById("selectMonth");
        const startDaySelect = document.getElementById("startDay");
        const endDaySelect = document.getElementById("endDay");
    
        // 🔹 Generate Years (current year ± 5)
        const currentYear = new Date().getFullYear();
        for (let y = currentYear - 5; y <= currentYear + 5; y++) {
            const option = document.createElement("option");
            option.value = y;
            option.text = y;
            if (y === currentYear) option.selected = true;
            yearSelect.appendChild(option);
        }
    
        // 🔹 Generate Months
        const months = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
        months.forEach((month, index) => {
            const option = document.createElement("option");
            option.value = index + 1;
            option.text = month;
            if (index === new Date().getMonth()) option.selected = true;
            monthSelect.appendChild(option);
        });
    
        // 🔹 Update days dynamically when month or year changes
        function updateDays() {
            const year = parseInt(yearSelect.value);
            const month = parseInt(monthSelect.value);
            if (!year || !month) return;
    
            const daysInMonth = new Date(year, month, 0).getDate();
            [startDaySelect, endDaySelect].forEach(select => {
                select.innerHTML = '<option value="">Select Day</option>';
                for (let d = 1; d <= daysInMonth; d++) {
                    const option = document.createElement("option");
                    option.value = d;
                    option.text = d;
                    select.appendChild(option);
                }
            });
        }
    
        // 🔹 Trigger when user changes month or year
        yearSelect.addEventListener("change", updateDays);
        monthSelect.addEventListener("change", updateDays);
    
        // Load initial days for current month
        updateDays();
    });
</script>
    
    <script>
    $(document).ready(function() {
        let showCompleted = false;
        let resetting = false;
    
        // initialize select2
        $("#selectMarket, #selectManagers, #location, #ambassadorName, #ambassadorEmail, #brandId").select2();
    
        const filterSelectors = "#selectMarket, #selectManagers, #location, #ambassadorName, #ambassadorEmail, #brandId, #year, #month, #startDay, #endDay";
    
        // 🔹 Function to load filtered payment forecast
        function loadFilteredForecast(page = 1) {
            let year = $("#year").val();
            let month = $("#month").val();
            let startDay = $("#startDay").val();
            let endDay = $("#endDay").val();
            
            let no_show = $('#noShow').is(':checked') ? 1 : 0;
            let only_events_with_recaps = $('#onlyEventsWithRecaps').is(':checked') ? 1 : 0;
            let only_events_with_no_recaps = $('#onlyEventsWithNoRecaps').is(':checked') ? 1 : 0;
            let exclude_unpaid_events = $('#excludeUnpaidEvents').is(':checked') ? 1 : 0;
            let pinata_pay = $('#pinataPay').is(':checked') ? 1 : 0;
    
            $.ajax({
                url: "{{ route($routePrefix . '.payment-forecast') }}?page=" + page,
                type: "GET",
                data: {
                    _token: "{{ csrf_token() }}",
                    market_id: $("#selectMarket").val(),
                    manager_id: $("#selectManagers").val(),
                    location: $("#location").val(),
                    ambassador_name: $("#ambassadorName").val(),
                    ambassador_email: $("#ambassadorEmail").val(),
                    brand_id: $("#brandId").val(),
                    year: year,
                    month: month,
                    start_day: startDay,
                    end_day: endDay,
                    show_completed: showCompleted ? 1 : 0,
                    
                    no_show: no_show,
                    only_events_with_recaps: only_events_with_recaps,
                    only_events_with_no_recaps: only_events_with_no_recaps,
                    exclude_unpaid_events: exclude_unpaid_events,
                    pinata_pay: pinata_pay,
                },
                beforeSend: function() {
                    $(".table-responsive").html('<div class="text-center p-5">Loading...</div>');
                },
                success: function(response) {
                    console.log(response.table_html);
                    $(".table-responsive").html(response.table_html);
                    $(".pagination-container").html(response.pagination);
                    $(".total-data").text(response.total);
                },
                error: function(xhr) {
                    console.error("Error fetching forecast:", xhr.responseText);
                }
            });
        }
    
        // 🔹 Filter change listeners
        $(filterSelectors + ', #noShow, #onlyEventsWithRecaps, #onlyEventsWithNoRecaps, #excludeUnpaidEvents, #pinataPay').on("change keyup", function() {
            if (resetting) return;
            if ($(this).attr("type") === "date") {
                setTimeout(() => loadFilteredForecast(1), 100);
            } else {
                loadFilteredForecast(1);
            }
        });
    
        // 🔹 Pagination click
        $(document).on("click", ".pagination a", function(e) {
            e.preventDefault();
            const page = $(this).attr("href").split("page=")[1];
            loadFilteredForecast(page);
        });
    
        // 🔹 Toggle show/hide completed
        $(document).on("click", "#toggleCompleted", function() {
            showCompleted = !showCompleted;
            const btnText = showCompleted ? "Hide Completed" : "Show Completed";
            $(this).find(".btn-text").text(btnText);
            loadFilteredForecast(1);
        });
    
        // 🔹 Reset filters
        $("#resetFilters").on("click", function() {
            resetting = true;
            $(filterSelectors).each(function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).val(null).trigger("change");
                } else {
                    $(this).val("");
                }
            });
            $('#noShow, #onlyEventsWithRecaps, #onlyEventsWithNoRecaps, #excludeUnpaidEvents, #pinataPay')
                    .prop('checked', false);
                    
            resetting = false;
            loadFilteredForecast(1);
        });
    });
    </script>

@endpush
