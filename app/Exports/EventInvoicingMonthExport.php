<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\InvoiceEventLink;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EventInvoicingMonthExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        private Carbon $start,
        private Carbon $end,
        private ?int $clientId = null
    ) {}

    public function collection()
    {
        $events = Event::query()
            ->with(['brand', 'recap', 'billingParties.client', 'client'])
            ->whereBetween('start_time', [$this->start, $this->end])
            ->when($this->clientId, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('client_id', $this->clientId)
                        ->orWhereHas('billingParties', function ($bq) {
                            $bq->where('client_id', $this->clientId);
                        });
                });
            })
            ->orderBy('start_time')
            ->get();

        $rows = new Collection();

        foreach ($events as $event) {
            $blocked = $this->eventIsBlocked($event);

            // Determine billing parties for this event (matches InvoiceController logic)
            $parties = $event->billingParties;
            if ($parties->isEmpty() && $event->client_id) {
                $parties = collect([
                    (object) [
                        'client_id' => $event->client_id,
                        'is_primary' => true,
                        'client' => $event->client,
                    ],
                ]);
            }

            foreach ($parties as $partyIndex => $party) {
                $client = $party->client ?? null;
                if (! $client) {
                    continue;
                }
                if ($this->clientId && (int) $client->id !== (int) $this->clientId) {
                    continue;
                }

                $amounts = $this->amountsForClientOnEvent($event, $client);
                $total = (float) ($amounts['labor'] + $amounts['buyback'] + $amounts['cups']);

                $alreadyInvoiced = $this->eventAlreadyInvoicedForClient($event, $client);

                $rows->push([
                    'Event ID' => (int) $event->id,
                    'Event Date' => $event->start_time ? Carbon::parse($event->start_time)->format('Y-m-d') : '-',
                    'Event Name' => $event->event_name ?? '-',
                    'Brand' => $event->brand->brand_name ?? '-',
                    'Supplier/Customer' => $client->name ?? '-',
                    'Supplier ID' => (int) $client->id,
                    'Ops Status' => $event->admin_status ?? '-',
                    'Recap Submitted' => $event->recap ? 'Yes' : 'No',
                    'Blocked' => $blocked ? 'Yes' : 'No',
                    'Already Invoiced' => $alreadyInvoiced ? 'Yes' : 'No',
                    'Labor' => (float) $amounts['labor'],
                    'Buyback' => (float) $amounts['buyback'],
                    'Cups' => (float) $amounts['cups'],
                    'Total' => $total,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Event ID',
            'Event Date',
            'Event Name',
            'Brand',
            'Supplier/Customer',
            'Supplier ID',
            'Ops Status',
            'Recap Submitted',
            'Blocked',
            'Already Invoiced',
            'Labor',
            'Buyback',
            'Cups',
            'Total',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ],
        ];
    }

    private function eventIsBlocked(Event $event): bool
    {
        if (in_array($event->admin_status, ['no_show', 'cancelled_no_fee'], true)) {
            return true;
        }

        // Cancelled (matches existing month-invoice guard)
        return (string) $event->event_status === '2';
    }

    private function eventAlreadyInvoicedForClient(Event $event, User $client): bool
    {
        $qbId = $client->clientProfile?->quickbooks_customer_id;
        if (! $qbId) {
            return false;
        }

        if (Invoice::query()
            ->where('event_id', $event->id)
            ->where('customer_id', $qbId)
            ->exists()
        ) {
            return true;
        }

        return InvoiceEventLink::query()
            ->where('event_id', $event->id)
            ->where('client_id', $client->id)
            ->exists();
    }

    /**
     * Match InvoiceController split logic for labor/buyback/cups.
     *
     * @return array{labor:float,buyback:float,cups:float}
     */
    private function amountsForClientOnEvent(Event $event, User $client): array
    {
        $laborTotal     = (float) ($event->labor_amount ?? 0);
        $buybackTotal   = (float) ($event->buyback_amount ?? 0);
        $cupsTotal      = (float) ($event->cups_amount ?? 0);
        $parkingTotal   = (float) ($event->parking_fee  ?? 0);
        $expediteTotal  = (float) ($event->expedite_fee ?? 0);
        $shippingTotal  = (float) ($event->shipping_fee ?? 0);
        $driveTimeTotal = (float) ($event->drive_time   ?? 0);

        // Cancellation variants (match transfer logic)
        if ($event->admin_status === 'cancelled_2_3_business_days_fee_15') {
            $laborTotal     = 15.00;
            $buybackTotal   = 0;
            $cupsTotal      = 0;
            $parkingTotal   = 0;
            $expediteTotal  = 0;
            $shippingTotal  = 0;
            $driveTimeTotal = 0;
        }
        if ($event->admin_status === 'cancelled_24_hours_or_less_full_labor') {
            $cupsTotal = 0;
        }

        $parties = $event->billingParties;
        if ($parties->isEmpty() && $event->client_id) {
            return [
                'labor'        => (float) $laborTotal,
                'buyback'      => (float) $buybackTotal,
                'cups'         => (float) $cupsTotal,
                'parking_fee'  => (float) $parkingTotal,
                'expedite_fee' => (float) $expediteTotal,
                'shipping_fee' => (float) $shippingTotal,
                'drive_time'   => (float) $driveTimeTotal,
            ];
        }

        $partyIndex = null;
        $isPrimary  = false;
        foreach ($parties as $i => $p) {
            if ((int) $p->client_id === (int) $client->id) {
                $partyIndex = (int) $i;
                $isPrimary  = (bool) ($p->is_primary ?? false);
                break;
            }
        }

        if ($partyIndex === null) {
            return ['labor' => 0.0, 'buyback' => 0.0, 'cups' => 0.0,
                    'parking_fee' => 0.0, 'expedite_fee' => 0.0,
                    'shipping_fee' => 0.0, 'drive_time' => 0.0];
        }

        $N = max(1, $parties->count());

        $split = function (float $total) use ($N, $partyIndex): float {
            $per = round($total / $N, 2);
            return ($N > 1 && $partyIndex === $N - 1)
                ? round($total - $per * ($N - 1), 2)
                : $per;
        };

        $laborPerParty = round($laborTotal / $N, 2);
        $laborShare    = ($N > 1 && $partyIndex === $N - 1)
            ? round($laborTotal - $laborPerParty * ($N - 1), 2)
            : $laborPerParty;

        $cupsShare = $isPrimary ? (float) $cupsTotal : 0.0;

        return [
            'labor'        => (float) $laborShare,
            'buyback'      => $split($buybackTotal),
            'cups'         => $cupsShare,
            'parking_fee'  => $split($parkingTotal),
            'expedite_fee' => $split($expediteTotal),
            'shipping_fee' => $split($shippingTotal),
            'drive_time'   => $split($driveTimeTotal),
        ];
    }
}

