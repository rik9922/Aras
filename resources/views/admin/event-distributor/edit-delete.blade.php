@extends('layout.app', ['page_name' => 'Edit Distributor'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Edit Distributor</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route($routePrefix.'.update.event-destributor')}}" method="POST">@csrf
                <input type="hidden" name="id" value="{{$eventDistributor->id}}">
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">Name</label>
                            <input type="text" class="form-control shadow-none @error('name') is-invalid @enderror" value="{{$eventDistributor->name}}" name="name" id="firstName">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    {{-- <div class="col-12">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Email</label>
                            <input type="text" class="form-control shadow-none @error('email') is-invalid @enderror" value="{{$eventDistributor->email}}" name="email" id="lastName">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="phone" class="form-label text-uppercase">Phone</label>
                            <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror" value="{{$eventDistributor->phone}}" name="phone" id="phone">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="companyname" class="form-label text-uppercase">Company Name</label>
                            <input type="text" class="form-control shadow-none @error('company_name') is-invalid @enderror" value="{{$eventDistributor->company_name}}" name="company_name" id="company_name">
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="distributor_type" class="form-label text-uppercase">Distributor Type</label>
                            <input type="text" class="form-control shadow-none @error('distributor_type') is-invalid @enderror" value="{{$eventDistributor->distributor_type}}" name="distributor_type" id="distributor_type">
                            @error('distributor_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="address" class="form-label text-uppercase">Address</label>
                            <input type="text" class="form-control shadow-none @error('address') is-invalid @enderror" value="{{$eventDistributor->address}}" name="address" id="address">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div> --}}
                </div>
                <div class="col-2 gap-2">
                    <button type="submit" class="btn-ds btn-save">Save</button>
                    <button type="button" class="btn-ds btn-back" onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>
@endsection
