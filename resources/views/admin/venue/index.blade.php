@extends('layout.app', ['page_name' => 'Venue Type List'])
@section('content')

    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Venue/Store Type List : <span class="total-data"> @if(!empty($venues) && count($venues) > 0) {{count($venues)}} @endif</span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col-md-9 pt-3">
                </div>
                <div class="col-md-3 pt-3">
                    <div class="d-grid">
                        <a href="{{ route('admin.venue.create') }}">
                            <button type="button" class="btn-ds btn-secondary-ds">Add Venue/Store Type</button>
                        </a>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div>Name</div>
                            </th>
                            <th>
                                <div>Description</div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($venues) && count($venues) > 0)
                            @foreach ($venues as $venue)
                                <tr>
                                    <td>{{ $venue->name }}</td>
                                    <td>{{ $venue->description }}</td>
                                    <td>
                                        <a href="{{ route('admin.edit.venue', ['id' => $venue->id]) }}">
                                            <button type="button" class="btn-ds btn-primary-ds">
                                                <span>Edit</span>
                                            </button>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="3" class="text-center">Data not Found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</script>