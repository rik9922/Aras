@extends('layout.app', ['page_name' => 'Create Department'])
@section('content')
    <div class="container py-2">
        <div class="d-flex">
            <div class="h5">Department Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route("admin.store.event-department")}}" method="POST">@csrf
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="brandName" class="form-label text-uppercase">Department name</label>
                            <input type="text" class="form-control shadow-none  @error('name') is-invalid @enderror" name="name" id="brandName"
                                placeholder="Name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="Description" class="form-label text-uppercase">Description</label>
                            <textarea type="text" class="form-control shadow-none @error('description') is-invalid @enderror" name="description" id="Description" placeholder="Description"></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="col-2 gap-3">
                    <button type="submit" class="btn-ds btn-save">Save</button>
                    <button type="button" class="btn-ds btn-back" onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>
@endsection
