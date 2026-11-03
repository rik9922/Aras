<?php

namespace App\Exports;

use App\Exports\Concerns\UsesTemporaryCellCache;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Models\Recap;
use App\Services\RecapFormService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminRecapEvent implements FromQuery, WithEvents, WithMapping, WithHeadings, WithStyles
{
    use UsesTemporaryCellCache;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $events = (new EventsExport($this->filters))->query()->reorder()->select('events.id');
        return Recap::with(['event.brand', 'event.location', 'event.manager', 'event.department', 'event.distributor', 'event.market', 'event.eventType', 'ambassador'])
            ->where(fn ($q) => $q->where('is_draft', false)->orWhereNull('is_draft'))->whereIn('event_id', $events)
            ->orderBy('recaps.id');
    }

    public function map($recap): array
    {
        $values = (new SingleRecapDetailExport($recap))->collection()->pluck('Value', 'Field');
        return array_map(fn ($field) => $values[$field] ?? '-', $this->headings());
    }

    public function headings(): array
    {
        return [
            'Recap ID', 'Event ID', 'Event Name', 'Event Date', 'Event Start Time', 'Event End Time',
            'Event Type', 'Brand', 'Venue / Store', 'Location City', 'Location State', 'Ambassador Name',
            'Department', 'Manager', 'Market', 'Distributor', 'Recap type', 'Submission Status',
            'Was event timing successful?', 'Traffic at location (registers, aisles, etc.)', 'Traffic flow',
            'Consumers interacted with (approx.)', 'Consumer interactions', 'Consumers sampled', 'Sampling number',
            'Attendance / consumer interactions', 'Total products sold', 'Products sold details', 'Samples handed out', 'Product type sold', 'Units sold', 'Drinks purchased after sampling (consumer)',
            'Buyback amount ($)', 'Receipt total', 'Event summary', 'Consumer feedback', 'Consumer comments',
            'Consumer suggestions', 'Consumer comments/suggestions', 'Account feedback', 'Ambassador feedback',
            'Consumer photos', 'Brand photo', 'Product attachment 1', 'Product attachment 2', 'Receipt', 'Off-premise receipts',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
