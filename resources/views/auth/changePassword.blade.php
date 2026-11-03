@extends('layouts.admin-master', ['title' => 'Change Password'])

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <h5 class="card-header">Change Password</h5>
                    <!-- Account -->
                    <hr class="my-0" />
                    <div class="card-body">
                        <form id="formAccountSettings" method="POST" action="{{ route('updatePassword') }}">
                            @csrf
                            <div class="row form-password-toggle">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="mb-3 col-md-12 input-group input-group-merge">
                                    <input class="form-control @error('current_password') is-invalid @enderror"
                                        type="password" id="current_password" name="current_password" autofocus
                                        aria-describedby="password" autocomplete="off"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                    @error('current_password')
                                        <span class="help-block invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                            </div>
                            <div class="row form-password-toggle">
                                <label for="password" class="form-label">New Password</label>
                                <div class="mb-3 col-md-12 input-group input-group-merge">
                                    <input class="form-control @error('password') is-invalid @enderror" type="password"
                                        id="password" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        autofocus aria-describedby="password" autocomplete="off" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                    @error('password')
                                        <span class="help-block invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                            </div>
                            <div class="row form-password-toggle">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <div class="mb-3 col-md-12 input-group input-group-merge">
                                    <input class="form-control @error('password_confirmation') is-invalid @enderror"
                                        type="password" id="password_confirmation" name="password_confirmation"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        autofocus aria-describedby="password" autocomplete="off" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                    @error('password_confirmation')
                                        <span class="help-block invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
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
