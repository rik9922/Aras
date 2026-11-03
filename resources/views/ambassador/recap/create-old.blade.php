@extends('layout.app', ['page_name' => 'Create Recap'])

@section('content')
    <div class="container py-3">
        <div class="h5">Submit Recap</div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{ route('ambassador.store-recap') }}" enctype="multipart/form-data" method="POST"> @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <div class="row ">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="traffic_location" class="form-label text-uppercase">Describe the location's traffic
                                (Registers, Aisles, Other parts of location)</label>
                            <input type="text" name="traffic_location"
                                class="form-control shadow-none @error('traffic_location') is-invalid @enderror"
                                id="traffic_location">
                            @error('traffic_location')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="eventSuccesfull" class="form-label text-uppercase">
                                Was the timing of this event succesfull?
                            </label>
                            <input type="datetime-local" name="close_time"
                                class="form-control shadow-none @error('close_time') is-invalid @enderror"
                                id="eventSuccesfull">
                            @error('close_time')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="checkIn" class="form-label text-uppercase">
                                    With whom did you check in? Provide name, phone and/or email
                                </label>
                                <input type="text" class="form-control shadow-none" name="check_in_with_person"
                                    id="checkIn">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_attachments1" class="form-label text-uppercase">
                                    Attach a photo of each product everywhere you see it on a display (not the shelf/not
                                    your
                                    setup). Show price/competitive set if available.
                                </label>
                                <input type="file" class="form-control shadow-none" name="product_attachments1"
                                    id="product_attachments1">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectShirtSize" class="form-label text-uppercase">
                                Is there a promotion running for this product?
                            </label>
                            <select class="form-select shadow-none @error('promotion_product_status') is-invalid @enderror"
                                name="promotion_product_status" id="selectShirtSize">
                                <option value="" selected>Select</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                            @error('promotion_product_status')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="everydayPriceTip" class="form-label text-uppercase">
                                Everyday Price Tip (line break) What is the Feature/Special Pricing?
                            </label>
                            <input type="text" class="form-control shadow-none" name="tip" id="everydayPriceTip">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="product_attachments2" class="form-label text-uppercase">
                                Attach a photo of each product everywhere you see it on the shelf (not a display) showing
                                surrounding. Show price/competitors if available.
                            </label>
                            <input type="file" class="form-control shadow-none" name="product_attachments2"
                                id="product_attachments2">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="competitorBrands" class="form-label text-uppercase">
                                If competitor brands were having demos on-site, list their names, setup, location and price
                                point.
                            </label>
                            <input type="text" class="form-control shadow-none" name="brand_dmeo_name"
                                id="competitorBrands">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="brand_photo" class="form-label text-uppercase">
                                Add a photo of you with your setup (include table, products, etc.)
                            </label>
                            <input type="file" class="form-control shadow-none" name="brand_photo" id="brand_photo">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="predominantGender" class="form-label text-uppercase">
                                What was the predominant gender of consumers you interacted with?
                            </label>
                            <select class="form-select shadow-none @error('interaction_gender') is-invalid @enderror"
                                name="interaction_gender" id="predominantGender">
                                <option value="" selected>Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            @error('interaction_gender')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="predominantAge" class="form-label text-uppercase">
                                What was the predominant age of consumers you interacted with?
                            </label>
                            <select class="form-select shadow-none @error('interaction_age') is-invalid @enderror"
                                name="interaction_age" id="predominantAge">
                                <option value="" selected>Select</option>
                                <option value="20-25">20-25</option>
                                <option value="25-30">25-30</option>
                                <option value="30-35">30-35</option>
                                <option value="35-40">35-40</option>
                                <option value="40-45">40-45</option>
                                <option value="45-50">45-50</option>
                            </select>
                            @error('interaction_age')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="manyConsumers" class="form-label text-uppercase">
                                Approximately how many consumers did you interact with?
                            </label>
                            <select class="form-select shadow-none @error('total_interaction') is-invalid @enderror"
                                name="total_interaction" id="manyConsumers">
                                <option value="" selected>Select</option>
                                <option value="1-5">1-5</option>
                                <option value="5-10">5-10</option>
                                <option value="15-20">15-20</option>
                            </select>
                            @error('total_interaction')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="manySamples" class="form-label text-uppercase">
                                How many samples did you hand out?
                            </label>
                            <input type="number" min="0" step="1"
                                class="form-control shadow-none @error('samples') is-invalid @enderror"
                                name="samples" id="manySamples" placeholder="0"
                                value="{{ old('samples') }}">
                            @error('samples')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="consumer_photo" class="form-label text-uppercase">
                                Attach 2-4 photos of consumers with the product.
                            </label>
                            <input type="file" class="form-control shadow-none" name="consumer_photo[]" id="consumer_photo" multiple>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="productSelling" class="form-label text-uppercase">
                                What type of product were you selling?
                            </label>
                            <select class="form-select shadow-none" name="brand_selling_id" id="productSelling">
                                <option selected>Select</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="productWereSold" class="form-label text-uppercase">
                                How many units of each product were sold during your task?
                            </label>
                            <input type="number" min="0" step="1"
                                class="form-control shadow-none @error('sold_unit') is-invalid @enderror"
                                name="sold_unit" id="productWereSold" placeholder="0"
                                value="{{ old('sold_unit') }}">
                            @error('sold_unit')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @include('ambassador.recap.partials.buyback-amount-field')
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="consumerFeedback" class="form-label text-uppercase">
                                Please list all relevant consumer feedback.
                            </label>
                            <input type="text" class="form-control shadow-none" name="consumer_feedback"
                                id="consumerFeedback">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="ambassadorFeedback" class="form-label text-uppercase">
                                Ambassador Feedback
                            </label>
                            <input type="text" class="form-control shadow-none" name="ambassador_feedback"
                                id="ambassadorFeedback">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="receipt" class="form-label text-uppercase">
                                Receipt photo
                            </label>
                            <input type="file" class="form-control shadow-none" name="receipt"
                                id="receipt">
                        </div>
                    </div>

                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="receipt" class="form-label text-uppercase"> State </label>
                            <select name="state_id" id="stateId" class="form-control @error('state_id') is-invalid @enderror">
                                <option value="">Select State</option>
                                @foreach (getState() as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                            @error('state_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="brand_count" class="form-label text-uppercase">How many people knew about the brand?</label>
                            <input type="number" class="form-control shadow-none @error('brand_count') is-invalid @enderror" name="brand_count" id="brand_count">
                            @error('brand_count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <hr />
                
                <div class="d-flex justify-content-between">
                    <div class="h5">Integrate With Best</div>
                    <small class="text-info">All fields are required</small>
                </div>
                
                <div class="fixed-border"></div>
                <div class="row mb-5">
                    <div class="col-md-6 mt-3">
                        <label for="name" class="form-label text-uppercase">Event Name</label>
                        <input type="text" name="name"
                            class="form-control shadow-none @error('name') is-invalid @enderror"
                            id="name" value="{{ $event->name }}" readonly>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mt-3">
                        <label for="Status__c" class="form-label text-uppercase">Status</label>
                        <select class="form-select shadow-none @error('Status__c') is-invalid @enderror"
                                name="Status__c">
                                <option>Planned</option>
                                <option>Unplanned</option>
                            </select>
                        @error('Status__c')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mt-3">
                        <label for="Target_Audience__c" class="form-label text-uppercase">Target Audience</label>
                        <select class="form-select shadow-none @error('Target_Audience__c') is-invalid @enderror"
                            name="Target_Audience__c">
                            <option>Consumer</option>
                            <option>Unplanned</option>
                        </select>
                        @error('Target_Audience__c')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mt-3">
                        <label for="Account__c" class="form-label text-uppercase">Account</label>
                        <select class="form-select shadow-none @error('Account__c') is-invalid @enderror"
                            name="Account__c">
                            <option>Consumer</option>
                            <option>Unplanned</option>
                        </select>
                        @error('Account__c')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-design color-grey px-4">Complete</button>
                </div>
            </form>
        </div>
    </div>
@endsection
