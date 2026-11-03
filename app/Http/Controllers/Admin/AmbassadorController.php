<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MissingDwollaProfileDataException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAmbassadorRequest;
use App\Models\Ambassador;
use App\Models\AmbassadorLiquorLicense;
use App\Models\AmbassadorPayout;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\Market;
use App\Models\Recap;
use App\Models\Reimbursement;
use App\Models\State;
use App\Models\User;
use App\Models\UserTransaction;
use App\Notifications\AmbassadorWelcomeNotification;
use App\Services\BankSetupNotificationService;
use App\Services\Dwolla\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AmbassadorController extends Controller
{
    protected $dwollaCustomerService;

    public function __construct(CustomerService $dwollaCustomerService)
    {
        $this->dwollaCustomerService = $dwollaCustomerService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $states = State::get();
        $markets = Market::get();
        $ambassadors = User::where('role_id', 5)
            ->with([
                'ambassadorProfile.markets',
                'ambassadorLiquorLicense',
                'dwollaCustomer.fundingSources',
                'dwollaCustomer.verifiedDefaultFundingSource',
                'lastEvent' => function ($q) {
                    $q->select('events.id', 'event_name', 'end_time')
                        ->orderBy('end_time', 'desc');
                },
            ])
            ->orderBy('id', 'DESC')
            ->paginate(20);

        $totalAmbassadors = $ambassadors->total();

        return view('admin.ambassador.index', compact('ambassadors', 'markets', 'states', 'totalAmbassadors'));
    }

    public function filterAmbassador(Request $request)
    {
        if ($request->ajax()) {
            $query = User::where('role_id', 5);

            \Log::info('state', [$request->state]);

            if ($request->filled('first_name')) {
                $query->where('first_name', 'like', "%{$request->first_name}%");
            }

            if ($request->filled('last_name')) {
                $query->where('last_name', 'like', "%{$request->last_name}%");
            }

            if ($request->filled('email')) {
                $query->where('email', 'like', "%{$request->email}%");
            }

            if ($request->filled('phone')) {
                $query->where('phone', 'like', "%{$request->phone}%");
            }

            if ($request->filled('city')) {
                $query->whereHas('ambassadorProfile', fn($q) =>
                    $q->where('legal_city', 'like', "%{$request->city}%")
                );
            }

            if ($request->filled('state')) {
                $query->whereHas('ambassadorProfile', fn($q) =>
                    $q->where('legal_state', 'like', "%{$request->state}%")
                );
            }

            if ($request->filled('market_id')) {
                $query->whereHas('ambassadorProfile.markets', fn($q) =>
                    $q->where('markets.id', $request->market_id)
                );
            }

            if ($request->filled('region')) {
                $query->whereHas('ambassadorProfile', fn($q) =>
                    $q->where('region', $request->region)
                );
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereHas('ambassadorProfile', fn($q) =>
                    $q->whereBetween('hired', [$request->start_date, $request->end_date])
                );
            }

            if ($request->filled('status')) {
                if ($request->status == '1') {
                    $query->where('status', '1');
                } elseif ($request->status == '0') {
                    $query->where('status', '0');
                }
            }

            if ($request->filled('liquor_license')) {
                if ($request->liquor_license === '1') {
                    $query->whereHas('ambassadorLiquorLicense', function ($q) {
                        $q->where('has_liquor_license', true);
                    });
                } elseif ($request->liquor_license === '0') {
                    $query->where(function ($q) {
                        $q->whereDoesntHave('ambassadorLiquorLicense')
                            ->orWhereHas('ambassadorLiquorLicense', function ($sub) {
                                $sub->where('has_liquor_license', false);
                            });
                    });
                }
            }

            if ($request->filled('bank_setup') && $request->bank_setup !== 'all') {
                $query->bankSetupStatus($request->bank_setup);
            }

            $ambassadors = $query->with([
                'ambassadorProfile.markets',
                'ambassadorLiquorLicense',
                'dwollaCustomer.fundingSources',
                'dwollaCustomer.verifiedDefaultFundingSource',
                'lastEvent' => function ($q) {
                    $q->select('events.id', 'event_name', 'end_time')
                        ->orderBy('end_time', 'desc');
                },
            ])->orderBy('id', 'DESC')->paginate(20);

            return response()->json([
                'tableHtml' => view('admin.ambassador.ambassador_table', compact('ambassadors'))->render(),
                'paginationHtml' => $ambassadors->links('vendor.pagination.custom')->toHtml(),
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $states = State::get();
        $markets = Market::get();

        return view('admin.ambassador.create', compact('states', 'markets'));
    }

    public function show(User $ambassador)
    {
        abort_unless((int) $ambassador->role_id === 5, 404);

        $ambassador->load([
            'ambassadorProfile.markets',
            'ambassadorLiquorLicense',
            'dwollaCustomer.fundingSources',
            'dwollaCustomer.verifiedDefaultFundingSource',
            'w9Form',
        ]);

        $assignmentsQuery = AssignAmbassador::query()
            ->where('ambassador_id', $ambassador->id);

        $recapsQuery = Recap::query()
            ->where('ambassador_id', $ambassador->id);

        $reimbursementsQuery = Reimbursement::query()
            ->where('user_id', $ambassador->id);

        $transactionsQuery = UserTransaction::query()
            ->where('user_id', $ambassador->id);

        $payoutsQuery = AmbassadorPayout::query()
            ->where('user_id', $ambassador->id);

        $stats = [
            'events_total' => (clone $assignmentsQuery)->distinct('event_id')->count('event_id'),
            'events_approved' => (clone $assignmentsQuery)->where('status', '1')->count(),
            'events_pending' => (clone $assignmentsQuery)->where('status', '0')->count(),
            'events_no_show' => (clone $assignmentsQuery)->where('is_no_show', true)->count(),
            'recaps_submitted' => (clone $recapsQuery)->where('is_draft', false)->count(),
            'recaps_approved' => (clone $recapsQuery)->where('is_approved', true)->count(),
            'recaps_draft' => (clone $recapsQuery)->where('is_draft', true)->count(),
            'reimbursements_total' => (clone $reimbursementsQuery)->count(),
            'reimbursements_paid' => (clone $reimbursementsQuery)->where('status', 'paid')->count(),
            'reimbursements_amount' => (float) (clone $reimbursementsQuery)->sum('amount'),
            'transactions_total' => (clone $transactionsQuery)->count(),
            'transactions_amount' => (float) (clone $transactionsQuery)->sum('amount'),
            'payouts_total' => (clone $payoutsQuery)->count(),
            'payouts_completed' => (clone $payoutsQuery)->where('status', 'completed')->count(),
            'payouts_amount' => (float) (clone $payoutsQuery)->sum('amount'),
        ];

        $assignments = (clone $assignmentsQuery)
            ->with(['event.brand', 'event.location', 'event.markets'])
            ->latest('id')
            ->limit(10)
            ->get();

        $recaps = (clone $recapsQuery)
            ->with(['event.brand', 'event.location'])
            ->latest('id')
            ->limit(10)
            ->get();

        $reimbursements = (clone $reimbursementsQuery)
            ->with(['event.brand', 'brand'])
            ->latest('id')
            ->limit(10)
            ->get();

        $transactions = (clone $transactionsQuery)
            ->latest('occurred_at')
            ->latest('id')
            ->limit(10)
            ->get();

        $payouts = (clone $payoutsQuery)
            ->with(['event.brand'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.ambassador.show', compact(
            'ambassador',
            'stats',
            'assignments',
            'recaps',
            'reimbursements',
            'transactions',
            'payouts'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAmbassadorRequest $request)
    {
        $request->validated();

        DB::beginTransaction();

        try {
            // ── users table: auth + identity only ──────────────────────────────
            $user = new User;
            $user->role_id   = 5;
            $user->first_name = $request->first_name;
            $user->last_name  = $request->last_name;
            $user->name       = $request->first_name . ' ' . $request->last_name;
            $user->email      = $request->email;
            $user->phone      = $request->phone;
            $user->status     = $request->status;
            $user->password   = Hash::make($request->password);
            $user->save();

            // ── ambassadors table: all profile-specific data ────────────────
            $ambassadorData = $this->buildAmbassadorData($request);
            $ambassadorData['user_id'] = $user->id;
            $ambassador = Ambassador::create($ambassadorData);

            // ── Pivot: attach selected Markets via ambassador_market table ─────
            $ambassador->markets()->attach((array) $request->market_id);

            AmbassadorLiquorLicense::create([
                'user_id' => $user->id,
                'has_liquor_license' => $request->boolean('has_liquor_license'),
                'license_number' => $request->license_number,
                'issuing_state' => $request->issuing_state,
                'issued_at' => $request->issued_at,
                'expires_at' => $request->expires_at,
                'document_on_file' => $request->boolean('document_on_file'),
                'notes' => $request->notes,
            ]);

            // Reload ambassador profile so Dwolla eligibility check reads the
            // newly created ambassadors row (legal_state etc.) via proxy accessors.
            $user->load('ambassadorProfile');
            $user->refresh();

            $dwollaNotice = null;
            if ($this->dwollaCustomerService->hasRequiredDwollaData($user)) {
                try {
                    $this->dwollaCustomerService->create($user);
                } catch (MissingDwollaProfileDataException $e) {
                    $dwollaNotice = 'Ambassador was added, but payment account setup is incomplete. Missing: '
                        .implode(', ', array_values($e->missingFields)).'.';
                } catch (\Exception $e) {
                    $dwollaNotice = 'Ambassador was added, but payment account setup could not be completed. '
                        .$e->getMessage();
                }
            } else {
                $missing = $this->dwollaCustomerService->getMissingDwollaFields($user);
                $dwollaNotice = 'Ambassador was added. Payment account setup is pending until they provide: '
                    .implode(', ', array_values($missing)).'.';
            }

            $user->notify(new AmbassadorWelcomeNotification(
                portalUrl: rtrim((string) config('app.url'), '/'),
                username: $user->email,
                password: (string) $request->password,
            ));

            DB::commit();

            $prefix = ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
            $redirect = redirect()
                ->route($prefix.'.ambassadors')
                ->with('success', 'Ambassador added successfully!');

            if ($dwollaNotice) {
                $redirect->with('warning', $dwollaNotice);
            }

            return $redirect;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create ambassador.', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'We could not add the ambassador. Please review the form and try again.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $ambassador = User::with('ambassadorLiquorLicense')->findOrFail($id);

        // Get assigned event IDs
        $assignAmb = AssignAmbassador::where('ambassador_id', $ambassador->id)
            ->pluck('event_id')
            ->toArray();

        // Get last event
        $lastEvent = Event::select('id', 'event_name', 'end_time')
            ->whereIn('id', $assignAmb)
            ->orderBy('end_time', 'desc')
            ->first();

        $states = State::get();
        $markets = Market::get();

        // Load pivot markets so the edit view can pre-select them
        $ambassador->load('ambassadorProfile.markets');
        $selectedMarketIds = $ambassador->ambassadorProfile?->markets->pluck('id')->toArray() ?? [];

        return view('admin.ambassador.edit', compact('ambassador', 'states', 'markets', 'lastEvent', 'selectedMarketIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateAmbassador(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'venmo' => 'nullable|string',
            // 'instagram' => 'nullable|string|unique:users,instagram,'.$request->id,
            'nickname' => 'nullable|string',
            'market_id'   => 'required|array|min:1',
            'market_id.*' => 'integer|exists:markets,id',
            'wage' => 'required|numeric|min:0.01',
            'hired' => 'required|date',
            'shirt_size' => 'required',
            'region' => 'required|string|in:Southeast,Midwest',
            'dob' => 'required',

            // Legal address — state stored as short code (e.g. NY, CA)
            'legal_address'   => 'required|string|max:255',
            'legal_city'      => 'required|string|max:255',
            'legal_state'     => 'required|string|max:3',
            'legal_zip_code'  => 'required|string|max:10',
            // Mailing address (optional)
            'mailing_address' => 'nullable|string|max:255',
            'mailing_city'    => 'nullable|string|max:255',
            'mailing_state'   => 'nullable|string|max:3',
            'mailing_zip_code'=> 'nullable|string|max:10',
            // Same as legal checkbox
            'same_as_legal' => 'boolean',

            'titos_trained' => 'boolean',
            'thc_quiz_passed' => 'boolean',
            'male_ba_lgbtq_ok' => 'boolean',
            'attended_trainings' => 'boolean',
            'tips_certified' => 'boolean',
            'has_permit' => 'boolean',

            'has_liquor_license' => 'boolean',
            'license_number' => 'nullable|string|max:100',
            'issuing_state' => 'nullable|string|max:50',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:issued_at',
            'document_on_file' => 'boolean',
            'notes' => 'nullable|string|max:2000',

            'favorite_coffee_shop' => 'nullable|string|max:255',
            'favorite_restaurant' => 'nullable|string|max:255',
            'favorite_place_to_shop' => 'nullable|string|max:255',
            'favorite_charity' => 'nullable|string|max:255',

            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $id   = $request->id;
        $user = User::with('ambassadorProfile')->findOrFail($id);

        // ── users table: auth + identity only ────────────────────────────────
        $user->role_id    = 5;
        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->name       = $request->first_name . ' ' . $request->last_name;
        $user->phone      = $request->phone;
        $user->status     = $request->status;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $this->saveProfileImage($user, $request);
        $user->save();

        // ── ambassadors table: all profile-specific data ─────────────────────
        $existingProfile = $user->ambassadorProfile;
        $ambassadorData  = $this->buildAmbassadorData($request, $existingProfile?->getRawOriginal('profile_image'));
        $ambassadorRow   = $user->ambassadorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $ambassadorData
        );

        // ── Pivot: sync selected Markets via ambassador_market table ────────
        $ambassadorRow->markets()->sync((array) $request->market_id);

        $user->ambassadorLiquorLicense()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'has_liquor_license' => $request->boolean('has_liquor_license'),
                'license_number' => $request->license_number,
                'issuing_state' => $request->issuing_state,
                'issued_at' => $request->issued_at,
                'expires_at' => $request->expires_at,
                'document_on_file' => $request->boolean('document_on_file'),
                'notes' => $request->notes,
            ]
        );

        if ($request->filled('password')) {
            $user->notify(new AmbassadorWelcomeNotification(
                portalUrl: rtrim((string) config('app.url'), '/'),
                username: $user->email,
                password: (string) $request->password,
            ));
        }

        $prefix = ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        return redirect()->route($prefix.'.ambassadors')->with('success', 'Ambassador updated successfully!');
    }


    /**
     * Build the ambassadors table data from an incoming request.
     * Handles same_as_legal mirror and profile image path.
     * State is now stored as short code (e.g. "NY") directly — no state_id lookup.
     */
    private function buildAmbassadorData(Request $request, ?string $existingImage = null): array
    {
        $sameAsLegal  = $request->boolean('same_as_legal');
        $mailingAddr  = $sameAsLegal ? $request->legal_address   : $request->mailing_address;
        $mailingCity  = $sameAsLegal ? $request->legal_city      : $request->mailing_city;
        $mailingState = $sameAsLegal ? $request->legal_state      : $request->mailing_state;
        $mailingZip   = $sameAsLegal ? $request->legal_zip_code  : $request->mailing_zip_code;

        $data = [
            'dob'                    => $request->dob,
            'nickname'               => $request->nickname,
            'venmo'                  => $request->venmo,
            'region'                 => $request->region,
            'hired'                  => $request->hired,
            'wage'                   => $request->wage,
            'shirt_size'             => $request->shirt_size,
            'latitude'               => $request->latitude,
            'longitude'              => $request->longitude,

            // Legal address — state stored as short code
            'legal_address'          => $request->legal_address,
            'legal_city'             => $request->legal_city,
            'legal_state'            => strtoupper(trim((string) $request->legal_state)),
            'legal_zip_code'         => $request->legal_zip_code,

            // Mailing address
            'same_as_legal'          => $sameAsLegal,
            'mailing_address'        => $mailingAddr,
            'mailing_city'           => $mailingCity,
            'mailing_state'          => $mailingState ? strtoupper(trim((string) $mailingState)) : null,
            'mailing_zip_code'       => $mailingZip,

            // Certifications / flags
            'titos_trained'          => $request->boolean('titos_trained'),
            'thc_quiz_passed'        => $request->boolean('thc_quiz_passed'),
            'male_ba_lgbtq_ok'       => $request->boolean('male_ba_lgbtq_ok'),
            'attended_trainings'     => $request->boolean('attended_trainings'),
            'tips_certified'         => $request->boolean('tips_certified'),
            'has_permit'             => $request->boolean('has_permit'),

            // About fields
            'favorite_coffee_shop'   => $request->favorite_coffee_shop,
            'favorite_restaurant'    => $request->favorite_restaurant,
            'favorite_place_to_shop' => $request->favorite_place_to_shop,
            'favorite_charity'       => $request->favorite_charity,
        ];

        // profile_image belongs to users table — handled separately via saveProfileImage()
        return $data;
    }

    // ── Legacy method kept for backward compat — no longer used in create/update
    private function syncLegacyAddressFromLegal(User $user, Request $request): void
    {
        // no-op: address data now stored in ambassadors table
    }

    private function saveProfileImage(User $user, Request $request): void
    {
        if (! $request->hasFile('profile_image')) {
            return;
        }

        $file = $request->file('profile_image');
        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('profile'), $filename);
        $user->profile_image = 'profile/'.$filename;
        // save() is called by the caller after this method returns
    }

    public function sendBankingReminder(Request $request, User $ambassador, BankSetupNotificationService $bankSetupNotifications)
    {
        if ((int) $ambassador->role_id !== 5) {
            return back()->with('error', 'Invalid ambassador.');
        }

        if ($ambassador->hasVerifiedBankAccount()) {
            return back()->with('error', 'This ambassador already has a verified bank account.');
        }

        $bankSetupNotifications->sendSetupReminder($ambassador, auth()->id());

        return back()->with('success', 'Banking setup reminder sent to '.$ambassador->name.'.');
    }

    public function sendBulkBankingReminder(Request $request, BankSetupNotificationService $bankSetupNotifications)
    {
        $request->validate([
            'ambassador_ids' => 'required|array|min:1',
            'ambassador_ids.*' => 'integer|exists:users,id',
        ]);

        $ambassadors = User::where('role_id', 5)
            ->whereIn('id', $request->ambassador_ids)
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($ambassadors as $ambassador) {
            if ($bankSetupNotifications->sendSetupReminder($ambassador, auth()->id())) {
                $sent++;
            } else {
                $skipped++;
            }
        }

        if ($sent === 0) {
            return back()->with('error', 'No reminders were sent. Selected ambassadors may already have verified bank accounts.');
        }

        $message = "Banking setup reminder sent to {$sent} ambassador(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} already verified and were skipped.";
        }

        return back()->with('success', $message);
    }

    public function removeMarkets(Request $request, User $ambassador)
    {
        abort_unless((int) $ambassador->role_id === 5, 404);

        $validated = $request->validate([
            'market_ids' => 'required|array|min:1',
            'market_ids.*' => 'integer|exists:markets,id',
        ]);

        $profile = $ambassador->ambassadorProfile;

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Ambassador profile was not found.',
            ], 404);
        }

        $profile->markets()->detach($validated['market_ids']);
        $remainingMarkets = $profile->markets()->orderBy('name')->get(['markets.id', 'markets.name']);

        return response()->json([
            'success' => true,
            'markets' => $remainingMarkets->map(fn ($market) => [
                'id' => $market->id,
                'name' => $market->name,
            ])->values(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if ($user) {
            $user->ambassadorLiquorLicense()->delete();
            $user->delete();

            $prefix = ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
            return redirect()->route($prefix.'.ambassadors')->with('success', 'Ambassador Deleted Successfully!');
        }
    }
}
