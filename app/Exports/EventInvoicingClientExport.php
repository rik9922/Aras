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
 * Client-wise export for the Invoice Queue.
 * Groups events by client/billing party — one row per client per event.
 */
class EventInvoicingClientExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        private Carbon  $start,
        private Carbon  $end,
        private ?string $clientSearch = null
    ) {}

    public function collection(): Collection
    {
        $query = Event::query()
            ->with(['eventType', 'recap', 'billingParties.client.clientProfile', 'client.clientProfile'])
            ->whereBetween('start_time', [$this->start, $this->end])
            ->where(function ($q) {
                $q->whereNotNull('client_id')->orWhereHas('billingParties');
            })
            ->orderBy('start_time');

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

            // Normalise to a flat list of [client, is_primary]
            if ($parties->isEmpty() && $event->client_id) {
                $resolvedParties = collect([
                    (object) ['client' => $event->client, 'is_primary' => true],
                ]);
            } else {
                $resolvedParties = $parties->map(fn ($p) => (object) [
                    'client'     => $p->client,
                    'is_primary' => (bool) ($p->is_primary ?? false),
                ]);
            }

            $eventDate   = $event->start_time ? Carbon::parse($event->start_time)->format('Y-m-d') : '—';
            $eventStatus = match ((string) $event->event_status) {
                '1' => 'Completed',
                '2' => 'Cancelled',
                default => 'Pending',
            };
            $adminStatus = $event->admin_status ?? '';
            $recapStatus = $event->recap
                ? 'Submitted'
                : ($event->recap_needed
                    ? ($event->recap_status === 'overdue' ? 'Overdue' : 'Pending')
                    : 'N/A');
            $qbStatus = match ($event->qb_publish_status) {
                'success' => 'Synced',
                'failed'  => 'Failed',
                default   => '—',
            };

            foreach ($resolvedParties as $rp) {
                $client = $rp->client;
                if (! $client) {
                    continue;
                }

                // Apply client_search filter at row level (already filtered at DB level,
                // but double-check when legacy client_id path is used)
                if ($this->clientSearch) {
                    $cs = strtolower($this->clientSearch);
                    $match = str_contains(strtolower($client->name ?? ''), $cs)
                        || str_contains(strtolower($client->email ?? ''), $cs)
                        || str_contains(strtolower($client->phone ?? ''), $cs);
                    if (! $match) {
                        continue;
                    }
                }

                $invoiced = $this->eventAlreadyInvoicedForClient($event, $client);

                $eligibility = match (true) {
                    $invoiced => 'Already Invoiced',
                    $adminStatus === 'no_show' => 'No Show',
                    $adminStatus === 'cancelled_no_fee' => 'Cancelled - No Fee',
                    $adminStatus === 'cancelled_2_3_business_days_fee_15' => 'Cancelled - $15 Fee',
                    $adminStatus === 'cancelled_24_hours_or_less_full_labor' => 'Cancelled - Full Labor',
                    ! $client->clientProfile?->quickbooks_customer_id => 'Missing QuickBooks Customer',
                    default => 'Ready',
                };

                $invoiceStatus = $invoiced ? 'Invoiced' : (
                    in_array($adminStatus, ['no_show', 'cancelled_no_fee'], true) ? 'Blocked' : 'Not Invoiced'
                );

                $rows->push([
                    'Client Name'         => $client->name ?? '—',
                    'Client Email'        => $client->email ?? '—',
                    'Client Phone'        => $client->phone ?? '—',
                    'Event Date'          => $eventDate,
                    'Event Name'          => $event->event_name ?? '—',
                    'Event Type'          => $event->eventType?->name ?? '—',
                    'Recap Status'        => $recapStatus,
                    'Event Status'        => $eventStatus,
                    'Invoice Status'      => $invoiceStatus,
                    'Invoice Eligibility' => $eligibility,
                    'QuickBooks Status'   => $qbStatus,
                    'Primary Party'       => $rp->is_primary ? 'Yes' : 'No',
                ]);
            }
        }

        // Sort by client name then event date
        return $rows->sortBy([['Client Name', 'asc'], ['Event Date', 'asc']])->values();
    }

    public function headings(): array
    {
        return [
            'Client Name',
            'Client Email',
            'Client Phone',
            'Event Date',
            'Event Name',
            'Event Type',
            'Recap Status',
            'Event Status',
            'Invoice Status',
            'Invoice Eligibility',
            'QuickBooks Status',
            'Primary Party',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    private function eventAlreadyInvoicedForClient(Event $event, User $client): bool
    {
        $qbId = $client->clientProfile?->quickbooks_customer_id;
        if ($qbId && Invoice::query()
            ->where('event_id', $event->id)
            ->where('customer_id', $qbId)
            ->exists()) {
            return true;
        }

        return InvoiceEventLink::query()
            ->where('event_id', $event->id)
            ->where('client_id', $client->id)
            ->exists();
    }
}
