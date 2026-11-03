@extends('layout.app', ['page_name' => 'Payment Forecast'])

@php
    $roleId = (string) (Auth::user()->role_id ?? '');
    $routePrefix = $roleId === '6' ? 'manager' : 'admin';

    // Detect whether any filter is currently active so we can keep the panel open
    $hasActiveFilters = collect([
        'market_id',
        'manager_id',
        'location',
        'ambassador_name',
        'ambassador_email',
        'brand_id',
        'year',
        'month',
        'start_day',
        'end_day',
        'pinata_pay',
        'no_show',
        'only_events_with_recaps',
        'only_events_with_no_recaps',
        'exclude_unpaid_events',
    ])->contains(fn($k) => request()->filled($k));
@endphp

@section('content')
    <div class="container">
        <x-page-header title="Payment Forecasts"
            subtitle="Total Forecast: <span class='total-data'>{{ $recaps->total() }}</span>" />

        <div class="page-card-design">

            {{-- Filter toggle button --}}
            <div class="d-flex justify-content-end mx-3">
                <button class="btn-ds btn-primary-ds" type="button" data-bs-toggle="collapse" data-bs-target="#search"
                    aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}" aria-controls="search" id="searchToggleBtn">
                    <i class="fa-solid fa-caret-up" id="searchIcon"></i>
                    <span class="px-2">Filters</span>
                </button>
            </div>

            {{-- Filter form — GET so all values appear in the URL --}}
            <form method="GET" action="{{ route($routePrefix . '.payment-forecast') }}" id="filterForm">
                <div class="collapse {{ $hasActiveFilters ? 'show' : '' }}" id="search">
                    <div class="card list-form-card mt-3">
                        <div class="row p-3">
                            <div class="col-md-9">
                                <div class="row">

                                    {{-- Location --}}
                                    <div class="col-md-6 col-xl-4 mb-3">
                                        <label for="location" class="form-label">Location</label>
                                        <select class="form-select filter-select" id="location" name="location">
                                            <option value="">Select Location</option>
                                            @foreach ($locations as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ request('location') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->address }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Market --}}
                                    <div class="col-md-6 col-xl-4 mb-3">
                                        <label for="selectMarket" class="form-label">Market</label>
                                        <select class="form-select filter-select" id="selectMarket" name="market_id">
                                            <option value="">Select Market</option>
                                            @foreach ($markets as $market)
                                                <option value="{{ $market->id }}"
                                                    {{ request('market_id') == $market->id ? 'selected' : '' }}>
                                                    {{ $market->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Brand --}}
                                    <div class="col-md-6 col-xl-4 mb-3">
                                        <label for="brandId" class="form-label">Brand</label>
                                        <select class="form-select filter-select" id="brandId" name="brand_id">
                                            <option value="">Select Brand</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}"
                                                    {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                                    {{ $brand->brand_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Manager --}}
                                    <div class="col-md-6 col-xl-4 mb-3">
                                        <label for="selectManagers" class="form-label">Manager</label>
                                        <select class="form-select filter-select" id="selectManagers" name="manager_id">
                                            <option value="">Select Manager</option>
                                            @foreach ($managers as $manager)
                                                <option value="{{ $manager->id }}"
                                                    {{ request('manager_id') == $manager->id ? 'selected' : '' }}>
                                                    {{ $manager->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Ambassador Name --}}
                                    <div class="col-md-6 col-xl-4 mb-3">
                                        <label for="ambassadorName" class="form-label">Ambassador Name</label>
                                        <select class="form-select filter-select" id="ambassadorName"
                                            name="ambassador_name">
                                            <option value="">Select Ambassador</option>
                                            @foreach ($ambassadors as $ambassador)
                                                <option value="{{ $ambassador->id }}"
                                                    {{ request('ambassador_name') == $ambassador->id ? 'selected' : '' }}>
                                                    {{ $ambassador->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Ambassador Email --}}
                                    <div class="col-md-6 col-xl-4 mb-3">
                                        <label for="ambassadorEmail" class="form-label">Ambassador Email</label>
                                        <select class="form-select filter-select" id="ambassadorEmail"
                                            name="ambassador_email">
                                            <option value="">Select Email</option>
                                            @foreach ($ambassadors as $ambassador)
                                                <option value="{{ $ambassador->id }}"
                                                    {{ request('ambassador_email') == $ambassador->id ? 'selected' : '' }}>
                                                    {{ $ambassador->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Date filters: year / month / start date / end date --}}
                                    <div class="col-md-6 col-xl-3 mb-3">
                                        <label for="selectYear" class="form-label">Year</label>
                                        <select class="form-select filter-select" id="selectYear" name="year">
                                            <option value="">Select Year</option>
                                            @for ($y = now()->year - 5; $y <= now()->year + 2; $y++)
                                                <option value="{{ $y }}"
                                                    {{ request('year') == $y ? 'selected' : '' }}>
                                                    {{ $y }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div class="col-md-6 col-xl-3 mb-3">
                                        <label for="selectMonth" class="form-label">Month</label>
                                        <select class="form-select filter-select" id="selectMonth" name="month">
                                            <option value="">Select Month</option>
                                            @foreach (['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $i => $monthName)
                                                <option value="{{ $i + 1 }}"
                                                    {{ request('month') == $i + 1 ? 'selected' : '' }}>
                                                    {{ $monthName }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 col-xl-3 mb-3">
                                        <label for="startDay" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDay" name="start_day"
                                            value="{{ request('start_day') }}">
                                    </div>

                                    <div class="col-md-6 col-xl-3 mb-3">
                                        <label for="endDay" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDay" name="end_day"
                                            value="{{ request('end_day') }}">
                                    </div>

                                </div>
                            </div>

                            {{-- Checkboxes + actions --}}
                            <div class="col-md-3">
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" name="no_show"
                                        value="1" id="noShow" {{ request('no_show') == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="noShow">No Show</label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox"
                                        name="only_events_with_recaps" value="1" id="onlyEventsWithRecaps"
                                        {{ request('only_events_with_recaps') == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="onlyEventsWithRecaps">Only Events With
                                        Recaps</label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox"
                                        name="only_events_with_no_recaps" value="1" id="onlyEventsWithNoRecaps"
                                        {{ request('only_events_with_no_recaps') == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="onlyEventsWithNoRecaps">Only Events With No
                                        Recaps</label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox"
                                        name="exclude_unpaid_events" value="1" id="excludeUnpaidEvents"
                                        {{ request('exclude_unpaid_events') == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="excludeUnpaidEvents">Exclude Unpaid
                                        Events</label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" name="pinata_pay"
                                        value="1" id="pinataPay" {{ request('pinata_pay') == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pinataPay">Pinata Pay</label>
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    {{-- <button type="submit" class="btn-ds btn-primary-ds text-uppercase">
                                        <i class="fa-solid fa-filter"></i> Apply Filters
                                    </button> --}}
                                    <a href="{{ route($routePrefix . '.payment-forecast') }}"
                                        class="btn-ds btn-secondary-ds text-uppercase">
                                        <i class="fa-solid fa-rotate-left"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Results table --}}
            <div class="table-responsive mt-4">
                @include('admin.payment.partials.forecast-table')
            </div>

            {{-- Pagination — withQueryString() in controller keeps all filters in links --}}
            <div class="d-flex justify-content-end mt-3">
                {{ $recaps->links('vendor.pagination.custom') }}
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialise select2 on all filter selects
            $(".filter-select").select2({
                width: '100%',
                allowClear: true,
                placeholder: function() {
                    return $(this).find('option:first').text();
                }
            });

            // Submit the form automatically when any select/checkbox changes
            $(document).on('change',
                '.filter-select, #noShow, #onlyEventsWithRecaps, #onlyEventsWithNoRecaps, #excludeUnpaidEvents, #pinataPay, #startDay, #endDay',
                function() {
                    $('#filterForm').submit();
                });
        });
    </script>
@endpush
