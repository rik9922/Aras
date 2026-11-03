<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecapRequest extends FormRequest
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
        // Fetch event safely
        $event = Event::find($this->input('event_id'));
        $integrateWithBest = $this->boolean('integrate_with_best');

        // Base validation rules
        $rules = [
            'event_id' => 'required|exists:events,id',
            'traffic_location' => 'required|string|max:255',
            'close_time' => 'required|date',
            'check_in_with_person' => 'required|string|max:255',

            'product_attachments1' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'product_attachments2' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'brand_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'receipt' => 'nullable|image|mimes:jpeg,png,jpg,gif',

            'promotion_product_status' => 'required|boolean',
            'tip' => 'nullable|string|max:255',
            'brand_dmeo_name' => 'nullable|string|max:255',

            'interaction_gender' => 'required|string',
            'interaction_age' => 'required|integer|between:21,25',
            'total_interaction' => 'required|string',
            'samples' => 'nullable|integer|min:0',

            'consumer_photo' => 'nullable|array',
            'consumer_photo.*' => 'image|mimes:jpeg,png,jpg,gif',

            'consumer_feedback' => 'nullable|string|max:255',
            'ambassador_feedback' => 'nullable|string|max:255',

            'state_id' => 'required|exists:states,id',
            'buyback_amount' => 'nullable|numeric|min:0',
        ];

        // Conditional rules for THC events
        if ($event && $event->is_thc_event) {
            $rules['thc_products'] = 'required|array|min:1';
            $rules['thc_products.*.brand'] = 'required|string|max:255';
            $rules['thc_products.*.flavor'] = 'required|string|max:255';
            $rules['thc_products.*.dosage'] = 'required|string|max:255';
            $rules['thc_products.*.type'] = 'required|string|max:255';
        } else {
            $rules['brand_selling_id'] = 'nullable|exists:brands,id';
            $rules['sold_unit'] = 'nullable|integer|min:0';
            $rules['brand_count'] = 'nullable|integer';
        }

        // BEST rules
        if ($integrateWithBest) {
            $rules += [
                'event_name' => 'required|string|max:255',
                'status' => ['required', Rule::in(['Planned', 'Completed', 'Canceled'])],
                'target_audience' => ['required', Rule::in(['Consumer', 'Trade'])],
                'account_id' => 'required|string',
                'Associated_with_Demand_Creation_Station' => ['required', Rule::in(['Yes', 'No'])],
                'wet_or_dry' => ['required', Rule::in(['Wet', 'Dry'])],
                'objective' => 'required|string',
                'description' => 'required|string',
                'sampling_type' => ['required', Rule::in(['Single Brand Sampling', 'Multi Brand Sampling'])],
                'attendees' => 'required|integer|min:0',
                'interactions' => 'required|integer|min:0',
                'event_results' => 'required|string',
                'key_learnings_future_considerations' => 'required|string',
                'consumer_feedback' => 'required|string', // BEST-specific
                'account_feedback' => 'required|string',
                'budget' => 'required|integer|min:0',
                'Account_Premise_Type' => 'required|string',
                'program_id' => 'required|string',
                'tag_id' => 'required|string',
                '1l_bottles' => 'required|numeric|min:0',
                '175l_bottles' => 'required|numeric|min:0',
                '750ml_bottles' => 'required|numeric|min:0',
                '375ml_bottles' => 'required|numeric|min:0',
                'total_samples_distributed' => 'required|numeric|min:0',
                'total_samples_distributed_cocktail' => 'required|numeric|min:0',
                'total_samples_distributed_neat_shot' => 'required|numeric|min:0',
                'start_time' => 'required',
                'end_time' => 'required|after:start_time',
                'end_date' => 'required|date',
            ];
        }

        return $rules;
    }
}
