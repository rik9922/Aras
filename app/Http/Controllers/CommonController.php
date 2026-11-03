<?php

namespace App\Http\Controllers;

use App\Jobs\InitiateAmbassadorPayout;
use App\Mail\RecapApprovedMail;
use App\Mail\RecapDeniedMail;
use App\Models\AmbassadorPayout;
use App\Models\Event;
use App\Models\EventLocation;
use App\Models\Recap;
use App\Models\State;
use App\Models\User;
use App\Models\Venue;
use App\Services\BankSetupNotificationService;
use App\Services\BEST\EventPushService;
use App\Services\ExpeditedPayoutCalculator;
use Illuminate\Http\Request;
use App\Services\Dwolla\CustomerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CommonController extends Controller
{
    public function profile()
    {
        $user = User::with('ambassadorProfile.markets')
            ->where('id', Auth::id())
            ->first();

        $liquor = $user?->ambassadorLiquorLicense;
        $profile = $user?->ambassadorProfile;

        // The state dropdowns use state IDs, but ambassadors table stores short codes (e.g. "NY").
        // Resolve short code → state ID so the selects pre-select correctly.
        $legalStateId   = $profile?->legal_state
            ? \App\Models\State::where('short_name', $profile->legal_state)->value('id')
            : null;
        $mailingStateId = $profile?->mailing_state
            ? \App\Models\State::where('short_name', $profile->mailing_state)->value('id')
            : null;

        return view('ambassador.profile', compact('user', 'liquor', 'profile', 'legalStateId', 'mailingStateId'));
    }

    public function updateProfile(Request $request)
    {

        $id = Auth::id();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            // 'address' => 'required|string|max:255',
            'phone' => 'required|string|max:15|unique:users,phone,'.$id,
            // 'state_id' => 'required',
            // 'state' => 'required',
            'venmo' => 'nullable|string',
            // 'instagram' => 'nullable|string|unique:users,instagram,'.$id,
            'nickname' => 'nullable|string',
            // 'city' => 'required',
            'market_id' => 'required|array|min:1',
            'dob' => 'required',

            // Legal address fields (required)
            'legal_address' => 'required|string|max:255',
            'legal_city' => 'required|string|max:255',
            'legal_state_id' => 'required',
            'legal_zip_code' => 'required|numeric|digits-between:5,6',
            // Mailing address fields (optional)
            'mailing_address' => 'nullable|string|max:255',
            'mailing_city' => 'nullable|string|max:255',
            'mailing_state_id' => 'nullable',
            'mailing_zip_code' => 'nullable|numeric|digits-between:5,6',
            // Same as legal checkbox
            'same_as_legal' => 'boolean',

            // Certifications & preferences are NOT accepted here — they are managed
            // by Admins/Managers only. Any submitted cert values are ignored.

            // Liquor license details (ambassador self-service)
            'has_liquor_license' => 'boolean',
            'license_number' => 'nullable|string|max:100',
            'issuing_state' => 'nullable|string|max:50',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'document_on_file' => 'boolean',
            'notes' => 'nullable|string|max:2000',

            // Nullable to match the mobile API. These come from the optional map
            // picker; requiring them blocked ambassadors who never set a location
            // (the confusing "latitude field is required" save failure). Existing
            // coordinates are preserved below when none are submitted.
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

            'favorite_coffee_shop' => 'nullable|string|max:255',
            'favorite_restaurant' => 'nullable|string|max:255',
            'favorite_place_to_shop' => 'nullable|string|max:255',
            'favorite_charity' => 'nullable|string|max:255',
        ]);

        $user = User::find($id);

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('profile'), $filename);
            $user->profile_image = 'profile/'.$filename;
        }

        // ── users table: identity + auth fields only ────────────────────────
        $user->role_id    = 5;
        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->name       = $request->first_name . ' ' . $request->last_name;
        $user->phone      = $request->phone;
        $user->status     = $request->status ?? 1;
        $user->market_id  = is_array($request->market_id) ? implode(',', $request->market_id) : implode(',', $user->market_id ?? []);
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // ── Resolve state IDs to short codes for ambassadors table ──────────
        // The blade sends legal_state_id / mailing_state_id (integer PK from states table).
        // The ambassadors table stores short codes (e.g. "NY", "CA").
        $legalStateShort   = null;
        $mailingStateShort = null;
        if ($request->filled('legal_state_id')) {
            $legalStateShort = \App\Models\State::find($request->legal_state_id)?->short_name;
        } elseif ($request->filled('legal_state')) {
            $legalStateShort = strtoupper(trim((string) $request->legal_state));
        }
        if ($request->filled('mailing_state_id')) {
            $mailingStateShort = \App\Models\State::find($request->mailing_state_id)?->short_name;
        } elseif ($request->filled('mailing_state')) {
            $mailingStateShort = strtoupper(trim((string) $request->mailing_state));
        }

        // ── ambassadors table: all profile-specific data ──────────────────
        $sameAsLegal   = $request->boolean('same_as_legal');
        $mailingAddr   = $sameAsLegal ? $request->legal_address  : $request->mailing_address;
        $mailingCity   = $sameAsLegal ? $request->legal_city     : $request->mailing_city;
        $mailingState  = $sameAsLegal ? $legalStateShort         : $mailingStateShort;
        $mailingZip    = $sameAsLegal ? $request->legal_zip_code : $request->mailing_zip_code;

        $user->ambassadorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'dob'                    => $request->dob,
                'venmo'                  => $request->venmo,
                'nickname'               => $request->nickname,
                // Preserve existing coordinates when none are submitted so a save
                // without opening the map does not wipe a previously set location.
                'latitude'               => $request->filled('latitude') ? $request->latitude : $user->ambassadorProfile?->latitude,
                'longitude'              => $request->filled('longitude') ? $request->longitude : $user->ambassadorProfile?->longitude,

                // Legal address — state as short code resolved from state_id
                'legal_address'          => $request->legal_address,
                'legal_city'             => $request->legal_city,
                'legal_state'            => $legalStateShort,
                'legal_zip_code'         => $request->legal_zip_code,

                // Mailing address
                'same_as_legal'          => $sameAsLegal,
                'mailing_address'        => $mailingAddr,
                'mailing_city'           => $mailingCity,
                'mailing_state'          => $mailingStateShort ? strtoupper(trim((string) $mailingStateShort)) : null,
                'mailing_zip_code'       => $mailingZip,

                // Certifications are intentionally omitted here — managed by
                // Admins/Managers only. Leaving them out of updateOrCreate preserves
                // the existing manager-set values (never reset from self-service).

                // About fields
                'favorite_coffee_shop'   => $request->favorite_coffee_shop,
                'favorite_restaurant'    => $request->favorite_restaurant,
                'favorite_place_to_shop' => $request->favorite_place_to_shop,
                'favorite_charity'       => $request->favorite_charity,

            ]
        );

        // ── Sync markets via ambassador_market pivot ─────────────────────────
        if ($request->filled('market_id')) {
            $user->ambassadorProfile
                ?->markets()
                ->sync((array) $request->market_id);
        }

        // Persist ambassador liquor license details
        $user->ambassadorLiquorLicense()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'has_liquor_license' => $request->boolean('has_liquor_license'),
                'license_number' => $request->filled('license_number') ? $request->license_number : null,
                'issuing_state' => $request->filled('issuing_state') ? $request->issuing_state : null,
                'issued_at' => $request->filled('issued_at') ? $request->issued_at : null,
                'expires_at' => $request->filled('expires_at') ? $request->expires_at : null,
                'document_on_file' => $request->boolean('document_on_file'),
                'notes' => $request->filled('notes') ? $request->notes : null,
            ]
        );

        // Ensure Dwolla customer exists for ambassadors after profile has all required KYC fields
        if ($user->role_id == 5) {
            /** @var CustomerService $dwollaCustomerService */
            $dwollaCustomerService = app(CustomerService::class);

            if ($dwollaCustomerService->hasRequiredDwollaData($user) && ! $user->dwollaCustomer) {
                try {
                    $dwollaCustomerService->create($user);
                    $request->session()->forget(['dwolla_payment_setup_required', 'dwolla_missing_fields']);
                } catch (\App\Exceptions\MissingDwollaProfileDataException $e) {
                    $request->session()->put([
                        'dwolla_payment_setup_required' => true,
                        'dwolla_missing_fields' => $e->missingFields,
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Failed to create Dwolla customer on profile update', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->route('ambassador.profile')->with('success', 'Profile updated successfully!');
    }

    public function viewEvent($id)
    {
        $event = Event::with(
            'distributor',
            'department',
            'venueType',
            'brand',
            'location',
            'eventType',
            'client.clientProfile',
            'manager',
            'markets',
            'eventAttachments',
            'billingParties.client.clientProfile',
            'primaryBillingParty.client.clientProfile'
        )->where('id', $id)->orderBy('id', 'DESC')->firstOrFail();
        $recaps = Recap::with('ambassador')->where('event_id', $id)->get();

        return view('common.view-event', compact('event', 'recaps'));
    }

    // public function viewRecap($id) {
    //     $recap = Recap::with('state', 'event')->where('event_id',$id)->first();
    //     return view('common.view-recap',compact('recap'));
    // }
    public function viewRecap($id)
    {
        // Bug 9 (MC cmon1a7u4005d3r1h5cdct2vh): when an ambassador clicks
        // a recap from Pay Forecast / Submitted Recaps, scope to *their*
        // recap on the event — if multiple BAs recapped the same event the
        // unscoped first() could return another BA's record (which then
        // looks like "only event details" because the current user's recap
        // fields aren't on the screen). Admin/manager/client users can see
        // any recap, so we only narrow when the viewer is an Ambassador.
        $with = ['state', 'event.brand', 'event.eventType', 'event.location', 'event.distributor', 'event.ambassador'];
        $user = auth()->user();
        $isAmbassador = $user && (string) ($user->role_id ?? '') === '5';

        $query = Recap::with($with)->where('event_id', $id);
        if ($isAmbassador) {
            $query->where('ambassador_id', $user->id);
        }

        $recap = $query->first();
        if (! $recap) {
            // Some existing ambassador links pass the recap id instead of the
            // event id. Support both shapes, but keep ambassador views scoped
            // to the signed-in ambassador.
            $fallbackQuery = Recap::with($with)->where('id', $id);
            if ($isAmbassador) {
                $fallbackQuery->where('ambassador_id', $user->id);
            }
            $recap = $fallbackQuery->first();
        }

        if (! $recap) {
            abort(404, 'Recap not found.');
        }

        $brand = $recap->event->brand ?? null;

        return view('common.view-recap', compact('recap', 'brand'));
    }

    public function showRecap($id)
    {
        $recap = Recap::with('state', 'event.brand', 'event.eventType')->where('id', $id)->first();
        $event = Event::with('brand:id,brand_name', 'eventType')->find($recap->event_id);
        $eventLocation = EventLocation::find($event->event_location_id);
        $store_address = $eventLocation ? $eventLocation->address : 'Not available';
        $assignedAmbassadors = explode(',', $event->ambassador_id);

        $ambsCollection = User::select('id', 'name')->whereIn('id', $assignedAmbassadors)->get();
        $ambassadors = $ambsCollection->pluck('name')->join(', ');

        return view('common.view-recap', compact('recap', 'event', 'store_address', 'ambassadors'));
    }

    public function approveRecap(Request $request, $id)
    {
        $recap = Recap::with(['event', 'ambassador'])->findOrFail($id);
        $expeditedLog = Log::channel('expedited_pay');

        $expeditedLog->info('Recap approval started.', [
            'recap_id' => $recap->id,
            'event_id' => $recap->event_id,
            'ambassador_id' => $recap->ambassador_id,
            'is_expedited_requested' => (bool) $recap->is_expedited_requested,
            'is_approved' => (bool) $recap->is_approved,
            'manager_no_show' => (bool) $recap->manager_no_show,
            'admin_user_id' => auth()->id(),
        ]);

        if ($recap->is_approved) {
            if ($recap->is_expedited_requested) {
                $expeditedLog->warning('Expedited recap approval stopped because recap is already approved.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $recap->ambassador_id,
                ]);
            }

            return back()->with('error', 'Recap already approved.');
        }

        // If manager marked this as a No-Show, never create payouts or QuickBooks entries
        if ($recap->manager_no_show) {
            if ($recap->is_expedited_requested) {
                $expeditedLog->warning('Expedited recap approval stopped because recap is marked manager no-show.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $recap->ambassador_id,
                ]);
            }

            // We deliberately do NOT set is_approved here so it can stay visible as a No-Show
            return back()->with('success', 'Recap marked as No-Show. No payout will be created.');
        }

        $assignedAmbassador = getAssignAmbassador($recap->event_id, $recap->ambassador_id);
        $skippedAmbassadors = [];

        try {
            DB::transaction(function () use ($recap, $assignedAmbassador, &$skippedAmbassadors, $expeditedLog) {

            // Approve recap once
            $recap->update([
                'deny_reason' => null,
                'denied_by' => null,
                'denied_at' => null,
                'is_approved' => true,
                'approved_at' => now(),
            ]);

            if ($recap->is_expedited_requested) {
                $expeditedLog->info('Expedited recap detected after approval update.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $recap->ambassador_id,
                    'approved_at' => optional($recap->approved_at)->toDateTimeString(),
                ]);
            }

            // Mark event completed when recap is approved (BAs can recap while event was still pending).
            if ($recap->event) {
                $recap->event->update(['event_status' => '1']);
            }

            if (! $assignedAmbassador) {
                if ($recap->is_expedited_requested) {
                    $expeditedLog->warning('Expedited payout preparation stopped: assigned ambassador row not found.', [
                        'recap_id' => $recap->id,
                        'event_id' => $recap->event_id,
                        'ambassador_id' => $recap->ambassador_id,
                    ]);
                }

                return;
            }

            if (! empty($assignedAmbassador->is_no_show) || $recap->manager_no_show) {
                if ($recap->is_expedited_requested) {
                    $expeditedLog->warning('Expedited payout preparation stopped: assigned ambassador is no-show.', [
                        'recap_id' => $recap->id,
                        'event_id' => $recap->event_id,
                        'ambassador_id' => $recap->ambassador_id,
                        'assign_ambassador_id' => $assignedAmbassador->id ?? null,
                    ]);
                }

                return;
            }

            $ambassador = $assignedAmbassador->ambassador ?? $recap->ambassador;
            if (! $ambassador) {
                if ($recap->is_expedited_requested) {
                    $expeditedLog->warning('Expedited payout preparation stopped: ambassador model not found.', [
                        'recap_id' => $recap->id,
                        'event_id' => $recap->event_id,
                        'ambassador_id' => $recap->ambassador_id,
                        'assign_ambassador_id' => $assignedAmbassador->id ?? null,
                    ]);
                }

                return;
            }

            if (! $ambassador->hasVerifiedBankAccount()) {
                $skippedAmbassadors[] = $ambassador;

                if ($recap->is_expedited_requested) {
                    $expeditedLog->warning('Expedited payout preparation stopped: ambassador has no verified bank account.', [
                        'recap_id' => $recap->id,
                        'event_id' => $recap->event_id,
                        'ambassador_id' => $ambassador->id,
                    ]);
                }

                return;
            }

            // Calculate payout for only the ambassador whose recap is being approved.
            // Use the User model accessor so ambassador-profile wages are honored.
            $wage = (float) ($ambassador->wage ?? 0);

            $bonus = ($assignedAmbassador->override_pay ?? 0)
                + ($assignedAmbassador->pinata_amount ?? 0);

            // If manager entered an override amount, use it; otherwise fall back to event-based calc.
            if ($recap->manager_override_amount !== null) {
                $amount = round($recap->manager_override_amount + $bonus, 2);
            } else {
                $amount = round(
                    $recap->event->getAmbassadorAmount($wage) + $bonus,
                    2
                );
            }

            if ($recap->is_expedited_requested) {
                $expeditedLog->info('Expedited payout gross amount calculated.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $ambassador->id,
                    'wage' => $wage,
                    'override_pay' => (float) ($assignedAmbassador->override_pay ?? 0),
                    'pinata_amount' => (float) ($assignedAmbassador->pinata_amount ?? 0),
                    'manager_override_amount' => $recap->manager_override_amount,
                    'gross_amount' => $amount,
                ]);
            }

            if ($amount <= 0) {
                if ($recap->is_expedited_requested) {
                    $expeditedLog->warning('Expedited payout preparation stopped: gross payout amount is not positive.', [
                        'recap_id' => $recap->id,
                        'event_id' => $recap->event_id,
                        'ambassador_id' => $ambassador->id,
                        'gross_amount' => $amount,
                    ]);
                }

                return;
            }

            $reference = sprintf(
                'PAYOUT-%s-%s-%s',
                $recap->event_id,
                $ambassador->id,
                Str::uuid()
            );

            $payoutAmounts = app(ExpeditedPayoutCalculator::class)->calculate(
                $amount,
                (bool) $recap->is_expedited_requested
            );

            if ($payoutAmounts['amount'] <= 0) {
                $expeditedLog->warning('Expedited recap payout cannot cover the processing fee.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $ambassador->id,
                    'original_amount' => $payoutAmounts['original_amount'],
                    'expedited_fee' => $payoutAmounts['expedited_fee'],
                    'net_amount' => $payoutAmounts['amount'],
                ]);

                return;
            }

            // Create or refresh an unprocessed payout for this recap. `amount`
            // is the net amount so every Dwolla path sends the correct value.
            $payout = AmbassadorPayout::where('event_id', $recap->event_id)
                ->where('user_id', $ambassador->id)
                ->first();
            $fundingSource = $ambassador->dwollaCustomer?->ensureVerifiedDefaultFundingSource();

            if (! $fundingSource) {
                return;
            }

            if ($payout && $payout->status === 'approved') {
                $payout->update([
                    'dwolla_funding_source_id' => $fundingSource->id,
                    ...$payoutAmounts,
                    'currency' => 'USD',
                    'approved_at' => $payout->approved_at ?? now(),
                ]);
                $expeditedLog->info('AmbassadorPayout refreshed for expedited recap.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $ambassador->id,
                    'payout_id' => $payout->id,
                    'gross_amount' => $payoutAmounts['original_amount'],
                    'expedited_fee' => $payoutAmounts['expedited_fee'],
                    'net_amount' => $payoutAmounts['amount'],
                    'status' => $payout->status,
                ]);
            } elseif (! $payout) {
                $payout = AmbassadorPayout::create([
                    'dwolla_funding_source_id' => $fundingSource->id,
                    'event_id' => $recap->event_id,
                    'user_id' => $ambassador->id,
                    ...$payoutAmounts,
                    'currency' => 'USD',
                    'reference' => $reference,
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
                $expeditedLog->info('AmbassadorPayout created for expedited recap.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $ambassador->id,
                    'payout_id' => $payout->id,
                    'gross_amount' => $payoutAmounts['original_amount'],
                    'expedited_fee' => $payoutAmounts['expedited_fee'],
                    'net_amount' => $payoutAmounts['amount'],
                    'status' => $payout->status,
                ]);
            } elseif ($recap->is_expedited_requested) {
                $expeditedLog->warning('Expedited payout dispatch skipped because an existing payout is already beyond approval state.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $ambassador->id,
                    'payout_id' => $payout->id,
                    'gross_amount' => $payout->original_amount,
                    'expedited_fee' => $payout->expedited_fee,
                    'net_amount' => $payout->amount,
                    'status' => $payout->status,
                    'dwolla_transfer_id' => $payout->dwolla_transfer_id,
                ]);
            }

            // Expedited recaps bypass Batch Payroll and use the existing
            // immediate Dwolla payout job. Queue the job after the approval
            // transaction commits so its fresh payout query can always see the
            // payout record. Normal recaps remain approved for Batch Payroll.
            if ($recap->is_expedited_requested && $payout->status === 'approved') {
                $payout->update([
                    'status' => 'queued',
                    'queued_at' => now(),
                ]);

                $expeditedLog->info('Expedited payout queued for immediate Dwolla dispatch.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $ambassador->id,
                    'payout_id' => $payout->id,
                    'status' => $payout->status,
                    'gross_amount' => $payout->original_amount,
                    'expedited_fee' => $payout->expedited_fee,
                    'net_amount' => $payout->amount,
                ]);

                DB::afterCommit(function () use ($payout, $expeditedLog) {
                    $expeditedLog->info('Dispatching InitiateAmbassadorPayout for expedited payout.', [
                        'payout_id' => $payout->id,
                        'event_id' => $payout->event_id,
                        'ambassador_id' => $payout->user_id,
                        'status' => $payout->status,
                    ]);

                    InitiateAmbassadorPayout::dispatch($payout)->onQueue('payouts');
                });
            }
        });
        } catch (\Throwable $e) {
            if ($recap->is_expedited_requested) {
                $expeditedLog->error('Expedited recap approval failed with exception.', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'ambassador_id' => $recap->ambassador_id,
                    'exception_class' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            throw $e;
        }

        // BEST upload: only push after approval
        if ($recap->event && $recap->event->integrate_with_best && ! empty($recap->best_payload)) {
            try {
                $eventPushService = app(EventPushService::class);
                $payload = json_decode($recap->best_payload, true);

                if (is_array($payload)) {
                    Log::info('BEST API call attempted for recap approval', [
                        'recap_id' => $recap->id,
                        'event_id' => $recap->event_id,
                        'best_action' => EventPushService::RECAP_SAMPLING_ACTION,
                        'best_endpoint' => $eventPushService->recapSamplingEndpoint(),
                        'payload' => $eventPushService->safeRecapSamplingPayload($payload),
                    ]);

                    $response = $eventPushService->postRecapSamplingToBest($payload);
                    $responseBody = $eventPushService->safeResponseBody($response->body());
                    $responseJson = $response->json();
                    $responseForLog = $responseJson ?? $responseBody;
                    $bestAccepted = $response->successful()
                        && $eventPushService->recapSamplingWasAccepted($responseJson);

                    if ($bestAccepted) {
                        Log::info('BEST accepted recap data successfully', [
                            'recap_id' => $recap->id,
                            'event_id' => $recap->event_id,
                            'best_action' => EventPushService::RECAP_SAMPLING_ACTION,
                            'best_endpoint' => $eventPushService->recapSamplingEndpoint(),
                            'http_status' => $response->status(),
                            'response' => $responseForLog,
                            'best_call_attempted' => true,
                            'best_accepted' => true,
                        ]);

                        $recap->event->update([
                            'best_synced_at' => now(),
                            'best_status' => 'success',
                            'best_response' => json_encode(['ok' => true, 'status' => $response->status()]),
                        ]);
                    } else {
                        Log::error('BEST rejected recap data or returned non-success status', [
                            'recap_id' => $recap->id,
                            'event_id' => $recap->event_id,
                            'best_action' => EventPushService::RECAP_SAMPLING_ACTION,
                            'best_endpoint' => $eventPushService->recapSamplingEndpoint(),
                            'http_status' => $response->status(),
                            'response' => $responseForLog,
                            'best_call_attempted' => true,
                            'best_accepted' => false,
                        ]);

                        $recap->event->update([
                            'best_status' => 'failed',
                            'best_response' => json_encode([
                                'ok' => false,
                                'status' => $response->status(),
                                'body' => $responseJson ?? $responseBody,
                            ]),
                        ]);
                    }
                } else {
                    Log::error('BEST recap payload invalid; API call not attempted', [
                        'recap_id' => $recap->id,
                        'event_id' => $recap->event_id,
                        'best_action' => EventPushService::RECAP_SAMPLING_ACTION,
                        'best_endpoint' => $eventPushService->recapSamplingEndpoint(),
                        'payload_type' => gettype($payload),
                        'best_call_attempted' => false,
                        'best_accepted' => false,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('BEST API call failed with exception during recap approval', [
                    'recap_id' => $recap->id,
                    'event_id' => $recap->event_id,
                    'best_action' => EventPushService::RECAP_SAMPLING_ACTION,
                    'best_endpoint' => isset($eventPushService) ? $eventPushService->recapSamplingEndpoint() : null,
                    'best_call_attempted' => true,
                    'best_accepted' => false,
                    'exception_class' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $recap->event->update([
                    'best_status' => 'failed',
                    'best_response' => json_encode(['ok' => false, 'error' => $e->getMessage()]),
                ]);
            }
        }

        Mail::to($recap->ambassador->email)->send(new RecapApprovedMail($recap));

        $bankSetupNotifications = app(BankSetupNotificationService::class);
        foreach ($skippedAmbassadors as $skippedAmbassador) {
            $bankSetupNotifications->sendPaymentSkippedNotification($skippedAmbassador, auth()->id());
        }

        $successMessage = $recap->is_expedited_requested
            ? 'Recap approved. Expedited Pay has been queued for immediate payout.'
            : 'Recap approved. Ambassador payment will be included in the next payroll batch.';

        $response = back()->with('success', $successMessage);

        if ($skippedAmbassadors !== []) {
            $warning = collect($skippedAmbassadors)
                ->map(fn ($ambassador) => "{$ambassador->name} was skipped because Direct Deposit setup is incomplete.")
                ->implode(' ');

            $response->with('warning', $warning);
        }

        return $response;
    }

    public function denyRecap(Request $request, $id)
    {
        $request->validate([
            'deny_reason' => 'required|string|max:1000',
        ]);

        $recap = Recap::findOrFail($id);

        $recap->is_approved = 0;
        $recap->approved_at = null;
        $recap->deny_reason = $request->deny_reason;
        $recap->denied_by = auth()->id();
        $recap->denied_at = now();
        $recap->save();

        Mail::to($recap->ambassador->email)->send(new RecapDeniedMail($recap));

        return back()->with('success', 'Recap has been denied. The ambassador has been notified.');
    }

    public function updateRecapManagerFields(Request $request, $id)
    {
        $recap = Recap::findOrFail($id);

        $data = $request->validate([
            'manager_override_amount' => 'nullable|numeric|min:0',
            'manager_no_show' => 'nullable|boolean',
        ]);

        $recap->manager_override_amount = $data['manager_override_amount'] ?? null;
        $recap->manager_no_show = !empty($data['manager_no_show']) ? 1 : 0;
        $recap->save();

        return back()->with('success', 'Manager review fields updated.');
    }

    public function locationProfile()
    {
        $location = Auth::user();
        $states = State::get();
        $venues = Venue::get();

        return view('location.profile', compact('location', 'venues', 'states'));
    }

    public function updateLocationProfile(Request $request)
    {
        $id = $request->id;

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:15|unique:users,phone,'.$id,
            'state_id' => 'required',
            'city' => 'required',
            'venue_type' => 'required',
            'venue_name' => 'required|unique:users,venue_name,'.$id,
        ]);

        $user = User::find($id);

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('profile'), $filename);
            $user->profile_image = 'profile/'.$filename;
        }

        $user->role_id = 4;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->state_id = $request->state_id;
        $user->city = $request->city;
        $user->address = $request->address;
        $user->venue_name = $request->venue_name;
        $user->venue_type = $request->venue_type;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return redirect()->route('location.profile')->with('success', 'Profile updated successfully!');
    }

    public function clientProfile()
    {
        $client = Auth::user();
        $states = State::get();
        $venues = Venue::get();

        return view('client.profile', compact('client', 'venues', 'states'));
    }

    public function updateClientProfile(Request $request)
    {
        $id = $request->id;

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:15|unique:users,phone,'.$id,
            'state_id' => 'required',
            'city' => 'required',
            'zip_code' => 'required|numeric|digits_between:5,6',
        ]);

        $user = User::find($id);

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('profile'), $filename);
            $user->profile_image = 'profile/'.$filename;
        }

        $user->role_id = 3;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->state_id = $request->state_id;
        $user->city = $request->city;
        $user->address = $request->address;
        $user->venue_name = $request->venue_name;
        $user->venue_type = $request->venue_type;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        if ($request->zip_code) {
            $user->zip_code = $request->zip_code;
        }
        $user->save();

        return redirect()->route('client.profile')->with('success', 'Profile updated successfully!');
    }

    public function adminProfile()
    {
        $user = User::where('id', Auth::id())->first();

        return view('admin.profile', compact('user'));
    }

    public function adminUpdateProfile(Request $request)
    {
        $id = Auth::id();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'required|string|max:15|unique:users,phone,'.$id,
            'zip_code' => 'nullable|numeric|digits_between:5,6',
        ]);

        $user = User::find($id);
        $roles = [
            1 => 'admin',
            6 => 'manager',
        ];

        $role = $roles[$user->role_id] ?? 'unknown';

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('profile'), $filename);
            $user->profile_image = 'profile/'.$filename;
        }
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->phone = $request->phone;
        $user->address = $request->input('address');
        $user->zip_code = $request->input('zip_code');
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return redirect()->route($role.'.profile')->with('success', 'Profile updated successfully!');
    }
}
