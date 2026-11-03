<?php

namespace App\Exports;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientEventExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Event::with(['brand', 'department', 'distributor', 'location', 'assignAmbassadors', 'assignAmbassadors.ambassador',])
            ->where('client_id', Auth::id());

        if (!empty($this->filters)) {
            if (isset($this->filters['department_id'])) {
                $query->where('department_id', $this->filters['department_id']);
            }

            if (isset($this->filters['market_id'])) {
                $query->where('market_id', $this->filters['market_id']);
            }

            if (isset($this->filters['venue_id'])) {
                $query->where('venue_id', $this->filters['venue_id']);
            }

            if (isset($this->filters['distributor_id'])) {
                $query->where('distributor_id', $this->filters['distributor_id']);
            }

            if (isset($this->filters['location_id'])) {
                $query->where('location_id', $this->filters['location_id']);
            }

            if (isset($this->filters['varietals'])) {
                $query->where('varietals', 'like', '%' . $this->filters['varietals'] . '%');
            }

            if (isset($this->filters['brand_id'])) {
                $query->where('brand_id', $this->filters['brand_id']);
            }

            if (isset($this->filters['event_id'])) {
                $query->where('id', $this->filters['event_id']);
            }

            if (isset($this->filters['payment_type'])) {
                $query->where('payment_type', $this->filters['payment_type']);
            }

            if (isset($this->filters['ambassador_name_id']) || isset($this->filters['ambassador_email_id'])) {
                $query->whereHas('ambassadors', function($q) {
                    if (isset($this->filters['ambassador_name_id'])) {
                        $q->where('ambassador_id', $this->filters['ambassador_name_id']);
                    }
                    if (isset($this->filters['ambassador_email_id'])) {
                        $q->where('ambassador_id', $this->filters['ambassador_email_id']);
                    }
                });
            }
        }
        
        return $query->get()
            ->map(function ($event, $key) {
                // Get ambassador names for this event
                $ambassadorNames = ambassadorName($event->id);
                
                return [
                    'ID' => $key + 1,
                    'Event ID' => $event->id,
                    'Event Name' => $event->event_name ?? '-',
                    'Day' => $event->Dayname,
                    'Date' => $event->FormattedDate,
                    'Time' => strip_tags($event->FormattedTime),
                    'Brand' => $event->brand->brand_name ?? '-',
                    'Varietals' => $event->varietals,
                    'Department' => $event->department->name ?? '-',
                    'Distributor' => $event->distributor->name ?? '-',
                    'Location' => $event->location->address ?? $event->location->venue_name ?? '-',
                    'City' => $event->location->city ?? '-',
                    'Assigned' => $ambassadorNames ?: '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'No.',
            'Event ID',
            'Event Name',
            'Day',
            'Date',
            'Time',
            'Brand',
            'Varietals',
            'Department',
            'Distributor',
            'Location',
            'City',
            'Assigned Ambassadors'
        ];
    }

    public function styles(Worksheet $sheet)
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
}