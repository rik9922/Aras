@extends('layout.app', ['page_name' => 'My Account'])

@section('content')
    <div class="container p-3">
        <div class="page-card-design">
            <form action="{{route('location.update-profile')}}" method="POST" enctype="multipart/form-data">@csrf
                <input type="hidden" name="id" value="{{$location->id}}">
                <div class="row">
                    <div class="col-md-6 col-xl-8 order-xl-1 order-2">
                        <div class="row pe-xl-4">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label text-uppercase">First Name</label>
                                    <input type="text" class="form-control shadow-none @error('first_name') is-invalid @enderror" value="{{$location->first_name}}" name="first_name" id="firstName">
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label text-uppercase">Last Name</label>
                                    <input type="text" class="form-control shadow-none @error('last_name') is-invalid @enderror" value="{{$location->last_name}}" name="last_name" id="lastName">
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label text-uppercase">Email</label>
                                    <input type="email" readonly disabled class="form-control shadow-none @error('email') is-invalid @enderror" value="{{$location->email}}" id="email">

                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label text-uppercase">Phone</label>
                                    <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror" value="{{$location->phone}}" name="phone" id="phone">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="venue_name" class="form-label text-uppercase">Venue/Store Name</label>
                                    <input type="text" class="form-control shadow-none @error('venue_name') is-invalid @enderror" value="{{$location->venue_name}}" name="venue_name" id="venue_name">
                                    @error('venue_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="venue_type" class="form-label text-uppercase">Venue/Store Type</label>
                                    <select class="form-select shadow-none  @error('state_id') is-invalid @enderror" id="venue_type" name="venue_type">
                                        @if(!empty($venues))
                                            @foreach ($venues as $venue)
                                                <option @if($location->venue_type == $venue->id) selected @endif value="{{$venue->id}}">{{$venue->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('venue_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label text-uppercase">Address</label>
                                    <input type="text" class="form-control shadow-none @error('address') is-invalid @enderror" value="{{$location->address}}" name="address" id="address">
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label text-uppercase">City</label>
                                    <input type="text" class="form-control shadow-none @error('city') is-invalid @enderror" value="{{$location->city}}" name="city" id="city">
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label text-uppercase">Password</label>
                                    <input type="password" class="form-control shadow-none @error('city') is-invalid @enderror" name="password" id="password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="selectBrands" class="form-label text-uppercase">State</label>
                                    <div class="input-group mb-3">
                                        <select class="form-select shadow-none  @error('state_id') is-invalid @enderror" id="selectBrands" name="state_id">
                                            @if(!empty($states))
                                                @foreach ($states as $state)
                                                    <option value="{{$state->id}}" @if($location->state_id == $state->id) selected @endif>{{$state->short_name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    @error('state_id')
                                        <div class="text-danger w-100">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="d-flex flex-wrap align-items-center">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input shadow-none" name="status" type="radio" value="1" @if($location->status=='1') checked @endif id="active" >
                                        <label class="form-check-label text-uppercase" for="active">
                                            Active
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mt-4 mx-2">
                                        <input class="form-check-input shadow-none" name="status" type="radio" value="0" @if($location->status=='0') checked @endif id="inactive">
                                        <label class="form-check-label text-uppercase" for="inactive">
                                            Inactive
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                   
                    <div class="col-md-6 col-xl-4 order-xl-2 order-1">
                        <div class="row">
                            <div class="h4 text-uppercase">Profile Picture</div>
                            <div class="col-12">
                                <div class="profile-img">
                                    <img id="preview-image" src="{{ asset($location->profile_image ?? 'assets/images/profile.jpg') }}" height="150px" width="150px" style="object-fit: cover; border-radius: 50%;">
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
                <div class="d-grid mx-3 mb-3">
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-design text-uppercase">Save</button>
                        <button type="button" class="btn btn-design grey-color" onclick="window.history.back()">Back</button>
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
