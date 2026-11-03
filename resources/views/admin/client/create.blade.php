@extends('layout.app', ['page_name' => 'Create Bill To'])
<link rel="stylesheet" href="{{asset('assets/select2/select2.css')}}">
@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && (int) $user->role_id === 6 ? 'manager' : 'admin';
        $similarCheckUrl = route($prefix . '.clients.check-similar-billings');
        $storeClientUrl = route($prefix . '.store.client');
        $nextQbIdUrl = route($prefix . '.clients.next-qb-customer-id');
        $quickbooksConnected = $quickbooksConnected ?? false;
        $connectQuickBooksUrl = $connectQuickBooksUrl ?? route($prefix . '.quickbooks.auth');
    @endphp
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5 text-uppercase">Bill To Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            @if (session('error'))
                <div class="alert alert-danger mx-3 mt-3 mb-0">{{ session('error') }}</div>
            @endif

            <form id="clientCreateForm" action="{{ $storeClientUrl }}" method="POST">@csrf
                <input type="hidden" name="billing_duplicate_acknowledged" id="billing_duplicate_acknowledged" value="0">
                <input type="hidden" name="quickbooks_customer_id" id="quickbooks_customer_id" value="{{ old('quickbooks_customer_id') }}">

                <div class="row p-3">
                    {{-- QuickBooks ID (when connected) --}}
                    @if ($quickbooksConnected)
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="qbCustomerIdDisplay" class="form-label text-uppercase">QuickBooks ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted" title="Auto-assigned, locked"><i class="fa-solid fa-lock"></i></span>
                                <input type="text"
                                    class="form-control shadow-none bg-light"
                                    id="qbCustomerIdDisplay"
                                    readonly
                                    disabled
                                    placeholder="Loading next ID…"
                                    value="{{ old('quickbooks_customer_id') }}">
                            </div>
                            <div id="qbIdLoading" class="small text-muted mt-1">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Fetching next QuickBooks customer ID…
                            </div>
                            <div id="qbIdError" class="small text-danger mt-1 d-none"></div>
                        </div>
                    </div>
                    @endif

                    {{-- Labor Rate Per Hour --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="defaultLaborAmount" class="form-label text-uppercase">Labor Rate Per Hour <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" required
                                class="form-control shadow-none @error('default_labor_amount') is-invalid @enderror"
                                name="default_labor_amount" id="defaultLaborAmount"
                                value="{{ old('default_labor_amount') }}" placeholder="0.00">
                            @error('default_labor_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Used when this Bill To is selected on events.</small>
                        </div>
                    </div>

                    {{-- Cup Charge --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="defaultCupsAmount" class="form-label text-uppercase">Cup Charge <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" required
                                class="form-control shadow-none @error('default_cups_amount') is-invalid @enderror"
                                name="default_cups_amount" id="defaultCupsAmount"
                                value="{{ old('default_cups_amount') }}" placeholder="0.00">
                            @error('default_cups_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Applied to the primary Bill To when price split is off.</small>
                        </div>
                    </div>

                    {{-- Receipt Percent --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="defaultReceiptPercent" class="form-label text-uppercase">Receipt Percent <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="number" step="1" min="0" max="100"
                                class="form-control shadow-none @error('default_receipt_percent') is-invalid @enderror"
                                name="default_receipt_percent" id="defaultReceiptPercent"
                                value="{{ old('default_receipt_percent') }}" placeholder="e.g. 10">
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
                            <input type="text" required class="form-control shadow-none @error('first_name') is-invalid @enderror" placeholder="Enter First Name" value="{{ old('first_name') }}" name="first_name" id="firstName">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Last Name --}}
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Last Name <span class="text-danger">*</span></label>
                            <input type="text" required class="form-control shadow-none @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" placeholder="Enter Last Name" name="last_name" id="lastName">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email <span class="text-danger">*</span></label>
                            <input type="email" required class="form-control shadow-none @error('email') is-invalid @enderror" name="email" id="email" value="{{ old('email') }}" placeholder="Enter Email Address">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- CC Emails --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">CC <span class="small fw-normal text-muted">(optional)</span></label>
                            <div id="ccEmailsContainer">
                                @foreach(old('cc_emails', []) as $cc)
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
                            <input type="text" required class="form-control shadow-none @error('phone') is-invalid @enderror" name="phone" id="phone" placeholder="Enter Phone Number" value="{{ old('phone') }}">
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
                                <select
                                    class="form-select shadow-none multiple-select2 @error('brand_id') is-invalid @enderror"
                                    id="selectBrands"
                                    name="brand_id[]"
                                    multiple="multiple"
                                    data-placeholder="Select Brand(s)">
                                    @if(!empty($brands))
                                        @foreach ($brands as $brand)
                                            <option
                                                value="{{ $brand->id }}"
                                                {{ (collect(old('brand_id'))->contains($brand->id)) ? 'selected' : '' }}
                                            >
                                                {{ $brand->brand_name }}
                                            </option>
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
                                <select
                                    class="form-select shadow-none multiple-select2 @error('distributor_id') is-invalid @enderror"
                                    id="selectDistributors"
                                    name="distributor_id[]"
                                    multiple="multiple"
                                    data-placeholder="Select Distributor(s)">
                                    @foreach ($distributors as $distributor)
                                        <option
                                            value="{{ $distributor->id }}"
                                            {{ (collect(old('distributor_id'))->contains($distributor->id)) ? 'selected' : '' }}
                                        >
                                            {{ $distributor->name }}
                                        </option>
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
                                value="{{ old('address') }}"
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
                            <input type="text" class="form-control shadow-none" id="city" name="city" value="{{ old('city') }}" placeholder="City">
                        </div>
                    </div>

                    {{-- State --}}
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="state" class="form-label text-uppercase">State</label>
                            <input type="text" class="form-control shadow-none" id="state" name="state" value="{{ old('state') }}" placeholder="State">
                        </div>
                    </div>

                    {{-- ZIP Code --}}
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="zip_code" class="form-label text-uppercase">ZIP Code</label>
                            <input type="text" class="form-control shadow-none" id="zip_code" name="zip_code" value="{{ old('zip_code') }}" placeholder="ZIP Code">
                        </div>
                    </div>

                    {{-- Company Name (optional) --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="companyName" class="form-label text-uppercase">Company Name <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="text" class="form-control shadow-none" name="company_name" id="companyName" value="{{ old('company_name') }}" placeholder="Enter Company Name">
                        </div>
                    </div>

                    {{-- Primary Billing Contact (optional) --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="primaryBillingContact" class="form-label text-uppercase">Primary Billing Contact <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="text" class="form-control shadow-none" name="primary_billing_contact" id="primaryBillingContact" value="{{ old('primary_billing_contact') }}" placeholder="Enter Primary Billing Contact">
                        </div>
                    </div>

                    {{-- Country (optional, default USA) --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="country" class="form-label text-uppercase">Country <span class="small fw-normal text-muted">(optional)</span></label>
                            <input type="text" class="form-control shadow-none" name="country" id="country" value="{{ old('country', 'USA') }}" placeholder="USA">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="col-6">
                      <div class="mb-3 position-relative">
                        <label for="password" class="form-label text-uppercase">
                          Password (Leave blank to keep unchanged)
                        </label>
                        <div class="input-group">
                          <input
                            type="password"
                            class="form-control shadow-none @error('password') is-invalid @enderror"
                            name="password"
                            id="password"
                            value="password"
                            placeholder="Enter Password"
                          >
                          <button
                            type="button"
                            class="btn-ds btn-secondary-outline"
                            id="togglePassword"
                            tabindex="-1"
                          >
                            <i class="fa-solid fa-eye-slash" id="toggleIcon"></i>
                          </button>
                        </div>
                        @error('password')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-info">Default password is <strong>password</strong></small>
                      </div>
                    </div>

                    {{-- Status + Contract --}}
                    <div class="col-6 mb-3">
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="form-check m-2">
                                <input
                                    class="form-check-input shadow-none"
                                    name="status"
                                    type="radio"
                                    value="1"
                                    id="active"
                                    {{ old('status', '1') == '1' ? 'checked' : '' }}
                                >
                                <label class="form-check-label text-uppercase" for="active">Active</label>
                            </div>

                            <div class="form-check m-2">
                                <input
                                    class="form-check-input shadow-none"
                                    name="status"
                                    type="radio"
                                    value="0"
                                    id="inactive"
                                    {{ old('status') == '0' ? 'checked' : '' }}
                                >
                                <label class="form-check-label text-uppercase" for="inactive">Inactive</label>
                            </div>

                            <div class="form-check m-2">
                                <input
                                    class="form-check-input shadow-none"
                                    name="contract_recieved"
                                    type="checkbox"
                                    value="1"
                                    id="contractReceived"
                                    {{ old('contract_recieved') ? 'checked' : '' }}
                                >
                                <label class="form-check-label text-uppercase" for="contractReceived">
                                    Contract Received
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Notes (optional) --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="notes" class="form-label text-uppercase">Notes <span class="small fw-normal text-muted">(optional)</span></label>
                            <textarea class="form-control shadow-none" name="notes" id="notes" rows="3" placeholder="Enter any notes…">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-row gap-3 px-3 pb-3">
                    <button type="submit" class="btn-ds btn-primary-ds" id="clientSaveBtn" @if($quickbooksConnected) disabled @endif>Save</button>
                    <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

                // Set the street address (number + route only)
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
        (function() {
            const quickbooksConnected = @json($quickbooksConnected);
            const nextQbIdUrl = @json($nextQbIdUrl);
            const qbHidden = document.getElementById('quickbooks_customer_id');
            const qbDisplay = document.getElementById('qbCustomerIdDisplay');
            const qbLoading = document.getElementById('qbIdLoading');
            const qbError = document.getElementById('qbIdError');
            const saveBtn = document.getElementById('clientSaveBtn');

            function setQbIdReady(nextId) {
                if (qbHidden) qbHidden.value = nextId;
                if (qbDisplay) qbDisplay.value = nextId;
                if (qbLoading) qbLoading.classList.add('d-none');
                if (qbError) qbError.classList.add('d-none');
                if (saveBtn) saveBtn.disabled = false;
            }

            function setQbIdFailed(message) {
                if (qbDisplay) { qbDisplay.value = ''; qbDisplay.placeholder = 'Unable to load ID'; }
                if (qbLoading) qbLoading.classList.add('d-none');
                if (qbError) { qbError.textContent = message || 'Failed to fetch QuickBooks customer ID.'; qbError.classList.remove('d-none'); }
                if (saveBtn) saveBtn.disabled = true;
            }

            function loadNextQbId() {
                if (!nextQbIdUrl || typeof jQuery === 'undefined') return;
                if (saveBtn) saveBtn.disabled = true;
                if (qbLoading) qbLoading.classList.remove('d-none');
                jQuery.ajax({
                    url: nextQbIdUrl,
                    method: 'GET',
                    success: function(res) {
                        if (res && res.success && res.next_id) {
                            setQbIdReady(res.next_id);
                        } else {
                            setQbIdFailed((res && res.message) ? res.message : null);
                        }
                    },
                    error: function(xhr) {
                        setQbIdFailed(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : null);
                    }
                });
            }

            if (document.getElementById('clientCreateForm') && quickbooksConnected) {
                loadNextQbId();
            }
        })();
    </script>
    <script>
        (function() {
            const quickbooksConnected = @json($quickbooksConnected);
            const similarCheckUrl = @json($similarCheckUrl);
            const form = document.getElementById('clientCreateForm');
            if (!form || typeof Swal === 'undefined' || typeof jQuery === 'undefined') return;

            form.addEventListener('submit', function(e) {
                if (quickbooksConnected) {
                    const qbHidden = document.getElementById('quickbooks_customer_id');
                    if (!qbHidden || !qbHidden.value) {
                        e.preventDefault();
                        Swal.fire('QuickBooks ID required', 'Wait for the QuickBooks customer ID to load, or refresh the page.', 'warning');
                        return;
                    }
                }
                const ack = document.getElementById('billing_duplicate_acknowledged');
                if (ack && ack.value === '1') return;
                e.preventDefault();

                const token = form.querySelector('[name="_token"]')?.value || '';
                jQuery.ajax({
                    url: similarCheckUrl,
                    method: 'POST',
                    data: {
                        _token: token,
                        first_name: form.querySelector('[name="first_name"]')?.value || '',
                        last_name: form.querySelector('[name="last_name"]')?.value || '',
                        email: form.querySelector('[name="email"]')?.value || '',
                        phone: form.querySelector('[name="phone"]')?.value || '',
                        address: form.querySelector('[name="address"]')?.value || '',
                    },
                    success: function(res) {
                        if (!res.has_matches || !res.matches || !res.matches.length) {
                            if (ack) ack.value = '1';
                            form.submit();
                            return;
                        }
                        let rows = res.matches.map(function(m) {
                            const qb = m.quickbooks_customer_id ? ('QB ID: ' + m.quickbooks_customer_id) : 'No QB ID';
                            const badge = m.status === 'active' ?
                                '<span class="badge bg-success">Active</span>' :
                                '<span class="badge bg-secondary">Inactive</span>';
                            const reasons = (m.reasons || []).join(', ');
                            return '<tr><td class="text-start"><strong>' + (m.name || '') + '</strong><br><small class="text-muted">' +
                                (m.email || '') + '</small></td><td>' + badge + '</td><td class="small">' + qb + '</td><td class="small text-muted">' +
                                reasons + '</td></tr>';
                        }).join('');
                        const html = '<p class="text-start mb-2">These existing bill-to records look very similar. Continue only if you are sure this is a new billing entity.</p>' +
                            '<div class="table-responsive"><table class="table table-sm table-bordered text-start"><thead><tr><th>Name / Email</th><th>Status</th><th>QuickBooks</th><th>Why similar</th></tr></thead><tbody>' +
                            rows + '</tbody></table></div>';

                        Swal.fire({
                            title: 'Similar billings on file',
                            html: html,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Add anyway',
                            cancelButtonText: 'Cancel',
                            width: '42rem',
                        }).then(function(result) {
                            if (result.isConfirmed && ack) {
                                ack.value = '1';
                                form.submit();
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'Could not check for similar billings. You can try again or submit without the check.', 'error');
                    }
                });
            });
        })();
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
