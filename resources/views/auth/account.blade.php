@extends('layouts.admin-master', ['title' => 'My Profile'])

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-12">
                
                <form id="formAccountSettings" method="POST" action="{{ route('updateAuthAccount', $user->id) }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="card mb-4">
                        <h5 class="card-header">Profile Details</h5>
                        <!-- Account -->
                        <div class="card-body">
                            <div class="d-flex align-items-start align-items-sm-center gap-4">
                                <img src="{{ asset($user->profile_image ?? '/assets/img/avatars/2.jpg') }}"
                                    alt="user-avatar" class="d-block rounded" height="100" width="100"
                                    id="uploadedAvatar" />
                                <div class="button-wrapper">
                                    <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                                        <span class="d-none d-sm-block">Upload new photo</span>
                                        <i class="bx bx-upload d-block d-sm-none"></i>
                                        <input type="file" id="upload" class="account-file-input" hidden
                                            accept="image/png, image/jpeg, image/jpg" name="profile_image" />
                                    </label>
                                    {{-- <button type="button" class="btn btn-outline-secondary account-image-reset mb-4">
                                        <i class="bx bx-reset d-block d-sm-none"></i>
                                        <span class="d-none d-sm-block">Reset</span>
                                    </button> --}}

                                </div>
                            </div>
                        </div>
                        <hr class="my-0" />
                        <div class="card-body">
                            <div class="row">
                              
                                <div class="mb-3">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label" for="basic-default-first_name">First Name</label>
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                            value="{{ old('first_name',$user->first_name) }}" autocomplete="first_name" autofocus
                                            id="basic-default-first_name" placeholder="Enter First Name Name..."
                                            name="first_name" />
                                        @error('first_name')
                                            <span class="help-block invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label" for="basic-default-last_name">Last Name</label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                            value="{{ old('last_name',$user->last_name) }}" autocomplete="last_name" autofocus
                                            id="basic-default-last_name" placeholder="Enter Last Name..."
                                            name="last_name" />
                                        @error('last_name')
                                            <span class="help-block invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                                <div class="mb-3 col-md-12">
                                    <label for="email" class="form-label">E-mail</label>
                                    <input class="form-control @error('email') is-invalid @enderror" type="text"
                                        id="email" name="email" value="{{ $user->email }}"
                                        placeholder="john.doe@example.com" readonly />
                                    @error('email')
                                        <span class="help-block invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="mb-3 col-md-12">
                                    <label class="form-label" for="zip_code">Zip Code</label>
                                    <div class="input-group input-group-merge">
                                        <input type="text" id="zip_code" name="zip_code"
                                            class="form-control @error('zip_code') is-invalid @enderror"
                                            placeholder="Enter Zip Code..." value="{{ $user->zip_code }}" />
                                        @error('zip_code')
                                            <span class="help-block invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                        
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary me-2">Save changes</button>
                                <button type="button" onclick="window.location='{{ route('admin.dashoard') }}'"
                                    class="btn btn-outline-secondary">Cancel</button>
                            </div>
                </form>
            </div>
            <!-- /Account -->
        </div>
    </div>
    </div>
    </div>
@endsection
