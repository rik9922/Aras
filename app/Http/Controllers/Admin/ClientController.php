<?php

namespace App\Http\Controllers\Admin;

use App\Helper\QuickBooksHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SyncAllClientsWithQuickBooks;
use App\Models\Brand;
use App\Models\Client;
use App\Models\EventDistributor;
use App\Models\User;
use App\Notifications\ClientWelcomeNotification;
use App\Services\QuickBooksService;
use App\Services\SimilarBillingClientMatcher;
use App\Trait\EnsuresQuickBooksCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    use EnsuresQuickBooksCustomer;

    protected function clientsIndexRoute(): string
    {
        return auth()->user() && (int) auth()->user()->role_id === 6
            ? 'manager.bills'
            : 'admin.bills';
    }

    protected function routePrefix(): string
    {
        return auth()->user() && (int) auth()->user()->role_id === 6
            ? 'manager'
            : 'admin';
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::where('role_id', 3)->with('clientProfile');
            $routePrefix = $this->routePrefix();

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%");
                });
            }

            if ($request->inactive == '0') {
                $query->where('status', $request->inactive);
            }

            $clients = $query->paginate(20);

            $quickbooksConnected = QuickBooksHelper::isConnected();

            return response()->json([
                'html' => view('admin.client.client_table', compact('clients', 'routePrefix', 'quickbooksConnected'))->render(),
                'totalCount' => $clients->total(),
            ]);
        }

        $clients = User::where('role_id', 3)->with('clientProfile')->orderBy('id', 'DESC')->paginate(20);
        $totalClients = User::where('role_id', 3)->count();
        $unsyncedCount = User::where('role_id', 3)
            ->whereDoesntHave('clientProfile', fn ($q) => $q->whereNotNull('quickbooks_customer_id'))
            ->count();
        $routePrefix = $this->routePrefix();
        $quickbooksConnected = QuickBooksHelper::isConnected();

        return view('admin.client.index', compact('clients', 'totalClients', 'unsyncedCount', 'routePrefix', 'quickbooksConnected'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $brands = Brand::orderBy('id', 'DESC')->get();
        $distributors = EventDistributor::orderBy('id', 'DESC')->get();
        $quickbooksConnected = QuickBooksHelper::isConnected();
        $connectQuickBooksUrl = route($this->routePrefix().'.quickbooks.auth');

        return view('admin.client.create', compact(
            'brands',
            'distributors',
            'quickbooksConnected',
            'connectQuickBooksUrl',
        ));
    }

    /**
     * AJAX: preview next QuickBooks customer Id for the create form.
     */
    public function nextQuickBooksCustomerId(QuickBooksService $quickBooksService): JsonResponse
    {
        if (! QuickBooksHelper::isConnected()) {
            return response()->json([
                'success' => false,
                'message' => 'QuickBooks connection is required before creating clients.',
            ], 403);
        }

        $result = $quickBooksService->previewNextCustomerId();

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    /**
     * AJAX: Bill To default billing amounts for event forms.
     */
    public function billingDefaults(User $client): JsonResponse
    {
        if ((int) $client->role_id !== 3) {
            return response()->json(['success' => false, 'message' => 'Invalid Bill To client.'], 404);
        }

        $profile = $client->clientProfile;

        return response()->json([
            'success'       => true,
            'client_id'     => (int) $client->id,
            'labor_amount'  => $profile && $profile->default_labor_amount !== null   ? (float) $profile->default_labor_amount   : null,
            'buyback_amount'=> $profile && $profile->default_buyback_amount !== null ? (float) $profile->default_buyback_amount : null,
            'cups_amount'   => $profile && $profile->default_cups_amount !== null    ? (float) $profile->default_cups_amount    : null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'first_name'              => 'required|string|max:255',
            'last_name'               => 'required|string|max:255',
            'email'                   => 'required|email|unique:users,email',
            'phone'                   => 'required|string|max:15',
            'brand_id'                => 'required|array|min:1',
            'distributor_id'          => 'required|array|min:1',
            'address'                 => 'required|string|max:255',
            'default_labor_amount'      => 'required|numeric|min:0',
            'default_cups_amount'       => 'required|numeric|min:0',
            'default_receipt_percent'   => 'nullable|integer|min:0|max:100',
            'company_name'              => 'nullable|string|max:255',
            'primary_billing_contact'   => 'nullable|string|max:255',
            'country'                   => 'nullable|string|max:100',
            'city'                      => 'nullable|string|max:100',
            'state'                     => 'nullable|string|max:100',
            'zip_code'                  => 'nullable|string|max:20',
            'notes'                     => 'nullable|string',
            'cc_emails'                 => 'nullable|array',
            'cc_emails.*'               => 'email|max:255',
        ];

        $request->validate($rules);

        $createdUser = null;

        DB::transaction(function () use ($request, &$createdUser) {
            // 1. Create the User (auth record)
            $user = User::create([
                'role_id'    => 3,
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'name'       => $request->first_name.' '.$request->last_name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'status'     => $request->status,
                'password'   => Hash::make($request->password),
            ]);

            // 2. Create the Client profile
            $client = Client::create([
                'user_id'                  => $user->id,
                'address'                  => $request->address,
                'city'                     => $request->input('city'),
                'state'                    => $request->input('state'),
                'zip_code'                 => $request->input('zip_code'),
                'company_name'             => $request->input('company_name'),
                'primary_billing_contact'  => $request->input('primary_billing_contact'),
                'country'                  => $request->input('country') ?: 'USA',
                'notes'                    => $request->input('notes'),
                'cc_emails'                => array_values(array_filter((array) $request->input('cc_emails', []))),
                'contract_recieved'        => $request->boolean('contract_recieved') ? 1 : 0,
                'default_labor_amount'     => $request->default_labor_amount,
                'default_cups_amount'      => $request->default_cups_amount,
                'default_receipt_percent'  => $request->filled('default_receipt_percent') ? (int) $request->default_receipt_percent : null,
            ]);

            // 3. Sync pivot tables
            $brandIds = collect($request->brand_id)->map(fn ($id) => (int) $id)->filter()->all();
            $client->brands()->sync($brandIds);

            $distributorIds = collect($request->distributor_id)->map(fn ($id) => (int) $id)->filter()->all();
            $client->distributors()->sync($distributorIds);

            $user->notify(new ClientWelcomeNotification(
                portalUrl: rtrim((string) config('app.url'), '/'),
                username:  $user->email,
                password:  (string) $request->password,
            ));

            $createdUser = $user;
        });

        $this->queueQuickBooksCustomerSyncIfReady($createdUser?->fresh('clientProfile'));

        return redirect()->route($this->clientsIndexRoute())->with('success', 'Bill To created successfully!');
    }

    /**
     * AJAX: find existing clients (Bill To) similar to the submitted fields.
     */
    public function checkSimilarBillings(Request $request, SimilarBillingClientMatcher $matcher)
    {
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:30',
            'address'    => 'nullable|string|max:500',
        ]);

        $matches = $matcher->findSimilar(
            $request->input('first_name'),
            $request->input('last_name'),
            $request->input('email'),
            $request->input('phone'),
            $request->input('address'),
            $request->integer('exclude_user_id') ?: null,
        );

        $payload = $matches->map(function (array $row) {
            /** @var User $c */
            $c = $row['client'];

            return [
                'id'                     => $c->id,
                'name'                   => trim($c->first_name.' '.$c->last_name) ?: $c->name,
                'email'                  => $c->email,
                'phone'                  => $c->phone,
                'address'                => $c->clientProfile?->address ?? $c->address,
                'quickbooks_customer_id' => $c->clientProfile?->quickbooks_customer_id,
                'status'                 => (string) $c->status === '1' || (int) $c->status === 1 ? 'active' : 'inactive',
                'status_label'           => (string) $c->status === '1' || (int) $c->status === 1 ? 'Active' : 'Inactive',
                'score'                  => round($row['score'], 1),
                'reasons'                => $row['reasons'],
            ];
        })->values();

        return response()->json([
            'matches'     => $payload,
            'has_matches' => $payload->isNotEmpty(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $client)
    {
        abort_unless((int) $client->role_id === 3, 404);
        $client->load('clientProfile.brands', 'clientProfile.distributors');

        return view('admin.client.view-client', compact('client'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $client = User::where('role_id', 3)->with('clientProfile.brands', 'clientProfile.distributors')->findOrFail($id);
        $brands = Brand::orderBy('id', 'DESC')->get();
        $distributors = EventDistributor::orderBy('id', 'DESC')->get();
        $updateClientRoute = route($this->clientsIndexRoute() === 'manager.bills' ? 'manager.update.client' : 'admin.update.client');

        return view('admin.client.edit', compact('client', 'brands', 'distributors', 'updateClientRoute'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateClient(Request $request)
    {
        $request->validate([
            'first_name'              => 'required|string|max:255',
            'last_name'               => 'required|string|max:255',
            'phone'                   => 'required|string|max:15',
            'brand_id'                => 'required|array|min:1',
            'distributor_id'          => 'required|array|min:1',
            'address'                 => 'required|string|max:255',
            'budget_program_name'     => 'nullable|string|max:255',
            'budget_max_amount'       => 'nullable|numeric|min:0',
            'budget_max_events'       => 'nullable|integer|min:0',
            'budget_warn_percent'     => 'nullable|integer|min:1|max:100',
            'budget_alert_email'      => 'nullable|email|max:255',
            'default_labor_amount'      => 'required|numeric|min:0',
            'default_buyback_amount'    => 'nullable|numeric|min:0',
            'default_cups_amount'       => 'required|numeric|min:0',
            'default_receipt_percent'   => 'nullable|integer|min:0|max:100',
            'company_name'            => 'nullable|string|max:255',
            'primary_billing_contact' => 'nullable|string|max:255',
            'country'                 => 'nullable|string|max:100',
            'city'                    => 'nullable|string|max:100',
            'state'                   => 'nullable|string|max:100',
            'zip_code'                => 'nullable|string|max:20',
            'notes'                   => 'nullable|string',
            'cc_emails'               => 'nullable|array',
            'cc_emails.*'             => 'email|max:255',
        ]);

        $user = User::where('role_id', 3)->findOrFail($request->id);

        DB::transaction(function () use ($request, $user) {
            // Update auth fields on User
            $user->first_name = $request->first_name;
            $user->last_name  = $request->last_name;
            $user->name       = $request->first_name.' '.$request->last_name;
            $user->phone      = $request->phone;
            $user->status     = $request->status;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();

            // Update or create Client profile
            $profile = $user->clientProfile ?? new Client(['user_id' => $user->id]);
            $profile->address                  = $request->address;
            $profile->city                     = $request->input('city');
            $profile->state                    = $request->input('state');
            $profile->zip_code                 = $request->input('zip_code');
            $profile->company_name             = $request->input('company_name');
            $profile->primary_billing_contact  = $request->input('primary_billing_contact');
            $profile->country                  = $request->input('country') ?: 'USA';
            $profile->notes                    = $request->input('notes');
            $profile->cc_emails                = array_values(array_filter((array) $request->input('cc_emails', [])));
            $profile->contract_recieved        = $request->boolean('contract_recieved') ? 1 : 0;
            $profile->default_labor_amount     = $request->default_labor_amount;
            $profile->default_buyback_amount   = $request->filled('default_buyback_amount') ? $request->default_buyback_amount : null;
            $profile->default_cups_amount      = $request->default_cups_amount;
            $profile->default_receipt_percent  = $request->filled('default_receipt_percent') ? (int) $request->default_receipt_percent : null;
            $profile->budget_program_name      = $request->input('budget_program_name') ?: null;
            $profile->budget_max_amount        = $request->filled('budget_max_amount') ? $request->budget_max_amount : null;
            $profile->budget_max_events        = $request->filled('budget_max_events') ? $request->budget_max_events : null;
            $profile->budget_warn_percent      = $request->input('budget_warn_percent', $profile->budget_warn_percent ?? 80);
            $profile->budget_enforce           = $request->boolean('budget_enforce');
            $profile->budget_require_approval  = $request->boolean('budget_require_approval');
            $profile->budget_alert_email       = $request->input('budget_alert_email') ?: null;
            $profile->save();

            // Parent Bill To shared fields cascade to children. QuickBooks IDs,
            // contacts, and relationship pivots remain child-specific.
            if ($profile->childBillTos()->exists()) {
                $profile->childBillTos()->update([
                    'address'                 => $profile->address,
                    'city'                    => $profile->city,
                    'state'                   => $profile->state,
                    'zip_code'                => $profile->zip_code,
                    'company_name'            => $profile->company_name,
                    'primary_billing_contact' => $profile->primary_billing_contact,
                    'country'                 => $profile->country,
                    'notes'                    => $profile->notes,
                    'default_labor_amount'    => $profile->default_labor_amount,
                    'default_buyback_amount'  => $profile->default_buyback_amount,
                    'default_cups_amount'     => $profile->default_cups_amount,
                    'default_receipt_percent' => $profile->default_receipt_percent,
                    'contract_recieved'       => $profile->contract_recieved,
                    'cc_emails'                => $profile->cc_emails,
                ]);
            }

            // Sync pivot tables
            $brandIds = collect($request->brand_id)->map(fn ($id) => (int) $id)->filter()->all();
            $profile->brands()->sync($brandIds);

            $distributorIds = collect($request->distributor_id)->map(fn ($id) => (int) $id)->filter()->all();
            $profile->distributors()->sync($distributorIds);
        });

        $this->queueQuickBooksCustomerSyncIfReady($user->fresh('clientProfile'));

        return redirect()->route($this->clientsIndexRoute())->with('success', 'Client updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function showForm()
    {
        return view('client.bank-account');
    }

    public function syncWithQuickBooks()
    {
        if (! QuickBooksHelper::isConnected()) {
            return redirect()->back()->with('error', 'Please connect to QuickBooks before syncing Bill To records.');
        }

        SyncAllClientsWithQuickBooks::dispatch();

        return redirect()->back()->with('success', 'QuickBooks sync started! Clients will be synced in the background.');
    }

    /**
     * Trigger sync for a single client (Bill To) to QuickBooks.
     */
    public function syncClientWithQuickBooks(User $client)
    {
        if (! QuickBooksHelper::isConnected()) {
            return redirect()->back()->with('error', 'Please connect to QuickBooks before syncing this Bill To record.');
        }

        if ((int) $client->role_id !== 3) {
            return redirect()->back()->with('error', 'Only clients can be synced to QuickBooks.');
        }

        $profile = $client->clientProfile;

        if ($profile?->quickbooks_customer_id && $profile?->qb_customer_sync_status !== 'failed') {
            return redirect()->back()->with('success', 'Client is already synced with QuickBooks.');
        }

        if (! $profile?->hasRequiredQuickBooksCustomerData()) {
            return redirect()->back()->with('error', 'Complete the Bill To name, email, phone, and address before syncing to QuickBooks.');
        }

        if (! $this->queueQuickBooksCustomerSyncIfReady($client->fresh('clientProfile'), true)) {
            return redirect()->back()->with('success', 'Client QuickBooks sync is already queued.');
        }

        return redirect()->back()->with('success', 'Client QuickBooks sync queued.');
    }

    public function transactions(Request $request, User $client)
    {
        $profile = $client->clientProfile;

        if (! $profile?->quickbooks_customer_id) {
            return view('admin.client.client-transactions', [
                'client'       => $client,
                'transactions' => [],
                'error'        => 'Client is not synced with QuickBooks.',
            ]);
        }

        $quickBooksService = app(QuickBooksService::class);
        $allTransactions = $quickBooksService->getCustomerTransactions($profile->quickbooks_customer_id);

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $allTransactions = array_filter($allTransactions, function ($transaction) use ($request) {
                $txnDate = isset($transaction->TxnDate) ? \Carbon\Carbon::parse($transaction->TxnDate) : null;
                if (! $txnDate) {
                    return false;
                }
                if ($request->filled('date_from') && $txnDate->lt(\Carbon\Carbon::parse($request->date_from))) {
                    return false;
                }
                if ($request->filled('date_to') && $txnDate->gt(\Carbon\Carbon::parse($request->date_to))) {
                    return false;
                }

                return true;
            });
        }

        $perPage     = 15;
        $currentPage = $request->get('page', 1);
        $offset      = ($currentPage - 1) * $perPage;
        $transactions = array_slice($allTransactions, $offset, $perPage);
        $total       = count($allTransactions);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $transactions,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.client.client-transactions', [
            'client'       => $client,
            'transactions' => $paginator,
            'filters'      => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function syncTransactions(User $client)
    {
        $profile = $client->clientProfile;

        if (! $profile?->quickbooks_customer_id) {
            return redirect()->back()->with('error', 'Client is not synced with QuickBooks.');
        }

        $quickBooksService = app(QuickBooksService::class);
        $quickBooksService->syncInvoices();

        return redirect()->back()->with('success', 'Transactions synced successfully.');
    }
}
