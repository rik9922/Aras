<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Recap;
use Illuminate\Validation\Rule;

class RecapFormService
{
    public const TYPE_BEST = 'best';

    public const TYPE_OFF_PREMISE = 'off_premise';

    public const TYPE_ON_PREMISE = 'on_premise';

    public const TYPE_SPECIAL_EVENT = 'special_event';

    /**
     * Recap types assignable on event_types.recap_type (BEST is event-flag only).
     *
     * @return list<string>
     */
    public static function eventTypeRecapTypes(): array
    {
        return [
            self::TYPE_OFF_PREMISE,
            self::TYPE_ON_PREMISE,
            self::TYPE_SPECIAL_EVENT,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allRecapTypes(): array
    {
        return [
            self::TYPE_BEST,
            self::TYPE_OFF_PREMISE,
            self::TYPE_ON_PREMISE,
            self::TYPE_SPECIAL_EVENT,
        ];
    }

    public static function resolveFromEvent(?Event $event): string
    {
        if ($event && (int) ($event->integrate_with_best ?? 0) === 1) {
            return self::TYPE_BEST;
        }

        $configured = $event?->eventType?->recap_type;

        if (is_string($configured) && in_array($configured, self::eventTypeRecapTypes(), true)) {
            return $configured;
        }

        return self::TYPE_OFF_PREMISE;
    }

    public static function resolveForRecap(Recap $recap): string
    {
        if (is_string($recap->recap_type) && in_array($recap->recap_type, self::allRecapTypes(), true)) {
            return $recap->recap_type;
        }

        return self::resolveFromEvent($recap->event);
    }

    public static function displayLabel(string $recapType): string
    {
        return match ($recapType) {
            self::TYPE_BEST => 'BEST Recap',
            self::TYPE_ON_PREMISE => 'On Premise Recap',
            self::TYPE_SPECIAL_EVENT => 'Special Event Recap',
            default => 'Off Premise Recap',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function baseRules(?Event $event, ?Recap $recap = null): array
    {
        $rules = [
            'traffic_location' => 'required|string|max:255',
            'timing_successful' => 'required_without:close_time|boolean',
            'close_time' => 'nullable|date',
            'check_in_with_person' => 'required|string|max:255',
            'product_attachments1' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'promotion_product_status' => 'required|boolean',
            'tip' => 'nullable|string|max:255',
            'product_attachments2' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'brand_dmeo_name' => 'nullable|string|max:255',
            'brand_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'interaction_gender' => 'required|string',
            'interaction_age' => 'required|integer|between:21,25',
            'total_interaction' => 'required|integer|min:0',
            'samples' => 'nullable|integer|min:0',
            'consumer_photo' => 'nullable|array',
            'consumer_photo.*' => 'image|mimes:jpeg,png,jpg,gif',
            'receipt' => [Rule::requiredIf(self::receiptRequired($event) && ! self::hasRetainedReceipts($recap)), 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp'],
            'state_id' => 'required|exists:states,id',
            'backup_brand_used' => 'required|in:0,1',
            'backup_brand_name' => ['nullable', 'string', 'max:255', Rule::requiredIf(request()->input('backup_brand_used') == '1')],
            'giveaway_count' => ['nullable', 'integer', 'min:0', Rule::requiredIf((bool) optional($event)->kit)],
            'giveaway_comments' => ['nullable', 'string', 'max:2000', Rule::requiredIf((bool) optional($event)->kit)],
            'buyback_amount' => 'nullable|numeric|min:0',
            'is_expedited_requested' => 'nullable|boolean',
        ];

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

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public static function typeSpecificRules(string $recapType): array
    {
        return match ($recapType) {
            self::TYPE_ON_PREMISE => [
                'drinks_purchased_after_sampling' => 'nullable|integer|min:0',
                'consumer_feedback' => 'required|string|min:200|max:255',
                'manager_comments' => 'required|string|min:200',
                'consumer_comments_suggestions' => 'required|string|min:200',
            ],
            self::TYPE_SPECIAL_EVENT => [
                'consumer_interactions' => 'required|integer|min:0',
                'consumer_sampled' => 'required|integer|min:0',
                'event_summary' => 'required|string|min:200',
                'consumer_feedback' => 'nullable|string|max:255',
                'ambassador_feedback' => 'nullable|string|max:255',
            ],
            default => [
                'consumer_feedback' => 'nullable|string|max:255',
                'ambassador_feedback' => 'nullable|string|max:255',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function offPremiseRules(?Event $event = null, ?Recap $recap = null): array
    {
        return [
            'traffic_flow' => 'required|string',
            'demographics_female' => 'nullable|integer|min:0|max:100',
            'demographics_male' => 'nullable|integer|min:0|max:100',
            'demographics_age_range' => 'nullable|string|max:20',
            'sampling_number' => 'nullable|integer|min:0',
            'consumer_interactions' => 'nullable|integer|min:0',
            'products_sold' => 'nullable|array',
            'products_sold.*.type' => 'nullable|string|max:50',
            'products_sold.*.name' => 'nullable|string|max:255',
            'products_sold.*.sold' => 'nullable|integer|min:0',
            'products_sold.*.regular_price' => 'nullable|numeric|min:0',
            'products_sold.*.sale_price' => 'nullable|numeric|min:0',
            'receipt_total' => 'nullable|numeric|min:0',
            'op_parking_fee' => 'nullable|numeric|min:0',
            'store_aware_of_demo' => 'boolean',
            'store_prepared_for_demo' => 'boolean',
            'product_on_sale' => 'boolean',
            'coupons_rebates_available' => 'boolean',
            'enough_product_in_stock' => 'boolean',
            'case_display' => 'boolean',
            'case_display_count' => 'nullable|string|max:50',
            'objections_to_sale' => 'nullable|string',
            'consumer_comments_suggestions' => 'nullable|string',
            'account_feedback' => 'nullable|string',
            'op_event_photos' => 'nullable|array',
            'op_event_photos.*' => 'file|mimes:jpeg,png,jpg,pdf|max:10240',
            'op_event_receipts' => [Rule::requiredIf(self::receiptRequired($event) && ! self::hasRetainedReceipts($recap)), 'nullable', 'array', 'min:1'],
            'op_event_receipts.*' => 'file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
            // The edit form submits the attachments that the ambassador chose
            // to keep. They are checked against the recap's stored paths before
            // being persisted (see RecapController), so these are not trusted
            // as arbitrary file paths.
            'existing_op_event_photos' => 'nullable|array',
            'existing_op_event_photos.*' => 'string|max:255',
            'existing_op_event_receipts' => 'nullable|array',
            'existing_op_event_receipts.*' => 'string|max:255',
            'off_premise_attachments_present' => 'nullable|boolean',
            'is_expedited_requested' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function submitRules(string $recapType, ?Event $event, ?Recap $recap = null): array
    {
        if ($recapType === self::TYPE_OFF_PREMISE) {
            return self::offPremiseRules($event, $recap);
        }

        return array_merge(self::baseRules($event, $recap), self::typeSpecificRules($recapType));
    }

    public static function receiptRequired(?Event $event): bool
    {
        return in_array(strtolower(trim((string) $event?->payment_type)), ['comdata', '3'], true);
    }

    private static function hasRetainedReceipts(?Recap $recap): bool
    {
        if (! $recap) {
            return false;
        }
        if ($recap->receipt) {
            return true;
        }
        $stored = is_array($recap->op_receipts) ? $recap->op_receipts : json_decode((string) $recap->op_receipts, true);
        $stored = is_array($stored) ? array_filter($stored, 'is_string') : [];
        if (request()->boolean('off_premise_attachments_present')) {
            return count(array_intersect($stored, (array) request()->input('existing_op_event_receipts', []))) > 0;
        }

        return count($stored) > 0;
    }

    /**
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        return [
            'timing_successful.required_without' => 'Please select Yes or No for event timing.',
            'receipt.required' => 'A receipt is required when the event payment type is Comdata.',
            'op_event_receipts.required' => 'A receipt is required when the event payment type is Comdata.',
            'product_attachments1.image' => 'Product Display Photo must be an image file.',
            'product_attachments1.mimes' => 'Product Display Photo must be a JPEG, PNG, JPG, or GIF file.',
            'product_attachments2.image' => 'Product Shelf Photo must be an image file.',
            'product_attachments2.mimes' => 'Product Shelf Photo must be a JPEG, PNG, JPG, or GIF file.',
            'brand_photo.image' => 'Setup Photo must be an image file.',
            'brand_photo.mimes' => 'Setup Photo must be a JPEG, PNG, JPG, or GIF file.',
            'consumer_photo.array' => 'Consumer photos must be uploaded as files.',
            'consumer_photo.*.image' => 'Each Consumers With Product file must be an image.',
            'consumer_photo.*.mimes' => 'Each Consumers With Product file must be a JPEG, PNG, JPG, or GIF file.',
            'receipt.image' => 'Receipt must be an image file.',
            'receipt.mimes' => 'Receipt must be a JPEG, PNG, JPG, or GIF file.',
            'interaction_age.integer' => 'The predominant age must be a two-digit number.',
            'interaction_age.between' => 'The predominant age must be between 21 and 25.',
            'op_event_photos.*.mimes' => 'Each event photo must be a JPEG, PNG, JPG, or PDF file.',
            'op_event_receipts.*.mimes' => 'Each receipt must be a JPEG, PNG, JPG, WebP, or PDF file.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function draftAllowedFields(): array
    {
        return [
            'traffic_location',
            'timing_successful',
            'close_time',
            'check_in_with_person',
            'promotion_product_status',
            'tip',
            'brand_dmeo_name',
            'interaction_gender',
            'interaction_age',
            'total_interaction',
            'samples',
            'brand_selling_id',
            'sold_unit',
            'consumer_feedback',
            'ambassador_feedback',
            'state_id',
            'brand_count',
            'backup_brand_used',
            'backup_brand_name',
            'giveaway_count',
            'giveaway_comments',
            'buyback_amount',
            'is_expedited_requested',
            'consumer_interactions',
            'consumer_sampled',
            'event_summary',
            'drinks_purchased_after_sampling',
            'manager_comments',
            'consumer_comments_suggestions',
            // Off Premise fields
            'traffic_flow',
            'demographics_female',
            'demographics_male',
            'demographics_age_range',
            'sampling_number',
            'products_sold',
            'receipt_total',
            'op_parking_fee',
            'store_aware_of_demo',
            'store_prepared_for_demo',
            'product_on_sale',
            'coupons_rebates_available',
            'enough_product_in_stock',
            'case_display',
            'case_display_count',
            'objections_to_sale',
            'account_feedback',
        ];
    }

    /**
     * @return list<array{0: string, 1: string|null}>
     */
    public static function exportRowsForType(Recap $recap, string $recapType): array
    {
        $rows = [];

        if ($recapType === self::TYPE_SPECIAL_EVENT) {
            $rows[] = ['Consumer interactions', $recap->consumer_interactions !== null ? (string) $recap->consumer_interactions : '-'];
            $rows[] = ['Consumers sampled', $recap->consumer_sampled !== null ? (string) $recap->consumer_sampled : '-'];
            $rows[] = ['Event summary', $recap->event_summary ?? '-'];
        }

        if ($recapType === self::TYPE_ON_PREMISE) {
            $rows[] = ['Drinks purchased after sampling (consumer)', $recap->drinks_purchased_after_sampling !== null ? (string) $recap->drinks_purchased_after_sampling : '-'];
            $rows[] = ['Consumer comments', $recap->consumer_feedback ?? '-'];
            $rows[] = ['Account feedback', $recap->manager_comments ?? '-'];
            $rows[] = ['Consumer suggestions', $recap->consumer_comments_suggestions ?? '-'];

            return $rows;
        }

        if ($recapType === self::TYPE_OFF_PREMISE) {
            $products = is_array($recap->products_sold) ? $recap->products_sold : (json_decode((string) $recap->products_sold, true) ?: []);
            $products = array_filter($products, 'is_array');
            $rows[] = ['Total products sold', (string) array_sum(array_column($products, 'sold'))];
            $rows[] = ['Products sold details', implode("\n", array_map(static fn ($product) => implode(' | ', [
                $product['type'] ?? '', $product['name'] ?? '', 'Sold: '.($product['sold'] ?? 0),
                'Regular price: '.($product['regular_price'] ?? '-'), 'Sale price: '.($product['sale_price'] ?? '-'),
            ]), $products)) ?: '-'];
            $rows[] = ['Traffic flow', $recap->traffic_flow ?? '-'];
            $rows[] = ['Demographics female', $recap->demographics_female !== null ? $recap->demographics_female.'%' : '-'];
            $rows[] = ['Demographics male', $recap->demographics_male !== null ? $recap->demographics_male.'%' : '-'];
            $rows[] = ['Demographics age range', $recap->demographics_age_range ?? '-'];
            $rows[] = ['Sampling number', $recap->sampling_number !== null ? (string) $recap->sampling_number : '-'];
            $rows[] = ['Receipt total', $recap->receipt_total !== null ? '$'.number_format($recap->receipt_total, 2) : '-'];
            $rows[] = ['Parking fee', $recap->op_parking_fee !== null ? '$'.number_format($recap->op_parking_fee, 2) : '-'];
            $rows[] = ['Store aware of demo', $recap->store_aware_of_demo ? 'Yes' : 'No'];
            $rows[] = ['Store prepared for demo', $recap->store_prepared_for_demo ? 'Yes' : 'No'];
            $rows[] = ['Product on sale', $recap->product_on_sale ? 'Yes' : 'No'];
            $rows[] = ['Coupons/rebates available', $recap->coupons_rebates_available ? 'Yes' : 'No'];
            $rows[] = ['Enough product in stock', $recap->enough_product_in_stock ? 'Yes' : 'No'];
            $rows[] = ['Case display', $recap->case_display ? 'Yes' : 'No'];
            $rows[] = ['Case display count', $recap->case_display_count ?? '-'];
            $rows[] = ['Objections to sale', $recap->objections_to_sale ?? '-'];
            $rows[] = ['Consumer comments/suggestions', $recap->consumer_comments_suggestions ?? '-'];
            $rows[] = ['Account feedback', $recap->account_feedback ?? '-'];
            if ($recap->consumer_feedback) $rows[] = ['Consumer feedback', $recap->consumer_feedback];
            if ($recap->ambassador_feedback) $rows[] = ['Ambassador feedback', $recap->ambassador_feedback];

            return $rows;
        }

        $rows[] = ['Consumer feedback', $recap->consumer_feedback ?? '-'];
        $rows[] = ['Ambassador feedback', $recap->ambassador_feedback ?? '-'];

        return $rows;
    }
}
