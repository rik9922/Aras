@extends('layout.app', ['page_name' => 'My Account'])

@section('content')
    <div class="container p-3">
        <form>
            <div class="row">
                <div class="col-md-6 col-xl-8">
                    <div class="row pe-xl-4">
                        <div class="h4 text-uppercase">Profile</div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="firstName" class="form-label text-uppercase">First Name</label>
                                <input type="text" class="form-control shadow-none" name="first_name" value="{{}}" id="firstName"
                                    placeholder="Brody">
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="lastName" class="form-label text-uppercase">Last Name</label>
                                <input type="text" class="form-control shadow-none" name="last name" id="lastName"
                                    placeholder="Stephenson">
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="phoneNumber" class="form-label text-uppercase">Cell Phone Number</label>
                                <input type="text" class="form-control shadow-none" name="phone number" id="phoneNumber">
                                <div class="text-muted">
                                    Your cell phone may be used for text notifications. Standard message and data rates may
                                    apply
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="emailORusername" class="form-label text-uppercase">Email / Username</label>
                                <input type="email" class="form-control shadow-none" name="email or username"
                                    id="emailORusername" placeholder="brody@nosotrostequila.com">
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="address" class="form-label text-uppercase">Address</label>
                                <input type="text" class="form-control shadow-none" name="address" id="address"
                                    placeholder="Milwaukee, WI">
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="city" class="form-label text-uppercase">City</label>
                                <input type="text" class="form-control shadow-none" name="city" id="city"
                                    placeholder="Milwaukee">
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="state" class="form-label text-uppercase">State</label>
                                <input type="text" class="form-control shadow-none" name="state" id="state"
                                    placeholder="WI">
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="zip" class="form-label text-uppercase">Zip</label>
                                <input type="text" class="form-control shadow-none" name="zip" id="zip"
                                    placeholder="456576">
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="password" class="form-label text-uppercase">Password</label>
                                <input type="password" class="form-control shadow-none" name="password" id="password">
                                <div class="text-muted">
                                    Leave blank to keep unchanged
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="row">
                        <div class="h4 text-uppercase">Distributors</div>
                        <div class="col-12">
                            <ul>
                                <li>Badger Liquor North</li>
                                <li>Badger Liquor South</li>
                                <li>Badger Liquor West</li>
                                <li>Frank Liquor</li>
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="h4 text-uppercase">Brands</div>
                        <div class="col-12">
                            <ul>
                                <li>Nosotros Tequila</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-grid mb-3 border-top pt-3">
                <button type="button" class="btn btn-design text-uppercase">Save</button>
            </div>
        </form>
    </div>
@endsection
