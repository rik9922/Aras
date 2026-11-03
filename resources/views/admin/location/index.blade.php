@extends('layout.app', ['page_name' => 'Location List'])

@section('content')
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Locations : <span class="total-data">{{ $locations->total() }}</span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col-md-6 col-lg-8 pt-3">
                    <input type="search" class="form-control shadow-none" id="search" name="serach" placeholder="Search for a Name, Email or Phone">
                </div>
                <div class="col-md-3 col-lg-2 pt-3">
                    <div class="form-check">
                        <input class="form-check-input shadow-none" type="checkbox"  value="0" name="inactive" id="viewInactiveCheckbox">
                        <label class="form-check-label text-uppercase" for="viewInactiveCheckbox">
                            View Inactive
                        </label>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2 pt-3">
                    <div class="d-grid">
                        <a href="{{ route('admin.location.create') }}">
                            <button type="button" class="btn-ds btn-secondary-ds">Add Location</button>
                        </a>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div class="text-nowrap">Location Name</div>
                            </th>
                            <th>
                                <div>Email</div>
                            </th>
                            <th>
                                <div>Phone</div>
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="location-table">
                        @if (!empty($locations) && count($locations) > 0)
                            @foreach ($locations as $location)
                                <tr>
                                    <td>
                                        <div>{{ $location->venue_name ?: $location->name }}</div>
                                        @if(!empty($location->venue_name) && $location->name !== $location->venue_name)
                                            <small class="text-muted">Contact: {{ $location->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $location->email }}</td>
                                    <td>{{ $location->phone }}</td>
                                    <td>{{ $location->address }}</td>
                                    <td>{{ $location->city }}</td>
                                    <td>{{ $location->state ? $location->state : '-' }}</td>
                                    <td>
                                        <span class="span-text @if($location->status == '1') success @else danger @endif">{{ ($location->status == '1') ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="{{ route('admin.edit.location', ['id' => $location->id]) }}">
                                                <button type="button" class="btn-ds btn-primary-ds">
                                                    <span>Edit</span>
                                                </button>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="text-center" colspan="8">Data not found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3">
                    {{ $locations->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
<script>
    $(document).ready(function() {

        function loadFilteredClients() {
            let search = $('#search').val();
            let inactive = $('#viewInactiveCheckbox').prop('checked') ? '0' : '';

            $.ajax({
                url: '{{ route("admin.locations") }}',
                method: 'GET',
                data: {
                    search: search,
                    inactive: inactive
                },
                success: function(data) {
                    $('#location-table').html(data.html);
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
