<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuickbookInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'doc_number' => 'nullable|string',
            'terms' => 'required|string',
            'status' => 'required|string|in:Draft,Sent,Paid,Overdue',
            'currency_code' => 'required|string|size:3',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'po_number' => 'nullable|string',
            'customer_name' => 'required|string',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'billing_address.street' => 'nullable|string',
            'billing_address.city' => 'nullable|string',
            'billing_address.state' => 'nullable|string',
            'billing_address.zip' => 'nullable|string',
            'billing_address.country' => 'nullable|string',
            'shipping_address.street' => 'nullable|string',
            'shipping_address.city' => 'nullable|string',
            'shipping_address.state' => 'nullable|string',
            'shipping_address.zip' => 'nullable|string',
            'shipping_address.country' => 'nullable|string',
            'same_as_billing' => 'nullable|boolean',
            'line_items.*.service_id' => 'nullable|exists:services,id',
            'line_items.*.name' => 'required_if:line_items.*.service_id,null|string|max:255',
            'line_items.*.description' => 'nullable|string',
            'line_items.*.qty' => 'required|numeric|min:1',
            'line_items.*.rate' => 'required|numeric|min:0',
            'line_items.*.taxable' => 'nullable|boolean',
            'discount' => 'nullable|string',
            'tax_rate' => 'required|numeric|min:0',
            'sales_tax_category' => 'nullable|string',
            'adjustment_note' => 'nullable|string',
            'customer_memo' => 'nullable|string',
            'private_note' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'action' => 'required|in:draft,send',
        ];
    }

    public function messages(): array
    {
        return [

            // Event
            'event_id.required' => 'Please select an event.',
            'event_id.exists' => 'The selected event is invalid.',

            // Basic Info
            'doc_number.string' => 'Document number must be a valid text value.',
            'terms.required' => 'Please select invoice terms.',
            'status.required' => 'Please select invoice status.',
            'status.in' => 'Invoice status must be Draft, Sent, Paid, or Overdue.',
            'currency_code.required' => 'Please select a currency.',
            'currency_code.size' => 'Currency code must be a valid 3-letter code.',

            // Dates
            'invoice_date.required' => 'Please select an invoice date.',
            'invoice_date.date' => 'Invoice date must be a valid date.',
            'due_date.required' => 'Please select a due date.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after_or_equal' => 'Due date must be on or after the invoice date.',

            // Customer Info
            'customer_name.required' => 'Please enter the customer name.',
            'customer_email.email' => 'Please enter a valid customer email address.',
            'customer_phone.string' => 'Customer phone number must be valid text.',

            // Billing Address
            'billing_address.street.string' => 'Billing street must be valid text.',
            'billing_address.city.string' => 'Billing city must be valid text.',
            'billing_address.state.string' => 'Billing state must be valid text.',
            'billing_address.zip.string' => 'Billing ZIP must be valid text.',
            'billing_address.country.string' => 'Billing country must be valid text.',

            // Shipping Address
            'shipping_address.street.string' => 'Shipping street must be valid text.',
            'shipping_address.city.string' => 'Shipping city must be valid text.',
            'shipping_address.state.string' => 'Shipping state must be valid text.',
            'shipping_address.zip.string' => 'Shipping ZIP must be valid text.',
            'shipping_address.country.string' => 'Shipping country must be valid text.',

            'same_as_billing.boolean' => 'Same as billing must be true or false.',

            // Line Items
            'line_items.*.service_id.exists' => 'The selected service is invalid.',
            'line_items.*.name.required_if' => 'Please enter a service name when no existing service is selected.',
            'line_items.*.name.string' => 'Service name must be valid text.',
            'line_items.*.name.max' => 'Service name may not exceed 255 characters.',
            'line_items.*.description.string' => 'Description must be valid text.',

            'line_items.*.qty.required' => 'Please enter quantity for each line item.',
            'line_items.*.qty.numeric' => 'Quantity must be a valid number.',
            'line_items.*.qty.min' => 'Quantity must be at least 1.',

            'line_items.*.rate.required' => 'Please enter a rate for each line item.',
            'line_items.*.rate.numeric' => 'Rate must be a valid number.',
            'line_items.*.rate.min' => 'Rate cannot be negative.',

            'line_items.*.taxable.boolean' => 'Please specify whether the line item is taxable or not.',

            // Discount & Tax
            'tax_rate.required' => 'Please enter a tax rate.',
            'tax_rate.numeric' => 'Tax rate must be a valid number.',
            'tax_rate.min' => 'Tax rate cannot be negative.',

            // Attachments
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.mimes' => 'Attachments must be PDF, DOC, DOCX, JPG, JPEG, or PNG files.',
            'attachments.*.max' => 'Each attachment must not exceed 2MB.',

            // Action
            'action.required' => 'Please choose an action.',
            'action.in' => 'Action must be either draft or send.',
        ];
    }
}
