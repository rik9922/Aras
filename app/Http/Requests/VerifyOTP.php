<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Models\PasswordResetToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyOTP extends FormRequest
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
            'email' => ['bail', 'required', 'email:rfc,dns', 'string', 'exists:users,email'],
            'code' => [
                'bail',
                'required',
                Rule::exists('password_reset_tokens', 'token')->where(function ($query) {
                    $query->where('email', $this->input('email'))
                        ->whereNull('verified_at')
                        ->whereRaw('created_at = (SELECT MAX(created_at) FROM password_reset_tokens WHERE email = ?)', [$this->input('email')]);
                }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'required' => ':attribute is required.',
            'code.exists' => 'Please recheck the code sent to your email and try again.',
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
