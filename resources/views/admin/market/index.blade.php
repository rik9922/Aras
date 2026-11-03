@extends('layout.app', ['page_name' => 'Market List'])

@php
    $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
@endphp

@section('content')
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5"> Markets : <span class="total-data">{{ $markets->total() }}</span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col-md-6 col-lg-6 pt-3">
                    <input type="text" class="form-control shadow-none" id="search" name="serach"
                        placeholder="Search for a Market Name or Location">
                </div>
                <div class="col-md-3 col-lg-2 pt-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input shadow-none" type="checkbox" value="1" id="viewInactiveCheckbox"
                            @checked($showInactive ?? false)>
                        <label class="form-check-label" for="viewInactiveCheckbox">
                            Show inactive
                        </label>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2 pt-3">
                    <div class="d-grid">
                        <a href="{{ route($routePrefix.'.market.create') }}">
                            <button type="button" class="btn-ds btn-secondary-ds">Add Market</button>
                        </a>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2 pt-3 d-none" id="bulkDeleteContainer">
                    <form id="bulkDeleteForm" action="{{ route($routePrefix.'.delete.markets.bulk') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            Delete Markets (<span id="selectedMarketCount">0</span>)
                        </button>
                    </form>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAllMarkets"
                                    aria-label="Select all markets">
                            </th>
                            <th>
                                <div class="text-nowrap">Name</div>
                            </th>
                            <th>
                                <div class="text-nowrap">Location</div>
                            </th>
                            <th>
                                <div>Description</div>
                            </th>
                            <th>
                                <div>Status</div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="market-table">
                        @if(!empty($markets) && count($markets) > 0)
                            @foreach ($markets as $market)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input market-checkbox"
                                            value="{{ $market->id }}" aria-label="Select {{ $market->name }}">
                                    </td>
                                    <td>{{ $market->name }}</td>
                                    <td>{{ $market->location }}</td>
                                    <td>{{ $market->description }}</td>
                                    <td class="text-capitalize">{{ $market->status }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route($routePrefix.'.edit.markets', ['id' => $market->id]) }}">
                                                <button type="button" class="btn-ds btn-primary-ds">
                                                    <span>Edit</span>
                                                </button>
                                            </a>
                                            <form action="{{ route($routePrefix.'.delete.markets', ['market' => $market->id]) }}"
                                                method="POST" onsubmit="return confirm('Are you sure you want to delete this market?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else 
                            <tr><td colspan="6" class="text-center">Data not found</td></tr>
                        @endif

                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3">
                    {{ $markets->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
<script>
    $(document).ready(function() {
        const deleteUrlTemplate = @json(route($routePrefix.'.delete.markets', ['market' => '__MARKET_ID__']));
        const csrfToken = @json(csrf_token());

        function selectedMarketIds() {
            return $('.market-checkbox:checked').map(function() {
                return this.value;
            }).get();
        }

        function updateBulkDeleteControls() {
            const selectedCount = selectedMarketIds().length;
            const checkboxCount = $('.market-checkbox').length;

            $('#selectedMarketCount').text(selectedCount);
            $('#bulkDeleteContainer').toggleClass('d-none', selectedCount === 0);
            $('#selectAllMarkets')
                .prop('checked', checkboxCount > 0 && selectedCount === checkboxCount)
                .prop('indeterminate', selectedCount > 0 && selectedCount < checkboxCount);
        }

        $('#selectAllMarkets').on('change', function() {
            $('.market-checkbox').prop('checked', this.checked);
            updateBulkDeleteControls();
        });

        $('#market-table').on('change', '.market-checkbox', updateBulkDeleteControls);

        $('#bulkDeleteForm').on('submit', function(event) {
            const marketIds = selectedMarketIds();

            if (marketIds.length === 0 || !confirm('Are you sure you want to delete the selected markets?')) {
                event.preventDefault();
                return;
            }

            $(this).find('.bulk-market-id').remove();
            marketIds.forEach((marketId) => {
                $('<input>', {
                    type: 'hidden',
                    name: 'market_ids[]',
                    value: marketId,
                    class: 'bulk-market-id',
                }).appendTo(this);
            });
        });

        $('#search, #viewInactiveCheckbox').on('change keyup', function() {
            let search = $('#search').val();
            let showInactive = $('#viewInactiveCheckbox').prop('checked') ? '1' : '0';
            // Fetch filtered data with AJAX
            $.ajax({
                url: '{{ route($routePrefix.".markets") }}',
                method: 'GET',
                data: {
                    search: search,
                    show_inactive: showInactive,
                },
                success: function(response) {
                    var rows = '';
                    if(response.markets.data.length > 0) {
                        $('.total-data').text(response.markets.total);
                        $.each(response.markets.data, function(index, market) {
                            rows += '<tr>';
                            rows += '<td class="text-center"><input type="checkbox" class="form-check-input market-checkbox" value="' + market.id + '" aria-label="Select market"></td>';
                            rows += '<td>' + market.name + '</td>';
                            rows += '<td>' + market.location + '</td>';
                            rows += '<td>' + market.description + '</td>';
                            rows += '<td class="text-capitalize">' + market.status + '</td>';
                            rows += '<td><div class="d-flex gap-2">';
                            rows += '<a href="/{{ $routePrefix }}/edit-markets/' + market.id + '">';
                            rows += '<button type="button" class="btn-ds btn-primary-ds"><span>Edit</span></button></a>';
                            rows += '<form action="' + deleteUrlTemplate.replace('__MARKET_ID__', market.id) + '" method="POST" onsubmit="return confirm(\'Are you sure you want to delete this market?\')">';
                            rows += '<input type="hidden" name="_token" value="' + csrfToken + '">';
                            rows += '<input type="hidden" name="_method" value="DELETE">';
                            rows += '<button type="submit" class="btn btn-danger">Delete</button></form>';
                            rows += '</div></td>';
                            rows += '</tr>';
                        });
                    } else {
                        $('.total-data').text('0');
                        rows = '<tr><td colspan="6" class="text-center">Data not found</td></tr>';
                    }
                    $('table tbody').html(rows);
                    $('#selectAllMarkets').prop('checked', false).prop('indeterminate', false);
                    updateBulkDeleteControls();
                },
            });
        });
    });

</script>
