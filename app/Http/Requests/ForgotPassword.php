<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
class ForgotPassword extends FormRequest
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
            'email' => ['bail', 'required', 'email:rfc,dns', 'string', 'exists:users']
        ];
    }

    public function messages()
    {
        return [
            'required' => ':attribute is required.',
            'exists'   => 'The email address you entered is not associated with an account, please try again with a different email.'
        ];
    }

     /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
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
