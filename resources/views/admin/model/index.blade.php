@extends('layout.app', ['page_name' => 'Model'])

@section('content')
    <div class="container">
        <div class="page-contents">
            <div class="d-grid">
                <div class="h5 text-uppercase">Search Ambassadors</div>
            </div>
            <div class="d-flex justify-content-end mx-3">
                <button class="btn-ds btn-primary-ds" type="button" data-bs-toggle="collapse" data-bs-target="#search"
                    aria-expanded="false" aria-controls="search">
                    <i class="fa-solid fa-caret-up"></i>
                    <span class="px-2">Search</span>
                </button>
            </div>
            <div class="collapse" id="search">
                <div class="card list-form-card mt-3">
                    <div class="row p-3">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="firstName" class="form-label">First Name</label>
                                        <input type="text" class="form-control shadow-none" name="first name"
                                            id="firstName">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lastName" class="form-label">Last Name</label>
                                        <input type="text" class="form-control shadow-none" name="last name"
                                            id="lastName">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control shadow-none" name="email"
                                            id="email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control shadow-none" name="phone"
                                            id="phone">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control shadow-none" name="city"
                                            id="city">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="selectMarket" class="form-label">Markets</label>
                                        <select class="form-select shadow-none" id="selectMarket"
                                            aria-label="Select Market(s)">
                                            <option value="1">select</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12 col-xl-6">
                                    <div class="mb-3">
                                        <label for="selectDivision" class="form-label">Select Hired Date or Date
                                            Range</label>
                                        <div class="d-flex align-items-center">
                                            <select class="form-select shadow-none" aria-label="Select Division">
                                                <option value="1">select</option>
                                                <option value="2">Two</option>
                                                <option value="3">Three</option>
                                            </select>
                                            <div class="px-2">to</div>
                                            <select class="form-select shadow-none" aria-label="Select Division">
                                                <option value="1">select</option>
                                                <option value="2">Two</option>
                                                <option value="3">Three</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="daysSinceLastEvent" class="form-label">Days Since Last Event</label>
                                        <select class="form-select shadow-none" id="daysSinceLastEvent">
                                            <option value="1">select</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="activeCheckbox">
                                    <label class="form-check-label" for="activeCheckbox">
                                        Active
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="inactiveCheckbox">
                                    <label class="form-check-label" for="inactiveCheckbox">
                                        Inactive
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="documents">
                                    <label class="form-check-label" for="documents">
                                        All Documents Received
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="videoWatched">
                                    <label class="form-check-label" for="videoWatched">
                                        Videos Watched
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="hasUnwatchedVideos">
                                    <label class="form-check-label" for="hasUnwatchedVideos">
                                        Has Unwatched Videos
                                    </label>
                                </div>
                                <div class="form-check my-2">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="comdataCardSent">
                                    <label class="form-check-label" for="comdataCardSent">
                                        Comdata Card Sent
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- pagination -->
            <nav class="nav justify-content-center mt-4">
                <ul class="pagination">
                    <li class="page-item"><a class="page-link" href="#">First</a></li>
                    <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                    <li class="page-item"><a class="page-link active" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                    <li class="page-item"><a class="page-link" href="#">Last</a></li>
                </ul>
            </nav>
            <!-- pagination -->
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div style="width: 200px">Brand</div>
                            </th>
                            <th>
                                <div style="width: 200px">Event Name</div>
                            </th>
                            <th>
                                <div style="width: 80px;">City</div>
                            </th>
                            <th>
                                <div style="width: 100px;">Date</div>
                            </th>
                            <th>
                                <div>Ambassador(s)</div>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Lorem, ipsum dolor.</td>
                            <td>Lorem ipsum dolor sit amet consectetur.</td>
                            <td>
                                Stevens Point
                            </td>
                            <td>oct-30-2024</td>
                            <td>Lorem ipsum</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn-ds btn-primary-ds">
                                        <span>Edit</span>
                                    </button>
                                    <button type="button" class="btn-ds btn-secondary-ds">
                                        <span class="text-nowrap">View</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Lorem, ipsum dolor.</td>
                            <td>Lorem ipsum dolor sit amet consectetur.</td>
                            <td>
                                Stevens Point
                            </td>
                            <td>oct-30-2024</td>
                            <td>Lorem ipsum</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn-ds btn-primary-ds">
                                        <span>Edit</span>
                                    </button>
                                    <button type="button" class="btn-ds btn-secondary-ds">
                                        <span class="text-nowrap">View</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Lorem, ipsum dolor.</td>
                            <td>Lorem ipsum dolor sit amet consectetur.</td>
                            <td>
                                Stevens Point
                            </td>
                            <td>oct-30-2024</td>
                            <td>Lorem ipsum</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn-ds btn-primary-ds">
                                        <span>Edit</span>
                                    </button>
                                    <button type="button" class="btn-ds btn-secondary-ds">
                                        <span class="text-nowrap">View</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
