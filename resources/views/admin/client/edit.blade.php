@extends('layout.app', ['page_name' => 'Edit Bill To'])
<link rel="stylesheet" href="{{asset('assets/select2/select2.css')}}">
@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5 text-uppercase">Edit Bill To</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{ $updateClientRoute ?? route((request()->routeIs('manager.*') ? 'manager' : 'admin').'.update.client') }}" method="POST">@csrf
                <input type="hidden" name="id" value="{{$client->id}}">
                <div class="row p-3">

                    {{-- QuickBooks ID (always shown, locked) --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="qbCustomerIdDisplay" class="form-label text-uppercase">QuickBooks ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted" title="Assigned in QuickBooks, locked"><i class="fa-solid fa-lock"></i></span>
                                <input type="text"
                                    class="form-control shadow-none bg-light"
                                    id="qbCustomerIdDisplay"
                                    readonly
                                    disabled
                                    value="{{ $client->quickbooks_customer_id ?? 'Not assigned' }}">
                            </div>
                        </div>
                    </div>

                    {{-- Labor Rate Per Hour --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="defaultLaborAmount" class="form-label text-uppercase">Labor Rate Per Hour <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" required
                                class="form-control shadow-none @error('default_labor_amount') is-invalid @enderror"
                                name="default_labor_amount" id="defaultLaborAmount"
                                value="{{ old('default_labor_amount', $client->clientProfile?->default_labor_amount) }}" placeholder="0.00">
                            @error('default_labor_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Cup Charge --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="defaultCupsAmount" class="form-label text-uppercase">Cup Charge <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" required
                                class="form-control shadow-none @error('default_cups_amount') is-invalid @enderror"
                                name="default_cups_amount" id="defaultCupsAmount"
                                value="{{ old('default_cups_amount', $client->clientProfile?->default_cups_amount) }}" placeholder="0.00">
                            @error('default_cups_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Receipt Percent --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="defaultReceiptPercent" class="form-label text-uppercase">Receipt Percent <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="number" step="1" min="0" max="100"
                                class="form-control shadow-none @error('default_receipt_percent') is-invalid @enderror"
                                name="default_receipt_percent" id="defaultReceiptPercent"
                                value="{{ old('default_receipt_percent', $client->clientProfile?->default_receipt_percent) }}" placeholder="e.g. 10">
                            @error('default_receipt_percent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Default receipt reimbursement percentage for this Bill To.</small>
                        </div>
                    </div>

                    {{-- First Name --}}
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">First Name <span class="text-danger">*</span></label>
                            <input type="text" required class="form-control shadow-none @error('first_name') is-invalid @enderror" value="{{ old('first_name', $client->first_name) }}" name="first_name" id="firstName">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Last Name --}}
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Last Name <span class="text-danger">*</span></label>
                            <input type="text" required class="form-control shadow-none @error('last_name') is-invalid @enderror" value="{{ old('last_name', $client->last_name) }}" name="last_name" id="lastName">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Email (read-only on edit) --}}
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email <span class="text-danger">*</span></label>
                            <input type="email" readonly disabled class="form-control shadow-none @error('email') is-invalid @enderror" value="{{ $client->email }}" id="email">
                        </div>
                    </div>

                    {{-- CC Emails --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">CC <span class="small fw-normal text-muted">(optional)</span></label>
                            <div id="ccEmailsContainer">
                                @foreach(old('cc_emails', $client->clientProfile?->cc_emails ?? []) as $cc)
                                    <div class="input-group mb-2 cc-email-row">
                                        <input type="email" class="form-control shadow-none" name="cc_emails[]" value="{{ $cc }}" placeholder="CC email address">
                                        <button type="button" class="btn btn-outline-danger cc-remove-btn"><i class="fa-solid fa-times"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="addCcEmail">
                                <i class="fa-solid fa-plus me-1"></i>Add CC
                            </button>
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label text-uppercase">Phone <span class="text-danger">*</span></label>
                            <input type="text" required class="form-control shadow-none @error('phone') is-invalid @enderror" value="{{ old('phone', $client->phone) }}" name="phone" id="phone">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Brands --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectBrands" class="form-label text-uppercase">Brands <span class="text-danger">*</span></label>
                            <div class="input-group mb-3">
                                <select class="form-select shadow-none multiple-select2 @error('brand_id') is-invalid @enderror" id="selectBrands" name="brand_id[]" multiple="multiple" data-placeholder="Select Brand">
                                    @php
                                        $selectedBrandIds = old('brand_id',
                                            $client->clientProfile
                                                ? $client->clientProfile->brands->pluck('id')->map(fn($id) => (int)$id)->all()
                                                : []
                                        );
                                    @endphp
                                    @if(!empty($brands))
                                        @foreach ($brands as $brand)
                                            <option value="{{$brand->id}}" @if(in_array((int)$brand->id, (array)$selectedBrandIds)) selected @endif>{{$brand->brand_name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            @error('brand_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Distributors --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectDistributors" class="form-label text-uppercase">Distributors <span class="text-danger">*</span></label>
                            <div class="input-group mb-3">
                                <select class="form-select shadow-none multiple-select2 @error('distributor_id') is-invalid @enderror" id="selectDistributors" name="distributor_id[]" multiple="multiple" data-placeholder="Select Distributors">
                                    @php
                                        $selectedDistributorIds = old('distributor_id',
                                            $client->clientProfile
                                                ? $client->clientProfile->distributors->pluck('id')->map(fn($id) => (int)$id)->all()
                                                : []
                                        );
                                    @endphp
                                    @foreach ($distributors as $distributor)
                                        <option value="{{$distributor->id}}" @if(in_array((int)$distributor->id, (array)$selectedDistributorIds)) selected @endif>{{$distributor->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('distributor_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Address (autocomplete) --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="address" class="form-label text-uppercase">Address <span class="text-danger">*</span></label>
                            <input
                                class="form-control shadow-none @error('address') is-invalid @enderror"
                                id="address"
                                name="address"
                                value="{{ old('address', $client->clientProfile?->address) }}"
                                placeholder="Start typing an address…"
                                autocomplete="off"
                                required
                            >
                            @error('address')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- City --}}
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">City</label>
                            <input type="text" class="form-control shadow-none" id="city" name="city" value="{{ old('city', $client->clientProfile?->city) }}" placeholder="City">
                        </div>
                    </div>

                    {{-- State --}}
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="state" class="form-label text-uppercase">State</label>
                            <input type="text" class="form-control shadow-none" id="state" name="state" value="{{ old('state', $client->clientProfile?->state) }}" placeholder="State">
                        </div>
                    </div>

                    {{-- ZIP Code --}}
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="zip_code" class="form-label text-uppercase">ZIP Code</label>
                            <input type="text" class="form-control shadow-none" id="zip_code" name="zip_code" value="{{ old('zip_code', $client->clientProfile?->zip_code) }}" placeholder="ZIP Code">
                        </div>
                    </div>

                    {{-- Company Name (optional) --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="companyName" class="form-label text-uppercase">Company Name <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="text" class="form-control shadow-none" name="company_name" id="companyName" value="{{ old('company_name', $client->clientProfile?->company_name) }}" placeholder="Enter Company Name">
                        </div>
                    </div>

                    {{-- Primary Billing Contact (optional) --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="primaryBillingContact" class="form-label text-uppercase">Primary Billing Contact <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="text" class="form-control shadow-none" name="primary_billing_contact" id="primaryBillingContact" value="{{ old('primary_billing_contact', $client->clientProfile?->primary_billing_contact) }}" placeholder="Enter Primary Billing Contact">
                        </div>
                    </div>

                    {{-- Country (optional) --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="country" class="form-label text-uppercase">Country <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="text" class="form-control shadow-none" name="country" id="country" value="{{ old('country', $client->clientProfile?->country ?? 'USA') }}" placeholder="USA">
                        </div>
                    </div>

                    {{-- Status + Contract --}}
                    <div class="col-6 mb-3">
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="1" @if($client->status=='1') checked @endif id="active">
                                <label class="form-check-label text-uppercase" for="active">Active</label>
                            </div>

                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="0" @if($client->status=='0') checked @endif id="inactive">
                                <label class="form-check-label text-uppercase" for="inactive">Inactive</label>
                            </div>

                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="contract_recieved" type="checkbox" @if($client->clientProfile?->contract_recieved=='1') checked @endif value="1" id="contractReceived">
                                <label class="form-check-label text-uppercase" for="contractReceived">Contract Received</label>
                            </div>
                        </div>
                    </div>

                    {{-- Notes (optional) --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="notes" class="form-label text-uppercase">Notes <span class="small fw-normal text-muted">(optional)</span></label>
                            <textarea class="form-control shadow-none" name="notes" id="notes" rows="3" placeholder="Enter any notes…">{{ old('notes', $client->clientProfile?->notes) }}</textarea>
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="col-6">
                        <div class="mb-3 position-relative">
                            <label for="password" class="form-label text-uppercase">Password (Leave blank to keep unchanged)</label>
                            <div class="input-group">
                                <input type="password" class="form-control shadow-none @error('password') is-invalid @enderror" name="password" id="password" placeholder="Enter new password">
                                <button type="button" class="btn-ds btn-secondary-outline" id="togglePassword" tabindex="-1">
                                    <i class="fa-solid fa-eye-slash" id="toggleIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Default Buyback --}}
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Default buyback ($) <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="number" step="0.01" min="0" class="form-control shadow-none" name="default_buyback_amount"
                                value="{{ old('default_buyback_amount', $client->clientProfile?->default_buyback_amount) }}" placeholder="0">
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <div class="h6 text-uppercase text-muted">Program / budget tracking <span class="small fw-normal">(optional)</span></div>
                        <p class="small text-muted mb-3">Limits apply to <strong>Completed</strong> events only. Billable total = labor + buyback + cups (same as invoicing).</p>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Program / allocation name</label>
                            <input type="text" class="form-control shadow-none" name="budget_program_name"
                                value="{{ old('budget_program_name', $client->clientProfile?->budget_program_name) }}" placeholder="e.g. 2025 Spring Promo">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Max budget ($)</label>
                            <input type="number" step="0.01" min="0" class="form-control shadow-none" name="budget_max_amount"
                                value="{{ old('budget_max_amount', $client->clientProfile?->budget_max_amount) }}" placeholder="Leave blank for no cap">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Max completed events</label>
                            <input type="number" min="0" class="form-control shadow-none" name="budget_max_events"
                                value="{{ old('budget_max_events', $client->clientProfile?->budget_max_events) }}" placeholder="Allocations">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Warning at %</label>
                            <input type="number" min="1" max="100" class="form-control shadow-none" name="budget_warn_percent"
                                value="{{ old('budget_warn_percent', $client->clientProfile?->budget_warn_percent ?? 80) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Budget alert email</label>
                            <input type="email" class="form-control shadow-none" name="budget_alert_email"
                                value="{{ old('budget_alert_email', $client->clientProfile?->budget_alert_email) }}" placeholder="Optional override">
                        </div>
                    </div>
                    <div class="col-md-6 d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="budget_enforce" value="1" id="budget_enforce"
                                @checked(old('budget_enforce', $client->clientProfile?->budget_enforce))>
                            <label class="form-check-label" for="budget_enforce">Enforce limits (block or require approval)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="budget_require_approval" value="1" id="budget_require_approval"
                                @checked(old('budget_require_approval', $client->clientProfile?->budget_require_approval))>
                            <label class="form-check-label" for="budget_require_approval">Require management approval when over limit</label>
                        </div>
                    </div>

                </div>
                <div class="col-2 px-3 pb-3">
                    <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                    <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>
    <script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapsPublicKey() }}&libraries=places&callback=initAutocomplete" async defer></script>
    <script>
        function initAutocomplete() {
            const input = document.getElementById('address');
            const autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['geocode'],
                componentRestrictions: { country: 'us' }
            });

            autocomplete.addListener('place_changed', function () {
                const place = autocomplete.getPlace();
                if (!place.geometry) return;

                const streetNumber = getComponent(place, 'street_number', 'short_name');
                const route = getComponent(place, 'route', 'long_name');
                input.value = [streetNumber, route].filter(Boolean).join(' ') || place.formatted_address;

                document.getElementById('city').value = getComponent(place, 'locality', 'long_name')
                    || getComponent(place, 'sublocality_level_1', 'long_name') || '';
                document.getElementById('state').value = getComponent(place, 'administrative_area_level_1', 'short_name') || '';
                document.getElementById('zip_code').value = getComponent(place, 'postal_code', 'short_name') || '';
            });
        }

        function getComponent(place, type, nameType) {
            const comp = (place.address_components || []).find(c => c.types.includes(type));
            return comp ? comp[nameType] : '';
        }
    </script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        });
    </script>
    <script>
        (function () {
            const container = document.getElementById('ccEmailsContainer');
            document.getElementById('addCcEmail').addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'input-group mb-2 cc-email-row';
                row.innerHTML = '<input type="email" class="form-control shadow-none" name="cc_emails[]" placeholder="CC email address" autofocus>' +
                    '<button type="button" class="btn btn-outline-danger cc-remove-btn"><i class="fa-solid fa-times"></i></button>';
                container.appendChild(row);
                row.querySelector('input').focus();
            });
            container.addEventListener('click', function (e) {
                if (e.target.closest('.cc-remove-btn')) {
                    e.target.closest('.cc-email-row').remove();
                }
            });
        })();
    </script>
@endsection
