<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Recap;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientRecapEventExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $submittedId = Recap::pluck('event_id')
            ->filter()
            ->map(function($id) { 
                return (int) $id; 
            })
            ->toArray();

        if (empty($submittedId)) {
            return collect();
        }
      
        return Event::with(['brand', 'department', 'distributor', 'location'])->whereIn('id',$submittedId)
            ->get()
            ->map(function ($event, $key) {
                return [
                    'ID' => $key + 1,
                    'EventName' => $event->event_name ?? '-',
                    'Date' => $event->FormattedDate,
                    'Brand' => $event->brand->brand_name ?? '-',
                    'Location' => $event->location->city ?? '-',
                    'Assigned' => '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID', 'EventName','Date','Brand', 
            'Location', 'Assigned'
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
