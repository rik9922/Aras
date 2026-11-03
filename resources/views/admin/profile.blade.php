@extends('layout.app', ['page_name' => 'My Account'])
<link rel="stylesheet" href="{{asset('assets/select2/select2.css')}}">
@section('content')
    <div class="container p-3">
        <div class="h4">Profile</div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            @php
                $roles = [
                    1 => "admin",
                    6 => "manager"
                ];
                $loggedInRole = auth()->user()->role_id ?? null;
                $role = $roles[$loggedInRole] ?? 'unknown';
            @endphp
            <form action="{{route($role.'.update-profile')}}" method="POST" enctype="multipart/form-data">@csrf
                <div class="row">
                    <div class="col-md-6 col-xl-8 order-xl-1 order-2">
                        <div class="row pe-xl-4">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label text-uppercase">First Name</label>
                                    <input type="text" class="form-control shadow-none  @error('first_name') is-invalid @enderror" value="{{$user->first_name}}" name="first_name" id="firstName">
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label text-uppercase">Last Name</label>
                                    <input type="text" class="form-control shadow-none @error('last_name') is-invalid @enderror" value="{{$user->last_name}}" name="last_name" id="lastName">
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="phoneNumber" class="form-label text-uppercase">Cell Phone Number</label>
                                    <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror" name="phone" value="{{$user->phone}}" id="phoneNumber">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="emailORusername" class="form-label text-uppercase">Email</label>
                                    <input type="email" class="form-control shadow-none" value="{{$user->email}}" readonly disabled
                                        id="emailORusername">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label text-uppercase">Address</label>
                                    <input type="text" class="form-control shadow-none @error('address') is-invalid @enderror" name="address" id="address" value="{{$user->address}}">
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="zip" class="form-label text-uppercase">Zip Code</label>
                                    <input type="text" maxlength="6" class="form-control shadow-none @error('zip_code') is-invalid @enderror" value="{{$user->zip_code}}" name="zip_code" id="zip">
                                    @error('zip_code')
                                        <div class="text-danger w-100">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4 order-xl-2 order-1">
                        <div class="row">
                            <div class="h4 text-uppercase">Profile Picture</div>
                            <div class="col-12">
                                <div class="profile-img">
                                    <img id="preview-image" src="{{ asset($user->profile_image ?? 'assets/images/profile.jpg') }}" height="150px" width="150px" style="object-fit: cover; border-radius: 50%;">
                                </div>
                                <div class="content-box-grey p-3 mt-3">
                                    You can change your profile image at any time, just select a file and click save.
                                    Be sure to make your face center of the image for the best results.
                                    <div class="pt-2">
                                        <label for="file-upload" class="profile-file-upload">
                                            Choose File
                                        </label>
                                        <input id="file-upload" type="file" name="profile_image"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-grid mb-3">
                    <div class="col-sm-3">
                        <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                        <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">Back</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('file-upload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview-image');
    
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
    
@endsection
