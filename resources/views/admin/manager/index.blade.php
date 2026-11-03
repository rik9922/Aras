@extends('layout.app', ['page_name' => 'Manager List'])

@section('content')
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Manager : <span class="total-data">
                    @if (!empty($managers) && count($managers) > 0)
                        {{ count($managers) }}
                    @else
                        0
                    @endif
                </span>
            </div>
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
                        <a href="{{ route('admin.manager.create') }}">
                            <button type="button" class="btn-ds btn-secondary-ds">Add Manager</button>
                        </a>
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
                                <div>Markets</div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="manager-table">
                        @if (!empty($managers) && count($managers) > 0)
                            @foreach ($managers as $manager)
                                <tr>
                                    <td>{{ $manager->first_name }}</td>
                                    <td>{{ $manager->last_name }}</td>
                                    <td>{{ $manager->email }}</td>
                                    <td>{{ $manager->phone }}</td>
                                    <td>
                                        <span class="span-text @if($manager->status == '1') success @else danger @endif">{{ ($manager->status == '1') ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td>
                                        @if ($manager->managerMarkets->isNotEmpty())
                                            {{ $manager->managerMarkets->pluck('name')->join(', ') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <a href="{{ route('admin.edit.manager', ['id' => $manager->id]) }}">
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
                                <td class="text-center" colspan="7">Data not found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script>
    $(document).ready(function() {

        function loadFilteredClients() {
            let search = $('#search').val();
            let inactive = $('#viewInactiveCheckbox').prop('checked') ? '0' : '';

            $.ajax({
                url: '{{ route("admin.managers") }}',
                method: 'GET',
                data: {
                    search: search,
                    inactive: inactive
                },
                success: function(data) {
                    $('#manager-table').html(data.html);
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
