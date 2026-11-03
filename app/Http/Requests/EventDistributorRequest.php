<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventDistributorRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            // 'email' => 'required|email|unique:event_distributors,email',
            // 'phone' => 'nullable|string|max:15',
            // 'company_name' => 'nullable|string|max:255',
            // 'address' => 'nullable|string|max:500',
            // 'distributor_type' => 'required|string',
        ];
    }
}
