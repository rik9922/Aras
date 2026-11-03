@extends('layout.app', ['page_name' => 'Event Type List'])

@section('content')
    <div class="container py-3">
        <div class="d-grid">
            <div class="h5"> Event Type List : <span class="total-data">@if (!empty($AmbassadorType) && count($AmbassadorType) > 0) {{count($AmbassadorType)}} @endif</span> </div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col d-flex justify-content-end">
                    <a href="{{ route('admin.eventtypes.create') }}">
                        <button type="button" class="btn-ds btn-primary-ds">Add Event Type</button>
                    </a>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div>Name</div>
                            </th>
                            <th>
                                <div>Description</div>
                            </th>
                            <th>
                                <div>Recap Form Type</div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($AmbassadorType) && count($AmbassadorType) > 0)
                            @foreach ($AmbassadorType as $ambassadorType)
                                <tr>
                                    <td>{{ $ambassadorType->name }}</td>
                                    <td>{{ $ambassadorType->description }}</td>
                                    <td>{{ \App\Services\RecapFormService::displayLabel($ambassadorType->recap_type ?: 'off_premise') }}</td>
                                    <td>
                                        <a href="{{ route('admin.edit.eventtypes', ['id' => $ambassadorType->id]) }}">
                                            <button type="button" class="btn-ds btn-secondary-ds">
                                                <span>Edit</span>
                                            </button>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="text-center" colspan="5">Data Not Found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
