@extends('layout.app', ['page_name' => 'Edit Recap'])

@php
@endphp

@section('content')
    <div class="container py-3">
        <div class="h5">Edit Recap</div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            @include('ambassador.recap.partials.recap-event-context', ['recap' => $recap, 'recapType' => $recapType ?? null])
            <form @if(Auth::user()->id=='1') action="{{ route('admin.update-recap') }}" @else action="{{ route('ambassador.update-recap') }}" @endif enctype="multipart/form-data" method="POST"> @csrf
                <input type="hidden" name="recap_id" value="{{ $recap_id }}">
                <div class="row ">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="traffic_location" class="form-label text-uppercase">Describe the location's traffic
                                (Registers, Aisles, Other parts of location)</label>
                            <input type="text" name="traffic_location" class="form-control shadow-none @error('traffic_location') is-invalid @enderror" value="{{ $recap->traffic_location??'' }}" id="traffic_location">
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
                                id="eventSuccesfull" value="{{ $recap->close_time??'' }}">
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
                                    id="checkIn" value="{{ $recap->check_in_with_person??'' }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_attachments1" class="form-label text-uppercase">
                                    Attach a photo of each product everywhere you see it on a display (not the shelf/not
                                    your
                                    setup). Show price/competitive set if available.
                                </label>
                                <input type="file"
                                    class="form-control shadow-none @error('product_attachments1') is-invalid @enderror"
                                    name="product_attachments1"
                                    id="product_attachments1">
                                @error('product_attachments1')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
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
                                <option value="1" @if($recap->promotion_product_status == '1') selected @endif>Yes</option>
                                <option value="0" @if($recap->promotion_product_status == '0') selected @endif>No</option>
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
                            <input type="text" class="form-control shadow-none" name="tip" value="{{ $recap->tip??'' }}" id="everydayPriceTip">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="product_attachments2" class="form-label text-uppercase">
                                Attach a photo of each product everywhere you see it on the shelf (not a display) showing
                                surrounding. Show price/competitors if available.
                            </label>
                            <input type="file"
                                class="form-control shadow-none @error('product_attachments2') is-invalid @enderror"
                                name="product_attachments2"
                                id="product_attachments2">
                            @error('product_attachments2')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="competitorBrands" class="form-label text-uppercase">
                                If competitor brands were having demos on-site, list their names, setup, location and price
                                point.
                            </label>
                            <input type="text" class="form-control shadow-none" name="brand_dmeo_name"
                                id="competitorBrands" value="{{ $recap->brand_dmeo_name??'' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Backup brand</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="backup_brand_used"
                                        id="backup_brand_no" value="0"
                                        {{ old('backup_brand_used', $recap->backup_brand_used ? '1' : '0') == '0' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="backup_brand_no">No</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="backup_brand_used"
                                        id="backup_brand_yes" value="1"
                                        {{ old('backup_brand_used', $recap->backup_brand_used ? '1' : '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="backup_brand_yes">Back up brand used</label>
                                </div>
                            </div>
                            @error('backup_brand_used')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6" id="backup_brand_name_wrap"
                        style="{{ old('backup_brand_used', $recap->backup_brand_used ? '1' : '0') == '1' ? '' : 'display: none;' }}">
                        <div class="mb-3">
                            <label for="backup_brand_name" class="form-label text-uppercase">What brand did you use?</label>
                            <input type="text" class="form-control shadow-none @error('backup_brand_name') is-invalid @enderror"
                                name="backup_brand_name" id="backup_brand_name"
                                value="{{ old('backup_brand_name', $recap->backup_brand_name) }}"
                                maxlength="255" placeholder="Brand name">
                            @error('backup_brand_name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="brand_photo" class="form-label text-uppercase">
                                Add a photo of you with your setup (include table, products, etc.)
                            </label>
                            <input type="file"
                                class="form-control shadow-none @error('brand_photo') is-invalid @enderror"
                                name="brand_photo" id="brand_photo">
                            @error('brand_photo')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
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
                                <option value="male" @if($recap->interaction_gender == 'male') selected @endif>Male</option>
                                <option value="female" @if($recap->interaction_gender == 'female') selected @endif>Female</option>
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
                            <input type="text"
                                class="form-control shadow-none @error('interaction_age') is-invalid @enderror"
                                name="interaction_age" id="predominantAge" placeholder="21-25"
                                inputmode="numeric" maxlength="2" pattern="2[1-5]"
                                title="Enter an age from 21 to 25"
                                value="{{ old('interaction_age', $recap->interaction_age) }}">
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
                                <option value="1-5" @if($recap->total_interaction == '1-5') selected @endif>1-5</option>
                                <option value="5-10" @if($recap->total_interaction == '5-10') selected @endif>5-10</option>
                                <option value="15-20" @if($recap->total_interaction == '15-20') selected @endif>15-20</option>
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
                                value="{{ old('samples', $recap->samples) }}">
                            @error('samples')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12" id="kit-giveaways-section" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="giveaway_count" class="form-label text-uppercase">How many giveaways/items were handed out?</label>
                                    <input type="number" min="0" step="1"
                                        class="form-control shadow-none @error('giveaway_count') is-invalid @enderror"
                                        name="giveaway_count" id="giveaway_count"
                                        value="{{ old('giveaway_count', $recap->giveaway_count) }}">
                                    @error('giveaway_count')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="giveaway_comments" class="form-label text-uppercase">Comments on POS/giveaways</label>
                                    <textarea rows="3"
                                        class="form-control shadow-none @error('giveaway_comments') is-invalid @enderror"
                                        name="giveaway_comments" id="giveaway_comments"
                                        placeholder="Add any notes about POS materials and giveaways...">{{ old('giveaway_comments', $recap->giveaway_comments) }}</textarea>
                                    @error('giveaway_comments')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="consumer_photo" class="form-label text-uppercase">
                                Attach 2-4 photos of consumers with the product.
                            </label>
                            <input type="file"
                                class="form-control shadow-none {{ $errors->has('consumer_photo') || $errors->has('consumer_photo.*') ? 'is-invalid' : '' }}"
                                name="consumer_photo[]" id="consumer_photo" multiple>
                            @error('consumer_photo')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            @if($errors->has('consumer_photo.*'))
                                <div class="text-danger">{{ $errors->first('consumer_photo.*') }}</div>
                            @endif
                        </div>
                    </div>
                    @if($recap->event && $recap->event->isThcEvent())
                    <!-- THC Products Section -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">THC Products Sold</label>
                            <div id="thc-products-container">
                                @if($recap->thc_products_sold)
                                    @php
                                        $thcProducts = is_string($recap->thc_products_sold) ? json_decode($recap->thc_products_sold, true) : $recap->thc_products_sold;
                                    @endphp
                                    @if($thcProducts && is_array($thcProducts))
                                        @foreach($thcProducts as $index => $product)
                                        <div class="row thc-product-row mb-2" data-index="{{ $index }}">
                                            <div class="col-md-3">
                                                <input type="text" name="thc_products[{{ $index }}][brand]" class="form-control" placeholder="Brand" value="{{ $product['brand'] }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="thc_products[{{ $index }}][flavor]" class="form-control" placeholder="Flavor" value="{{ $product['flavor'] }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="thc_products[{{ $index }}][dosage]" class="form-control" placeholder="Dosage" value="{{ $product['dosage'] }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="thc_products[{{ $index }}][type]" class="form-control" placeholder="Type" value="{{ $product['type'] }}" required>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-thc-product">×</button>
                                            </div>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="row thc-product-row mb-2" data-index="0">
                                            <div class="col-md-3">
                                                <input type="text" name="thc_products[0][brand]" class="form-control" placeholder="Brand" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="thc_products[0][flavor]" class="form-control" placeholder="Flavor" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="thc_products[0][dosage]" class="form-control" placeholder="Dosage" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="thc_products[0][type]" class="form-control" placeholder="Type" required>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-thc-product">×</button>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="row thc-product-row mb-2" data-index="0">
                                        <div class="col-md-3">
                                            <input type="text" name="thc_products[0][brand]" class="form-control" placeholder="Brand" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="thc_products[0][flavor]" class="form-control" placeholder="Flavor" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="thc_products[0][dosage]" class="form-control" placeholder="Dosage" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="thc_products[0][type]" class="form-control" placeholder="Type" required>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-thc-product">×</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" id="add-thc-product" class="btn btn-secondary btn-sm mt-2">Add Product</button>
                        </div>
                    </div>
                    @else
                    <!-- Regular Products Section -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="productSelling" class="form-label text-uppercase">
                                What type of product were you selling?
                            </label>
                            <select class="form-select shadow-none" name="brand_selling_id" id="productSelling">
                                <option selected value="">Select</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" @if($brand->id == $recap->brand_selling_id) selected @endif>{{ $brand->brand_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if(($recapType ?? \App\Services\RecapFormService::resolveForRecap($recap)) !== 'on_premise')
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="productWereSold" class="form-label text-uppercase">
                                How many units of each product were sold during your task?
                            </label>
                            <input type="number" min="0" step="1"
                                class="form-control shadow-none @error('sold_unit') is-invalid @enderror"
                                name="sold_unit" id="productWereSold" placeholder="0"
                                value="{{ old('sold_unit', $recap->sold_unit) }}">
                            @error('sold_unit')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @endif
                    @endif
                    @include('ambassador.recap.partials.buyback-amount-field', ['value' => $recap->buyback_amount])
                    @include('ambassador.recap.partials.recap-type-fields', [
                        'recapType' => $recapType ?? \App\Services\RecapFormService::resolveForRecap($recap),
                        'mode' => 'edit',
                        'recap' => $recap,
                    ])

                </div>
                @include('ambassador.recap.partials.quickbooks-requirements-card')
                @include('ambassador.recap.partials.expedited-pay', [
                    'existingValue' => $recap->is_expedited_requested ?? false,
                    'submitBtnId'   => 'recapSubmitBtn',
                ])

                <div class="d-flex gap-2">
                    @if(($recap->is_draft ?? false) && Auth::user()->id != '1')
                        <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-secondary px-4">Save as Draft</button>
                        <button type="submit" id="recapSubmitBtn" class="btn btn-design color-grey px-4" {{ isset($quickbooksRequirements) && ! ($quickbooksRequirements['ready'] ?? true) ? 'disabled' : '' }}>Submit</button>
                    @else
                        <button type="submit" id="recapSubmitBtn" class="btn btn-design color-grey px-4" {{ isset($quickbooksRequirements) && ! ($quickbooksRequirements['ready'] ?? true) ? 'disabled' : '' }}>Update</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
let thcProductIndex = {{ $recap->thc_products_sold ? count(json_decode($recap->thc_products_sold, true)) : 1 }};

$(document).ready(function() {
    function syncBackupBrandFields() {
        const on = $('input[name="backup_brand_used"]:checked').val() === '1';
        $('#backup_brand_name_wrap').toggle(on);
        if (!on) {
            $('#backup_brand_name').val('');
        }
    }
    $('input[name="backup_brand_used"]').on('change', syncBackupBrandFields);

    // Add THC Product
    $('#add-thc-product').click(function() {
        const newRow = `
            <div class="row thc-product-row mb-2" data-index="${thcProductIndex}">
                <div class="col-md-3">
                    <input type="text" name="thc_products[${thcProductIndex}][brand]" class="form-control" placeholder="Brand" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="thc_products[${thcProductIndex}][flavor]" class="form-control" placeholder="Flavor" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="thc_products[${thcProductIndex}][dosage]" class="form-control" placeholder="Dosage" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="thc_products[${thcProductIndex}][type]" class="form-control" placeholder="Type" required>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-thc-product">×</button>
                </div>
            </div>
        `;
        $('#thc-products-container').append(newRow);
        thcProductIndex++;
    });

    // Remove THC Product
    $(document).on('click', '.remove-thc-product', function() {
        if ($('.thc-product-row').length > 1) {
            $(this).closest('.thc-product-row').remove();
        }
    });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kitRequired = {{ (int) (optional($recap->event)->kit ?? 0) }} === 1;
        const section = document.getElementById('kit-giveaways-section');
        if (!section) return;
        section.style.display = kitRequired ? 'block' : 'none';
    });
</script>
