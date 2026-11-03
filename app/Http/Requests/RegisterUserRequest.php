<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterUserRequest extends FormRequest
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
            'first_name'  => 'required|string|min:2|max:100',
            'last_name'  => 'nullable|string|min:2|max:100',
            'email'      => 'required|email|unique:users,email|email:rfc,dns',
            'password'   => 'required|string|min:6|confirmed',
            'zip_code' => 'required'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'     => 'failed',
            'message'    => $validator->errors()->first(),
            'error_type' => 'ERROR',
            'data'       => new \stdClass()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
