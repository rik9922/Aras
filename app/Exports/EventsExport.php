<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Database\Eloquent\Builder;

class EventsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Event::with(['brand', 'department', 'distributor', 'location', 'eventType', 'client']);

        if (!empty($this->filters)) {

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
                $query->where('location_id', $this->filters['location_id']);
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
                $query->whereBetween('event_date', [$this->filters['startDate'], $this->filters['endDate']]);
            } else if (!empty($this->filters['startDate'])) {
                $query->where('event_date', '>=', $this->filters['startDate']);
            } else if (!empty($this->filters['endDate'])) {
                $query->where('event_date', '<=', $this->filters['endDate']);
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
                $query->whereHas('eventAmbassadors', function (Builder $query) {
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
                $query->whereRaw('(SELECT COUNT(*) FROM event_ambassadors WHERE event_ambassadors.event_id = events.id) < events.required_ambassadors');
            }

            if (!empty($this->filters['overview_needed'])) {
                $query->whereNull('overview')->orWhere('overview', '');
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

        return $query->get()->map(function ($event, $key) {
            $ambassadorNames = '';
            if ($event->eventAmbassadors && count($event->eventAmbassadors) > 0) {
                $ambassadorNames = $event->eventAmbassadors->map(function($ea) {
                    return $ea->ambassador ? $ea->ambassador->name : '';
                })->filter()->implode(', ');
            }

            return [
                'ID' => $key + 1,
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
                'Location' => $event->location ? ($event->location->address ?? $event->location->venue_name ?? '-') : '-',
                'Assigned' => $ambassadorNames ?: '-',
                'BillTo' => $event->client?$event->client->name : '-',
                'Status' => $event->event_status == 0 ? 'Pending' : ($event->event_status == 1 ? 'Completed' : 'Cancelled'),
            ];
        });
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