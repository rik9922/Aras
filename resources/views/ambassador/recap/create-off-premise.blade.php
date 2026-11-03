@extends('layout.app', ['page_name' => 'Create Recap'])

@section('content')
    <div class="container py-3">
        <div class="h5">Submit Recap</div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            @include('ambassador.recap.partials.recap-event-context', [
                'event' => $event,
                'recapType' => $recapType ?? null,
            ])

            {{-- OFF PREMISE HEADER --}}
            <h4 class="fw-bold text-uppercase mt-3">
                OFF-PREMISE EVENT: {{ $event->event_name ?? '' }}
            </h4>
            @php
                $eventStart = $event->start_time
                    ? \Carbon\Carbon::parse($event->start_time)->format('m/d/Y g:i A')
                    : '';
                $brandName = $event->brand->brand_name ?? '';
            @endphp
            @if ($eventStart)
                <p class="mb-1"><strong>Event Time:</strong> {{ $eventStart }}</p>
            @endif
            @if ($brandName)
                <p class="mb-1"><strong>Brand:</strong> {{ $brandName }}</p>
            @endif
            <hr>

            <form action="{{ route('ambassador.store-recap') }}" enctype="multipart/form-data" method="POST">
                @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">

                {{-- DESCRIBE TRAFFIC FLOW --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Describe Traffic Flow</h6>
                <hr class="mt-0 mb-2">
                <div class="mb-3">
                    <textarea name="traffic_flow" rows="3"
                        class="form-control shadow-none @error('traffic_flow') is-invalid @enderror"
                        placeholder="Describe the traffic flow...">{{ old('traffic_flow') }}</textarea>
                    @error('traffic_flow')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                {{-- DEMOGRAPHICS --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Demographics</h6>
                <hr class="mt-0 mb-2">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label text-uppercase fw-semibold small">Male (%)</label>
                        <select name="demographics_male" id="demographics_male"
                            class="form-select shadow-none @error('demographics_male') is-invalid @enderror">
                            <option value="">-- Select --</option>
                            @foreach ([0, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100] as $pct)
                                <option value="{{ $pct }}" @selected(old('demographics_male') !== null && old('demographics_male') !== '' && old('demographics_male') == $pct)>{{ $pct }}
                                </option>
                            @endforeach
                        </select>
                        @error('demographics_male')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-uppercase fw-semibold small">Female (%)</label>
                        <select id="demographics_female_display" disabled
                            class="form-select shadow-none @error('demographics_female') is-invalid @enderror">
                            <option value="">-- Select --</option>
                            @foreach ([0, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100] as $pct)
                                <option value="{{ $pct }}">{{ $pct }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="demographics_female" id="demographics_female"
                            value="{{ old('demographics_female') }}">
                        @error('demographics_female')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-uppercase fw-semibold small">Age Range</label>
                        <select name="demographics_age_range"
                            class="form-select shadow-none @error('demographics_age_range') is-invalid @enderror">
                            <option value="">-- Select --</option>
                            @foreach (['21-30', '30-50', '50-65', '65+'] as $range)
                                <option value="{{ $range }}" @selected(old('demographics_age_range') == $range)>{{ $range }}
                                </option>
                            @endforeach
                        </select>
                        @error('demographics_age_range')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase" for="consumer_interactions">Consumers interacted with / Attendance</label>
                        <input type="number" min="0" step="1" class="form-control" name="consumer_interactions" id="consumer_interactions" value="{{ old('consumer_interactions', $recap->consumer_interactions ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-uppercase fw-semibold small">Sampling Number</label>
                        <input type="number" name="sampling_number" min="0"
                            class="form-control shadow-none @error('sampling_number') is-invalid @enderror"
                            value="{{ old('sampling_number') }}">
                        @error('sampling_number')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- AMOUNT OF PRODUCT SOLD --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Amount of Product Sold</h6>
                <hr class="mt-0 mb-2">
                <div class="table-responsive mb-2">
                    <table class="table table-bordered align-middle" id="products-table">
                        <thead class="table-dark">
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Sold</th>
                                <th>Regular Price</th>
                                <th>Sale Price</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody">
                            {{-- rows injected by JS --}}
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-dark btn-sm mb-3" id="add-product-row">ADD</button>

                {{-- RECEIPT TOTAL --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Receipt Total <small class="fw-normal text-muted">(*Tax
                        Included)</small></h6>
                <hr class="mt-0 mb-2">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" name="receipt_total"
                                class="form-control shadow-none @error('receipt_total') is-invalid @enderror"
                                value="{{ old('receipt_total') }}">
                        </div>
                        @error('receipt_total')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                {{-- PARKING FEE --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Parking Fee</h6>
                <hr class="mt-0 mb-2">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" name="op_parking_fee"
                                class="form-control shadow-none @error('op_parking_fee') is-invalid @enderror"
                                value="{{ old('op_parking_fee', '0.00') }}">
                        </div>
                        @error('op_parking_fee')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- CHECKBOXES --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Demo Assessment</h6>
                <hr class="mt-0 mb-2">
                <div class="mb-3">
                    @php
                        $checkboxes = [
                            'store_aware_of_demo' => 'WAS THE STORE AWARE OF THE DEMO?',
                            'store_prepared_for_demo' => 'WAS THE STORE PREPARED FOR THE DEMO?',
                            'product_on_sale' => 'WAS THE PRODUCT ON SALE?',
                            'coupons_rebates_available' => 'WERE THERE COUPONS OR REBATES AVAILABLE?',
                            'enough_product_in_stock' => 'WAS THERE ENOUGH PRODUCT IN STOCK?',
                        ];
                    @endphp
                    @foreach ($checkboxes as $name => $label)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="{{ $name }}" value="1"
                                id="{{ $name }}" @checked(old($name))>
                            <label class="form-check-label text-uppercase"
                                for="{{ $name }}">{{ $label }}</label>
                        </div>
                    @endforeach

                    {{-- Case Display with inline count --}}
                    <div class="form-check mb-2 d-flex align-items-center gap-3">
                        <div>
                            <input class="form-check-input" type="checkbox" name="case_display" value="1"
                                id="case_display" @checked(old('case_display'))>
                            <label class="form-check-label text-uppercase" for="case_display">WAS THERE A CASE
                                DISPLAY?</label>
                        </div>
                        <div id="case-display-count-wrapper" class="{{ old('case_display') ? '' : 'd-none' }}">
                            <input type="text" name="case_display_count"
                                class="form-control form-control-sm shadow-none" style="width:200px"
                                placeholder="# of Cases in Display" value="{{ old('case_display_count') }}">
                        </div>
                    </div>
                </div>

                {{-- OBJECTIONS TO THE SALE --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Objections to the Sale?</h6>
                <hr class="mt-0 mb-2">
                <div class="mb-3">
                    <textarea name="objections_to_sale" rows="3"
                        class="form-control shadow-none @error('objections_to_sale') is-invalid @enderror"
                        placeholder="List any objections...">{{ old('objections_to_sale') }}</textarea>
                    @error('objections_to_sale')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                {{-- CONSUMER COMMENTS/SUGGESTIONS --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Consumer Comments / Suggestions</h6>
                <hr class="mt-0 mb-2">
                <div class="mb-3">
                    <textarea name="consumer_comments_suggestions" rows="3"
                        class="form-control shadow-none @error('consumer_comments_suggestions') is-invalid @enderror"
                        placeholder="Consumer comments and suggestions...">{{ old('consumer_comments_suggestions') }}</textarea>
                    @error('consumer_comments_suggestions')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ACCOUNT FEEDBACK --}}
                <h6 class="text-uppercase fw-bold mt-4 mb-1">Account Feedback <small
                        class="fw-normal text-muted">(Optional)</small></h6>
                <hr class="mt-0 mb-2">
                <div class="mb-3">
                    <textarea name="account_feedback" rows="3"
                        class="form-control shadow-none @error('account_feedback') is-invalid @enderror"
                        placeholder="Account feedback...">{{ old('account_feedback') }}</textarea>
                    @error('account_feedback')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                {{-- FILE UPLOADS --}}
                <div class="row g-3 mb-4">
                    {{-- Event Photos --}}
                    <div class="col-md-6">
                        <div class="bg-dark text-white text-uppercase fw-bold px-3 py-2 rounded-top">ATTACH PHOTOS</div>
                        <div class="border border-top-0 rounded-bottom p-3">
                            <input type="file" name="op_event_photos[]" multiple accept="image/*,.pdf"
                                class="form-control shadow-none mb-2 {{ $errors->has('op_event_photos') || $errors->has('op_event_photos.*') ? 'is-invalid' : '' }}"
                                id="op_event_photos">
                            <ul class="list-unstyled mb-0" id="photos-preview"></ul>
                            @error('op_event_photos')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                            @foreach ($errors->get('op_event_photos.*') as $messages)
                                @foreach ($messages as $message)
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    {{-- Event Receipts --}}
                    <div class="col-md-6">
                        <div class="bg-dark text-white text-uppercase fw-bold px-3 py-2 rounded-top">ATTACH ITEMIZED
                            RECEIPT</div>
                        <div class="border border-top-0 rounded-bottom p-3">
                            <input type="file" name="op_event_receipts[]" multiple
                                accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                class="form-control shadow-none mb-2 {{ $errors->has('op_event_receipts') || $errors->has('op_event_receipts.*') ? 'is-invalid' : '' }}"
                                id="op_event_receipts">
                            <ul class="list-unstyled mb-0" id="receipts-preview"></ul>
                            @error('op_event_receipts')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                            @foreach ($errors->get('op_event_receipts.*') as $messages)
                                @foreach ($messages as $message)
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- BUTTONS --}}
                @include('ambassador.recap.partials.quickbooks-requirements-card')
                @include('ambassador.recap.partials.expedited-pay', ['submitBtnId' => 'recapSubmitBtn'])
                <div class="d-flex gap-3 justify-content-end mt-3">
                    <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-secondary">SAVE AS
                        DRAFT</button>
                    <button type="submit" id="recapSubmitBtn" class="btn btn-dark"
                        {{ isset($quickbooksRequirements) && !($quickbooksRequirements['ready'] ?? true) ? 'disabled' : '' }}>SUBMIT
                        RECAP</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                var trafficFlow = document.querySelector('textarea[name="traffic_flow"]');
                trafficFlow.addEventListener('input', function() {
                    var selectionStart = this.selectionStart;
                    var selectionEnd = this.selectionEnd;
                    var formatted = this.value
                        .replace(/(^\s*|[.!?]\s+)([a-z])/gm, function(match, prefix, letter) {
                            return prefix + letter.toUpperCase();
                        })
                        .replace(/\bi\b/g, 'I');

                    if (formatted !== this.value) {
                        this.value = formatted;
                        this.setSelectionRange(selectionStart, selectionEnd);
                    }
                });

                var maleDemographics = document.getElementById('demographics_male');
                var femaleDemographics = document.getElementById('demographics_female');
                var femaleDemographicsDisplay = document.getElementById('demographics_female_display');

                function syncDemographics() {
                    var femaleValue = maleDemographics.value === '' ? '' : String(100 - Number(maleDemographics.value));
                    femaleDemographics.value = femaleValue;
                    femaleDemographicsDisplay.value = femaleValue;
                }

                maleDemographics.addEventListener('change', syncDemographics);
                syncDemographics();

                // ?? Products table ??????????????????????????????????????????????
                var tbody = document.getElementById('products-tbody');
                var rowIndex = 0;

                function addProductRow(data) {
                    data = data || {};
                    var i = rowIndex++;
                    var typeEntries = [{
                            value: '',
                            label: 'Select'
                        },
                        {
                            value: 'beer',
                            label: 'Beer (6 Packs)'
                        },
                        {
                            value: 'beer12',
                            label: 'Beer (12 Packs)'
                        },
                        {
                            value: 'rtd4',
                            label: 'RTD-4 pack'
                        },
                        {
                            value: 'rtd6',
                            label: 'RTD-6 pack'
                        },
                        {
                            value: 'rtd8',
                            label: 'RTD-8 pack'
                        },
                        {
                            value: 'rtd12',
                            label: 'RTD-12 pack'
                        },
                        {
                            value: 'thc4',
                            label: 'THC-4 pack'
                        },
                        {
                            value: 'thc6',
                            label: 'THC-6 pack'
                        },
                        {
                            value: 'thc8',
                            label: 'THC-8 pack'
                        },
                        {
                            value: 'thc12',
                            label: 'THC-12 pack'
                        },
                        {
                            value: 'thc750ml',
                            label: 'THC- 750ml'
                        },
                        {
                            value: 'thcShot',
                            label: 'THC- Shot'
                        },
                        {
                            value: 'spirit',
                            label: 'Spirit (Bottles)'
                        },
                        {
                            value: 'wine',
                            label: 'Wine (Bottles)'
                        },
                        {
                            value: 'food',
                            label: 'Food'
                        },
                        {
                            value: 'na beverage',
                            label: 'NA Beverage'
                        },
                        {
                            value: 'other',
                            label: 'Other'
                        },
                    ];
                    var typeOptions = typeEntries.map(function(t) {
                        return '<option value="' + t.value + '"' + (data.type === t.value ? ' selected' : '') +
                            '>' + t.label + '</option>';
                    }).join('');

                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td><select name="products_sold[' + i +
                        '][type]" class="form-select form-select-sm shadow-none">' + typeOptions + '</select></td>' +
                        '<td><input type="text" name="products_sold[' + i +
                        '][name]" class="form-control form-control-sm shadow-none" value="' + (data.name || '') +
                        '"></td>' +
                        '<td><input type="number" name="products_sold[' + i +
                        '][sold]" class="form-control form-control-sm shadow-none" min="0" value="' + (data.sold || '') +
                        '"></td>' +
                        '<td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" step="0.01" min="0" name="products_sold[' +
                        i + '][regular_price]" class="form-control shadow-none" value="' + (data.regular_price || '') +
                        '"></div></td>' +
                        '<td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" step="0.01" min="0" name="products_sold[' +
                        i + '][sale_price]" class="form-control shadow-none" value="' + (data.sale_price || '') +
                        '"></div></td>' +
                        '<td><button type="button" class="btn btn-danger btn-sm remove-row">REMOVE</button></td>';
                    tbody.appendChild(tr);
                    tr.querySelector('.remove-row').addEventListener('click', function() {
                        tr.remove();
                    });
                }

                document.getElementById('add-product-row').addEventListener('click', function() {
                    addProductRow();
                });

                // Seed with one empty row by default
                addProductRow();

                // ?? Case display toggle ?????????????????????????????????????????
                var caseCheck = document.getElementById('case_display');
                var caseWrapper = document.getElementById('case-display-count-wrapper');
                caseCheck.addEventListener('change', function() {
                    caseWrapper.classList.toggle('d-none', !this.checked);
                    if (!this.checked) caseWrapper.querySelector('input').value = '';
                });

                // ?? File preview helper ?????????????????????????????????????????
                // File preview helper (DataTransfer-based so files are actually submitted)
                function setupFilePreview(inputId, previewId) {
                    var input = document.getElementById(inputId);
                    var preview = document.getElementById(previewId);
                    var fileEntries = [];

                    function syncInput() {
                        var dt = new DataTransfer();
                        fileEntries.forEach(function(e) {
                            dt.items.add(e.file);
                        });
                        input.files = dt.files;
                    }

                    input.addEventListener('change', function() {
                        Array.from(this.files).forEach(function(file) {
                            var entry = {
                                file: file
                            };
                            var li = document.createElement('li');
                            li.className = 'd-flex align-items-center gap-2 mb-1 border rounded p-1';
                            entry.li = li;

                            var isImage = file.type.startsWith('image/');
                            var isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith(
                                '.pdf');

                            var nameSpan = document.createElement('span');
                            nameSpan.className = 'flex-grow-1 small text-truncate';
                            nameSpan.textContent = file.name;

                            var removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-danger btn-sm py-0';
                            removeBtn.textContent = 'Remove';
                            removeBtn.addEventListener('click', function() {
                                fileEntries = fileEntries.filter(function(e) {
                                    return e !== entry;
                                });
                                syncInput();
                                li.remove();
                            });

                            if (isImage) {
                                var thumb = document.createElement('img');
                                thumb.style.cssText =
                                    'width:40px;height:40px;object-fit:cover;border-radius:4px;';
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    thumb.src = e.target.result;
                                };
                                reader.readAsDataURL(file);
                                li.appendChild(thumb);
                            } else if (isPdf) {
                                var pdfIcon = document.createElement('span');
                                pdfIcon.className = 'badge bg-danger';
                                pdfIcon.style.cssText = 'width:40px;font-size:10px;';
                                pdfIcon.textContent = 'PDF';
                                li.appendChild(pdfIcon);
                            }
                            li.appendChild(nameSpan);
                            li.appendChild(removeBtn);
                            preview.appendChild(li);
                            fileEntries.push(entry);
                        });
                        syncInput();
                    });
                }

                setupFilePreview('op_event_photos', 'photos-preview');
                setupFilePreview('op_event_receipts', 'receipts-preview');
            })();
        </script>
    @endpush
    @include('ambassador.recap.partials.receipt-ocr-confirmation')
@endsection
