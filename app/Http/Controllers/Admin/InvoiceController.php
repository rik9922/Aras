<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuickbookInvoiceRequest;
use App\Models\Event;
use App\Models\EventBillingParty;
use App\Models\Invoice;
use App\Models\InvoiceEventLink;
use App\Models\InvoiceLineItem;
use App\Models\QuickBooksTerm;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\QuickBooksService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    protected $quickBooksService;

    public function __construct(QuickBooksService $quickBooksService)
    {
        $this->quickBooksService = $quickBooksService;
    }

    /**
     * Web route prefix for admin vs manager redirects.
     */
    protected function routePrefix(): string
    {
        $u = auth()->user();

        return ($u && (int) $u->role_id === 6) ? 'manager' : 'admin';
    }

    /**
     * Metrics for a billing customer + calendar month of event start_time.
     *
     * @return array{submitted:int,not_recapped:int,no_shows:int,late:int,cancelled:int,complete:bool,total_events:int}
     */
    protected function computeEventMonthStatsForClient(User $user, Carbon $start, Carbon $end): array
    {
        $base = Event::query()
            ->whereBetween('start_time', [$start, $end])
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhereHas('billingParties', function ($bq) use ($user) {
                        $bq->where('client_id', $user->id);
                    });
            });

        $totalEvents = (clone $base)->count();

        $submitted = (clone $base)
            ->whereHas('recap')
            ->count();

        $notRecapped = (clone $base)
            ->where('recap_needed', 1)
            ->where(function ($q) {
                $q->whereDoesntHave('recap')
                    ->orWhere('recap_status', 'overdue');
            })
            ->count();

        $noShows = (clone $base)
            ->where(function ($q) {
                $q->where('admin_status', 'no_show')
                    ->orWhereHas('recap', function ($rq) {
                        $rq->where('manager_no_show', 1);
                    });
            })
            ->count();

        $cancelled = (clone $base)
            ->where('admin_status', 'like', 'cancelled_%')
            ->count();

        $late = (clone $base)
            ->whereHas('recap', function ($rq) {
                $rq->whereRaw('recaps.created_at > DATE_ADD(events.end_time, INTERVAL 24 HOUR)');
            })
            ->count();

        return [
            'submitted' => $submitted,
            'not_recapped' => $notRecapped,
            'no_shows' => $noShows,
            'late' => $late,
            'cancelled' => $cancelled,
            'complete' => $notRecapped === 0,
            'total_events' => $totalEvents,
        ];
    }

    private function eventInvoiceTaxCode(): string
    {
        $raw = Setting::where('name', 'event_invoice_tax')->value('value');
        $data = $raw ? json_decode($raw, true) : [];
        $taxCode = is_array($data) ? ($data['tax_code'] ?? 'NON') : 'NON';

        return in_array($taxCode, ['TAX', 'NON'], true) ? $taxCode : 'NON';
    }

    private function billingClientsForEvent(Event $event)
    {
        $parties = $event->billingParties ?? collect();

        if ($parties->isEmpty() && $event->client_id && $event->client) {
            return collect([$event->client]);
        }

        return $parties
            ->map(fn ($party) => $party->client)
            ->filter()
            ->unique('id')
            ->values();
    }

    private function clientMatchesSupplierSearch(User $client, ?string $search): bool
    {
        $needle = mb_strtolower(trim((string) $search));
        if ($needle === '') {
            return true;
        }

        return str_contains(mb_strtolower((string) $client->name), $needle)
            || str_contains(mb_strtolower((string) $client->email), $needle)
            || str_contains(mb_strtolower((string) $client->phone), $needle);
    }

    private function invoiceRecapBlocker(Event $event): ?string
    {
        if (! $event->recap_needed) {
            return null;
        }

        if (! $event->recap) {
            return $event->recap_status === 'overdue'
                ? 'Required recap is overdue.'
                : 'Required recap has not been submitted.';
        }

        if (! $event->recap->is_approved) {
            return 'Recap has been submitted but is not approved yet.';
        }

        return null;
    }

    private function invoiceQueueEventDetail(Event $event, User $client): array
    {
        $amounts = $this->amountsForClientOnEvent($event, $client);
        $billableTotal = round(
            (float) ($amounts['labor'] ?? 0)
            + (float) ($amounts['buyback'] ?? 0)
            + (float) ($amounts['cups'] ?? 0),
            2
        );

        $alreadyInvoiced = $this->eventAlreadyInvoicedForClient($event, $client);
        $adminStatus = (string) ($event->admin_status ?? '');
        $eventStatus = (string) ($event->event_status ?? '');
        $qbStatus = $event->qb_publish_status;
        $qbError = trim((string) ($event->qb_publish_error ?? ''));
        $recapBlocker = $this->invoiceRecapBlocker($event);

        $recapLabel = 'N/A';
        $recapClass = 'bg-light text-muted border';
        if ($event->recap) {
            if ($event->recap->is_approved) {
                $recapLabel = 'Approved';
                $recapClass = 'bg-success';
            } else {
                $recapLabel = 'Submitted';
                $recapClass = 'bg-warning text-dark';
            }
        } elseif ($event->recap_needed) {
            $recapLabel = $event->recap_status === 'overdue' ? 'Overdue' : 'Missing';
            $recapClass = $event->recap_status === 'overdue' ? 'bg-danger' : 'bg-warning text-dark';
        }

        $issues = [];
        $blocker = null;

        if ($qbStatus === 'failed' && $qbError !== '') {
            $issues[] = $qbError;
        }

        if ($recapBlocker) {
            $issues[] = $recapBlocker;
        }

        if ($alreadyInvoiced) {
            $status = 'invoiced';
            $statusLabel = 'Already Invoiced';
            $statusClass = 'bg-success';
            $blocker = 'This supplier already has an invoice for this event.';
        } elseif ($recapBlocker) {
            $status = 'blocked';
            $statusLabel = 'Recap Not Approved';
            $statusClass = 'bg-warning text-dark';
            $blocker = $recapBlocker;
        } elseif ($adminStatus === 'no_show') {
            $status = 'blocked';
            $statusLabel = 'Blocked';
            $statusClass = 'bg-secondary';
            $blocker = 'No invoice is created for events marked no_show.';
        } elseif ($adminStatus === 'cancelled_no_fee') {
            $status = 'blocked';
            $statusLabel = 'Blocked';
            $statusClass = 'bg-secondary';
            $blocker = 'No invoice is created for cancelled_no_fee events.';
        } elseif ($eventStatus === '2') {
            $status = 'blocked';
            $statusLabel = 'Cancelled';
            $statusClass = 'bg-secondary';
            $blocker = 'Month invoice publishing skips cancelled events.';
        } elseif (! $client->clientProfile?->quickbooks_customer_id) {
            $status = 'blocked';
            $statusLabel = 'Missing QB Customer';
            $statusClass = 'bg-danger';
            $blocker = 'Supplier must be synced with QuickBooks before publishing.';
        } elseif ($billableTotal <= 0) {
            $status = 'blocked';
            $statusLabel = 'No Billable Amount';
            $statusClass = 'bg-light text-muted border';
            $blocker = 'No labor, buyback, or cup amount is available for this supplier on this event.';
        } else {
            $status = 'ready';
            $statusLabel = 'Ready';
            $statusClass = 'bg-success';
        }

        if ($blocker) {
            array_unshift($issues, $blocker);
        }

        return [
            'event' => $event,
            'amounts' => $amounts,
            'billable_total' => $billableTotal,
            'already_invoiced' => $alreadyInvoiced,
            'publishable' => $status === 'ready',
            'status' => $status,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'recap_label' => $recapLabel,
            'recap_class' => $recapClass,
            'qb_status' => $qbStatus,
            'qb_error' => $qbError,
            'issues' => array_values(array_unique(array_filter($issues))),
        ];
    }

    private function supplierInvoiceQueueRows($events, ?string $clientSearch)
    {
        $rows = collect();

        foreach ($events as $event) {
            foreach ($this->billingClientsForEvent($event) as $client) {
                if (! $this->clientMatchesSupplierSearch($client, $clientSearch)) {
                    continue;
                }

                $key = (int) $client->id;
                if (! $rows->has($key)) {
                    $rows->put($key, [
                        'client' => $client,
                        'events' => collect(),
                        'total_amount' => 0.0,
                    ]);
                }

                $detail = $this->invoiceQueueEventDetail($event, $client);
                $row = $rows->get($key);
                $row['events']->push($detail);
                $row['total_amount'] = round($row['total_amount'] + (float) $detail['billable_total'], 2);
                $rows->put($key, $row);
            }
        }

        return $rows
            ->map(function ($row) {
                $events = $row['events'];
                $totalEvents = $events->count();
                $publishable = $events->where('publishable', true)->count();
                $invoiced = $events->where('status', 'invoiced')->count();
                $blocked = $events->where('status', 'blocked')->count();
                $failed = $events->where('qb_status', 'failed')->count();
                $issueCount = $events->filter(fn ($detail) => ! empty($detail['issues']))->count();

                if ($totalEvents > 0 && $invoiced === $totalEvents) {
                    $summary = ['label' => 'Invoiced', 'class' => 'bg-success'];
                } elseif ($failed > 0) {
                    $summary = ['label' => 'Has Failures', 'class' => 'bg-danger'];
                } elseif ($publishable > 0 && $blocked === 0) {
                    $summary = ['label' => 'Ready', 'class' => 'bg-success'];
                } elseif ($publishable > 0) {
                    $summary = ['label' => 'Partially Ready', 'class' => 'bg-warning text-dark'];
                } else {
                    $summary = ['label' => 'Blocked', 'class' => 'bg-secondary'];
                }

                $row['total_events'] = $totalEvents;
                $row['publishable_count'] = $publishable;
                $row['invoiced_count'] = $invoiced;
                $row['blocked_count'] = $blocked;
                $row['failed_count'] = $failed;
                $row['issue_count'] = $issueCount;
                $row['summary'] = $summary;

                return $row;
            })
            ->sortBy(fn ($row) => mb_strtolower((string) $row['client']->name))
            ->values();
    }

    private function paginateSupplierInvoiceRows($rows, Request $request): LengthAwarePaginator
    {
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        ))->withQueryString();
    }

    /**
     * Invoice Queue — supplier-first monthly summary with expandable event details.
     * Display only. Invoice publishing still uses the existing month invoice flow.
     */
    public function eventInvoicing(Request $request)
    {
        $isConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');
        $prefix      = $this->routePrefix();

        // ── Month filter ────────────────────────────────────────────────────
        $monthInput = $request->input('month', now()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
            $monthInput = $monthStart->format('Y-m');
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        // ── Base query — events in selected month that have a billing party ─
        $query = Event::query()
            ->with([
                'eventType',
                'recap',
                'brand',
                'location',
                'client.clientProfile',
                'billingParties.client.clientProfile',
                'ambassador',
            ])
            ->whereBetween('start_time', [$monthStart, $monthEnd])
            ->where(function ($q) {
                // Must have at least one billing party OR a legacy client_id
                $q->whereNotNull('client_id')
                    ->orWhereHas('billingParties');
            })
            ->orderBy('start_time');

        // ── Filters ─────────────────────────────────────────────────────────

        // Event name search
        if ($request->filled('search')) {
            $query->where('event_name', 'like', '%'.$request->search.'%');
        }

        // Client search by name / email / phone
        if ($request->filled('client_search')) {
            $cs = $request->client_search;
            $query->where(function ($q) use ($cs) {
                $q->whereHas('billingParties.client', function ($uq) use ($cs) {
                    $uq->where('name', 'like', '%'.$cs.'%')
                        ->orWhere('email', 'like', '%'.$cs.'%')
                        ->orWhere('phone', 'like', '%'.$cs.'%');
                })
                ->orWhereHas('client', function ($uq) use ($cs) {
                    $uq->where('name', 'like', '%'.$cs.'%')
                        ->orWhere('email', 'like', '%'.$cs.'%')
                        ->orWhere('phone', 'like', '%'.$cs.'%');
                });
            });
        }

        $events = $query->get();
        $supplierRows = $this->paginateSupplierInvoiceRows(
            $this->supplierInvoiceQueueRows($events, $request->input('client_search')),
            $request
        );

        $accountsSynced  = \App\Models\QuickBooksAccount::count() > 0;
        $servicesSynced  = $this->allServicesSynced();
        $qbSetupReady    = $isConnected && $accountsSynced && $servicesSynced;

        return view('admin.invoice.event-invoicing', [
            'isConnected'    => $isConnected,
            'accountsSynced' => $accountsSynced,
            'servicesSynced' => $servicesSynced,
            'qbSetupReady'   => $qbSetupReady,
            'eventInvoiceTaxCode' => $this->eventInvoiceTaxCode(),
            'prefix'         => $prefix,
            'month'          => $monthInput,
            'monthLabel'     => $monthStart->format('F Y'),
            'supplierRows'   => $supplierRows,
            'filters'        => $request->only(['month', 'search', 'client_search']),
        ]);
    }

    /**
     * Phase 2 — Save Draft.
     * Creates (or updates) one Draft Invoice per Billing Party.
     * No QuickBooks API is called.
     */
    public function saveDraft(Request $request, Event $event)
    {
        $prefix = $this->routePrefix();

        $event->load([
            'billingParties.client.clientProfile',
            'client.clientProfile',
        ]);

        $partiesInput = $request->input('parties', []);

        if (empty($partiesInput)) {
            return back()->with('error', 'No invoice data submitted.');
        }

        // ── Validation ──────────────────────────────────────────────
        $errors = [];
        foreach ($partiesInput as $i => $p) {
            $label = 'Client ' . chr(65 + (int) $i);
            if (empty($p['invoice_date'])) {
                $errors[] = "{$label}: Invoice date is required.";
            }
            if (empty($p['due_date'])) {
                $errors[] = "{$label}: Due date is required.";
            }
            if (empty($p['client_id'])) {
                $errors[] = "{$label}: Client is missing.";
            }
        }
        if (! empty($errors)) {
            return back()->with('error', implode(' ', $errors));
        }

        // ── Services map (name key → Service model) ─────────────────
        $servicesMap = Service::where('is_active', true)
            ->get()
            ->keyBy(fn ($s) => mb_strtolower($s->name));

        // Map service_key slug → canonical lowercase name
        $keyToName = [
            'labour_charge' => 'labour charge',
            'buyback'       => 'buyback',
            'cup_charge'    => 'cup charge',
            'parking_fee'   => 'parking fee',
            'expedite_fee'  => 'expedite fee',
            'shipping_fee'  => 'shipping/postage fee',
            'drive_time'    => 'drive-time pay',
        ];

        try {
            DB::transaction(function () use ($event, $partiesInput, $prefix, $servicesMap, $keyToName) {

                foreach ($partiesInput as $i => $p) {
                    $billingPartyId = ! empty($p['billing_party_id']) ? (int) $p['billing_party_id'] : null;
                    $clientId       = ! empty($p['client_id'])       ? (int) $p['client_id']       : null;

                    // Resolve QB customer ID if available
                    $client = $clientId ? User::with('clientProfile')->find($clientId) : null;
                    $qbCustomerId = $client?->clientProfile?->quickbooks_customer_id;

                    // ── Line items ─────────────────────────────────
                    $rawLines  = $p['line_items'] ?? [];
                    $subtotal  = 0.0;
                    $lineRows  = [];

                    foreach ($rawLines as $sortOrder => $line) {
                        $amt = (float) ($line['amount'] ?? 0);
                        $qty = (float) ($line['quantity'] ?? 1);
                        $rate = (float) ($line['rate'] ?? $amt);

                        // Resolve service_id
                        $serviceId = ! empty($line['service_id'])
                            ? (int) $line['service_id']
                            : null;

                        if (! $serviceId && ! empty($line['service_key'])) {
                            $canonicalName = $keyToName[$line['service_key']] ?? null;
                            if ($canonicalName && isset($servicesMap[$canonicalName])) {
                                $serviceId = $servicesMap[$canonicalName]->id;
                            }
                        }

                        $subtotal += $amt;
                        $lineRows[] = [
                            'service_id'  => $serviceId,
                            'description' => $line['description'] ?? '',
                            'quantity'    => $qty,
                            'rate'        => $rate,
                            'amount'      => $amt,
                            'sort_order'  => (int) $sortOrder,
                        ];
                    }

                    // Tax is calculated by QuickBooks from the global invoice tax setting.
                    $taxCode = $this->eventInvoiceTaxCode();
                    $total   = round($subtotal, 2); // subtotal only; QB adds tax after publish

                    $billingAddr = [
                        'line1'                    => $p['billing']['addr1'] ?? '',
                        'line2'                    => $p['billing']['addr2'] ?? '',
                        'city'                     => $p['billing']['city']  ?? '',
                        'country_sub_division_code'=> $p['billing']['state'] ?? '',
                        'postal_code'              => $p['billing']['zip']   ?? '',
                        'country'                  => $p['billing']['country'] ?? '',
                    ];
                    $shippingAddr = [
                        'line1'                    => $p['shipping']['addr1'] ?? '',
                        'line2'                    => $p['shipping']['addr2'] ?? '',
                        'city'                     => $p['shipping']['city']  ?? '',
                        'country_sub_division_code'=> $p['shipping']['state'] ?? '',
                        'postal_code'              => $p['shipping']['zip']   ?? '',
                        'country'                  => $p['shipping']['country'] ?? '',
                    ];

                    // ── Upsert target: update existing Draft, never touch Published ──
                    $existing = Invoice::where('event_id', $event->id)
                        ->where('source', Invoice::SOURCE_APP)
                        ->where(function ($q) use ($billingPartyId, $clientId) {
                            if ($billingPartyId) {
                                $q->where('billing_party_id', $billingPartyId);
                            } else {
                                $q->where('customer_id', function ($sq) use ($clientId) {
                                    $sq->select('quickbooks_customer_id')
                                        ->from('clients')
                                        ->where('user_id', $clientId)
                                        ->limit(1);
                                });
                            }
                        })
                        ->first();

                    $invoiceData = [
                        'event_id'          => $event->id,
                        'billing_party_id'  => $billingPartyId,
                        'user_id'           => auth()->id(),
                        'source'            => Invoice::SOURCE_APP,
                        'status'            => 'Draft',
                        // Priority: 1) user-entered custom number, 2) already-saved number, 3) auto-generate
                        'doc_number'        => (! empty($p['doc_number']))
                                                ? trim($p['doc_number'])
                                                : ($existing?->doc_number ?? Invoice::nextDocNumber()),
                        'customer_id'       => $qbCustomerId,
                        'customer_name'     => $client?->name,
                        'invoice_date'      => $p['invoice_date'],
                        'due_date'          => $p['due_date'],
                        'terms'             => $p['terms'] ?? null,
                        'po_number'         => $p['po_number'] ?? null,
                        'currency_code'     => $p['currency_code'] ?? 'USD',
                        'subtotal'          => $subtotal,
                        'tax_code'          => $taxCode,
                        'tax_rate'          => 0,
                        'tax_amount'        => 0,
                        'total_amount'      => $total,
                        'balance'           => $total,
                        'billing_address'   => $billingAddr,
                        'shipping_address'  => $shippingAddr,
                        'notes_to_customer' => $p['notes'] ?? null,
                        'draft_saved_at'    => now(),
                        // QB fields — untouched in Phase 2
                        'qb_invoice_id'     => null,
                        'qb_publish_status' => null,
                    ];

                    if ($existing) {
                        // Never modify a Published invoice
                        if ($existing->isPublished()) {
                            throw new \RuntimeException(
                                'Cannot overwrite a Published invoice for ' . ($client?->name ?? 'Billing Party ' . ($i + 1)) . '.'
                            );
                        }
                        $existing->update($invoiceData);
                        $invoice = $existing;
                        // Replace line items
                        $invoice->lineItems()->delete();
                    } else {
                        $invoice = Invoice::create($invoiceData);
                    }

                    // ── Persist line items ─────────────────────────
                    foreach ($lineRows as $row) {
                        $invoice->lineItems()->create($row);
                    }
                }
            });

        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to save draft: ' . $e->getMessage());
        }

        return back()->with('success', 'Draft invoices saved successfully.');
    }

    /**
     * Invoice Review — displays one invoice card per billing party for an event.
     * Phase 2: UI only. No save / QuickBooks integration.
     */
    public function invoiceReview(Request $request, Event $event)
    {
        $isConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');
        $prefix      = $this->routePrefix();

        $event->load([
            'eventType',
            'billingParties.client.clientProfile',
            'client.clientProfile',
        ]);

        // Normalise billing parties to a real collection with client loaded
        $billingParties = $event->billingParties;

        // Fallback: legacy single-client events with no billing party rows
        if ($billingParties->isEmpty() && $event->client_id) {
            // Synthesise a pseudo-party so the view stays uniform
            $fakeparty = new \App\Models\EventBillingParty([
                'client_id'  => $event->client_id,
                'is_primary' => true,
                'labor_rate' => $event->labor_amount,
            ]);
            $fakeparty->setRelation('client', $event->client);
            $billingParties = collect([$fakeparty]);
        }

        // Services lookup for + Add Service dropdown
        $availableServices = Service::where('is_active', true)->get();

        // ── Draft hydration ────────────────────────────────────────────────
        // Load any existing app-source Draft invoices for this event,
        // keyed by billing_party_id so the view can look them up per card.
        $draftInvoices = Invoice::where('event_id', $event->id)
            ->where('source', Invoice::SOURCE_APP)
            ->with('lineItems.service')
            ->get()
            ->keyBy('billing_party_id');

        return view('admin.invoice.invoice-review', [
            'isConnected'      => $isConnected,
            'prefix'           => $prefix,
            'event'            => $event,
            'billingParties'   => $billingParties,
            'availableServices'=> $availableServices,
            'draftInvoices'    => $draftInvoices,
            'eventInvoiceTaxCode' => $this->eventInvoiceTaxCode(),
        ]);
    }

    /**
     * Per-customer event list for a month with links to publish each event to QuickBooks.
     */
    public function eventInvoicingShow(Request $request, User $client)
    {
        $monthInput = $request->query('month', now()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
            $monthInput = $monthStart->format('Y-m');
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $events = Event::query()
            ->with(['recap', 'brand', 'location'])
            ->whereBetween('start_time', [$monthStart, $monthEnd])
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                    ->orWhereHas('billingParties', function ($bq) use ($client) {
                        $bq->where('client_id', $client->id);
                    });
            })
            ->orderBy('start_time')
            ->get();

        $stats = $this->computeEventMonthStatsForClient($client, $monthStart, $monthEnd);

        return view('admin.invoice.event-invoicing-show', [
            'client' => $client,
            'month' => $monthInput,
            'monthLabel' => $monthStart->format('F Y'),
            'events' => $events,
            'stats' => $stats,
            'isConnected' => Cache::has('qb_access_token') && Cache::has('qb_realm_id'),
        ]);
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        // Check if QuickBooks is connected
        $isConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');

        if (! $isConnected) {
            return view('admin.invoice.index', [
                'invoices' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'isConnected' => false,
                'message' => 'QuickBooks is not connected. Please connect to view invoices.',
                'filters' => $request->only(['customer', 'status', 'date_from', 'date_to']),
            ]);
        }

        // Sync invoices if requested or if no invoices exist
        if ($request->has('sync') || Invoice::count() == 0) {
            $this->quickBooksService->syncInvoices();
        }

        // Get invoices with pagination and filtering
        $query = Invoice::query();

        // Filter by customer name
        if ($request->filled('customer')) {
            $query->where('customer_name', 'like', '%'.$request->customer.'%');
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(20);

        return view('admin.invoice.index', [
            'invoices' => $invoices,
            'isConnected' => true,
            'filters' => $request->only(['customer', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Sync invoices from QuickBooks
     */
    public function sync()
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            return redirect()->back()->with('error', 'QuickBooks is not connected.');
        }

        $success = $this->quickBooksService->syncInvoices();

        if ($success) {
            return redirect()->back()->with('success', 'Invoices synced successfully from QuickBooks.');
        } else {
            $message = $this->quickBooksService->getLastQbError() ?: 'Failed to sync invoices from QuickBooks.';

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(string $id)
    {
        $invoice = Invoice::with([
            'lineItems.service',
            'event.ambassador',
            'event.location',
            'event.eventType',
            'billingParty.client.clientProfile',
        ])->findOrFail($id);

        $prefix = $this->routePrefix();

        return view('admin.invoice.show', compact('invoice', 'prefix'));
    }

    public function printView(string $id)
    {
        $invoice = Invoice::with([
            'lineItems.service',
            'event.ambassador',
            'event.location',
            'billingParty.client.clientProfile',
        ])->findOrFail($id);

        $prefix         = $this->routePrefix();
        $billingClient  = $invoice->billingParty?->client ?? null;
        $billAddr       = $invoice->billing_address ?? [];
        $event          = $invoice->event;
        $lineItems      = $invoice->lineItems->count() ? $invoice->lineItems : collect();

        $jsonLines = collect();
        if ($lineItems->isEmpty() && ! empty($invoice->line_items)) {
            $raw = is_array($invoice->line_items)
                ? $invoice->line_items
                : (json_decode($invoice->line_items, true) ?? []);
            $jsonLines = collect($raw);
        }

        $receiptPercent = (int) ($billingClient?->clientProfile?->default_receipt_percent ?? 0);

        $subtotal = (float) ($invoice->subtotal ?? $invoice->total_amount ?? 0);
        if ($lineItems->count()) {
            $subtotal = $lineItems->sum(fn ($li) => (float) $li->amount);
        } elseif ($jsonLines->count()) {
            $subtotal = $jsonLines->sum(fn ($li) => (float) ($li['amount'] ?? 0));
        }

        // Add receipt percent amount to subtotal for display
        $totalReceiptAmt = 0;
        if ($lineItems->count() && $receiptPercent > 0) {
            foreach ($lineItems as $li) {
                if ($li->service && mb_strtolower($li->service->name) === 'buyback') {
                    $totalReceiptAmt += round((float) $li->amount * ($receiptPercent / 100), 2);
                }
            }
        }
        $displaySubtotal = round($subtotal + $totalReceiptAmt, 2);

        $taxAmount   = (float) ($invoice->tax_amount ?? 0);
        $totalAmount = (float) ($invoice->total_amount ?? 0);
        $balance     = (float) ($invoice->balance ?? 0);
        $grandTotal  = round($totalAmount + $totalReceiptAmt, 2);
        $invoiceNum  = $invoice->doc_number ?: ('INV-' . $invoice->id);

        return view('admin.invoice.print', compact(
            'invoice', 'prefix', 'billingClient', 'billAddr', 'event',
            'lineItems', 'jsonLines', 'receiptPercent', 'subtotal',
            'displaySubtotal', 'taxAmount', 'totalAmount', 'balance',
            'grandTotal', 'invoiceNum', 'totalReceiptAmt'
        ));
    }

    /**
     * Send invoice email to the billing party client.
     * Reuses ArasBrandedMail pattern via InvoiceClientMail.
     */
    public function sendEmail(string $id)
    {
        $invoice = Invoice::with(['lineItems.service', 'billingParty.client.clientProfile'])->findOrFail($id);

        $client = $invoice->billingParty?->client ?? null;
        $email  = $client?->email ?? null;

        // Fallback: try customer_name via User lookup if no billing party loaded
        if (! $email && $invoice->customer_id) {
            $clientByQb = \App\Models\User::whereHas('clientProfile', fn ($q) =>
                $q->where('quickbooks_customer_id', $invoice->customer_id)
            )->first();
            $email = $clientByQb?->email;
        }

        if (! $email) {
            return response()->json(['success' => false, 'message' => 'No email address found for this billing party.'], 422);
        }

        try {
            $mail = new \App\Mail\InvoiceClientMail($invoice);

            // CC emails from client profile
            $ccEmails = $client?->clientProfile?->cc_emails ?? [];
            $mailer   = \Illuminate\Support\Facades\Mail::to($email);
            if (! empty($ccEmails)) {
                $filtered = array_filter(array_map('trim', (array) $ccEmails));
                if ($filtered) {
                    $mailer = $mailer->cc($filtered);
                }
            }
            $mailer->send($mail);

            $invoice->update(['email_status' => 'EmailSent']);

            \Illuminate\Support\Facades\Log::info('Invoice email sent', ['invoice_id' => $invoice->id, 'to' => $email]);

            return response()->json(['success' => true, 'message' => 'Invoice sent to ' . $email . '.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Invoice email failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    public function generateInvoice(Request $request, Event $event)
    {
        $isConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');
        if (! $isConnected) {
            return redirect()->route($this->routePrefix().'.invoices')->with('error', 'QuickBooks is not connected. Please connect to create invoices.');
        }

        $event->load('client.clientProfile', 'billingParties.client.clientProfile');

        $parties = $event->billingParties;
        if ($parties->isEmpty() && $event->client_id) {
            $parties = collect([(object) ['client_id' => $event->client_id, 'is_primary' => true, 'client' => $event->client]]);
        }

        if ($parties->isEmpty() || ! $event->client) {
            return redirect()->route($this->routePrefix().'.events')->with('error', 'This event does not have a billing party assigned.');
        }

        foreach ($parties as $p) {
            $client = $p->client ?? $event->client;
            if (! $client || ! $client->clientProfile?->quickbooks_customer_id) {
                return redirect()->route($this->routePrefix().'.events')->with('error', 'All billing parties must be synced with QuickBooks before creating an invoice.');
            }
        }

        $customers = User::where('role_id', 3)->with('clientProfile')->get();
        $services = \App\Models\Service::where('is_active', true)->get();

        $multipleBillingParties = $parties->count() > 1;

        return view('admin.invoice.create', [
            'isConnected'            => true,
            'event'                  => $event,
            'customers'              => $customers,
            'services'               => $services,
            'multipleBillingParties' => $multipleBillingParties,
            'billingParties'         => $parties,
        ]);
    }

    /**
     * Create a month-level invoice for a customer, including only events that are not yet invoiced for that customer.
     * If invoked again after new events are added, it will generate a second invoice with an incremented sequence.
     */
    public function createMonthInvoice(Request $request, User $client)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            return redirect()->back()->with('error', 'QuickBooks is not connected.');
        }

        $profile = $client->clientProfile;

        if (! $profile?->quickbooks_customer_id) {
            return redirect()->back()->with('error', 'Customer must be synced with QuickBooks first.');
        }

        $monthInput = $request->query('month', now()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
            $monthInput = $monthStart->format('Y-m');
        }
        $monthEnd = $monthStart->copy()->endOfMonth();
        $monthKey = $monthStart->format('Y-m');
        $monthLabel = $monthStart->format('F Y');

        $events = Event::query()
            ->with(['billingParties', 'recap'])
            ->whereBetween('start_time', [$monthStart, $monthEnd])
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                    ->orWhereHas('billingParties', function ($bq) use ($client) {
                        $bq->where('client_id', $client->id);
                    });
            })
            ->orderBy('start_time')
            ->get();

        // Filter: eligible (not blocked) AND not yet invoiced for this client.
        $eligible = [];
        foreach ($events as $event) {
            if (in_array($event->admin_status, ['no_show', 'cancelled_no_fee'], true)) {
                continue;
            }
            if ((string) $event->event_status === '2') { // cancelled
                continue;
            }

            if ($this->invoiceRecapBlocker($event)) {
                continue;
            }

            if ($this->eventAlreadyInvoicedForClient($event, $client)) {
                continue;
            }

            $amounts = $this->amountsForClientOnEvent($event, $client);
            if (($amounts['labor'] ?? 0) <= 0 && ($amounts['buyback'] ?? 0) <= 0 && ($amounts['cups'] ?? 0) <= 0) {
                continue;
            }

            $eligible[] = ['event' => $event, 'amounts' => $amounts];
        }

        if (empty($eligible)) {
            return redirect()->back()->with('error', "No approved, remaining billable events to invoice for {$client->name} in {$monthLabel}.");
        }

        $sequence = $this->nextMonthInvoiceSequence($client, $monthKey);
        $docNumber = Invoice::nextDocNumber();  // globally unique INV-YYYY-NNNN
        $taxCode = $this->eventInvoiceTaxCode();

        $defaultItemId = (string) config('quickbooks.default_item_id', '1');
        $lines = [];
        $total = 0.0;
        $eventIds = [];

        foreach ($eligible as $row) {
            /** @var \App\Models\Event $event */
            $event = $row['event'];
            $amounts = $row['amounts'];
            $eventIds[] = (int) $event->id;

            $dateLabel = $event->start_time ? Carbon::parse($event->start_time)->format('Y-m-d') : '';

            foreach (['labor' => 'Labor', 'buyback' => 'Buyback', 'cups' => 'Cups'] as $k => $label) {
                $amt = (float) ($amounts[$k] ?? 0);
                if ($amt <= 0) {
                    continue;
                }
                $total += $amt;
                $lines[] = [
                    'Amount'      => $amt,
                    'DetailType'  => 'SalesItemLineDetail',
                    'Description' => trim("Event {$event->id} {$dateLabel} - {$label}"),
                    'SalesItemLineDetail' => [
                        'ItemRef'   => ['value' => $defaultItemId],
                        'Qty'       => 1,
                        'UnitPrice' => $amt,
                        'TaxCodeRef' => ['value' => $taxCode],
                    ],
                ];
            }
        }

        $invoiceData = [
            'DocNumber'   => $docNumber,
            'TxnDate'     => now()->format('Y-m-d'),
            'DueDate'     => now()->addDays(30)->format('Y-m-d'),
            'CustomerRef' => ['value' => $profile->quickbooks_customer_id],
            'Line'        => $lines,
            'PrivateNote' => "Portal month invoice for {$client->name} ({$monthLabel}) ({$sequence}) | Events: ".implode(',', $eventIds),
            'CurrencyRef' => ['value' => 'USD'],
        ];

        $qbInvoice = $this->quickBooksService->createQuickBooksInvoice($invoiceData);
        if (! $qbInvoice) {
            return redirect()->back()->with('error', 'Failed to create month invoice in QuickBooks.');
        }

        DB::transaction(function () use ($client, $profile, $qbInvoice, $docNumber, $monthKey, $monthLabel, $sequence, $total, $lines, $eventIds) {
            $invoice = Invoice::create([
                'event_id'         => null,
                'user_id'          => auth()->id(),
                'qb_invoice_id'    => $qbInvoice->Id,
                'doc_number'       => $qbInvoice->DocNumber ?? $docNumber,
                'customer_name'    => $client->name,
                'customer_id'      => $profile->quickbooks_customer_id,
                'total_amount'     => $total,
                'subtotal'         => $total,
                'tax_code'         => $taxCode,
                'tax_rate'         => 0,
                'tax_amount'       => 0,
                'balance'          => $total,
                'due_date'         => now()->addDays(30)->format('Y-m-d'),
                'invoice_date'     => now()->format('Y-m-d'),
                'status'           => 'Draft',
                'currency_code'    => 'USD',
                'line_items'       => $lines,
                'billing_address'  => [],
                'shipping_address' => [],
                'email_status'     => null,
                'print_status'     => $qbInvoice->PrintStatus ?? 'NotSet',
                'private_note'     => "MONTH:{$monthKey} SEQ:{$sequence} {$monthLabel}",
                'customer_memo'    => null,
                'sync_token'       => $qbInvoice->SyncToken ?? null,
                'created_time'     => $qbInvoice->MetaData->CreateTime ?? now(),
                'last_updated_time' => $qbInvoice->MetaData->LastUpdatedTime ?? now(),
            ]);

            foreach ($eventIds as $eventId) {
                InvoiceEventLink::firstOrCreate([
                    'invoice_id' => $invoice->id,
                    'event_id'   => $eventId,
                    'client_id'  => $client->id,
                ]);
            }
        });

        $suffixLabel = $sequence > 1 ? " ({$sequence})" : '';

        return redirect()
            ->back()
            ->with('success', "Created month invoice{$suffixLabel} for {$client->name} ({$monthLabel}) in QuickBooks.");
    }

    /**
     * Returns true only when all 7 mandatory System Services are synced with QuickBooks.
     * Custom (non-mandatory) services do NOT block invoice creation.
     */
    /**
     * Resolve a dynamic QuickBooks Term ID by display name.
     * Looks up the locally synced quickbooks_terms table — never hardcoded.
     * Returns null when Terms have not been synced yet.
     */
    private function resolveQbTermId(string $termName): ?string
    {
        return QuickBooksTerm::resolveQbTermId($termName);
    }

    protected function allServicesSynced(): bool
    {
        $mandatoryNames = array_map('mb_strtolower', \App\Http\Controllers\Admin\ServiceController::SYSTEM_SERVICES);

        return ! Service::where('is_active', true)
            ->whereNull('qb_item_id')
            ->get()
            ->filter(fn ($s) => in_array(mb_strtolower($s->name), $mandatoryNames, true))
            ->isNotEmpty();
    }

    private function nextMonthInvoiceSequence(User $client, string $monthKey): int
    {
        $prefix = "MONTH:{$monthKey} SEQ:";
        $qbId   = $client->clientProfile?->quickbooks_customer_id;

        $max = Invoice::query()
            ->where('customer_id', $qbId)
            ->whereNotNull('private_note')
            ->where('private_note', 'like', $prefix.'%')
            ->pluck('private_note')
            ->map(function ($note) use ($prefix) {
                $rest = substr((string) $note, strlen($prefix));

                return (int) preg_replace('/[^0-9].*$/', '', $rest);
            })
            ->max();

        return (int) ($max ?? 0) + 1;
    }

    private function eventAlreadyInvoicedForClient(Event $event, User $client): bool
    {
        $qbId = $client->clientProfile?->quickbooks_customer_id;

        if ($qbId && Invoice::query()
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
     * Compute this client's share of labor/buyback/cups for an event, mirroring split logic:
     * - Labor + buyback split evenly across billing parties when there are multiple billing parties.
     * - Cups: if split_cups then split evenly; else cups go to primary billing party.
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

        // Helper: even split with last-party rounding correction
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

        // Cups — primary only
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

    /**
     * Transfer event to QuickBooks with labor and buyback split evenly across billing parties;
     * cups go to primary billing party unless event has split_cups.
     */
    public function transferEventToQuickBooks(Request $request, Event $event)
    {
        if (! \Illuminate\Support\Facades\Cache::has('qb_access_token') || ! \Illuminate\Support\Facades\Cache::has('qb_realm_id')) {
            return redirect()->route($this->routePrefix().'.events')->with('error', 'QuickBooks is not connected.');
        }

        $event->load('billingParties.client');
        $parties = $event->billingParties;

        // Guard: operational statuses that should not create QB invoices
        if (in_array($event->admin_status, ['no_show', 'cancelled_no_fee'], true)) {
            $event->update([
                'qb_publish_status' => 'failed',
                'qb_publish_error' => 'Blocked by event operational status: '.$event->admin_status,
                'qb_published_at' => now(),
            ]);

            return redirect()->back()->with('error', 'This event is marked as '.$event->admin_status.' and should not be transferred to QuickBooks.');
        }

        if ($parties->isEmpty() && $event->client_id) {
            $client = $event->client;
            if ($client) {
                $parties = collect([
                    (object) [
                        'client_id' => $client->id,
                        'is_primary' => true,
                        'client' => $client,
                    ],
                ]);
            }
        }

        if ($parties->isEmpty()) {
            return redirect()->back()->with('error', 'Event has no billing parties.');
        }

        foreach ($parties as $p) {
            $c = $p->client ?? null;
            if (! $c || ! $c->clientProfile?->quickbooks_customer_id) {
                return redirect()->back()->with('error', 'All billing parties must be synced with QuickBooks.');
            }
        }

        $laborTotal       = (float) ($request->input('labor_amount') ?? $event->labor_amount ?? 0);
        $buybackTotal     = (float) ($request->input('buyback_amount') ?? $event->buyback_amount ?? 0);
        $cupsTotal        = (float) ($request->input('cups_amount') ?? $event->cups_amount ?? 0);
        $parkingTotal     = (float) ($event->parking_fee  ?? 0);
        $expediteTotal    = (float) ($event->expedite_fee ?? 0);
        $shippingTotal    = (float) ($event->shipping_fee ?? 0);
        $driveTimeTotal   = (float) ($event->drive_time   ?? 0);

        // Cancellation variants
        if ($event->admin_status === 'cancelled_2_3_business_days_fee_15') {
            $laborTotal    = 15.00;
            $buybackTotal  = 0;
            $cupsTotal     = 0;
            $parkingTotal  = 0;
            $expediteTotal = 0;
            $shippingTotal = 0;
            $driveTimeTotal = 0;
        }

        if ($event->admin_status === 'cancelled_24_hours_or_less_full_labor') {
            $buybackTotal  = (float) ($request->input('buyback_amount') ?? $event->buyback_amount ?? 0);
            $cupsTotal     = 0;
        }

        $N = $parties->count();

        $invoiceDate = $request->input('invoice_date', date('Y-m-d'));
        $dueDate     = $request->input('due_date', date('Y-m-d', strtotime('+30 days')));

        // Helper: split total evenly across N parties (last party absorbs rounding)
        $splitEvenly = function (float $total, int $count, int $index) use ($N): float {
            if ($count <= 0) return 0.0;
            $perParty = round($total / $count, 2);
            return ($count > 1 && $index === $count - 1)
                ? round($total - $perParty * ($count - 1), 2)
                : $perParty;
        };

        $laborPerParty  = $N > 0 ? round($laborTotal / $N, 2) : 0;
        // Cups always go to the primary billing party — no split option.
        $created = 0;
        // doc_number generated per-party using the global unique generator

        foreach ($parties as $index => $party) {
            $client = $party->client ?? User::find($party->client_id);
            if (! $client) {
                continue;
            }
            $clientQbId = $client->clientProfile?->quickbooks_customer_id;

            $isPrimary = $party->is_primary ?? ($index === 0);

            // Labor (per-party rate logic)
            $laborShare = $laborPerParty;
            if ($N > 1 && $index === $N - 1) {
                $laborShare = round($laborTotal - $laborPerParty * ($N - 1), 2);
            }

            // Cups — primary only
            $cupsShare = $isPrimary ? $cupsTotal : 0;

            // Buyback + 4 new fees — all split evenly (same logic)
            $buybackShare  = $splitEvenly($buybackTotal,  $N, $index);
            $parkingShare  = $splitEvenly($parkingTotal,  $N, $index);
            $expediteShare = $splitEvenly($expediteTotal, $N, $index);
            $shippingShare = $splitEvenly($shippingTotal, $N, $index);
            $driveTimeShare = $splitEvenly($driveTimeTotal, $N, $index);

            $amounts = [
                'labor'        => $laborShare,
                'buyback'      => $buybackShare,
                'cups'         => $cupsShare,
                'parking_fee'  => $parkingShare,
                'expedite_fee' => $expediteShare,
                'shipping_fee' => $shippingShare,
                'drive_time'   => $driveTimeShare,
            ];

            $totalAmt = array_sum($amounts);
            if ($totalAmt <= 0) {
                continue;
            }

            $qbInvoice = $this->quickBooksService->createInvoiceForEventBillingParty($client, $amounts, [
                'invoice_date' => $invoiceDate,
                'due_date'     => $dueDate,
                'doc_number'   => Invoice::nextDocNumber(),
                'private_note' => 'Event ID '.$event->id.($event->event_name ? ' - '.$event->event_name : ''),
                'currency_code' => 'USD',
            ]);

            if (! $qbInvoice) {
                continue;
            }

            $lineItems = [
                ['description' => 'Labour Charge',       'quantity' => 1, 'unit_price' => $laborShare,     'amount' => $laborShare,     'item_ref' => null],
                ['description' => 'Buyback',             'quantity' => 1, 'unit_price' => $buybackShare,   'amount' => $buybackShare,   'item_ref' => null],
                ['description' => 'Cup Charge',          'quantity' => 1, 'unit_price' => $cupsShare,      'amount' => $cupsShare,      'item_ref' => null],
                ['description' => 'Parking Fee',         'quantity' => 1, 'unit_price' => $parkingShare,   'amount' => $parkingShare,   'item_ref' => null],
                ['description' => 'Expedite Fee',        'quantity' => 1, 'unit_price' => $expediteShare,  'amount' => $expediteShare,  'item_ref' => null],
                ['description' => 'Shipping/Postage Fee','quantity' => 1, 'unit_price' => $shippingShare,  'amount' => $shippingShare,  'item_ref' => null],
                ['description' => 'Drive-time Pay',      'quantity' => 1, 'unit_price' => $driveTimeShare, 'amount' => $driveTimeShare, 'item_ref' => null],
            ];

            Invoice::create([
                'event_id'         => $event->id,
                'qb_invoice_id'    => $qbInvoice->Id,
                'doc_number'       => $qbInvoice->DocNumber ?? $docBase.($index + 1),
                'customer_name'    => $client->name,
                'customer_id'      => $clientQbId,
                'total_amount'     => $totalAmt,
                'balance'          => $totalAmt,
                'due_date'         => $dueDate,
                'invoice_date'     => $invoiceDate,
                'status'           => $request->input('status', 'Draft'),
                'currency_code'    => 'USD',
                'line_items'       => $lineItems,
                'billing_address'  => [],
                'shipping_address' => [],
                'email_status'     => null,
                'print_status'     => $qbInvoice->PrintStatus ?? 'NotSet',
                'private_note'     => 'Event ID '.$event->id,
                'customer_memo'    => null,
                'sync_token'       => $qbInvoice->SyncToken ?? null,
                'created_time'     => $qbInvoice->MetaData->CreateTime ?? now(),
                'last_updated_time' => $qbInvoice->MetaData->LastUpdatedTime ?? now(),
            ]);
            $created++;
        }

        if ($created === 0) {
            $event->update([
                'qb_publish_status' => 'failed',
                'qb_publish_error' => 'No invoices created (amounts were zero or missing).',
                'qb_published_at' => now(),
            ]);

            return redirect()->back()->with('error', 'No invoices were created. Check that labor, buyback, or cups amounts are set and QuickBooks is connected.');
        }

        $event->update([
            'qb_publish_status' => 'success',
            'qb_publish_error' => null,
            'qb_published_at' => now(),
        ]);

        $successMsg = $created === 1
            ? 'Success: Invoice transferred to QuickBooks.'
            : "Success: {$created} invoices transferred to QuickBooks (split across billing parties).";

        if ($event->client_id) {
            $month = Carbon::parse($event->start_time)->format('Y-m');

            return redirect()
                ->route($this->routePrefix().'.event-invoicing.show', ['client' => $event->client_id, 'month' => $month])
                ->with('success', $successMsg);
        }

        return redirect()->route($this->routePrefix().'.invoices')->with('success', $successMsg);
    }

    /**
     * Phase 3 — Publish Draft Invoices to QuickBooks.
     *
     * For every Draft Invoice belonging to the Event:
     *   1. Skip already-published invoices.
     *   2. Validate QB connection, billing party, QB customer ID, line items, QB item IDs.
     *   3. Build QB payload from the Draft (never from event data).
     *   4. Call existing createQuickBooksInvoice() — no new QB logic.
     *   5. On success: update qb_invoice_id, sync_token, qb_publish_status, qb_published_at, status.
     *   6. On failure: mark failed, continue — one failure never blocks others.
     *   7. Return JSON summary: published / failed / skipped counts + individual errors.
     */
    public function publishDrafts(Request $request, Event $event)
    {
        $prefix = $this->routePrefix();

        // ── 1. QB Connection guard ────────────────────────────────────────
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            return response()->json([
                'success' => false,
                'message' => 'QuickBooks is not connected.',
            ], 422);
        }

        // ── 2. Load Draft invoices for this event (app-source only) ──────
        $drafts = Invoice::where('event_id', $event->id)
            ->where('source', Invoice::SOURCE_APP)
            ->with(['lineItems.service', 'billingParty.client.clientProfile'])
            ->get();

        if ($drafts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No Draft invoices found for this event. Please save a draft first.',
            ], 422);
        }

        $published = 0;
        $failed    = 0;
        $skipped   = 0;
        $errors    = []; // ['party' => label, 'error' => message]

        foreach ($drafts as $invoice) {
            $partyLabel = $invoice->customer_name ?? 'Billing Party #' . $invoice->billing_party_id;

            // ── 3. Skip already-published ─────────────────────────────────
            if ($invoice->isPublished()) {
                $skipped++;
                continue;
            }

            // ── 4. Validate ───────────────────────────────────────────────
            $validationError = null;

            // ── 4a. Billing Party QuickBooks sync check ───────────────────
            // Resolve the client name for friendly error messages.
            $billingClient     = $invoice->billingParty?->client ?? null;
            $billingClientName = $billingClient?->name
                ?? $invoice->customer_name
                ?? 'Billing Party #' . $invoice->billing_party_id;

            // Check 1: customer_id must exist on the invoice (set during saveDraft from clientProfile)
            if (empty($invoice->customer_id)) {
                $validationError = "Client \"{$billingClientName}\" is not synced with QuickBooks. "
                    . 'Please sync this Billing Party from: Bill To → Sync Client to QuickBooks, then publish again.';
            }

            // Check 2: client profile must have quickbooks_customer_id and be in a synced state
            if (! $validationError && $billingClient) {
                $profile   = $billingClient->clientProfile;
                $qbCustId  = $profile?->quickbooks_customer_id;
                $syncStatus = $profile?->qb_customer_sync_status;

                if (empty($qbCustId)) {
                    $validationError = "Client \"{$billingClientName}\" is not synced with QuickBooks. "
                        . 'Please sync this Billing Party from: Bill To → Sync Client to QuickBooks, then publish again.';
                } elseif ($syncStatus !== null && $syncStatus !== 'synced') {
                    // Status exists but is not confirmed synced (e.g. queued, syncing, failed)
                    $validationError = "Client \"{$billingClientName}\" QuickBooks sync status is \"" . $syncStatus . '". '
                        . 'Please wait for sync to complete or re-sync from: Bill To → Sync Client to QuickBooks.';
                }
            }

            // Must have at least one line item
            if (! $validationError && $invoice->lineItems->isEmpty()) {
                $validationError = 'Invoice has no line items.';
            }

            // Every line item must have a QB Item ID via its Service
            if (! $validationError) {
                foreach ($invoice->lineItems as $li) {
                    if ($li->service && empty($li->service->qb_item_id)) {
                        $validationError = "Service '{$li->service->name}' is not synced with QuickBooks (missing QB Item ID).";
                        break;
                    }
                    // Manual line items without a service reference need a fallback item
                    if (! $li->service) {
                        // Use the configured default item; it must exist
                        if (! config('quickbooks.default_item_id')) {
                            $validationError = "Line item '{$li->description}' has no associated service and no default QB Item ID is configured.";
                            break;
                        }
                    }
                }
            }

            if ($validationError) {
                $failed++;
                $errors[] = ['party' => $partyLabel, 'error' => $validationError];
                $invoice->update([
                    'qb_publish_status' => 'failed',
                    'qb_publish_error'  => $validationError,
                ]);
                continue;
            }

            // ── 5. Build QB payload from Draft (never from event) ─────────
            // Receipt percent: load from billing party client profile.
            // Not stored as a line item — added onto the Buyback amount in the QB payload only.
            $receiptPercent = (int) ($invoice->billingParty?->client?->clientProfile?->default_receipt_percent ?? 0);

            $taxCode = $this->eventInvoiceTaxCode();
            $lines = [];
            foreach ($invoice->lineItems->sortBy('sort_order') as $li) {
                $itemId = $li->service?->qb_item_id
                    ?? (string) config('quickbooks.default_item_id', '1');

                // Description priority:
                // 1. service->description (the QB short description stored on the Service)
                // 2. service->name (fallback if description is blank)
                // 3. line item description (manual/generated — last resort)
                $qbLineDescription = '';
                if ($li->service) {
                    $qbLineDescription = trim((string) ($li->service->description ?? ''));
                    if ($qbLineDescription === '') {
                        $qbLineDescription = trim((string) ($li->service->name ?? ''));
                    }
                }
                if ($qbLineDescription === '') {
                    $qbLineDescription = trim((string) ($li->description ?? ''));
                }

                // For Buyback: add receipt percent amount into the QB line amount only.
                // UI shows them separately; QB receives a single merged Buyback line.
                $lineAmount = (float) $li->amount;
                $lineRate   = (float) $li->rate;
                if ($li->service && mb_strtolower($li->service->name) === 'buyback' && $receiptPercent > 0) {
                    $receiptAdd  = round($lineAmount * ($receiptPercent / 100), 2);
                    $lineAmount  = round($lineAmount + $receiptAdd, 2);
                    $lineRate    = $lineAmount; // qty is 1 for buyback
                }

                $lines[] = [
                    'Amount'             => $lineAmount,
                    'DetailType'         => 'SalesItemLineDetail',
                    'Description'        => $qbLineDescription,
                    'SalesItemLineDetail' => [
                        'ItemRef'   => [
                            'value' => $itemId,
                            'name'  => $li->service?->name ?? $li->description,
                        ],
                        'Qty'       => (float) $li->quantity,
                        'UnitPrice' => $lineRate,
                        'TaxCodeRef' => ['value' => $taxCode],
                    ],
                ];
            }

            // Billing address from Draft
            $bill = $invoice->billing_address ?? [];
            $ship = $invoice->shipping_address ?? [];

            $billAddr = array_filter([
                'Line1'                    => $bill['line1']                      ?? null,
                'City'                     => $bill['city']                       ?? null,
                'CountrySubDivisionCode'   => $bill['country_sub_division_code']  ?? null,
                'PostalCode'               => $bill['postal_code']                ?? null,
                'Country'                  => $bill['country']                    ?? null,
            ]);

            $shipAddr = array_filter([
                'Line1'                    => $ship['line1']                      ?? null,
                'City'                     => $ship['city']                       ?? null,
                'CountrySubDivisionCode'   => $ship['country_sub_division_code']  ?? null,
                'PostalCode'               => $ship['postal_code']                ?? null,
                'Country'                  => $ship['country']                    ?? null,
            ]);

            $invoiceData = [
                'DocNumber'   => $invoice->doc_number,
                'TxnDate'     => $invoice->invoice_date?->format('Y-m-d'),
                'DueDate'     => $invoice->due_date?->format('Y-m-d'),
                'CustomerRef' => ['value' => $invoice->customer_id],
                'Line'        => $lines,
                'CurrencyRef' => ['value' => $invoice->currency_code ?? 'USD'],
                'PrivateNote' => 'Event ID ' . $event->id
                    . ($event->event_name ? ' - ' . $event->event_name : '')
                    . ' | App Draft',
            ];

            if ($invoice->notes_to_customer) {
                $invoiceData['CustomerMemo'] = ['value' => $invoice->notes_to_customer];
            }

            if (! empty($bill)) {
                $invoiceData['BillAddr'] = $billAddr;
            }
            if (! empty($ship)) {
                $invoiceData['ShipAddr'] = $shipAddr;
            }

            // ── Email fields — primary + CC ──────────────────────────────
            // Primary email: users.email via billing party client
            $billingClientForEmail = $invoice->billingParty?->client ?? null;
            $primaryEmailAddr = trim((string) ($billingClientForEmail?->email ?? ''));
            if ($primaryEmailAddr !== '') {
                $invoiceData['BillEmail'] = ['Address' => $primaryEmailAddr];
            }

            // CC emails: clients.cc_emails (JSON array)
            // QB Customer objects do NOT support CC fields — CC must be set here on the Invoice.
            // Comma-separated addresses are accepted by QB's BillEmailCc field.
            $rawCcEmails = $billingClientForEmail?->clientProfile?->cc_emails ?? [];
            if (! empty($rawCcEmails)) {
                $ccAddresses = implode(',', array_filter(
                    array_map('trim', (array) $rawCcEmails),
                    fn ($e) => $e !== ''
                ));
                if ($ccAddresses !== '') {
                    $invoiceData['BillEmailCc'] = ['Address' => $ccAddresses];
                }
            }

            // Phase 6 — Dynamic Terms validation
            // Trim the stored value defensively before lookup so whitespace never causes a false miss.
            $invoiceTermsRaw = $invoice->terms;
            $invoiceTerms    = $invoiceTermsRaw !== null ? trim((string) $invoiceTermsRaw) : null;

            if ($invoiceTerms !== null && $invoiceTerms !== '') {
                $qbTermId = $this->resolveQbTermId($invoiceTerms);
                if ($qbTermId === null) {
                    // Distinguish: terms exist in DB but no matching QB record found.
                    // This is a lookup miss, NOT necessarily an un-synced state.
                    $termError = "No matching QuickBooks Term found for invoice term '{$invoiceTerms}'. "
                        . 'Please sync Terms from QuickBooks Settings and ensure the term name matches exactly.';
                    \Illuminate\Support\Facades\Log::warning('publishDrafts: term lookup miss', [
                        'invoice_id'  => $invoice->id,
                        'terms_raw'   => $invoiceTermsRaw,
                        'terms_used'  => $invoiceTerms,
                    ]);
                    $failed++;
                    $errors[] = ['party' => $partyLabel, 'error' => $termError];
                    $invoice->update([
                        'qb_publish_status' => 'failed',
                        'qb_publish_error'  => $termError,
                    ]);
                    continue;
                }
                $invoiceData['SalesTermRef'] = ['value' => $qbTermId];
            }

            // Note: TxnTaxDetail intentionally omitted — IPPTaxLine is not available
            // in this QB SDK version. QB calculates tax server-side from TaxCodeRef on each line.

            // ── 6. Call existing QB invoice creation (no new QB logic) ────
            try {
                $qbInvoice = $this->quickBooksService->createQuickBooksInvoice($invoiceData);
            } catch (\Throwable $e) {
                $qbInvoice = null;
                \Illuminate\Support\Facades\Log::error('publishDrafts: QB exception for ' . $partyLabel, [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            // ── 7. On success ─────────────────────────────────────────────
            if ($qbInvoice && ! empty($qbInvoice->Id)) {
                $invoice->update([
                    'qb_invoice_id'     => $qbInvoice->Id,
                    'sync_token'        => $qbInvoice->SyncToken ?? null,
                    'qb_publish_status' => 'success',
                    'qb_publish_error'  => null,
                    'qb_published_at'   => now(),
                    'status'            => 'Published',
                    'tax_code'          => $taxCode,
                ]);
                $published++;

            // ── 8. On failure — continue, never stop ──────────────────────
            } else {
                $qbErrMsg = $this->quickBooksService->getLastQbError();

                // Convert raw "Invalid Reference Id : Names element id" QB errors
                // into actionable user-friendly guidance instead of exposing API internals.
                if ($qbErrMsg && preg_match('/Names element id/i', $qbErrMsg)) {
                    $errMsg = "Unable to publish invoice for \"{$billingClientName}\". "
                        . 'The Billing Party is not available in the connected QuickBooks company. '
                        . 'If your QuickBooks Sandbox or Company was recently reset, please re-sync the client: '
                        . 'Bill To -> Open the client -> Sync Client to QuickBooks -> then publish again.';
                } elseif ($qbErrMsg) {
                    $errMsg = 'QuickBooks Error: ' . $qbErrMsg;
                } else {
                    $errMsg = 'QuickBooks API returned no invoice. Check QB connection and item setup.';
                }
                $invoice->update([
                    'qb_publish_status' => 'failed',
                    'qb_publish_error'  => $errMsg,
                    // status remains Draft
                ]);
                $failed++;
                $errors[] = ['party' => $partyLabel, 'error' => $errMsg];
            }
        }


        // ── Update events.qb_publish_status to reflect the overall publish result ──
        if ($published > 0 || $failed > 0) {
            $eventQbStatus = ($published > 0 && $failed === 0) ? 'success' : 'failed';
            $event->update([
                'qb_publish_status' => $eventQbStatus,
                'qb_publish_error'  => $failed > 0 ? implode('; ', array_column($errors, 'error')) : null,
                'qb_published_at'   => $published > 0 ? now() : null,
            ]);
        }

        // ── 9. Build summary response ─────────────────────────────────────
        $summary = "Published: {$published} | Failed: {$failed} | Skipped: {$skipped}";

        $success = $published > 0 && $failed === 0;
        $partial = $published > 0 && $failed > 0;

        return response()->json([
            'success'   => $success || $partial,
            'partial'   => $partial,
            'published' => $published,
            'failed'    => $failed,
            'skipped'   => $skipped,
            'summary'   => $summary,
            'errors'    => $errors,
        ]);
    }

    public function storeInvoice(StoreQuickbookInvoiceRequest $request)
    {
        // Validate the request data
        $validated = $request->validated();

        $event = Event::with('client')->findOrFail($request->event_id);

        // Check if client exists and has QuickBooks customer ID
        if (! $event->client) {
            return back()->withErrors(['event_id' => 'Event must have a client assigned.'])->withInput();
        }

        if (! $event->client->clientProfile?->quickbooks_customer_id) {
            return back()->withErrors(['event_id' => 'Client must be synced with QuickBooks first.'])->withInput();
        }

        // Handle custom line items by creating new services if necessary
        $processedLineItems = [];
        $subtotal = 0;
        $taxableSubtotal = 0;
        foreach ($request->line_items ?? [] as $key => $item) {
            $service = null;
            if (empty($item['service_id']) && ! empty($item['name'])) {
                // Create new service
                $serviceData = [
                    'name' => preg_replace('/\s+/', ' ', trim((string) $item['name'])) ?? (string) $item['name'],
                    'rate' => $item['rate'],
                    'taxable' => $item['taxable'] ?? 0,
                    'description' => $item['description'] ?? '',
                ];

                // Prevent duplicates (case/whitespace-insensitive). Reuse existing service if present.
                $existingService = Service::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $serviceData['name'])])
                    ->first();
                if ($existingService) {
                    $service = $existingService;
                    if (! $service->qb_item_id) {
                        $qbItem = $this->quickBooksService->createQuickBooksService($serviceData);
                        if ($qbItem) {
                            $service->update([
                                'qb_item_id' => $qbItem->Id,
                                'sync_token' => $qbItem->SyncToken,
                                'is_active' => true,
                            ]);
                        }
                    }
                }

                $serviceValidator = Validator::make($serviceData, [
                    'name' => 'required|string|max:255|unique:services,name',
                    'rate' => 'nullable|numeric|min:0',
                    'taxable' => 'required|boolean',
                    'description' => 'nullable|string',
                ]);
                if ($serviceValidator->fails()) {
                    return back()->withErrors($serviceValidator)->withInput();
                }
                if (! $service) {
                    $qbItem = $this->quickBooksService->createQuickBooksService($serviceData);
                    if (! $qbItem) {
                        return back()->withErrors(['quickbooks' => 'Failed to create service in QuickBooks.'])->withInput();
                    }
                    $service = Service::create([
                        'name' => $serviceData['name'],
                        'rate' => $serviceData['rate'],
                        'taxable' => $serviceData['taxable'],
                        'description' => $serviceData['description'],
                        'qb_item_id' => $qbItem->Id,
                        'sync_token' => $qbItem->SyncToken,
                        'is_active' => true,
                    ]);
                }
            } else {
                $service = Service::find($item['service_id']);
            }

            if (! $service) {
                return back()->withErrors(['line_items.'.$key => 'Invalid service.'])->withInput();
            }

            $amount = (float) $item['qty'] * (float) $item['rate'];
            $subtotal += $amount;
            if ($item['taxable'] ?? false) {
                $taxableSubtotal += $amount;
            }

            $line = [
                'Amount' => $amount,
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => [
                        'value' => $service->qb_item_id,
                        'name' => $service->name,
                    ],
                    'Qty' => (float) $item['qty'],
                    'UnitPrice' => (float) $item['rate'],
                    'TaxCodeRef' => [
                        'value' => ($item['taxable'] ?? false) ? 'TAX' : 'NON',
                    ],
                ],
                'Description' => $item['description'] ?? '',
            ];
            $processedLineItems[] = $line;
        }

        // Calculate discount
        $discountAmount = 0;
        if ($request->discount) {
            $discountValue = (float) preg_replace('/[^0-9.-]/', '', $request->discount);
            if (str_contains($request->discount, '%')) {
                $discountAmount = $subtotal * ($discountValue / 100);
            } else {
                $discountAmount = $discountValue;
            }
            if ($discountAmount > 0) {
                $processedLineItems[] = [
                    'Amount' => -$discountAmount,
                    'DetailType' => 'DiscountLineDetail',
                    'DiscountLineDetail' => [
                        'PercentBased' => str_contains($request->discount, '%'),
                        'DiscountPercent' => str_contains($request->discount, '%') ? $discountValue : 0,
                        'DiscountAccountRef' => null, // Assume configured in QB
                    ],
                ];
            }
        }

        // Calculate tax
        $taxAmount = $taxableSubtotal * ((float) $request->tax_rate / 100);
        $total = $subtotal - $discountAmount + $taxAmount;

        // Add tax as TxnTaxDetail (assuming a default tax rate ID; replace with actual mapping)
        $taxRateId = '1'; // Placeholder: Map based on $request->tax_rate, e.g., config('quickbooks.tax_rates')[$request->tax_rate]
        $txnTaxDetail = null;
        if ($taxAmount > 0) {
            $txnTaxDetail = [
                'TotalTax' => $taxAmount,
                'TaxLine' => [
                    [
                        'Amount' => $taxAmount,
                        'DetailType' => 'TaxLineDetail',
                        'TaxLineDetail' => [
                            'TaxRateRef' => ['value' => $taxRateId],
                            'PercentBased' => true,
                            'TaxPercent' => (float) $request->tax_rate,
                            'NetAmountTaxable' => $taxableSubtotal,
                        ],
                    ],
                ],
            ];
        }

        // Dynamic Terms lookup — never hardcode QB Term IDs
        $salesTermRef = null;
        if ($request->filled('terms')) {
            $qbTermId = $this->resolveQbTermId($request->terms);
            if ($qbTermId === null) {
                return back()->withErrors([
                    'terms' => 'QuickBooks Terms are not synced. Please sync Terms from QuickBooks Settings before publishing invoices.',
                ])->withInput();
            }
            $salesTermRef = ['value' => $qbTermId];
        }

        // Prepare shipping address
        $shippingAddress = $request->shipping_address;
        if ($request->same_as_billing) {
            $shippingAddress = $request->billing_address;
        }

        // Prepare QB Invoice data
        $docNumber = $request->doc_number ?: Invoice::nextDocNumber();
        $invoiceData = [
            'DocNumber' => $docNumber,
            'TxnDate' => $request->invoice_date,
            'DueDate' => $request->due_date,
            'Line' => $processedLineItems,
            'CustomerRef' => [
                'value' => $event->client->clientProfile?->quickbooks_customer_id,
            ],
            'BillEmail' => [
                'Address' => $request->customer_email,
            ],
            'BillAddr' => array_filter([
                'Line1' => $request->billing_address['street'] ?? null,
                'City' => $request->billing_address['city'] ?? null,
                'CountrySubDivisionCode' => $request->billing_address['state'] ?? null,
                'PostalCode' => $request->billing_address['zip'] ?? null,
                'Country' => $request->billing_address['country'] ?? null,
            ]),
            'ShipAddr' => array_filter([
                'Line1' => $shippingAddress['street'] ?? null,
                'City' => $shippingAddress['city'] ?? null,
                'CountrySubDivisionCode' => $shippingAddress['state'] ?? null,
                'PostalCode' => $shippingAddress['zip'] ?? null,
                'Country' => $shippingAddress['country'] ?? null,
            ]),
            'PrivateNote' => trim(($request->private_note ?? '').' PO: '.($request->po_number ?? '').' Adjustment: '.($request->adjustment_note ?? '')),
            'CustomerMemo' => [
                'value' => $request->customer_memo,
            ],
            'CurrencyRef' => [
                'value' => $request->currency_code,
            ],
            'SalesTermRef' => $salesTermRef,
            'TxnTaxDetail' => $txnTaxDetail,
            // Add other fields as needed, e.g., 'SalesTaxCodeRef' if using sales_tax_category
        ];

        // Create invoice in QuickBooks
        $qbInvoice = $this->quickBooksService->createQuickBooksInvoice($invoiceData);
        if (! $qbInvoice) {
            return back()->withErrors(['quickbooks' => 'Failed to create invoice in QuickBooks.'])->withInput();
        }

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachData = [
                    'FileName' => $file->getClientOriginalName(),
                    'ContentType' => $file->getMimeType(),
                    'Content' => base64_encode(file_get_contents($file->path())),
                    'AttachableRef' => [
                        [
                            'EntityRef' => [
                                'type' => 'Invoice',
                                'value' => $qbInvoice->Id,
                            ],
                        ],
                    ],
                ];
                $this->quickBooksService->createAttachable($attachData);
            }
        }

        // If action is send, send the invoice
        $emailStatus = $qbInvoice->EmailStatus ?? 'NotSent';
        $status = $request->status;
        if ($request->action === 'send') {
            $sendResult = $this->quickBooksService->sendInvoice($qbInvoice->Id);
            if ($sendResult) {
                $emailStatus = 'EmailSent';
                $status = 'Sent';
            } else {
                // Handle send failure if needed
            }
        }

        // Store in local invoices table
        $lineItemsForStorage = [];
        foreach ($request->line_items ?? [] as $item) {
            $lineItemsForStorage[] = [
                'description' => $item['name'] ?? '',
                'quantity' => $item['qty'],
                'unit_price' => $item['rate'],
                'amount' => (float) $item['qty'] * (float) $item['rate'],
                'item_ref' => $item['service_id'] ?? null,
            ];
        }

        $billingAddressForStorage = [
            'line1' => $request->billing_address['street'] ?? '',
            'city' => $request->billing_address['city'] ?? '',
            'country_sub_division_code' => $request->billing_address['state'] ?? '',
            'postal_code' => $request->billing_address['zip'] ?? '',
            'country' => $request->billing_address['country'] ?? '',
        ];

        $shippingAddressForStorage = [
            'line1' => $shippingAddress['street'] ?? '',
            'city' => $shippingAddress['city'] ?? '',
            'country_sub_division_code' => $shippingAddress['state'] ?? '',
            'postal_code' => $shippingAddress['zip'] ?? '',
            'country' => $shippingAddress['country'] ?? '',
        ];

        Invoice::create([
            'event_id' => $request->event_id,
            'qb_invoice_id' => $qbInvoice->Id,
            'doc_number' => $qbInvoice->DocNumber ?? $request->doc_number,
            'customer_name' => $request->customer_name,
            'customer_id'      => $event->client->clientProfile?->quickbooks_customer_id ?? null,
            'total_amount' => $total,
            'balance' => $total,
            'due_date' => $request->due_date,
            'invoice_date' => $request->invoice_date,
            'status' => $status,
            'currency_code' => $request->currency_code,
            'line_items' => $lineItemsForStorage,
            'billing_address' => $billingAddressForStorage,
            'shipping_address' => $shippingAddressForStorage,
            'email_status' => $emailStatus,
            'print_status' => $qbInvoice->PrintStatus ?? 'NotSet',
            'private_note' => $invoiceData['PrivateNote'],
            'customer_memo' => $request->customer_memo,
            'sync_token' => $qbInvoice->SyncToken,
            // 'payment_type' => $event->payment_type ?? null,
            // 'receipt_required' => $event->receipt_required ?? false,
            'created_time' => $qbInvoice->MetaData->CreateTime ?? now(),
            'last_updated_time' => $qbInvoice->MetaData->LastUpdatedTime ?? now(),
        ]);

        $month = Carbon::parse($event->start_time)->format('Y-m');

        return redirect()
            ->route($this->routePrefix().'.event-invoicing.show', ['client' => $event->client_id, 'month' => $month])
            ->with('success', 'Invoice created and synced with QuickBooks successfully.');
    }
}
