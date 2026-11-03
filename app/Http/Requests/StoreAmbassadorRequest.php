<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAmbassadorRequest extends FormRequest
{
    private const FAVORITE_FIELD_MAX = 100;
    private const WAGE_MAX = 500;

    private const US_STATES = [
        'AL', 'ALABAMA',
        'AK', 'ALASKA',
        'AZ', 'ARIZONA',
        'AR', 'ARKANSAS',
        'CA', 'CALIFORNIA',
        'CO', 'COLORADO',
        'CT', 'CONNECTICUT',
        'DE', 'DELAWARE',
        'DC', 'DISTRICT OF COLUMBIA',
        'FL', 'FLORIDA',
        'GA', 'GEORGIA',
        'HI', 'HAWAII',
        'ID', 'IDAHO',
        'IL', 'ILLINOIS',
        'IN', 'INDIANA',
        'IA', 'IOWA',
        'KS', 'KANSAS',
        'KY', 'KENTUCKY',
        'LA', 'LOUISIANA',
        'ME', 'MAINE',
        'MD', 'MARYLAND',
        'MA', 'MASSACHUSETTS',
        'MI', 'MICHIGAN',
        'MN', 'MINNESOTA',
        'MS', 'MISSISSIPPI',
        'MO', 'MISSOURI',
        'MT', 'MONTANA',
        'NE', 'NEBRASKA',
        'NV', 'NEVADA',
        'NH', 'NEW HAMPSHIRE',
        'NJ', 'NEW JERSEY',
        'NM', 'NEW MEXICO',
        'NY', 'NEW YORK',
        'NC', 'NORTH CAROLINA',
        'ND', 'NORTH DAKOTA',
        'OH', 'OHIO',
        'OK', 'OKLAHOMA',
        'OR', 'OREGON',
        'PA', 'PENNSYLVANIA',
        'RI', 'RHODE ISLAND',
        'SC', 'SOUTH CAROLINA',
        'SD', 'SOUTH DAKOTA',
        'TN', 'TENNESSEE',
        'TX', 'TEXAS',
        'UT', 'UTAH',
        'VT', 'VERMONT',
        'VA', 'VIRGINIA',
        'WA', 'WASHINGTON',
        'WV', 'WEST VIRGINIA',
        'WI', 'WISCONSIN',
        'WY', 'WYOMING',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->only([
            'license_number',
            'issuing_state',
            'legal_address',
            'legal_city',
            'legal_state',
            'mailing_address',
            'mailing_city',
            'mailing_state',
            'favorite_coffee_shop',
            'favorite_restaurant',
            'favorite_place_to_shop',
            'favorite_charity',
            'wage',
        ]);

        $normalized = [];

        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $value = preg_replace('/\s+/', ' ', trim($value));
            }

            if (in_array($key, ['issuing_state', 'legal_state', 'mailing_state'], true) && is_string($value)) {
                $value = strtoupper($value);
            }

            if ($key === 'wage' && is_string($value)) {
                $value = preg_replace('/^\$/', '', $value);
            }

            $normalized[$key] = $value;
        }

        $this->merge($normalized);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        //dd($this->all());
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            // Ambassador credentials may use the same email as an admin or manager,
            // but ambassador accounts remain unique among themselves.
            'email' => ['required', 'email', Rule::unique('users', 'email')->where(fn ($query) => $query->where('role_id', 5))],
            'password' => 'required|min:6',
            'phone' => 'required|string|max:15',
            'venmo' => 'nullable|string',
            // 'instagram' => 'nullable|string|unique:users,instagram',
            'nickname' => 'nullable|string',
            'market_id'   => 'required|array|min:1',
            'market_id.*' => 'integer|exists:markets,id',
            'wage' => ['required', 'regex:/^\d{1,3}(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:'.self::WAGE_MAX],
            'hired' => 'required|date|before_or_equal:today',
            'shirt_size' => 'required',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'region' => 'required|string|in:Southeast,Midwest',
            'dob' => 'required|date|before:today',

            // Legal address — state stored as short code (e.g. NY, CA)
            'legal_address' => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z0-9])(?!.*[ .,\/\'-]{4})[A-Za-z0-9][A-Za-z0-9 .,\/\'-]*[A-Za-z0-9.]$/'],
            'legal_city'    => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])(?!.*[ .,\'-]{4})[A-Za-z0-9][A-Za-z0-9 .,\'-]*[A-Za-z0-9.]$/'],
            'legal_state'   => ['required', 'string', 'max:3', Rule::in(self::US_STATES)],
            'legal_zip_code'=> 'required|string|max:10',
            // Mailing address (optional)
            'mailing_address' => ['nullable', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z0-9])(?!.*[ .,\/\'-]{4})[A-Za-z0-9][A-Za-z0-9 .,\/\'-]*[A-Za-z0-9.]$/'],
            'mailing_city'    => ['nullable', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])(?!.*[ .,\'-]{4})[A-Za-z0-9][A-Za-z0-9 .,\'-]*[A-Za-z0-9.]$/'],
            'mailing_state'   => ['nullable', 'string', 'max:3', Rule::in(self::US_STATES)],
            'mailing_zip_code'=> 'nullable|string|max:10',
            // Same as legal checkbox
            'same_as_legal' => 'boolean',

            'titos_trained' => 'boolean',
            'thc_quiz_passed' => 'boolean',
            'male_ba_lgbtq_ok' => 'boolean',
            'attended_trainings' => 'boolean',
            'tips_certified' => 'boolean',
            'has_permit' => 'boolean',

            'has_liquor_license' => 'boolean',
            'license_number' => ['nullable', 'string', 'max:50', 'regex:/^(?=.*[A-Za-z0-9])(?!.*[ .\/-]{3})[A-Za-z0-9](?:[A-Za-z0-9 .\/-]*[A-Za-z0-9])?$/'],
            'issuing_state' => ['nullable', 'string', 'max:50', Rule::in(self::US_STATES)],
            'issued_at' => 'nullable|date|before_or_equal:today',
            'expires_at' => 'nullable|date|after_or_equal:today|after_or_equal:issued_at',
            'document_on_file' => 'boolean',
            'notes' => 'nullable|string|max:2000',

            'favorite_coffee_shop' => $this->favoriteFieldRules(),
            'favorite_restaurant' => $this->favoriteFieldRules(),
            'favorite_place_to_shop' => $this->favoriteFieldRules(),
            'favorite_charity' => $this->favoriteFieldRules(),
        ];
    }

    private function favoriteFieldRules(): array
    {
        return [
            'nullable',
            'string',
            'max:'.self::FAVORITE_FIELD_MAX,
            'regex:/^(?=.*[A-Za-z0-9])(?!.*[ .,&\'\/-]{4})[A-Za-z0-9][A-Za-z0-9 .,&\'\/-]*[A-Za-z0-9.]$/',
        ];
    }

    public function messages(): array
    {
        return [
            'wage.regex' => 'The wage must be a valid dollar amount with up to 3 digits and 2 decimal places.',
            'wage.max' => 'The wage may not be greater than $'.self::WAGE_MAX.'.',
            'hired.before_or_equal' => 'The hired date cannot be in the future.',
            'license_number.regex' => 'The license number may contain letters, numbers, spaces, hyphens, slashes, and dots, and must include at least one letter or number.',
            'issuing_state.in' => 'The issuing state must be a valid US state name or abbreviation.',
            'legal_address.regex' => 'The legal address must include letters or numbers and may only use common address punctuation. Do not use # or symbols.',
            'legal_city.regex' => 'The legal city must be a valid city name using letters, numbers, spaces, and common punctuation.',
            'legal_state.in' => 'The legal state must be a valid US state abbreviation.',
            'mailing_address.regex' => 'The mailing address must include letters or numbers and may only use common address punctuation. Do not use # or symbols.',
            'mailing_city.regex' => 'The mailing city must be a valid city name using letters, numbers, spaces, and common punctuation.',
            'mailing_state.in' => 'The mailing state must be a valid US state abbreviation.',
            'favorite_coffee_shop.regex' => 'Favorite coffee shop must include letters or numbers and may only use common name punctuation.',
            'favorite_restaurant.regex' => 'Favorite restaurant must include letters or numbers and may only use common name punctuation.',
            'favorite_place_to_shop.regex' => 'Favorite place to shop must include letters or numbers and may only use common name punctuation.',
            'favorite_charity.regex' => 'Favorite charity must include letters or numbers and may only use common name punctuation.',
        ];
    }
}
