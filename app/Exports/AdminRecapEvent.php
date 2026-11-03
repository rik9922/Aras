<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Recap;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\DB;

class AdminRecapEvent implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Event::with(['brand', 'department', 'distributor', 'location','assignAmbassadors','assignAmbassadors.ambassador','manager', 'market'])
            ->whereHas('recap', function($query) {
                $query->whereNotNull('id');
            });

        if (!empty($this->filters)) {
            if (isset($this->filters['department_id'])) {
                $query->where('department_id', $this->filters['department_id']);
            }

            if (isset($this->filters['market_id'])) {
                $query->where('market_id', $this->filters['market_id']);
            }

            if (isset($this->filters['manager_id'])) {
                $query->where('manager_id', $this->filters['manager_id']);
            }

            if (isset($this->filters['venue_id'])) {
                $query->where('venue_type_id', $this->filters['venue_id']);
            }

            if (isset($this->filters['distributor_id'])) {
                $query->where('distributor_id', $this->filters['distributor_id']);
            }

            if (isset($this->filters['location_id'])) {
                $query->where('location_id', $this->filters['location_id']);
            }

            if (isset($this->filters['location_state'])) {
                $query->whereHas('location', function($q) {
                    $q->where('state_id', $this->filters['location_state']);
                });
            }

            if (isset($this->filters['brand_id'])) {
                $query->where('brand_id', $this->filters['brand_id']);
            }

            if (isset($this->filters['event_id'])) {
                $query->where('id', $this->filters['event_id']);
            }

            if (isset($this->filters['startDate']) && isset($this->filters['endDate'])) {
                $query->whereBetween('date', [$this->filters['endDate'], $this->filters['startDate']]);
            } else if (isset($this->filters['startDate'])) {
                $query->where('date', '<=', $this->filters['startDate']);
            } else if (isset($this->filters['endDate'])) {
                $query->where('date', '>=', $this->filters['endDate']);
            }

            if (isset($this->filters['overview_needed']) && $this->filters['overview_needed']) {
                $query->whereHas('recap', function($q) {
                    $q->where('overview_needed', 1);
                });
            }
        }
      
        return $query->get()
            ->map(function ($event, $key) {
                $ambassadorNames = ambassadorName($event->id);
                
                return [
                    'ID' => $key + 1,
                    'Event ID' => $event->id,
                    'Event Name' => $event->event_name ?? '-',
                    'Date' => $event->FormattedDate,
                    'Brand' => $event->brand->brand_name ?? '-',
                    'Location' => $event->location->city ?? '-',
                    'State' => $event->location->state->name ?? '-',
                    'Ambassadors' => $ambassadorNames ?: '-',
                    'Department' => $event->department->name ?? '-',
                    'Manager' => $event->manager->name ?? '-',
                    'Market' => $event->market->name ?? '-',
                    'Distributor' => $event->distributor->name ?? '-',
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
            'Brand', 
            'Location',
            'State',
            'Ambassadors',
            'Department',
            'Manager',
            'Market',
            'Distributor'
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