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

/**
 * Event-wise export for the Invoice Queue.
 * One row per event (not per billing party).
 */
class EventInvoicingEventExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        private Carbon  $start,
        private Carbon  $end,
        private ?string $eventSearch = null,
        private ?string $clientSearch = null
    ) {}

    public function collection(): Collection
    {
        $query = Event::query()
            ->with(['eventType', 'recap', 'billingParties.client.clientProfile', 'client'])
            ->whereBetween('start_time', [$this->start, $this->end])
            ->where(function ($q) {
                $q->whereNotNull('client_id')->orWhereHas('billingParties');
            })
            ->orderBy('start_time');

        if ($this->eventSearch) {
            $query->where('event_name', 'like', '%' . $this->eventSearch . '%');
        }

        if ($this->clientSearch) {
            $cs = $this->clientSearch;
            $query->where(function ($q) use ($cs) {
                $q->whereHas('billingParties.client', function ($uq) use ($cs) {
                    $uq->where('name', 'like', '%' . $cs . '%')
                        ->orWhere('email', 'like', '%' . $cs . '%')
                        ->orWhere('phone', 'like', '%' . $cs . '%');
                })->orWhereHas('client', function ($uq) use ($cs) {
                    $uq->where('name', 'like', '%' . $cs . '%')
                        ->orWhere('email', 'like', '%' . $cs . '%')
                        ->orWhere('phone', 'like', '%' . $cs . '%');
                });
            });
        }

        $events = $query->get();
        $rows   = new Collection();

        foreach ($events as $event) {
            $parties = $event->billingParties;
            if ($parties->isEmpty() && $event->client_id) {
                $billingNames = $event->client?->name ?? '—';
            } else {
                $billingNames = $parties->map(fn ($p) => $p->client?->name ?? '?')->implode(', ');
            }

            $invoiced    = $event->getIsInvoiceCreatedAttribute();
            $adminStatus = $event->admin_status;

            $eligibility = match (true) {
                $invoiced => 'Already Invoiced',
                $adminStatus === 'no_show' => 'No Show',
                $adminStatus === 'cancelled_no_fee' => 'Cancelled - No Fee',
                $adminStatus === 'cancelled_2_3_business_days_fee_15' => 'Cancelled - $15 Fee',
                $adminStatus === 'cancelled_24_hours_or_less_full_labor' => 'Cancelled - Full Labor',
                default => $this->checkMissingQb($event, $parties) ? 'Missing QuickBooks Customer' : 'Ready',
            };

            $eventStatus = match ((string) $event->event_status) {
                '1' => 'Completed',
                '2' => 'Cancelled',
                default => 'Pending',
            };

            $invoiceStatus = $invoiced ? 'Invoiced' : (
                in_array($adminStatus, ['no_show', 'cancelled_no_fee'], true) ? 'Blocked' : 'Not Invoiced'
            );

            $qbStatus = match ($event->qb_publish_status) {
                'success' => 'Synced',
                'failed'  => 'Failed',
                default   => '—',
            };

            $recapStatus = $event->recap
                ? 'Submitted'
                : ($event->recap_needed
                    ? ($event->recap_status === 'overdue' ? 'Overdue' : 'Pending')
                    : 'N/A');

            $rows->push([
                'Event Date'          => $event->start_time ? Carbon::parse($event->start_time)->format('Y-m-d') : '—',
                'Event Name'          => $event->event_name ?? '—',
                'Event Type'          => $event->eventType?->name ?? '—',
                'Billing Parties'     => $billingNames,
                'Recap Status'        => $recapStatus,
                'Event Status'        => $eventStatus,
                'Invoice Status'      => $invoiceStatus,
                'Invoice Eligibility' => $eligibility,
                'QuickBooks Status'   => $qbStatus,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Event Date',
            'Event Name',
            'Event Type',
            'Billing Parties',
            'Recap Status',
            'Event Status',
            'Invoice Status',
            'Invoice Eligibility',
            'QuickBooks Status',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    private function checkMissingQb(Event $event, $parties): bool
    {
        $checkParties = $parties->isEmpty() && $event->client_id
            ? collect([$event->client])
            : $parties->map(fn ($p) => $p->client);

        foreach ($checkParties as $c) {
            if (! $c || ! $c->clientProfile?->quickbooks_customer_id) {
                return true;
            }
        }

        return false;
    }
}
