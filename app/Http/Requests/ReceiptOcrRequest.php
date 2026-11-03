<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptOcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'receipt_image.required' => 'Choose a receipt image to scan.',
            'receipt_image.mimes' => 'The receipt must be a JPG, JPEG, PNG, or WebP image.',
            'receipt_image.max' => 'The receipt file may not be larger than 10 MB.',
        ];
    }
}
