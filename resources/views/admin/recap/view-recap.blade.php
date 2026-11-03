@extends('layout.app', ['page_name' => 'View Recap'])

@section('content')
    <div class="container py-3">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="describeLocation" class="form-label text-uppercase">Describe the location's traffic (Registers, Aisles, Other parts of location)</label>
                    <p class="text-bold">{{ 'Not available' }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="eventSuccesfull" class="form-label text-uppercase">Was the timing of this event successful?</label>
                    <p>{{ $recap->close_time ? \Carbon\Carbon::parse($recap->close_time)->format('Y-m-d H:i') : 'Not available' }}</p>
                </div>
            </div>
            <div class="row align-items-end">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="checkIn" class="form-label text-uppercase">With whom did you check in? Provide name, phone and/or email</label>
                        <p>{{ $recap->check_in_with_person ?? 'Not available' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="product_attachments1" class="form-label text-uppercase">Attach a photo of each product everywhere you see it on a display (not the shelf/not your setup). Show price/competitive set if available.</label>
                        <p>
                            @if($recap->product_attachments1)
                            <img src="{{asset('storage/app/private/' . $recap->product_attachments1) }}" alt="">
                            @else
                                Not available
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="selectShirtSize" class="form-label text-uppercase">Is there a promotion running for this product?</label>
                    <p>{{ $recap->promotion_product_status ? 'Yes' : 'No' }}</p>
                </div>
            </div>
            <div class="row align-items-end">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="everydayPriceTip" class="form-label text-uppercase">Everyday Price Tip (line break) What is the Feature/Special Pricing?</label>
                        <p>{{ $recap->tip ?? 'Not available' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="product_attachments2" class="form-label text-uppercase">Attach a photo of each product everywhere you see it on the shelf (not a display) showing surrounding. Show price/competitors if available.</label>
                        <p>
                            @if($recap->product_attachments2)
                                <img src="{{ asset('storage/' . $recap->product_attachments2) }}" alt="">
                            @else
                                Not available
                            @endif
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="competitorBrands" class="form-label text-uppercase">If competitor brands were having demos on-site, list their names, setup, location and price point.</label>
                        <p>{{ $recap->brand_dmeo_name ?? 'Not available' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="brand_photo" class="form-label text-uppercase">Add a photo of you with your setup (include table, products, etc.)</label>
                        <p>
                            @if($recap->brand_photo)
                                <img src="{{ asset('storage/' . $recap->brand_photo) }}" alt="">
                            @else
                                Not available
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="predominantGender" class="form-label text-uppercase">What was the predominant gender of consumers you interacted with?</label>
                    <p>{{ $recap->interaction_gender ? ucfirst($recap->interaction_gender) : 'Not available' }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="predominantAge" class="form-label text-uppercase">What was the predominant age of consumers you interacted with?</label>
                    <p>{{ $recap->interaction_age ?? 'Not available' }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="manyConsumers" class="form-label text-uppercase">Approximately how many consumers did you interact with?</label>
                    <p>{{ $recap->total_interaction ?? 'Not available' }}</p>
                </div>
            </div>
            <div class="row align-items-end">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="manySamples" class="form-label text-uppercase">How many samples did you hand out?</label>
                        <p>{{ $recap->samples ?? 'Not available' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="consumer_photo" class="form-label text-uppercase">Attach 2-4 photos of consumers with the product.</label>
                        <p>
                            @if($recap->consumer_photo)
                                <img src="{{ asset('storage/' . $recap->consumer_photo) }}" alt="">
                            @else
                                Not available
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    @if($recap->event && $recap->event->isThcEvent())
                        <label for="thcProducts" class="form-label text-uppercase">THC Products Sold</label>
                        @if($recap->thc_products_sold)
                            @php
                                $thcProducts = is_string($recap->thc_products_sold) ? json_decode($recap->thc_products_sold, true) : $recap->thc_products_sold;
                            @endphp
                            @if($thcProducts && is_array($thcProducts))
                                <div class="mt-2">
                                    @foreach($thcProducts as $index => $product)
                                        <p><strong>{{ $product['brand'] }} {{ $product['flavor'] }} {{ $product['dosage'] }} {{ $product['type'] }}</strong></p>
                                    @endforeach
                                </div>
                            @else
                                <p>Not available</p>
                            @endif
                        @else
                            <p>Not available</p>
                        @endif
                    @else
                        <label for="productSelling" class="form-label text-uppercase">What type of product were you selling?</label>
                        <p>{{ $brand->brand_name ?? 'Not available' }}</p>
                    @endif
                </div>
            </div>
            @if(!($recap->event && $recap->event->is_thc_event))
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="productWereSold" class="form-label text-uppercase">How many units of each product were sold during your task?</label>
                    <p>{{ $recap->sold_unit ?? 'Not available' }}</p>
                </div>
            </div>
            @endif
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="buyback_amount" class="form-label text-uppercase">Buyback amount ($)</label>
                    <p>
                        @if($recap->buyback_amount !== null)
                            ${{ number_format((float) $recap->buyback_amount, 2) }}
                        @else
                            Not available
                        @endif
                    </p>
                </div>
            </div>
            @include('common.partials.recap-type-display', ['recap' => $recap])
            <div class="d-flex gap-2 py-2">
                <button type="button" class="btn-ds btn-primary-ds" onclick="window.history.go(-1)">
                    <span class="text-nowrap">Back</span>
                </button>
            </div>
        </div>
    </div>
@endsection
