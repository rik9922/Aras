<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyEventsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        private Carbon $start,
        private Carbon $end,
        private ?int $clientId = null
    ) {}

    public function collection()
    {
        $query = Event::query()
            ->with(['brand', 'department', 'distributor', 'location', 'manager', 'market', 'billingParties.client', 'recap'])
            ->whereBetween('start_time', [$this->start, $this->end]);

        if ($this->clientId) {
            $query->where(function ($q) {
                $q->where('client_id', $this->clientId)
                    ->orWhereHas('billingParties', function ($bq) {
                        $bq->where('client_id', $this->clientId);
                    });
            });
        }

        return $query->orderBy('start_time')->get()
            ->map(function (Event $event, int $i) {
                return [
                    'No.' => $i + 1,
                    'Event ID' => $event->id,
                    'Event Name' => $event->event_name ?? '-',
                    'Date' => $event->FormattedDate ?? (optional($event->start_time)->format('Y-m-d') ?? '-'),
                    'Start Time' => $event->start_time ? Carbon::parse($event->start_time)->format('H:i') : '-',
                    'End Time' => $event->end_time ? Carbon::parse($event->end_time)->format('H:i') : '-',
                    'Brand' => $event->brand->brand_name ?? '-',
                    'City' => $event->location->city ?? '-',
                    'Manager' => $event->manager->name ?? '-',
                    'Market' => $event->market->name ?? '-',
                    'BA Status' => $event->event_status,
                    'Ops Status' => $event->admin_status ?? '-',
                    'Recap Needed' => (int) ($event->recap_needed ?? 0),
                    'Recap Status' => $event->recap_status ?? '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'No.',
            'Event ID',
            'Event Name',
            'Date',
            'Start Time',
            'End Time',
            'Brand',
            'City',
            'Manager',
            'Market',
            'BA Status',
            'Ops Status',
            'Recap Needed',
            'Recap Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}

