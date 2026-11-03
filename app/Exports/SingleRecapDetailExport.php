<?php

namespace App\Exports;

use App\Models\Recap;
use App\Models\User;
use App\Services\RecapFormService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SingleRecapDetailExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(protected Recap $recap)
    {
        try {
            $this->recap->loadMissing([
                'event.brand',
                'event.location.state',
                'event.department',
                'event.distributor',
                'event.manager',
                'event.market',
                'event.client',
                'event.venueType',
                'event.eventType',
                'ambassador',
                'state',
                'brand',
            ]);
        } catch (\Throwable) {
            // Fall back to whatever is already loaded on the model.
        }
    }

    public function collection(): Collection
    {
        return collect($this->buildRows())->map(fn (array $row) => [
            'Field' => $this->sanitizeCell($row[0]),
            'Value' => $this->sanitizeCell($row[1]),
        ]);
    }

    public function headings(): array
    {
        return ['Field', 'Value'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    protected function buildRows(): array
    {
        $recap = $this->recap;
        $event = $recap->event;
        $recapType = RecapFormService::resolveForRecap($recap);

        $rows = [
            $this->section('Recap'),
            ['Recap ID', (string) $recap->id],
            ['Recap type', $recapType],
            ['Submission Status', $this->approvalStatus($recap)],
            ['Is Draft', $this->yesNo($recap->is_draft ?? false)],
            ['Approved At', $this->formatDateTime($recap->approved_at)],
            ['Denied At', $this->formatDateTime($recap->denied_at)],
            ['Denied By', $recap->denied_by ? (User::find($recap->denied_by)?->name ?? (string) $recap->denied_by) : '-'],
            ['Deny Reason', $this->text($recap->deny_reason)],
            ['Recap Status', $this->text($recap->status)],
            ['Auto Deducted At', $this->formatDateTime($recap->auto_deducted_at)],
            ['Auto Deduction Reason', $this->text($recap->auto_deduction_reason)],
            ['Manager Payout Override ($)', $recap->manager_override_amount !== null ? number_format((float) $recap->manager_override_amount, 2) : '-'],
            ['Manager No-Show', $this->yesNo($recap->manager_no_show ?? false)],

            $this->section('Event'),
            ['Event ID', $event ? (string) $event->id : '-'],
            ['Event Name', $event?->event_name ?? '-'],
            ['Event Date', $event?->FormattedDate ?? '-'],
            ['Event Start Time', $event?->start_time ? $this->formatDateTime($event->start_time) : '-'],
            ['Event End Time', $event?->end_time ? $this->formatDateTime($event->end_time) : '-'],
            ['Event Type', $event?->venueType?->name ?? '-'],
            ['THC Event', $event && $event->isThcEvent() ? 'Yes' : 'No'],
            ['Brand', $event?->brand?->brand_name ?? '-'],
            ['Department', $event?->department?->name ?? '-'],
            ['Market', $event?->market?->name ?? '-'],
            ['Manager', $event?->manager?->name ?? '-'],
            ['Distributor', $event?->distributor?->name ?? '-'],
            ['Client / Bill To', $event?->client?->name ?? '-'],
            ['Location City', $event?->location?->city ?? '-'],
            ['Location State', $event?->location?->state?->name ?? '-'],
            ['Assigned Ambassadors', $event ? ambassadorName($event->id) : '-'],

            $this->section('Ambassador'),
            ['Ambassador Name', $recap->ambassador?->name ?? '-'],
            ['Ambassador Email', $recap->ambassador?->email ?? '-'],

            $this->section('Recap responses'),
            ['Store / State', $recap->state?->name ?? '-'],
            ['Traffic at location (registers, aisles, etc.)', $this->text($recap->traffic_location)],
            ['Event timing / close time', $this->formatDateTime($recap->close_time)],
            ['Checked in with (name, phone, email)', $this->text($recap->check_in_with_person)],
            ['Promotion running for product', $this->yesNo($recap->promotion_product_status ?? false)],
            ['Everyday price tip / feature or special pricing', $this->text($recap->tip)],
            ['Competitor brands on site', $this->text($recap->brand_dmeo_name)],
            ['Backup brand used', $this->yesNo($recap->backup_brand_used ?? false)],
            ['Backup brand name', $this->text($recap->backup_brand_name)],
            ['Predominant consumer gender', $recap->interaction_gender ? ucfirst((string) $recap->interaction_gender) : '-'],
            ['Predominant consumer age', $this->text($recap->interaction_age)],
            ['Consumers interacted with (approx.)', $this->text($recap->total_interaction)],
            ['Samples handed out', $this->text($recap->samples)],
            ['Product type sold', $this->productTypeSold($recap, $event)],
            ['Units sold', $this->text($recap->sold_unit)],
            ['THC products sold', $this->formatThcProducts($recap)],
            ['Buyback amount ($)', $recap->buyback_amount !== null ? number_format((float) $recap->buyback_amount, 2) : '-'],
            ...array_map(
                fn (array $row) => [$row[0], $this->text($row[1])],
                RecapFormService::exportRowsForType($recap, $recapType)
            ),
            ['People who knew about the brand', $recap->brand_count !== null ? (string) $recap->brand_count : '-'],
            ['Giveaway count', isset($recap->giveaway_count) && $recap->giveaway_count !== null ? (string) $recap->giveaway_count : '-'],
            ['Giveaway comments', $this->text($recap->giveaway_comments ?? null)],

            $this->section('Attachments (URLs)'),
            ['Consumer photos', $this->formatPhotoUrls($recap->consumer_photo)],
            ['Brand photo', $this->formatPhotoUrl($recap->brand_photo)],
            ['Product attachment 1', $this->formatPhotoUrl($recap->product_attachments1)],
            ['Product attachment 2', $this->formatPhotoUrl($recap->product_attachments2)],
            ['Receipt', $this->formatPhotoUrl($recap->receipt)],

            $this->section('Record metadata'),
            ['Created At', $this->formatDateTime($recap->created_at)],
            ['Updated At', $this->formatDateTime($recap->updated_at)],
        ];

        return array_values(array_filter($rows, fn ($row) => $row !== null));
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function section(string $title): array
    {
        return [$title, ''];
    }

    protected function approvalStatus(Recap $recap): string
    {
        if ($recap->is_draft ?? false) {
            return 'Draft';
        }

        if ($recap->is_approved === true) {
            return 'Approved';
        }

        if ($recap->is_approved === false && $recap->deny_reason) {
            return 'Denied';
        }

        if ($recap->is_approved === false) {
            return 'Pending';
        }

        return '-';
    }

    protected function productTypeSold(Recap $recap, $event): string
    {
        if ($event && $event->isThcEvent()) {
            return 'THC event (see THC products sold)';
        }

        return $recap->brand?->brand_name
            ?? $event?->brand?->brand_name
            ?? '-';
    }

    protected function formatThcProducts(Recap $recap): string
    {
        $products = $recap->thc_products_sold;

        if (is_string($products)) {
            $products = json_decode($products, true);
        }

        if (! is_array($products) || $products === []) {
            return '-';
        }

        $lines = [];
        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }
            $lines[] = trim(implode(' ', array_filter([
                $product['brand'] ?? '',
                $product['flavor'] ?? '',
                $product['dosage'] ?? '',
                $product['type'] ?? '',
            ])));
        }

        return $lines === [] ? '-' : implode('; ', $lines);
    }

    protected function formatPhotoUrls(mixed $photos): string
    {
        if (empty($photos)) {
            return '-';
        }

        if (is_string($photos)) {
            $decoded = json_decode($photos, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $photos = $decoded;
            } else {
                $photos = array_filter(array_map('trim', explode(',', $photos)));
            }
        }

        if (! is_array($photos)) {
            return $this->formatPhotoUrl($photos);
        }

        $urls = array_map(fn ($path) => $this->formatPhotoUrl($path), $photos);

        return implode("\n", array_filter($urls, fn ($url) => $url !== '-')) ?: '-';
    }

    protected function formatPhotoUrl(mixed $path): string
    {
        if (empty($path)) {
            return '-';
        }

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }

    protected function yesNo(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
    }

    protected function text(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return (string) $value;
    }

    protected function formatDateTime(mixed $value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * Strip characters that break Excel/XML export or SMTP MIME encoding.
     */
    protected function sanitizeCell(mixed $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
        $value = preg_replace('/[\x{FFFE}\x{FFFF}]/u', '', $value) ?? $value;

        return $value;
    }
}
