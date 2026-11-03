@extends('layout.app', ['page_name' => 'Invoices'])
@push('styles')
    <style>
        input[readonly] {
            background-color: #e9ecef !important;
            cursor: not-allowed;
        }
    </style>
@endpush
@section('content')
    <div class="container">
        <div class="page-card-design">
            @php
                $user = auth()->user();
                $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
            @endphp
            @if (!$isConnected)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> {{ $message ?? 'QuickBooks is not connected.' }}
                    <a href="{{ route($prefix.'.quickbooks.auth') }}" class="btn-ds btn-primary-ds btn-sm ms-2">Connect QuickBooks</a>
                </div>
            @else
                {{-- Add your invoice creation form here --}}
                <form method="POST" action="{{ route($prefix . '.invoice.store') }}" enctype="multipart/form-data" id="invoiceForm">
                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <!-- Invoice Details Section -->
                    <div class="mb-4 border-bottom pb-4">
                        <h2 class="h4 mb-3">Invoice Details</h2>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="event_id" class="form-label">Reference Event</label>
                                <input type="text" value="{{ $event->event_name }}" readonly name="event_name"
                                    id="event_name" class="form-control">
                                <input type="hidden" name="event_id" value="{{ $event->id }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="doc_number" class="form-label">Invoice Number</label>
                                <input type="text" name="doc_number" id="doc_number" class="form-control" readonly
                                    placeholder="Auto-generated" value="{{ old('doc_number') }}">
                            </div>
                            <!-- More Field: Terms -->
                            <div class="col-md-4 mb-3">
                                <label for="terms" class="form-label">Terms</label>
                                <select name="terms" id="terms" class="form-select">
                                    <option value="Due on Receipt" {{ old('terms') == 'Due on Receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                    <option value="Net 10" {{ old('terms', 'Net 10') == 'Net 10' ? 'selected' : '' }}>Net 10</option>
                                    <option value="Net 15" {{ old('terms') == 'Net 15' ? 'selected' : '' }}>Net 15</option>
                                    <option value="Net 30" {{ old('terms') == 'Net 30' ? 'selected' : '' }}>Net 30</option>
                                    <option value="Net 60" {{ old('terms') == 'Net 60' ? 'selected' : '' }}>Net 60</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Draft" {{ old('status', 'Draft') == 'Draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="Sent" {{ old('status') == 'Sent' ? 'selected' : '' }}>Sent</option>
                                    <option value="Paid" {{ old('status') == 'Paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="Overdue" {{ old('status') == 'Overdue' ? 'selected' : '' }}>Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="currency_code" class="form-label">Currency</label>
                                <select name="currency_code" id="currency_code" class="form-select">
                                    <option value="USD" {{ old('currency_code', 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                                    <option value="EUR" {{ old('currency_code') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    <option value="GBP" {{ old('currency_code') == 'GBP' ? 'selected' : '' }}>GBP</option>
                                    <!-- Add more currencies as needed -->
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="invoice_date" class="form-label">Invoice Date</label>
                                <input type="date" name="invoice_date" id="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}"
                                    class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" name="due_date" id="due_date"
                                    value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" class="form-control">
                            </div>
                            <!-- Additional Field: P.O. Number -->
                            <div class="col-md-6 mb-3">
                                <label for="po_number" class="form-label">P.O. Number</label>
                                <input type="text" name="po_number" id="po_number" class="form-control"
                                    placeholder="Purchase Order Number" value="{{ old('po_number') }}">
                            </div>
                        </div>
                    </div>
                    @if (!empty($multipleBillingParties) && $multipleBillingParties)
                    <div class="mb-4 border-bottom pb-4 p-3 bg-light rounded">
                        <h2 class="h5 mb-3">Transfer to QuickBooks (split across billing parties)</h2>
                        <p class="small text-muted">Labor uses each billing party's configured rate. Buyback is split evenly. Cups go to the primary billing party.</p>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Labor total ($)</label>
                                <input type="number" step="0.01" min="0" name="labor_amount" class="form-control form-control-sm"
                                    value="{{ old('labor_amount', $event->labor_amount) }}" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Buyback total ($)</label>
                                <input type="number" step="0.01" min="0" name="buyback_amount" class="form-control form-control-sm"
                                    value="{{ old('buyback_amount', $event->buyback_amount) }}" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Cups total ($)</label>
                                <input type="number" step="0.01" min="0" name="cups_amount" class="form-control form-control-sm"
                                    value="{{ old('cups_amount', $event->cups_amount) }}" placeholder="0">
                            </div>
                            {{-- Cups always go to the primary billing party — no manual selection needed. --}}
                            <input type="hidden" name="cups_allocation" value="primary">
                            <div class="col-md-3">
                                <label class="form-label small">Invoice date</label>
                                <input type="date" name="invoice_date" class="form-control form-control-sm"
                                    value="{{ old('invoice_date', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Due date</label>
                                <input type="date" name="due_date" class="form-control form-control-sm"
                                    value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button
                                    type="submit"
                                    class="btn-ds btn-primary-ds btn-sm"
                                    formmethod="POST"
                                    formaction="{{ route($prefix . '.event.transfer-to-quickbooks', $event) }}"
                                >
                                    Create split invoices in QuickBooks
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                    <!-- Bill To (Customer) Section -->
                    <div class="mb-4 border-bottom pb-4">
                        <h2 class="h4 mb-3">Bill To</h2>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="customer_name" class="form-label">Customer / Client</label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control"
                                    value="{{ old('customer_name', $event->client->name) }}">
                            </div>
                            <!-- Additional Field: Customer Email -->
                            <div class="col-md-4 mb-3">
                                <label for="customer_email" class="form-label">Customer Email</label>
                                <input type="email" name="customer_email" id="customer_email" class="form-control"
                                    placeholder="email@example.com" value="{{ old('customer_email', $event->client->email) }}">
                            </div>
                            <!-- More Field: Phone Number -->
                            <div class="col-md-4 mb-3">
                                <label for="customer_phone" class="form-label">Customer Phone</label>
                                <input type="tel" name="customer_phone" id="customer_phone" class="form-control"
                                    placeholder="+1 (123) 456-7890" value="{{ old('customer_phone', $event->client->phone) }}">
                            </div>
                            <!-- Billing Address (JSON structure) -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Billing Address</label>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <input type="text" name="billing_address[street]" placeholder="Street"
                                            class="form-control" value="{{ old('billing_address.street') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" name="billing_address[city]" placeholder="City"
                                            class="form-control" value="{{ old('billing_address.city') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" name="billing_address[state]" placeholder="State"
                                            class="form-control" value="{{ old('billing_address.state') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" name="billing_address[zip]" placeholder="ZIP"
                                            class="form-control" value="{{ old('billing_address.zip') }}">
                                    </div>
                                    <div class="col-12 mb-2">
                                        <input type="text" name="billing_address[country]" placeholder="Country"
                                            class="form-control" value="{{ old('billing_address.country') }}">
                                    </div>
                                </div>
                            </div>
                            <!-- Shipping Address -->
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="same_as_billing" id="same_as_billing" value="1"
                                        class="form-check-input" {{ old('same_as_billing') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="same_as_billing">Shipping Address same as
                                        Billing</label>
                                </div>
                                <div id="shipping-address" class="row mt-2">
                                    <div class="col-md-6 mb-2">
                                        <input type="text" name="shipping_address[street]" placeholder="Street"
                                            class="form-control" value="{{ old('shipping_address.street') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" name="shipping_address[city]" placeholder="City"
                                            class="form-control" value="{{ old('shipping_address.city') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" name="shipping_address[state]" placeholder="State"
                                            class="form-control" value="{{ old('shipping_address.state') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" name="shipping_address[zip]" placeholder="ZIP"
                                            class="form-control" value="{{ old('shipping_address.zip') }}">
                                    </div>
                                    <div class="col-12 mb-2">
                                        <input type="text" name="shipping_address[country]" placeholder="Country"
                                            class="form-control" value="{{ old('shipping_address.country') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Line Items Section (Dynamic) -->
                    <div class="mb-4 border-bottom pb-4">
                        <h2 class="h4 mb-3">Line Items</h2>
                        <table class="table table-bordered">
                            <thead>
                                <tr class="table-light">
                                    <th>Item/Service</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Rate</th>
                                    <th>Amount</th>
                                    <th>Taxable</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="line-items">
                                <tr>
                                    <td>
                                        <select name="line_items[0][service_id]" class="form-select service-select">
                                            <option value="">Select Service</option>
                                            @foreach ($services as $service)
                                                <option value="{{ $service->id }}" data-name="{{ $service->name }}"
                                                    data-description="{{ $service->description }}"
                                                    data-rate="{{ $service->rate }}"
                                                    data-taxable="{{ $service->taxable }}">
                                                    {{ $service->name }}
                                                </option>
                                            @endforeach
                                            <option value="custom">Custom Item</option>
                                        </select>
                                        <input type="text" name="line_items[0][name]"
                                            class="form-control mt-2 item-name" placeholder="Item name"
                                            style="display:none;">
                                    </td>
                                    <td>
                                        <input type="text" name="line_items[0][description]"
                                            class="form-control item-description">
                                    </td>
                                    <td>
                                        <input type="number" name="line_items[0][qty]" value="1"
                                            class="form-control item-qty" min="1">
                                    </td>
                                    <td>
                                        <input type="text" name="line_items[0][rate]" class="form-control item-rate"
                                            placeholder="$0.00">
                                    </td>
                                    <td class="amount fw-medium">$0.00</td>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" name="line_items[0][taxable]"
                                                class="form-check-input item-taxable" checked>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-ds btn-danger-ds btn-sm"
                                            onclick="removeRow(this)">Remove</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn-ds btn-primary-ds mt-2" onclick="addRow()">+ Add Item</button>
                        <button type="button" class="btn-ds btn-primary-ds mt-2 ms-2" data-bs-toggle="modal"
                            data-bs-target="#addServiceModal">+ Add New Service</button>
                    </div>
                    <!-- Fees & Adjustments Section -->
                    <div class="mb-4 border-bottom pb-4">
                        <h2 class="h4 mb-3">Fees &#x26; Adjustments</h2>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="discount" class="form-label">Discount (% or Fixed)</label>
                                <input type="text" name="discount" id="discount" class="form-control"
                                    placeholder="0% or $0.00" value="{{ old('discount') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="tax_rate" class="form-label">Tax Rate</label>
                                <select name="tax_rate" id="tax_rate" class="form-select">
                                    <option value="0" {{ old('tax_rate', '0') == '0' ? 'selected' : '' }}>0%</option>
                                    <option value="8.5" {{ old('tax_rate') == '8.5' ? 'selected' : '' }}>8.5%</option>
                                    <option value="10" {{ old('tax_rate') == '10' ? 'selected' : '' }}>10%</option>
                                </select>
                            </div>
                            <!-- Additional Field: Sales Tax Category -->
                            <div class="col-md-4 mb-3">
                                <label for="sales_tax_category" class="form-label">Sales Tax Category</label>
                                <input type="text" name="sales_tax_category" id="sales_tax_category"
                                    class="form-control" placeholder="e.g., Taxable Goods" value="{{ old('sales_tax_category') }}">
                            </div>
                            <!-- More Field: Adjustment Note -->
                            <div class="col-md-8 mb-3">
                                <label for="adjustment_note" class="form-label">Adjustment Note</label>
                                <textarea name="adjustment_note" id="adjustment_note" class="form-control" rows="2">{{ old('adjustment_note') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <!-- Notes Section -->
                    <div class="mb-4 border-bottom pb-4">
                        <h2 class="h4 mb-3">Notes</h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_memo" class="form-label">Customer Memo (Visible to Customer)</label>
                                <textarea name="customer_memo" id="customer_memo" class="form-control" rows="3">{{ old('customer_memo', $event->notes ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="private_note" class="form-label">Private Note (Internal)</label>
                                <textarea name="private_note" id="private_note" class="form-control" rows="3">{{ old('private_note', $event->internal_notes ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <!-- Summary Section (Real-time JS calculated) -->
                    <div class="mb-4 border-bottom pb-4">
                        <h2 class="h4 mb-3">Summary</h2>
                        <div class="row">
                            <div class="col-6 text-end">Subtotal:</div>
                            <div class="col-6" id="subtotal">$0.00</div>
                            <div class="col-6 text-end">Discount:</div>
                            <div class="col-6" id="discount_amount">$0.00</div>
                            <div class="col-6 text-end">Taxes:</div>
                            <div class="col-6" id="taxes">$0.00</div>
                            <div class="col-6 text-end fw-bold">Total Due:</div>
                            <div class="col-6 fw-bold" id="total">$0.00</div>
                        </div>
                    </div>
                    <!-- Attachments Section -->
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Attachments</h2>
                        <input type="file" name="attachments[]" multiple class="form-control">
                    </div>
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" name="action" value="draft" class="btn-ds btn-secondary-ds">Save
                            Draft</button>
                        <button type="submit" name="action" value="send" class="btn-ds btn-primary-ds">Create &#x26; Send
                            to QuickBooks</button>
                    </div>
                </form>

                <!-- Add New Service Modal -->
                <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addServiceForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Service Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control"
                                            placeholder="e.g. Parking fee">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Rate ($)</label>
                                        <input type="text" name="rate" class="form-control" placeholder="0.00">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Taxable</label>
                                        <select name="taxable" class="form-select">
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" rows="3" class="form-control"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-ds btn-secondary-ds" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn-ds btn-primary-ds" id="saveService">Save Service</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script>
    function addDays(dateStr, days) {
        const date = new Date(dateStr);
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }

    function updateDueDate() {
        const terms = $('#terms').val();
        const invoiceDate = $('#invoice_date').val();
        if (!invoiceDate) return;
        if (terms === 'Due on Receipt') {
            // Same day payment
            $('#due_date')
                .val(invoiceDate)
                .prop('readonly', true);
        } else {
            let days = 0;
            if (terms === 'Net 10') days = 10;
            if (terms === 'Net 15') days = 15;
            if (terms === 'Net 30') days = 30;
            if (terms === 'Net 60') days = 60;
            $('#due_date')
                .val(addDays(invoiceDate, days))
                .prop('readonly', false);
        }
    }
    // Listeners
    $(document).on('change', '#terms', updateDueDate);
    $(document).on('change', '#invoice_date', updateDueDate);
    // Initial load
    $(document).ready(updateDueDate);
</script>
<script>
    $(document).ready(function() {
        var userRoleId = '{{ auth()->user()->role_id ?? '' }}';
        var servicePrefix = userRoleId === '6' ? 'manager' : 'admin';
        /* ===============================
           BILLING → SHIPPING
        =============================== */
        const billingFields = {
            street: $('input[name="billing_address[street]"]'),
            city: $('input[name="billing_address[city]"]'),
            state: $('input[name="billing_address[state]"]'),
            zip: $('input[name="billing_address[zip]"]'),
            country: $('input[name="billing_address[country]"]'),
        };
        const shippingFields = {
            street: $('input[name="shipping_address[street]"]'),
            city: $('input[name="shipping_address[city]"]'),
            state: $('input[name="shipping_address[state]"]'),
            zip: $('input[name="shipping_address[zip]"]'),
            country: $('input[name="shipping_address[country]"]'),
        };

        function copyBillingToShipping() {
            $.each(billingFields, function(key, $billing) {
                shippingFields[key]
                    .val($billing.val())
                    .prop('readonly', true);
            });
        }

        function unlockShipping() {
            $.each(shippingFields, function(_, $field) {
                $field.prop('readonly', false);
                $field.val('');
            });
        }
        // ✅ Delegated event (this fixes your issue)
        $(document).on('change', '#same_as_billing', function() {
            if ($(this).is(':checked')) {
                copyBillingToShipping();
            } else {
                unlockShipping();
            }
        });
        // Live sync
        $.each(billingFields, function(_, $field) {
            $field.on('input', function() {
                if ($('#same_as_billing').is(':checked')) {
                    copyBillingToShipping();
                }
            });
        });
    });
</script>
<script>
    /* ===============================
        LINE ITEMS (unchanged)
    =============================== */
    function addRow() {
        const tbody = document.getElementById('line-items');
        const template = tbody.rows[0];
        const rowIndex = tbody.rows.length;
        const row = template.cloneNode(true);
        row.querySelectorAll('input, select').forEach(input => {
            input.name = input.name.replace(/\[\d+]/, `[${rowIndex}]`);
            if (input.type === 'checkbox') input.checked = true;
            else if (input.name.includes('[qty]')) input.value = 1;
            else if (input.tagName === 'SELECT') input.selectedIndex = 0;
            else input.value = '';
        });
        row.querySelector('.item-name').style.display = 'none';
        row.querySelector('.amount').textContent = '$0.00';
        tbody.appendChild(row);
        calculateTotals();
    }

    function removeRow(btn) {
        const tbody = document.getElementById('line-items');
        if (tbody.rows.length > 1) {
            btn.closest('tr').remove();
            calculateTotals();
        }
    }
    // Handle service selection
    $(document).on('change', '.service-select', function() {
        const $row = $(this).closest('tr');
        const $option = $(this).find('option:selected');
        if ($(this).val() === 'custom') {
            $row.find('.item-name').show().val('');
            $row.find('.item-description').val('');
            $row.find('.item-rate').val('');
            $row.find('.item-taxable').prop('checked', true);
        } else if ($(this).val()) {
            $row.find('.item-name').hide().val($option.data('name'));
            $row.find('.item-description').val($option.data('description') || '');
            $row.find('.item-rate').val($option.data('rate') || '');
            $row.find('.item-taxable').prop('checked', $option.data('taxable') == 1);
        } else {
            $row.find('.item-name').hide().val('');
            $row.find('.item-description').val('');
            $row.find('.item-rate').val('');
            $row.find('.item-taxable').prop('checked', true);
        }
        calculateTotals();
    });
</script>
<script>
    function parseMoney(value) {
        if (!value) return 0;
        return parseFloat(value.toString().replace(/[^0-9.-]/g, '')) || 0;
    }

    function calculateTotals() {
        let subtotal = 0;
        // Line items
        $('#line-items tr').each(function() {
            const qty = parseMoney($(this).find('.item-qty').val());
            const rate = parseMoney($(this).find('.item-rate').val());
            const amount = qty * rate;
            subtotal += amount;
            $(this).find('.amount').text(`$${amount.toFixed(2)}`);
        });
        // Discount
        let discountInput = $('#discount').val();
        let discountAmount = 0;
        if (discountInput) {
            if (discountInput.includes('%')) {
                discountAmount = subtotal * (parseMoney(discountInput) / 100);
            } else {
                discountAmount = parseMoney(discountInput);
            }
        }
        // Tax (only taxable items)
        let taxableTotal = 0;
        $('#line-items tr').each(function() {
            const isTaxable = $(this).find('.item-taxable').is(':checked');
            if (isTaxable) {
                const qty = parseMoney($(this).find('.item-qty').val());
                const rate = parseMoney($(this).find('.item-rate').val());
                taxableTotal += qty * rate;
            }
        });
        const taxRate = parseMoney($('#tax_rate').val());
        const taxAmount = taxableTotal * (taxRate / 100);
        // Grand total
        const total = subtotal + taxAmount - discountAmount;
        // UI update
        $('#subtotal').text(`$${subtotal.toFixed(2)}`);
        $('#discount_amount').text(`-$${discountAmount.toFixed(2)}`);
        $('#taxes').text(`$${taxAmount.toFixed(2)}`);
        $('#total').text(`$${total.toFixed(2)}`);
    }
    // 🔁 Live listeners
    $(document).on('input change', '#line-items input, #discount, #tax_rate', calculateTotals);
    // Initial run
    $(document).ready(calculateTotals);
</script>
<script>
    $(document).ready(function() {
        $('#saveService').click(function() {
            // Remove previous errors
            $('#addServiceModal .is-invalid').removeClass('is-invalid');
            $('#addServiceModal .invalid-feedback').remove();

            var formData = $('#addServiceForm').serialize();

            $.ajax({
                url: servicePrefix === 'manager'
                    ? '{{ route('manager.services.store') }}'
                    : '{{ route('admin.services.store') }}',
                type: 'POST',
                data: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success) {
                        var newService = response.service;
                        $('.service-select').each(function() {
                            $(this).append('<option value="' + newService.id +
                                '" data-name="' + newService.name +
                                '" data-description="' + newService
                                .description + '" data-rate="' + newService
                                .rate + '" data-taxable="' + newService
                                .taxable + '">' + newService.name + '</option>');
                        });
                        $('#addServiceModal').modal('hide');
                        $('#addServiceForm')[0].reset();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 || xhr.status === 500) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            var $input = $('#addServiceForm [name="' + key + '"]');
                            $input.addClass('is-invalid').after(
                                '<div class="invalid-feedback">' + value[0] +
                                '</div>');
                        });
                    }
                }
            });
        });
    });
</script>
