@extends('layout.app', ['page_name' => 'Create Brand Category'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Brand Category Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route($routePrefix.'.store.brand-category')}}" method="POST">@csrf
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="brandName" class="form-label text-uppercase">Category name</label>
                            <input type="text" class="form-control shadow-none @error('name') is-invalid @enderror" name="name" id="brandName"
                                placeholder="Name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="note" class="form-label text-uppercase">Note</label>
                            <textarea type="text" class="form-control shadow-none @error('note') is-invalid @enderror" name="note" id="note" placeholder="note"></textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection
