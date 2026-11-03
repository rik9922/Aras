@extends('layout.app', ['page_name' => 'Create Venue/Store Type'])

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Venue/Store Type Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route("admin.store.venue")}}" method="POST">@csrf
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="name" class="form-label text-uppercase">name</label>
                            <input type="text" class="form-control shadow-none @error('name') is-invalid @enderror" name="name" id="name"
                                placeholder="Name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="description" class="form-label text-uppercase">Description</label>
                            <textarea type="text" class="form-control shadow-none @error('description') is-invalid @enderror" name="description" id="description" placeholder="Description"></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                    <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>
@endsection
