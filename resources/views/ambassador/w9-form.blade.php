@extends('layout.app', ['page_name' => 'W-9 Form'])

@section('content')
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">W-9 Form - Request for Taxpayer Identification Number</h4>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @if ($w9Form)
                            <div class="alert alert-info">
                                <strong>W-9 Form Submitted</strong><br>
                                Submitted on: {{ $w9Form->signature_date->format('M d, Y') }}<br>
                                Name: {{ $w9Form->name }}<br>
                                QuickBooks Vendor ID: {{ $w9Form->quickbooks_vendor_id ?? 'Pending' }}
                            </div>
                        @else
                            <form action="{{ route('ambassador.w9.store') }}" method="POST">
                                @csrf

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Name (as shown on your income tax return)</label>
                                        <input type="text" name="name"
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name', Auth::user()->name) }}" required
                                            placeholder="Must match your profile: {{ trim(Auth::user()->last_name . ', ' . Auth::user()->first_name) }}">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">For individuals: Enter exactly as on your tax return. In
                                            QuickBooks we use: Last Name, First Name</small>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Federal Tax Classification</label>
                                        <select name="tax_classification"
                                            class="form-control @error('tax_classification') is-invalid @enderror" required>
                                            <option value="">Select Classification</option>
                                            <option value="individual">Individual/Sole Proprietor</option>
                                            <option value="c_corp">C Corporation</option>
                                            <option value="s_corp">S Corporation</option>
                                            <option value="partnership">Partnership</option>
                                            <option value="trust">Trust/Estate</option>
                                            <option value="llc">Limited Liability Company</option>
                                            <option value="other">Other</option>
                                        </select>
                                        @error('tax_classification')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12 mb-3" id="llc_classification_div" style="display:none;">
                                        <label class="form-label">LLC Tax Classification</label>
                                        <input type="text" name="llc_tax_classification" class="form-control"
                                            placeholder="Enter C=C Corp, S=S Corp, P=Partnership">
                                    </div>

                                    <div class="col-md-12 mb-3" id="other_classification_div" style="display:none;">
                                        <label class="form-label">Other Tax Classification</label>
                                        <input type="text" name="other_tax_classification" class="form-control">
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="address"
                                            class="form-control @error('address') is-invalid @enderror"
                                            value="{{ old('address', Auth::user()->address) }}" required>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city"
                                            class="form-control @error('city') is-invalid @enderror"
                                            value="{{ old('city', Auth::user()->city) }}" required>
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">State</label>
                                        <input type="text" name="state"
                                            class="form-control @error('state') is-invalid @enderror"
                                            value="{{ old('state', Auth::user()->state) }}" required>
                                        @error('state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">ZIP Code</label>
                                        <input type="text" name="zip_code"
                                            class="form-control @error('zip_code') is-invalid @enderror"
                                            value="{{ old('zip_code', Auth::user()->zip_code) }}" required>
                                        @error('zip_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Taxpayer Identification Number <span
                                                class="text-danger">*</span></label>
                                        <div class="alert alert-info py-2 px-3 mb-2 small">
                                            <strong>Note:</strong> Brand Ambassadors serve as independent contractors.
                                            Please provide your Social Security Number (SSN) unless you operate under a
                                            business entity and have an Employer Identification Number (EIN).
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_ein" value="0"
                                                id="ssn_radio" checked>
                                            <label class="form-check-label" for="ssn_radio">Social Security Number
                                                (SSN)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="is_ein" value="1"
                                                id="ein_radio">
                                            <label class="form-check-label" for="ein_radio">Employer Identification Number
                                                (EIN)</label>
                                        </div>
                                        <input type="text" name="ssn_ein" id="ssn_ein_input"
                                            class="form-control mt-2 @error('ssn_ein') is-invalid @enderror"
                                            placeholder="Enter 9 digits" maxlength="9" pattern="[0-9]{9}" required>
                                        @error('ssn_ein')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        {{-- <small class="text-danger d-block mt-1"><strong>Required for 1099 tracking in
                                                QuickBooks</strong></small> --}}
                                        <small class="text-muted d-block">Enter exactly 9 digits (SSN or EIN)</small>
                                    </div>

                                    <div class="col-md-12 mb-3" id="business_name_ein_div" style="display:none;">
                                        <label class="form-label">Business / Company Name (if using EIN) <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="business_name" id="business_name_ein_input"
                                            class="form-control @error('business_name') is-invalid @enderror"
                                            value="{{ old('business_name') }}"
                                            placeholder="Legal business name for 1099">
                                        @error('business_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Required when using EIN - your legal business/company
                                            name</small>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Signature</label>
                                        <input type="text" name="signature"
                                            class="form-control @error('signature') is-invalid @enderror"
                                            placeholder="Type your full name" required>
                                        @error('signature')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">By typing your name, you certify that the information
                                            provided is correct.</small>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="alert alert-warning">
                                            <strong>Certification:</strong> Under penalties of perjury, I certify that:
                                            <ol>
                                                <li>The number shown on this form is my correct taxpayer identification
                                                    number, and</li>
                                                <li>I am not subject to backup withholding, and</li>
                                                <li>I am a U.S. citizen or other U.S. person.</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary">Submit W-9 Form</button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const taxClassification = document.querySelector('select[name="tax_classification"]');
            const llcDiv = document.getElementById('llc_classification_div');
            const otherDiv = document.getElementById('other_classification_div');
            const ssnEinInput = document.getElementById('ssn_ein_input');

            const einRadio = document.getElementById('ein_radio');
            const businessNameEinDiv = document.getElementById('business_name_ein_div');
            const businessNameEinInput = document.getElementById('business_name_ein_input');
            if (einRadio && businessNameEinInput) {
                function updateBusinessNameEinRequired() {
                    var useEin = document.querySelector('input[name="is_ein"]:checked');
                    var isEin = useEin && useEin.value === '1';
                    businessNameEinDiv.style.display = isEin ? 'block' : 'none';
                    businessNameEinInput.required = isEin;
                }
                document.querySelectorAll('input[name="is_ein"]').forEach(function(radio) {
                    radio.addEventListener('change', updateBusinessNameEinRequired);
                });
                updateBusinessNameEinRequired();
            }

            taxClassification.addEventListener('change', function() {
                llcDiv.style.display = this.value === 'llc' ? 'block' : 'none';
                otherDiv.style.display = this.value === 'other' ? 'block' : 'none';
            });

            // Restrict to numbers only
            ssnEinInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);
            });
        });
    </script>
@endsection
