<?php

namespace App\Exports;

use App\Exports\Concerns\UsesTemporaryCellCache;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Illuminate\Database\Eloquent\Builder;

class EventsExport implements FromQuery, WithEvents, WithMapping, WithHeadings, WithStyles, WithColumnWidths
{
    use UsesTemporaryCellCache;

    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Event::with(['brand', 'department', 'distributor', 'location', 'eventType', 'client', 'assigneAmbassador.ambassador']);

        if (!empty($this->filters)) {
            if (!empty($this->filters['location_state'])) {
                $states = app(\App\Services\LocationStateService::class)->aliases($this->filters['location_state']);
                $query->whereHas('location', fn ($q) => $q->whereIn(\DB::raw('UPPER(TRIM(state))'), $states));
            }

            if (!empty($this->filters['department_id'])) {
                $query->where('department_id', $this->filters['department_id']);
            }

            if (!empty($this->filters['market_id'])) {
                $query->where('market_id', $this->filters['market_id']);
            }

            if (!empty($this->filters['manager_id'])) {
                $query->where('manager_id', $this->filters['manager_id']);
            }

            if (!empty($this->filters['event_type_id'])) {
                $query->where('event_type_id', $this->filters['event_type_id']);
            }

            if (!empty($this->filters['distributor_id'])) {
                $query->where('distributor_id', $this->filters['distributor_id']);
            }

            if (!empty($this->filters['location_id'])) {
                $query->where('event_location_id', $this->filters['location_id']);
            }

            if (!empty($this->filters['varietals'])) {
                $query->where('varietals', 'like', '%' . $this->filters['varietals'] . '%');
            }

            if (!empty($this->filters['brand_id'])) {
                $query->where('brand_id', $this->filters['brand_id']);
            }

            if (!empty($this->filters['event_id'])) {
                $query->where('id', $this->filters['event_id']);
            }

            if (!empty($this->filters['startDate']) && !empty($this->filters['endDate'])) {
                $query->whereDate('start_time', '>=', $this->filters['startDate'])->whereDate('start_time', '<=', $this->filters['endDate']);
            } else if (!empty($this->filters['startDate'])) {
                $query->whereDate('start_time', '>=', $this->filters['startDate']);
            } else if (!empty($this->filters['endDate'])) {
                $query->whereDate('start_time', '<=', $this->filters['endDate']);
            }

            if (!empty($this->filters['payment_type'])) {
                $query->where('payment_type', $this->filters['payment_type']);
            }

            if (!empty($this->filters['bill_id'])) {
                $query->where(function ($q) {
                    $q->where('client_id', $this->filters['bill_id'])
                        ->orWhereHas('billingParties', function ($bq) {
                            $bq->where('client_id', $this->filters['bill_id']);
                        });
                });
            }

            if (!empty($this->filters['ambassador_name_id']) || !empty($this->filters['ambassador_email_id'])) {
                $query->whereHas('assigneAmbassador', function (Builder $query) {
                    if (!empty($this->filters['ambassador_name_id'])) {
                        $query->where('ambassador_id', $this->filters['ambassador_name_id']);
                    }
                    if (!empty($this->filters['ambassador_email_id'])) {
                        $query->where('ambassador_id', $this->filters['ambassador_email_id']);
                    }
                });
            }

            if (!empty($this->filters['kit_required'])) {
                $query->where('kit', 1);
            }

            if (isset($this->filters['event_status'])) {
                $query->where('event_status', $this->filters['event_status']);
            }

            if (!empty($this->filters['exclude_cancellations'])) {
                $query->where('event_status', '!=', 2);
            }

            if (!empty($this->filters['not_fully_staffed'])) {
                $query->whereRaw('(SELECT COUNT(*) FROM assign_ambassadors WHERE assign_ambassadors.event_id = events.id AND assign_ambassadors.status = ?) < COALESCE(NULLIF(events.position, 0), 1)', ['1']);
            }

            if (!empty($this->filters['overview_needed'])) {
                $query->where('recap_needed', 1);
            }

            $backupBrandFilter = ! empty($this->filters['backup_brand_used'])
                || ! empty($this->filters['backup_brand_name']);
            if ($backupBrandFilter) {
                $query->whereHas('recap', function ($q) {
                    if (! empty($this->filters['backup_brand_used'])) {
                        $q->where('backup_brand_used', 1);
                    }
                    if (! empty($this->filters['backup_brand_name'])) {
                        $q->where('backup_brand_name', 'like', '%'.$this->filters['backup_brand_name'].'%');
                    }
                });
            }
        }

        return $query->orderBy('events.id');
    }

    public function map($event): array
    {
        $ambassadorNames = '';
        if ($event->assigneAmbassador && count($event->assigneAmbassador) > 0) {
            $ambassadorNames = $event->assigneAmbassador->map(function($ea) {
                return $ea->ambassador ? $ea->ambassador->name : '';
            })->filter()->implode(', ');
        }

        return [
            'ID' => $event->id,
            'EventName' => $event->event_name ?? '-',
            'EventType' => $event->eventType ? $event->eventType->name : '-',
            'Day' => $event->Dayname,
            'Date' => $event->FormattedDate,
            'Time' => strip_tags($event->FormattedTime ?? ''),
            'Brand' => $event->brand ? $event->brand->brand_name : '-',
            'Varietals' => $event->varietals ?? '-',
            'Kit' => $event->kit == 1 ? 'Y' : 'N',
            'Department' => $event->department ? $event->department->name : '-',
            'Distributor' => $event->distributor ? $event->distributor->name : '-',
            'Location' => $event->location ? ($event->location->name ?: $event->location->address ?: '-') : '-',
            'Assigned' => $ambassadorNames ?: '-',
            'BillTo' => $event->client?$event->client->name : '-',
            'Status' => $event->event_status == 0 ? 'Pending' : ($event->event_status == 1 ? 'Completed' : 'Cancelled'),
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 12, 'B' => 24, 'C' => 20, 'D' => 14, 'E' => 18, 'F' => 48, 'G' => 30, 'H' => 30, 'I' => 8, 'J' => 25, 'K' => 30, 'L' => 40, 'M' => 35, 'N' => 30, 'O' => 16];
    }

    public function headings(): array
    {
        return [
            'ID', 'Event Name', 'Event Type', 'Day', 'Date', 'Time', 'Brand', 'Varietals', 
            'Kit', 'Department', 'Distributor', 'Location', 'Assigned', 'BillTo', 'Status'
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
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F0F0']
                ]
            ],
        ];
    }
}