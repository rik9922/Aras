@extends('layout.app', ['page_name' => 'Location List'])

@section('content')
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Locations : <span class="total-data">{{ $locations->total() }}</span></div>
        </div>
        <div class="fixed-border"></div>

        {{-- Sync Lat/Lng result banner --}}
        @if (session('sync_result'))
            @php $r = session('sync_result'); @endphp
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <strong><i class="bi bi-check-circle-fill me-1"></i>Sync Complete</strong>
                <div class="mt-1 d-flex flex-wrap gap-4">
                    <span><strong>Processed:</strong> {{ $r['processed'] }}</span>
                    <span class="text-success"><strong>Updated:</strong> {{ $r['updated'] }}</span>
                    <span class="text-danger"><strong>Failed:</strong> {{ $r['failed'] }}</span>
                    <span class="text-muted"><strong>Skipped:</strong> {{ $r['skipped'] }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col-md-6 col-lg-8 pt-3">
                </div>
                <div class="col pt-3 d-flex gap-2 justify-content-end flex-wrap">
                    <button type="button" class="btn-ds btn-warning-ds" id="syncLatLngBtn">
                        <i class="bi bi-geo-alt-fill me-1"></i>Sync Lat/Lng
                    </button>
                    <a href="{{ route('admin.event.location.create') }}">
                        <button type="button" class="btn-ds btn-secondary-ds">Add Location</button>
                    </a>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th><div>Location Name</div></th>
                            <th><div>Address</div></th>
                            <th><div>City</div></th>
                            <th><div>State</div></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="location-table">
                        @if (!empty($locations) && count($locations) > 0)
                            @foreach ($locations as $location)
                                <tr>
                                    <td>{{ $location->name ?? '-' }}</td>
                                    <td>{{ $location->address }}</td>
                                    <td>{{ $location->city }}</td>
                                    <td>{{ $location->state ? $location->state : '-' }}</td>
                                    <td>
                                        <div>
                                            <a href="{{ route('admin.edit.event.location', ['id' => $location->id]) }}">
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
                                <td class="text-center" colspan="5">Data not found</td>
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

    {{-- Confirmation Modal --}}
    <div class="modal fade" id="syncConfirmModal" tabindex="-1" aria-labelledby="syncConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="syncConfirmModalLabel">
                        <i class="bi bi-geo-alt-fill me-2 text-warning"></i>Sync Lat/Lng
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">This will geocode all Event Locations that are missing latitude or longitude.</p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Records are processed in chunks. Large datasets may take a few minutes.
                        The page will reload automatically when complete.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="syncConfirmBtn">
                        <span id="syncBtnText"><i class="bi bi-play-fill me-1"></i>Yes, Continue</span>
                        <span id="syncBtnLoading" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Syncing…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden sync form --}}
    <form id="syncLatLngForm" action="{{ route('admin.event.locations.sync-lat-lng') }}" method="POST" class="d-none">
        @csrf
    </form>

@endsection

<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script>
    $(document).ready(function () {

        // Open confirmation modal on button click
        $('#syncLatLngBtn').on('click', function () {
            var modal = new bootstrap.Modal(document.getElementById('syncConfirmModal'));
            modal.show();
        });

        // Submit sync form on confirm
        $('#syncConfirmBtn').on('click', function () {
            $('#syncBtnText').addClass('d-none');
            $('#syncBtnLoading').removeClass('d-none');
            $(this).prop('disabled', true);
            $('#syncLatLngForm').submit();
        });

        function loadFilteredClients() {
            let search = $('#search').val();
            let inactive = $('#viewInactiveCheckbox').prop('checked') ? '0' : '';

            $.ajax({
                url: '{{ route("admin.locations") }}',
                method: 'GET',
                data: { search: search, inactive: inactive },
                success: function (data) {
                    $('#location-table').html(data.html);
                }
            });
        }

        $('#search, #viewInactiveCheckbox').on('change keyup', loadFilteredClients);
        $('#search').on('input', function () {
            if ($(this).val() === '') loadFilteredClients();
        });

    });
</script>
