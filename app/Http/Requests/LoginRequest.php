<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
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
            'email'      => ['bail', 'required','string'],
            'password'   => ['bail', 'required', 'string']
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $usernameOrEmail = $this->input('email');

        $validator->after(function ($validator) use ($usernameOrEmail) {
            $exists = User::where('username', $usernameOrEmail)
                ->orWhere('email', $usernameOrEmail)
                ->exists();

            if (!$exists) {
                $validator->errors()->add('username_or_email', 'The provided username or email does not exist.');
            }
        });
        
        throw new HttpResponseException(response()->json([
            'status'     => 'failed',
            'message'    => $validator->errors()->first(),
            'error_type' => 'ERROR',
            'data'       => new \stdClass()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
