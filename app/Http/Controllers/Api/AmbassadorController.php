<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\VerifyOTP;
use App\Trait\FormatResponseTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use App\Http\Requests\ForgotPassword;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\RegisterUserRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\EventService;
use App\Models\Role;
use App\Models\User;
use App\Models\Event;
use App\Models\EventLocation;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\Brand;
use App\Models\Market;
use App\Models\Venue;
use App\Models\State;
use App\Models\Recap;
use App\Models\RecapDraft;
use App\Models\AssignAmbassador;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Mail\ApplyEventMail;
use App\Mail\AmbassadorAppliedEventMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Services\AmbassadorRecapListingService;
use App\Services\EventCheckInService;
use App\Services\RecapFormService;
use App\Services\ReviewNotificationService;

class AmbassadorController extends Controller
{
    protected $eventService;

    protected AmbassadorRecapListingService $recapListingService;

    use FormatResponseTrait;
    
    public function __construct(
        EventService $eventService,
        AmbassadorRecapListingService $recapListingService,
    ) {
        $this->eventService = $eventService;
        $this->recapListingService = $recapListingService;
    }

    /**
     * Markets assigned to this ambassador through ambassador_market.
     * The legacy user.market_id field is intentionally not used.
     */
    private function assignedMarketIds(User $user): array
    {
        return $user->ambassadorProfile()
            ->with('markets:id')
            ->first()?->markets->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
    }
    
    public function getEvents(Request $request) {
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $assignAmb = AssignAmbassador::where('ambassador_id',$user->id)->pluck('event_id')->toArray();
            $marketIds = $this->assignedMarketIds($user);
            $lastDayTimestamp = strtotime(date('Y-m-t', strtotime($request->start_date)));
            $events = Event::select(DB::raw('DATE(start_time) as date'),DB::raw('COUNT(*) as count'))->whereIn('id',$assignAmb)
            ->whereHas('markets', fn ($q) => $q->whereIn('markets.id', $marketIds))
            ->whereBetween('start_time', [
                $request->start_date.' 00:00:00',
                date('Y-m-d', $lastDayTimestamp).' 23:59:59'
            ])
            ->where('event_status', $request->input('event_status', '0'))
            ->groupBy(DB::raw('DATE(start_time)'))
            ->get();
                
            if(count($events)>0){
                return $this->successResponse('Event Data.', $events);    
            } else {
                 return $this->errorResponse('No Event Data Found for '.date('F',strtotime($request->start_date)), 'ERROR',200,[]);
            }
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getStateMarket(Request $request){
        try {
            $user = JWTAuth::user();
            if (! $user) {
                return $this->errorResponse('User Not Found', [], 403);
            }
            $marketIds = $this->assignedMarketIds($user);
            $states = State::all();
            $markets = Market::query()->active()->whereIn('id', $marketIds)->ordered()->get();

            // Liquor license is ambassador self-service (same fields as the web
            // portal profile). Return the current record so the mobile profile
            // form can prefill it; null when the ambassador has never saved one.
            $user->load('ambassadorLiquorLicense');

            return $this->successResponse(
                'Data fetched successfully',
                [
                    'states' => $states,
                    'markets' => $markets,
                    'liquor_license' => $user->ambassadorLiquorLicense,
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong', 500, $e->getMessage());
        }
    }
    
    public function updateProfile(Request $request){
        $user = JWTAuth::user();
        if(!$user){
            return $this->errorResponse('Token is invalid', 'invalid_token', Response::HTTP_UNAUTHORIZED, new \stdClass());
        }

        // Eager-load ambassadorProfile so we can read existing values for defaults
        $user->load('ambassadorProfile');

        // Ionic checkboxes can submit "on", "1", or boolean values through FormData.
        // Normalize before validation so every valid checkbox representation is accepted.
        if ($request->has('same_as_legal')) {
            $request->merge([
                'same_as_legal' => filter_var($request->input('same_as_legal'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        
        $Validator = Validator::make($request->all(), [
            'first_name'  => 'required|string|min:2|max:255',
            'last_name'   => 'nullable|string|min:2|max:255',

            'dob'           => 'required|date',
            'venmo'         => 'nullable|string|max:255',
            'nickname'      => 'nullable|string|max:255',
            'same_as_legal' => 'nullable|boolean',

            // Address fields — state stored as short code (e.g. NY)
            'legal_address'    => 'nullable|string|max:255',
            'legal_city'       => 'nullable|string|max:255',
            'legal_state'      => 'nullable|string|max:3',
            'legal_zip_code'   => 'nullable|string|max:10',
            'mailing_address'  => 'nullable|string|max:255',
            'mailing_city'     => 'nullable|string|max:255',
            'mailing_state'    => 'nullable|string|max:3',
            'mailing_zip_code' => 'nullable|string|max:10',

            // Certifications & preferences
            'titos_trained'      => 'nullable|boolean',
            'thc_quiz_passed'    => 'nullable|boolean',
            'male_ba_lgbtq_ok'   => 'nullable|boolean',
            'attended_trainings' => 'nullable|boolean',
            'tips_certified'     => 'nullable|boolean',
            'has_permit'         => 'nullable|boolean',

            // Ambassador liquor license details (optional)
            'has_liquor_license' => 'nullable|boolean',
            'license_number'     => 'nullable|string|max:100',
            'issuing_state'      => 'nullable|string|max:50',
            'issued_at'          => 'nullable|date',
            'expires_at'         => 'nullable|date',
            'document_on_file'   => 'nullable|boolean',
            'notes'              => 'nullable|string|max:2000',

            'favorite_coffee_shop'    => 'nullable|string|max:255',
            'favorite_restaurant'     => 'nullable|string|max:255',
            'favorite_place_to_shop'  => 'nullable|string|max:255',
            'favorite_charity'        => 'nullable|string|max:255',

            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        if ($Validator->fails()) {
            $errors = $Validator->errors();
            foreach ($errors->all() as $key => $value) {
                return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }

        try {
            // ── Profile image → stays on users table ──────────────────────────
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                if ($image->isValid()) {
                    $imagename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('profile_images'), $imagename);
                    $user->profile_image = 'profile_images/' . $imagename;
                } else {
                    return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
            }

            // ── users table: identity + auth fields only ──────────────────────
            $user->first_name = $request->first_name;
            $user->last_name  = $request->last_name ?? $user->last_name;
            $user->name       = trim($request->first_name . ' ' . ($request->last_name ?? $user->last_name));
            $user->phone      = $request->phone ?? $user->phone;
            $user->save();

            // ── ambassadors table: all profile-specific data ──────────────────
            $profile = $user->ambassadorProfile;
            $sameAsLegal = $request->has('same_as_legal')
                ? $request->boolean('same_as_legal')
                : (bool) ($profile?->same_as_legal ?? false);

            $legalAddress  = $request->legal_address  ?? $profile?->legal_address;
            $legalCity     = $request->legal_city      ?? $profile?->legal_city;
            $legalState    = $request->filled('legal_state')
                ? strtoupper(trim((string) $request->legal_state))
                : ($request->filled('legal_state_id')
                    ? (State::find($request->legal_state_id)?->short_name ?? $profile?->legal_state)
                    : ($profile?->legal_state ?? null));
            $legalZip      = $request->legal_zip_code  ?? $profile?->legal_zip_code;

            if ($sameAsLegal) {
                $mailingAddress = $legalAddress;
                $mailingCity    = $legalCity;
                $mailingState   = $legalState;
                $mailingZip     = $legalZip;
            } else {
                $mailingAddress = $request->mailing_address  ?? $profile?->mailing_address;
                $mailingCity    = $request->mailing_city     ?? $profile?->mailing_city;
                $mailingState   = $request->filled('mailing_state')
                    ? strtoupper(trim((string) $request->mailing_state))
                    : ($request->filled('mailing_state_id')
                        ? (State::find($request->mailing_state_id)?->short_name ?? $profile?->mailing_state)
                        : ($profile?->mailing_state ?? null));
                $mailingZip     = $request->mailing_zip_code ?? $profile?->mailing_zip_code;
            }

            $ambassadorData = [
                'legal_address'   => $legalAddress,
                'legal_city'      => $legalCity,
                'legal_state'     => $legalState,
                'legal_zip_code'  => $legalZip,
                'same_as_legal'   => $sameAsLegal,
                'mailing_address' => $mailingAddress,
                'mailing_city'    => $mailingCity,
                'mailing_state'   => $mailingState,
                'mailing_zip_code'=> $mailingZip,
            ];

            if ($request->has('dob') && $request->filled('dob')) {
                $ambassadorData['dob'] = $request->dob;
            }
            if ($request->has('venmo'))    $ambassadorData['venmo']    = $request->venmo;
            if ($request->has('nickname')) $ambassadorData['nickname'] = $request->nickname;
            if ($request->filled('latitude'))  $ambassadorData['latitude']  = $request->latitude;
            if ($request->filled('longitude')) $ambassadorData['longitude'] = $request->longitude;

            if ($request->has('titos_trained'))      $ambassadorData['titos_trained']      = $request->boolean('titos_trained');
            if ($request->has('thc_quiz_passed'))    $ambassadorData['thc_quiz_passed']    = $request->boolean('thc_quiz_passed');
            if ($request->has('male_ba_lgbtq_ok'))   $ambassadorData['male_ba_lgbtq_ok']   = $request->boolean('male_ba_lgbtq_ok');
            if ($request->has('attended_trainings')) $ambassadorData['attended_trainings'] = $request->boolean('attended_trainings');
            if ($request->has('tips_certified'))     $ambassadorData['tips_certified']     = $request->boolean('tips_certified');
            if ($request->has('has_permit'))         $ambassadorData['has_permit']         = $request->boolean('has_permit');

            if ($request->has('favorite_coffee_shop'))   $ambassadorData['favorite_coffee_shop']   = $request->favorite_coffee_shop;
            if ($request->has('favorite_restaurant'))    $ambassadorData['favorite_restaurant']    = $request->favorite_restaurant;
            if ($request->has('favorite_place_to_shop')) $ambassadorData['favorite_place_to_shop'] = $request->favorite_place_to_shop;
            if ($request->has('favorite_charity'))       $ambassadorData['favorite_charity']       = $request->favorite_charity;

            $user->ambassadorProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $ambassadorData
            );

            // Reload so proxy accessors return fresh data
            $user->load('ambassadorProfile');
            $user->refresh();

            // Update liquor license only if ambassador provided liquor-related fields
            $liquorKeys = [
                'has_liquor_license',
                'license_number',
                'issuing_state',
                'issued_at',
                'expires_at',
                'document_on_file',
                'notes',
            ];
            $shouldUpdateLiquor = collect($liquorKeys)->contains(fn ($key) => $request->has($key));

            if ($shouldUpdateLiquor) {
                $data = [];

                if ($request->has('has_liquor_license')) {
                    $data['has_liquor_license'] = $request->boolean('has_liquor_license');
                }
                if ($request->has('license_number')) {
                    $data['license_number'] = $request->filled('license_number') ? $request->license_number : null;
                }
                if ($request->has('issuing_state')) {
                    $data['issuing_state'] = $request->filled('issuing_state') ? $request->issuing_state : null;
                }
                if ($request->has('issued_at')) {
                    $data['issued_at'] = $request->filled('issued_at') ? $request->issued_at : null;
                }
                if ($request->has('expires_at')) {
                    $data['expires_at'] = $request->filled('expires_at') ? $request->expires_at : null;
                }
                if ($request->has('document_on_file')) {
                    $data['document_on_file'] = $request->boolean('document_on_file');
                }
                if ($request->has('notes')) {
                    $data['notes'] = $request->filled('notes') ? $request->notes : null;
                }

                if (! empty($data)) {
                    $user->ambassadorLiquorLicense()->updateOrCreate(
                        ['user_id' => $user->id],
                        $data
                    );
                }
            }
            
            if(isset($user->role_id)){
                $user->role = Role::find($user->role_id);
            }

            // Return the up-to-date liquor license so the mobile client can keep
            // its cached user in sync after a save.
            $user->load('ambassadorLiquorLicense');

            return $this->successResponse('Profile updated successfully.', $user);
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }
    
    public function getEventsData(Request $request){
        $user = JWTAuth::user();
        if ($user) {
            $this->eventService->markEndedEventsCompletedForApi();

            $dob = $user->dob;
            $ambassadorId = $user->id;
            $marketIds = $this->assignedMarketIds($user);
            $ambassadorAge = null;
            if ($dob) {
                $ambassadorAge = Carbon::parse($dob)->age;
            }
            // Get assigned ambassador event IDs for current user (approved)
            $assignAmbasssadorIds = AssignAmbassador::where('ambassador_id', $ambassadorId)->where('status', '1')->pluck('event_id')->toArray();
            // Get pending ambassador event IDs for current user (not yet approved)
            $pendingAmbassadorIds = AssignAmbassador::where('ambassador_id', $ambassadorId)->where('status', '0')->pluck('event_id')->toArray();
            // The mobile client may omit DataTables-style search parameters.
            // Default to the available-events tab instead of dereferencing a null array.
            $searchInput = (array) $request->input('search', []);
            $isStatusFilter = filter_var($searchInput['regex'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $status = $isStatusFilter ? ($searchInput['value'] ?? 'all') : 'all';
            $search = $isStatusFilter ? null : ($searchInput['value'] ?? null);

            $page = (int) $request->input('start', 0);
            $perPage = 10; // Items per page

            // Determine sort order (default: asc = upcoming first, then past; desc = latest/farthest first)
            $sortOrder = in_array(strtolower($request->input('sort_order', 'asc')), ['asc', 'desc'])
                ? strtolower($request->input('sort_order', 'asc'))
                : 'asc';

            // Get all events that match basic filters first
            $baseQuery = Event::with('distributor', 'department', 'venueType', 'brand', 'location', 'assigneAmbassadors');
            if ($sortOrder === 'asc') {
                // Previous requirement (chronological ascending — kept for easy restore):
                // Upcoming events first (nearest at top), past events after
                $baseQuery->orderByRaw('CASE WHEN start_time >= NOW() THEN 0 ELSE 1 END ASC')
                          ->orderBy('start_time', 'asc');
            } else {
                // Latest client/PM requirement — descending by event date (farthest/newest first):
                $baseQuery->orderBy('start_time', 'desc');
            }
                
                
            // Apply age restriction filter
            // If ambassador is under 21 or DOB is null, exclude age-restricted events
            if (empty($ambassadorAge) || $ambassadorAge < 21) {
                $baseQuery->where(function ($q) {
                    $q->where('age_restricted', 0)
                        ->orWhereNull('age_restricted');
                });
            }

            // Apply search filter
            if (!empty($search) && ! $isStatusFilter) {
                $baseQuery->where(function ($q) use ($search) {
                    $q->whereHas('brand', function ($brandQuery) use ($search) {
                        $brandQuery->where('brand_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('location', function ($locationQuery) use ($search) {
                        $locationQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    })
                    ->orWhereHas('distributor', function ($distributorQuery) use ($search) {
                        $distributorQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('department', function ($departmentQuery) use ($search) {
                        $departmentQuery->where('name', 'like', "%{$search}%");
                    });
                });
            }

            // Apply market filter
            if ($request->filled('market_id')) {
                $marketId = (int) $request->market_id;
                $baseQuery->whereHas('markets', fn ($q) => $q->where('markets.id', $marketId));
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = $request->start_date;
                $endDate = $request->end_date;
                $baseQuery->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }

            // Apply status filter based on your requirements
            if ($status === 'completed') {
                // Direct assignments remain visible even if an event's market is
                // no longer among the ambassador's currently assigned markets.
                $baseQuery->where('event_status', '1')
                    ->whereIn('id', $assignAmbasssadorIds);
            } elseif ($status === 'pending') {
                // Show only events where ambassador has applied but not approved yet
                $baseQuery->whereIn('id', $pendingAmbassadorIds)
                    ->where('event_status', '0');
            } elseif ($status === 'assigned') {
                // Assigned lists only pending events; completed events have their own tab.
                $baseQuery->whereIn('id', $assignAmbasssadorIds)
                    ->where('event_status', '0');
            } elseif ($status === 'all') {
                // Market membership limits only the available-event catalogue;
                // it must not hide events directly assigned through the pivot.
                $baseQuery->whereHas('markets', fn ($q) => $q->whereIn('markets.id', $marketIds))
                    ->whereNotIn('id', array_merge(array_filter($pendingAmbassadorIds), array_filter($assignAmbasssadorIds)))
                    ->where('event_status', '0');
            }
            // Completed tab always sorts most-recent-first regardless of the toggle.
            // All other tabs respect the sort_order param (default asc = nearest upcoming first).
            $effectiveSortOrder = ($status === 'completed') ? 'desc' : $sortOrder;

            // Get all matching events
            $allEvents = $this->sortEventsForApi($baseQuery->get(), $effectiveSortOrder);

            // Filter events based on ambassador assignment logic
            // Only apply this filter for 'all' status, as other statuses are already filtered above
            if ($status === 'all') {
                $filteredEvents = $allEvents->filter(function ($event) use ($ambassadorId,$status) {
                    $assignedIds = !empty($event->ambassador_id) ? explode(',', $event->ambassador_id) : [];
                    $assignedCount = count($assignedIds);
                    $position = (int) $event->position;
                    
                    // If event is completed, only show if current user is assigned to it
                    if ($event->event_status == '1') {
                        return in_array($ambassadorId, $assignedIds);
                    }

                    // Show event if it's not full or current user is already assigned
                    return $assignedCount < $position || in_array($ambassadorId, $assignedIds);
                });
            } else {
                // For specific status filters, use all events from query
                $filteredEvents = $allEvents;
            }

            // Manual pagination
            $totalCount = $filteredEvents->count();
            $paginatedEvents = $filteredEvents->slice($page, $perPage);
            $events=[];
            foreach($paginatedEvents as $key=>$event){
                $eventDate = $event->formatted_date;
                if (! empty($event->formatted_time_with_date)) {
                    $eventDate = $event->formatted_time_with_date;
                }
                $temp=array(
                    'id'        => $event->id,
                    'date'  => $eventDate,
                    'time'      => $event->formatted_time,
                    'location'  => ( $event->location->address ?? '--') .' '. ($event->location->city ?? ''),
                    'brand'     => $event->brand->brand_name,
                );
                if ($event->event_status == '1' && $status == 'completed'){
                    $assigned = AssignAmbassador::where('ambassador_id',$user->id)->where('event_id',$event->id)->first();
                    $wage = $user->wage; // reads from ambassadors.wage via proxy accessor
                    $override = $assigned->override_pay ?? 0;
                    $pinata = $assigned->pinata_amount ?? 0;
                    $bonus = $override + $pinata;
                    $temp['total'] = '$'.number_format($event->getAmbassadorAmount($wage) + $bonus, 2);
                    
                    $temp['action']='<div style="display: flex;"><ion-icon id="'.$event->id.'"name="eye" color="primary" size="large"></ion-icon><ion-icon size="large" color="success" name="checkbox" id="'.$event->id.'"></ion-icon></div>';
                } else if (in_array($event->id, $assignAmbasssadorIds) && $status == 'assigned'){
                    $assignedRow = AssignAmbassador::where('ambassador_id', $user->id)
                        ->where('event_id', $event->id)
                        ->first();
                    $checkedIn = $assignedRow && ! empty($assignedRow->event_date_time);
                    $checkInIcon = $checkedIn
                        ? '<ion-icon class="check-in-action" data-event-id="'.$event->id.'" name="checkmark-circle" color="success" size="large"></ion-icon>'
                        : '<ion-icon class="check-in-action" data-event-id="'.$event->id.'" name="location" color="warning" size="large"></ion-icon>';
                    $temp['action'] = '<div style="display:flex;align-items:center;gap:6px;">'
                        .'<ion-icon id="'.$event->id.'" name="eye" color="primary" size="large"></ion-icon>'
                        .$checkInIcon
                        .'</div>';
                    $temp['is_checked_in'] = $checkedIn ? 1 : 0;
                } else if (in_array($event->id, $pendingAmbassadorIds) && $status == 'pending'){
                    $temp['action']='<div style="display: flex;"><ion-icon id="'.$event->id.'"name="checkmark-circle" color="success" size="large"></ion-icon><ion-icon id="'.$event->id.'"name="eye" color="primary" size="large"></ion-icon></div>';
                } else {
                    $duration=strtotime($event->end_time)-strtotime($event->start_time);
                    
                    $assigned = AssignAmbassador::where('ambassador_id',$user->id)->where('event_id',$event->id)->first();
                    $wage = $user->wage; // reads from ambassadors.wage via proxy accessor
                    $override = $assigned->override_pay ?? 0;
                    $pinata = $assigned->pinata_amount ?? 0;
                    $bonus = $override + $pinata;
                    $total = number_format($event->getAmbassadorAmount($wage) + $bonus, 2);
                    
                    $temp=array(
                        'id'        => $event->id,
                        'datetime'  => $event->formatted_time_with_date ?: $event->formatted_time,
                        'duration'  => $event->formatted_time,
                        'brand'     => $event->brand->brand_name,
                        'varietals' => $event->varietals,
                        'kit'       => $event->kit == 1 ? 'Yes' : 'No',
                        'location'  => ( $event->location->address ?? '--') .' '. ($event->location->city ?? ''),
                        'city'      => $event->city,
                        'total'     => '$'.$total,
                        'driver_pay'=> '',
                        'action'    => '<div style="display: flex;"><ion-icon id="'.$event->id.'"name="checkmark-circle" color="primary" size="large"></ion-icon></div>',
                    );
                }
                // Format in the event's own timezone so device timezone never shifts card values.
                $timezoneService = app(\App\Services\EventTimezoneService::class);
                $iana = $timezoneService->normalizeIana($event->timezone);
                $start = Carbon::parse($event->start_time, $iana);
                $end = Carbon::parse($event->end_time, $iana);
                $timezoneLabel = $timezoneService->abbreviationAt($iana, $event->start_time);
                $durationMinutes = $start->diffInMinutes($end);

                $temp['event_name'] = $event->event_name;
                $temp['location_name'] = trim((string) ($event->location->name ?? ''));
                $temp['location_address'] = trim(
                    ($event->location->address ?? '')
                    . (! empty($event->location->city) ? ', ' . $event->location->city : '')
                );
                $temp['card_date'] = $start->isSameDay($end)
                    ? $start->format('m/d/Y')
                    : $start->format('m/d/Y') . ' - ' . $end->format('m/d/Y');
                $temp['card_time'] = $start->format('h:i A') . ' - ' . $end->format('h:i A') . ' ' . $timezoneLabel;
                $temp['card_total_hours'] = sprintf('%02d:%02d HRS', intdiv($durationMinutes, 60), $durationMinutes % 60);
                $events[]=$temp;
            }
            
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $events,
                'recordsTotal'      => $totalCount,
                'recordsFiltered'   => $totalCount,
                'markets'           => Market::query()->active()->whereIn('id', $marketIds)->orderBy('name','ASC')->get()
            ]);
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function applyEvent(Request $request) {
        $user = JWTAuth::user();
        if ($user) {
            try {
                $request->validate([
                    'event_id' => 'required|integer|exists:events,id',
                ]);

                // Prevent duplicate applications for the same event.
                $existing = AssignAmbassador::query()
                    ->where('event_id', (int) $request->event_id)
                    ->where('ambassador_id', (int) $user->id)
                    ->first();
                if ($existing) {
                    return $this->successResponse('Already applied for Event', ['status'=>true, 'already_applied' => true]);
                }

                $eventAssign = new AssignAmbassador();
                $eventAssign->event_id = $request->event_id;
                $eventAssign->ambassador_id = $user->id;
                $eventAssign->status = '0';
                $eventAssign->save();
    
                $data = [
                    'event_name' => $eventAssign->event?$eventAssign->event->event_name:'-',
                    'event_date' => $eventAssign->event?$eventAssign->event->start_time .' '.$eventAssign->event->end_time:'-' ,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ];
                Mail::to('user1@jtplapps.in')->send(new ApplyEventMail($data));
                Mail::to('user1@jtplapps.in')->send(new AmbassadorAppliedEventMail($data));
                Mail::to($user->email)->send(new AmbassadorAppliedEventMail($data));
                return $this->successResponse('Applied for Event', ['status'=>true]);    
            } catch(Exception $e) {
                Log::info("Sending Mail Issue =>".$e->getMessage());
            }
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getRecapData(Request $request){
        $user = JWTAuth::user();
        if($user){
            $search = trim((string) $request->input('search.value', ''));
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);
            $length = $length > 0 ? $length : 10;
            $brandIds = Recap::query()
                ->where('ambassador_id', $user->id)
                ->where('is_draft', false)
                ->whereHas('event')
                ->with('event:id,brand_id')
                ->get()
                ->pluck('event.brand_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $query = Recap::query()
                ->with(['event.brand', 'event.location', 'event.manager'])
                ->where('ambassador_id', $user->id)
                ->where('is_draft', false)
                ->whereHas('event')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('event', fn ($eventQuery) => $eventQuery
                            ->where('event_name', 'LIKE', "%{$search}%"))
                            ->orWhereHas('event.brand', fn ($brandQuery) => $brandQuery
                                ->where('brand_name', 'LIKE', "%{$search}%"))
                            ->orWhereHas('event.location', fn ($locationQuery) => $locationQuery
                                ->where('city', 'LIKE', "%{$search}%")
                                ->orWhere('address', 'LIKE', "%{$search}%"))
                            ->orWhereHas('event.manager', fn ($managerQuery) => $managerQuery
                                ->where('name', 'LIKE', "%{$search}%"));
                    });
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            $total_record = $query->count();
            $data = $query->offset($start)->limit($length)->get();

            $venues = [];
            foreach ($data as $recap) {
                $event = $recap->event;
                $status = $recap->is_approved
                    ? 'Approved'
                    : ($recap->denied_at ? 'Denied' : 'Submitted');

                $venues[] = [
                    'id'           => $event?->id,
                    'event_id'     => $event?->id,
                    'recap_id'     => $recap->id,
                    'event_name'   => $event?->event_name ?? '-',
                    'brand'        => $event?->brand?->brand_name ?? '-',
                    'brand_name'   => $event?->brand?->brand_name ?? '-',
                    'city'         => $event?->location?->city ?? '-',
                    'location'     => trim((string) ($event?->location?->address ?? '').' '.(string) ($event?->location?->city ?? '')) ?: '-',
                    'manager'      => $event?->manager?->name ?? '-',
                    'manager_name' => $event?->manager?->name ?? '-',
                    'date'         => $event?->start_time ? Carbon::parse($event->start_time)->toDateString() : null,
                    'submitted_at' => $recap->created_at?->toIso8601String(),
                    'status'       => $status,
                    'is_approved'  => (bool) $recap->is_approved,
                ];
            }

            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $venues,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $total_record,
                'is_searched'       => $search,
                'brands'            => Brand::query()
                    ->select('id', 'brand_name')
                    ->whereIn('id', $brandIds)
                    ->orderBy('brand_name')
                    ->get(),
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }

        
        
    }
    
    public function getCreatedRecap(Request $request){
        $user = JWTAuth::user();
        if ($user) {
            $searchValue = (string) $request->input('search.value', '');
            $eventId = $searchValue !== '' ? (int) $searchValue : null;

            $allEvents = $this->recapListingService->openRecapEvents($user->id, $eventId);

            $total_record = $allEvents->count();
            $start        = (int) $request->input('start', 0);
            $paged        = $allEvents->slice($start, 10)->values();

            // Ensure relations used below are loaded (brand/location for display).
            $paged->loadMissing(['brand', 'location']);

            // Preload this ambassador's drafts (recaps.is_draft) for the page's
            // events so each row can show "Continue Draft" vs "Incomplete",
            // matching the web Create Recap list.
            $draftsByEventId = Recap::whereIn('event_id', $paged->pluck('id')->all())
                ->where('ambassador_id', $user->id)
                ->where('is_draft', true)
                ->get()
                ->keyBy('event_id');

            $venues = [];
            foreach ($paged as $val) {
                $draft = $draftsByEventId->get($val->id);
                $venues[] = [
                    'id'          => $val->id,
                    'event_name'  => $val->event_name ?? '-',
                    'city'        => $val->location?->city ?? '-',
                    // Same fields the web portal shows for parity.
                    'brand'       => $val->brand?->brand_name ?? '-',
                    'date_time'   => $val->FormattedTime,
                    'ambassadors' => ambassadorName($val->id),
                    'has_draft'   => (bool) $draft,
                    'draft_id'    => $draft?->id,
                    'action'      => '<ion-button expand="block" class="btn-warning mt-20 mb-10" data-event-id="'.$val->id.'">'.($draft ? 'Continue Draft' : 'Incomplete').'</ion-button>',
                ];
            }

            $eventsList = [];
            if ($searchValue === '') {
                foreach ($allEvents as $ev) {
                    $eventsList[] = [
                        'id'         => $ev->id,
                        'event_name' => $ev->event_name ?? '-',
                    ];
                }
            }

            return $this->successResponse('Data fetched successfully', [
                'draw'            => (int) $request->input('draw', 0),
                'data'            => $venues,
                'recordsTotal'    => $total_record,
                'recordsFiltered' => $total_record,
                'is_searched'     => $searchValue,
                'events'          => $eventsList,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getSubmittedRecap(Request $request){
        $user = JWTAuth::user();
        if ($user) {
            $searchValue = (string) $request->input('search.value', '');
            $marketIds = $this->assignedMarketIds($user);
            $marketId = $request->filled('marketId') ? (int) $request->marketId : null;

            $query = $this->recapListingService->submittedRecapEventsQuery(
                $user->id,
                $searchValue !== '' ? $searchValue : null,
                $marketId,
                ['brand', 'location', 'distributor', 'department', 'venueType', 'recap'],
            );

            $total_record = $query->count();
            $data = $query
                ->offset((int) $request->input('start', 0))
                ->limit(10)
                ->get();

            $venues = [];
            foreach ($data as $val) {
                $venues[] = [
                    'id'         => $val->id,
                    'event_name' => $val->event_name ?? '-',
                    'city'         => $val->location?->city ?? '-',
                    'submitted_at' => $val->recap?->created_at?->toIso8601String(),
                    'action'       => '<ion-icon name="eye" size="large" color="primary" id="'.$val->id.'"></ion-icon>',
                ];
            }

            return $this->successResponse('Data fetched successfully', [
                'draw'            => (int) $request->input('draw', 0),
                'data'            => $venues,
                'recordsTotal'    => $total_record,
                'recordsFiltered' => $total_record,
                'is_searched'     => $searchValue,
                'brands'          => Brand::get(),
                'assigned_markets' => Market::query()->active()->whereIn('id', $marketIds)->ordered()->get(),
                'markets'          => Market::query()->active()->whereIn('id', $marketIds)->ordered()->get(),
            ]);

        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getSingleSubmittedRecap(Request $request) {
        $user = JWTAuth::user();
        if ($user) {
            $recap = Recap::where(['id' => $request->id])->first();
            $locations = User::where('role_id',4)->get();
            $brands = Brand::get();
            return $this->successResponse('Data fetched successfully', [
                'recap'     => $recap,
                'brands'    => $brands,
                'locations' => $locations,
                'states'    => State::get()
            ]);
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    // public function updateSingleRecap(Request $request){
    //     $validatedData = $request->validate([
    //         'traffic_location' => 'required|string|max:255',
    //         'close_time' => 'required|date',
    //         'check_in_with_person' => 'required|string|max:255',
    //         'product_attachments1' => 'nullable|image|mimes:jpeg,png,jpg,gif',
    //         'promotion_product_status' => 'required|boolean',
    //         'tip' => 'nullable|string|max:255',
    //         'product_attachments2' => 'nullable|image|mimes:jpeg,png,jpg,gif',
    //         'brand_dmeo_name' => 'nullable|string|max:255',
    //         'brand_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif',
    //         'interaction_gender' => 'required|string',
    //         'interaction_age' => 'required|string',
    //         'total_interaction' => 'required|string',
    //         'samples' => 'nullable|string',
    //         'consumer_photo' => 'nullable|array',
    //         'consumer_photo.*' => 'image|mimes:jpeg,png,jpg,gif',
    //         'brand_selling_id' => 'nullable|exists:brands,id',
    //         'sold_unit' => 'nullable|string',
    //         'consumer_feedback' => 'nullable|string|max:255',
    //         'ambassador_feedback' => 'nullable|string|max:255',
    //         'receipt' => 'nullable|image|mimes:jpeg,png,jpg,gif',
    //         'state_id' => 'required|exists:states,id',
    //         'brand_count' => 'nullable',
    //     ]);
        

    //     if ($request->hasFile('product_attachments1')) {
    //         $product_attachments1 = $request->file('product_attachments1');
    //         $filename = time() . '_' . uniqid() . '.' . $product_attachments1->getClientOriginalExtension();
    //         $product_attachments1->move(public_path('recap'), $filename);
    //         $validatedData['product_attachments1'] = 'recap/' . $filename;
    //     }

    //     if ($request->hasFile('product_attachments2')) {
    //         $product_attachments2 = $request->file('product_attachments2');
    //         $filename = time() . '_' . uniqid() . '.' . $product_attachments2->getClientOriginalExtension();
    //         $product_attachments2->move(public_path('recap'), $filename);
    //         $validatedData['product_attachments2'] = 'recap/' . $filename;
    //     }

    //     if ($request->hasFile('brand_photo')) {
    //         $brand_photo = $request->file('brand_photo');
    //         $filename = time() . '_' . uniqid() . '.' . $brand_photo->getClientOriginalExtension();
    //         $brand_photo->move(public_path('recap'), $filename);
    //         $validatedData['brand_photo'] = 'recap/' . $filename;
    //     }

    //     // if ($request->hasFile('consumer_photo')) {
    //     //     $consumer_photo = $request->file('consumer_photo');
    //     //     $filename = time() . '_' . uniqid() . '.' . $consumer_photo->getClientOriginalExtension();
    //     //     $consumer_photo->move(public_path('recap'), $filename);
    //     //     $validatedData['consumer_photo'] = 'recap/' . $filename;
    //     // }
        
        
    //     if ($request->hasFile('consumer_photo')) {
    //         $consumerPhotos = $request->file('consumer_photo');
    //         $savedPhotos = [];  // Array to hold file paths of the uploaded photos

    //         // Loop through each file and process it
    //         foreach ($consumerPhotos as $consumerPhoto) {
    //             if ($consumerPhoto->isValid()) {
    //                 // Create a unique filename
    //                 $filename = time() . '_' . uniqid() . '.' . $consumerPhoto->getClientOriginalExtension();

    //                 // Move the file to the desired folder (e.g., 'recap')
    //                 $consumerPhoto->move(public_path('recap'), $filename);

    //                 // Store the path (relative to the public directory)
    //                 $savedPhotos[] = 'recap/' . $filename;
    //             }
    //         }
    //         $validatedData['consumer_photo'] = json_encode($savedPhotos);
    //     }

    //     if ($request->hasFile('receipt')) {
    //         $receipt = $request->file('receipt');
    //         $filename = time() . '_' . uniqid() . '.' . $receipt->getClientOriginalExtension();
    //         $receipt->move(public_path('recap'), $filename);
    //         $validatedData['receipt'] = 'recap/' . $filename;
    //     }

    //     // $validatedData['event_id'] = $request->event_id;
    //     $validatedData['state_id'] = $request->state_id;
    //     $validatedData['updated_by'] = Auth::id();
    //     $recap = Recap::where('id',$request->recap_id)->first();
    //     if (!$recap) {
    //         return redirect()->back()->with('error', 'Recap not found.');
    //     }
    //     $recap->update($validatedData);
  
    //     $data = [
    //         'event_name' => $recap->event?$recap->event->event_name:'-',
    //         'name' => User::where('id',$recap->ambassador_id)->first()->name,
    //     ];
    //     Mail::to('user1@jtplapps.in')->send(new SummitedRecapMail($data));
    //     if(Auth::id() == '1') {
    //         return redirect()->route('admin.recaps')->with('success', 'Recap has been successfully updated.');
    //     }
    //     return redirect()->route('ambassador.submitted-recap')->with('success', 'Recap has been successfully updated.');
    // }
    
    public function getPayForecast(Request $request){
        $user = JWTAuth::user();
        if ($user) {
            $userId = $user->id;
            $assignAmb = AssignAmbassador::where('ambassador_id', $userId)->pluck('event_id')->toArray();
        
            $wage = $user->wage;
        
            $query = Event::with('brand', 'location', 'eventType', 'client', 'recap', 'assignAmbassadors')
                          ->whereIn('id', $assignAmb)
                          ->where('event_status', '!=', '2')
                          // Previous requirement (chronological ascending — kept for easy restore):
                          // ->orderBy('start_time', 'ASC')
                          // Latest client/PM requirement — descending by event date (farthest/newest first):
                          ->orderBy('start_time', 'DESC');
        
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = $request->start_date;
                $endDate = $request->end_date;
                $query->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }
        
            $getEventCont = $query->count();
            $recaps = $query->get();
        
            $totalMinutes = 0;
            $totalOverridePay = 0;
        
            foreach ($recaps as $recap) {
                // Calculate Time
                if ($recap->start_time && $recap->end_time) {
                    $start = \Carbon\Carbon::parse($recap->start_time);
                    $end = \Carbon\Carbon::parse($recap->end_time);
                    $minutes = $start->diffInMinutes($end);
                    $totalMinutes += $minutes;
                }
        
                // Sum override pay
                $overridePay = optional($recap->assignAmbassadors)->override_pay;
                if (!empty($overridePay) && is_numeric($overridePay)) {
                    $totalOverridePay += (float)$overridePay;
                }
            }
        
            // Wage-based pay calculation
            $hoursWorked = floor($totalMinutes / 60);
            $extraMinutes = $totalMinutes % 60;
        
            $perMinuteWage = $wage / 60; // wage per minute
            $wagePay = ($hoursWorked * $wage) + ($extraMinutes * $perMinuteWage);
        
            // Final total pay
            $totalPay = round($wagePay + $totalOverridePay, 2); // round to 2 decimal places
        
            // Format time display
            $totalHoursFormatted = str_pad($hoursWorked, 2, '0', STR_PAD_LEFT) . ':' . str_pad($extraMinutes, 2, '0', STR_PAD_LEFT) . ' Hours';
        
        
        
            $venues=[];
            foreach ($recaps as $recap) {
                $venues[]=array(
                    'location'      => $recap->location ? $recap->location->address : '',
                    'brand'         => $recap->brand->brand_name,
                    'totalPay'      => '$'.$totalPay,
                    'action'        => '<ion-icon name="eye"  size="large" color="primary" id="'.$recap->id.'"></ion-icon>'
                );
            }
        
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $venues,
                'recordsTotal'      => $getEventCont,
                'recordsFiltered'   => $getEventCont,
                'wage'                  =>$wage,
                'getEventCont'          =>$getEventCont, 
                'totalPay'              =>$totalPay,
                'totalHoursFormatted'   =>$totalHoursFormatted, 
                'totalMinutes'          =>$totalMinutes
            ]);
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function getEventlist(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $events=[];
            $assignAmb = AssignAmbassador::where('ambassador_id',$user->id)->pluck('event_id')->toArray();
            $marketIds = $this->assignedMarketIds($user);
            $temp = Event::whereIn('id',$assignAmb)->whereHas('markets', fn ($q) => $q->whereIn('markets.id', $marketIds))->whereBetween('start_time',[
                $request->date.' 00:00:00',
                $request->date.' 23:59:59'
            ])->where('event_status', '!=', '2'); // Exclude cancelled — list matches badge count
            // Previous requirement (chronological ascending — kept for easy restore):
            // $data=$temp->orderBy('start_time', 'ASC')->orderBy('id', 'ASC')->offset($request->start)->limit(10)->get();
            // Latest client/PM requirement — descending by event date (farthest/newest first):
            $data=$temp->orderBy('start_time', 'DESC')->orderBy('id', 'DESC')->offset($request->start)->limit(10)->get();
            foreach($data as $key=>$event){
                $events[]=array(
                    'event_name'    => $event->event_name,
                    'booked_date'   => date('M j, Y',strtotime($event->booked_date)),
                    'status'        => $event->event_status=="1" ? "Completed" : ($event->event_status=="2" ? 'Cancelled' : "Pending"),
                    'action'        => '<ion-icon name="eye"  size="large" color="primary" id="'.$event->id.'"></ion-icon>'
                );
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $events,
                'recordsTotal'      => $temp->count(),
                'recordsFiltered'   => $temp->count()
            ]);
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getPayForecastDetail(Request $request){
        $user = JWTAuth::user();  
        if($user){
            $isAssigned = AssignAmbassador::query()
                ->where('event_id', (int) $request->id)
                ->where('ambassador_id', (int) $user->id)
                ->where('status', '1')
                ->exists();

            if (! $isAssigned) {
                return $this->errorResponse('Event not found', [], 404);
            }

            $recap = Recap::with('state', 'event','event.eventAttachments')
                ->where('event_id', (int) $request->id)
                ->where('ambassador_id', (int) $user->id)
                ->first();
            if($recap){
                $recap->event->start_time=date("h:i A", strtotime($recap->event->start_time));
                $recap->event->end_time=date("h:i A", strtotime($recap->event->end_time));
                $recap->event->brand=Brand::where('id',$recap->event->brand_id)->first();
            }
            return $this->successResponse('Event Data.', $recap);    
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getPayments(Request $request){
        $user = JWTAuth::user();
        $venues = [];
        if($user){
            $assignAmb = AssignAmbassador::where('ambassador_id', $user->id)->pluck('event_id')->toArray();
            $submittedId = Recap::where('ambassador_id', $user->id)->pluck('event_id')->toArray();
            $eventDifference = array_diff($assignAmb, $submittedId);
            $wage = $user->wage;
            $query = Event::with('brand', 'location', 'eventType', 'client', 'recap','assignAmbassadors')
                          ->whereIn('id', $submittedId)
                          ->where('event_status', '1')
                          // Previous requirement (chronological ascending — kept for easy restore):
                          // ->orderBy('start_time', 'ASC')
                          // Latest client/PM requirement — descending by event date (farthest/newest first):
                          ->orderBy('start_time', 'DESC');
    
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = $request->start_date;
                $endDate = $request->end_date;
                $query->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }
    
            $getEventCont = $query->count();
            $recaps = $query->get();
            foreach ($recaps as $recap) {
                $venues[]=array(
                    'location'      => $recap->location ? $recap->location->address : '',
                    'brand'         => $recap->brand->brand_name,
                    // 'event_pay'     => $recap->assignAmbassadors ? ($user->wage + $recap->assignAmbassadors->pinata_amount + ($recap->assignAmbassadors->override_pay) : 0
                    'event_pay'     => '$'. number_format(($recap->assignAmbassadors ? ($user->wage + $recap->assignAmbassadors->pinata_amount + ($recap->assignAmbassadors->override_pay ?? 0)) : 0),2),
                    'action'        => '<ion-icon name="eye"  size="large" color="primary" id="'.$recap->id.'"></ion-icon>'
                );
            }

            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $venues,
                'recordsTotal'      => $getEventCont,
                'recordsFiltered'   => $getEventCont,
            ]);
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getReadyCheckInEvents(Request $request, EventCheckInService $checkInService)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $this->eventService->markEndedEventsCompletedForApi();

        $assignments = AssignAmbassador::query()
            ->with(['event.location', 'event.brand'])
            ->where('ambassador_id', (int) $user->id)
            ->where('status', '1')
            ->where(function ($q) {
                $q->whereNull('event_date_time');
            })
            ->whereHas('event', function ($q) {
                $q->whereNotIn('event_status', ['1', '2'])
                    ->whereNotNull('start_time');
            })
            ->get();

        $events = $assignments
            ->filter(fn (AssignAmbassador $assignment) => $assignment->event
                && $checkInService->isEligibleForCheckIn($assignment->event, $assignment))
            ->map(function (AssignAmbassador $assignment) use ($checkInService) {
                $event = $assignment->event;

                return [
                    'id' => (int) $event->id,
                    'event_name' => (string) ($event->event_name ?? 'Event'),
                    'event_location_label' => $checkInService->eventLocationLabel($event),
                    'booked_date' => $event->booked_date,
                    'datetime' => $event->FormattedTime,
                ];
            })
            ->sortBy(fn (array $row) => $row['booked_date'] ?? '')
            ->values();

        return $this->successResponse('Ready check-in events.', $events);
    }

    public function getEventDetails(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $event = Event::with('distributor','department','venueType','brand','location','eventType','eventAttachments','manager')->where('id',$request->id)->orderBy('id','DESC')->first();
            $isAssigned = $event && AssignAmbassador::query()
                ->where('event_id', (int) $event->id)
                ->where('ambassador_id', (int) $user->id)
                ->where('status', '1')
                ->exists();

            if ($event && ! $isAssigned) {
                return $this->errorResponse('Event not found', [], 404);
            }

            $recaps = Recap::with('ambassador')
                ->where('event_id', (int) $request->id)
                ->where('ambassador_id', (int) $user->id)
                ->get();
            if($event){
                // Sub-task 2 Bug 1 (MC cmon1a7u4005d3r1h5cdct2vh): mobile event-detail
                // page binds the date to `event.booked_date`, which is the date the
                // event was *added to the system* (testers correctly identified this
                // as 'creation date'). The actual event date lives in start_time.
                // Capture start_time BEFORE the time-only overwrites below, then
                // replace booked_date with it so the unchanged mobile bundle starts
                // displaying the event date without needing a rebuild.
                $event->start_time_raw = $event->start_time;
                $event->end_time_raw = $event->end_time;
                $eventDate = $event->start_time ? date('Y-m-d', strtotime($event->start_time)) : null;
                $event->event_date = $eventDate;
                if ($eventDate) {
                    $event->booked_date = $eventDate;
                }
                $tzFormatted = app(\App\Services\EventTimezoneService::class)->formatEventTimesForApi($event);
                $event->start_time = $tzFormatted['start_time'] ?? $event->start_time;
                $event->end_time = $tzFormatted['end_time'] ?? $event->end_time;
                $event->time_display = $tzFormatted['time_display'] ?? null;
                $event->timezone_label = $tzFormatted['timezone_label'] ?? $event->timezone_label;
                $event->datetime = $event->FormattedTime;
                $getAmbassador = AssignAmbassador::where(['event_id' => $event->id, 'status' => '1'])->pluck('ambassador_id')->toArray();
                $event->is_assigned = $isAssigned ? 1 : 0;
                if ($isAssigned) {
                    $userAssignment = AssignAmbassador::where([
                        'event_id' => $event->id,
                        'ambassador_id' => $user->id,
                        'status' => '1',
                    ])->first();
                    $event->is_checked_in = $userAssignment && ! empty($userAssignment->event_date_time) ? 1 : 0;
                    $event->is_at_event_location = $userAssignment?->is_at_event_location;
                    $event->check_in_distance_meters = $userAssignment?->distance_from_event_meters;
                }
                $event->event_location_label = app(EventCheckInService::class)->eventLocationLabel($event);
                // Billing data and client charge amounts are not part of the Ambassador workflow.
                $event->makeHidden([
                    'client_id',
                    'billing_parties_data',
                    'labor_amount',
                    'buyback_amount',
                    'cups_amount',
                ]);
                if($getAmbassador) {
                    $ambData = User::whereIn('id',$getAmbassador)->pluck('name')->toArray();
                    $name = implode(",",$ambData);
                    $event->assigned_person=$name;
                    $event->ambassadors=User::select('id','name','phone')->whereIn('id',$getAmbassador)->get();
                }
            }
            return $this->successResponse('Event Data.', ['event'=>$event,'recaps'=>$recaps]);    
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getRecapById(Request $request){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $states = State::get();

        if ($request->filled('event_id')) {
            $event = Event::with('eventType', 'brand', 'location')->find($request->event_id);
            if (! $event) {
                return $this->errorResponse('Event not found', [], 404);
            }

            $recap = Recap::with('event.location', 'event.brand', 'event.eventType', 'brand', 'state', 'ambassador')
                ->where('event_id', $event->id)
                ->where('ambassador_id', $user->id)
                ->where('is_draft', true)
                ->first();

            $brands = $this->brandsForRecapEvent($event);
            $recapType = $recap
                ? RecapFormService::resolveForRecap($recap)
                : RecapFormService::resolveFromEvent($event);

            return $this->successResponse('Data Fetched Successfully!', [
                'recap' => $recap,
                'event' => $event,
                'recap_type' => $recapType,
                'recap_form_label' => RecapFormService::displayLabel($recapType),
                'event_type_name' => $event->eventType?->name,
                'brands' => $brands,
                'states' => $states,
            ]);
        }

        if ($request->filled('id')) {
            $recap = Recap::with('event.location', 'event.brand', 'event.eventType', 'brand.user', 'client', 'state', 'ambassador')
                ->find($request->id);

            if (! $recap) {
                return $this->errorResponse('Recap not found', [], 404);
            }

            $brands = $this->brandsForRecapEvent($recap->event);

            $recapType = RecapFormService::resolveForRecap($recap);

            return $this->successResponse('Data Fetched Successfully!', [
                'recap' => $recap,
                'event' => $recap->event,
                'recap_type' => $recapType,
                'recap_form_label' => RecapFormService::displayLabel($recapType),
                'event_type_name' => $recap->event?->eventType?->name,
                'brands' => $brands,
                'states' => $states,
            ]);
        }

        return $this->errorResponse('Recap or event id is required', [], 422);
    }
    
    /** Return the mobile-only draft owned by this ambassador for an event. */
    public function getRecapDraft(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user || (int) ($user->role_id ?? 0) !== 5) {
            return $this->errorResponse('Ambassador access required', [], 403);
        }
        if (! $request->filled('event_id')) {
            return $this->errorResponse('Event id is required', 'ERROR', 422, new \stdClass());
        }

        $event = Event::with('eventType')->find($request->integer('event_id'));
        if (! $event) {
            return $this->errorResponse('Event not found', [], 404);
        }

        $draft = RecapDraft::where('ambassador_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        return $this->successResponse('Draft fetched successfully.', [
            'draft' => $draft,
            'event' => $event,
            'recap_type' => $draft?->recap_type ?: RecapFormService::resolveFromEvent($event),
        ]);
    }

    /** Upsert a partial draft without changing submitted recap validation/workflow. */
    public function saveRecapDraft(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user || (int) ($user->role_id ?? 0) !== 5) {
            return $this->errorResponse('Ambassador access required', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'event_id' => 'required|integer|exists:events,id',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', 422, new \stdClass());
        }

        $event = Event::with('eventType')->find((int) $request->event_id);

        // Unified draft store: drafts live on the recaps table (is_draft = true) —
        // the SAME store the web portal uses — so a draft saved on the app is
        // visible on the web and vice versa. Mirrors Ambassador\RecapController.
        $recap = Recap::where('event_id', $event->id)
            ->where('ambassador_id', $user->id)
            ->where('is_draft', true)
            ->first();

        if (! $recap) {
            $recap = new Recap();
            $recap->event_id = $event->id;
            $recap->ambassador_id = $user->id;
            $recap->created_by = $user->id;
            $recap->recap_type = RecapFormService::resolveFromEvent($event);
        }

        foreach (RecapFormService::draftAllowedFields() as $field) {
            if ($request->exists($field)) {
                $value = $request->input($field);
                // Empty strings → null so partial drafts never fail integer/FK
                // columns (e.g. state_id) while a field is left blank.
                if ($field === 'products_sold' && is_array($value)) {
                    $value = ! empty($value) ? json_encode($value) : null;
                }
                $recap->{$field} = ($value === '') ? null : $value;
            }
        }

        if ($request->exists('buyback_amount')) {
            $raw = $request->input('buyback_amount');
            $recap->buyback_amount = ($raw !== null && $raw !== '') ? round((float) $raw, 2) : null;
        }

        if ($request->exists('is_expedited_requested')) {
            $recap->is_expedited_requested = $request->boolean('is_expedited_requested');
            $recap->expedited_requested_at = $recap->is_expedited_requested ? now() : null;
        }

        if ($request->exists('backup_brand_used')) {
            $recap->backup_brand_used = (bool) (int) $request->input('backup_brand_used');
            if (! $recap->backup_brand_used) {
                $recap->backup_brand_name = null;
            }
        }

        // Single-file uploads.
        foreach (['product_attachments1', 'product_attachments2', 'brand_photo', 'receipt'] as $requestKey) {
            if (! $request->hasFile($requestKey)) {
                continue;
            }
            $file = $request->file($requestKey);
            if (! $file->isValid()) {
                return $this->errorResponse('Invalid File', 'ERROR', 422, new \stdClass());
            }
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('recap'), $filename);
            $recap->{$requestKey} = 'recap/'.$filename;
        }

        // Multi-file: consumer photos (base form) and off-premise event photos both
        // persist to consumer_photo, matching the submit path.
        foreach (['consumer_photo', 'op_event_photos'] as $requestKey) {
            if (! $request->hasFile($requestKey)) {
                continue;
            }
            $paths = [];
            foreach ((array) $request->file($requestKey) as $file) {
                if (! $file || ! $file->isValid()) {
                    return $this->errorResponse('Invalid File', 'ERROR', 422, new \stdClass());
                }
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->move(public_path('recap'), $filename);
                $paths[] = 'recap/'.$filename;
            }
            $recap->consumer_photo = json_encode(
                $requestKey === 'op_event_photos'
                    ? $this->mergeStoredAttachmentPaths($recap->consumer_photo, $paths)
                    : $paths
            );
        }

        if ($request->hasFile('op_event_receipts')) {
            $paths = [];
            foreach ((array) $request->file('op_event_receipts') as $file) {
                if (! $file || ! $file->isValid()) {
                    return $this->errorResponse('Invalid File', 'ERROR', 422, new \stdClass());
                }
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->move(public_path('recap'), $filename);
                $paths[] = 'recap/'.$filename;
            }
            $recap->op_receipts = json_encode($this->mergeStoredAttachmentPaths($recap->op_receipts, $paths));
        }

        // Off-premise boolean checkboxes.
        foreach (['store_aware_of_demo', 'store_prepared_for_demo', 'product_on_sale', 'coupons_rebates_available', 'enough_product_in_stock', 'case_display'] as $boolField) {
            if ($request->exists($boolField)) {
                $recap->{$boolField} = (bool) $request->input($boolField);
            }
        }

        $recap->is_draft = true;
        $recap->updated_by = $user->id;
        $recap->save();

        return $this->successResponse('Draft saved successfully.', [
            'recap' => $recap->fresh(),
            'event' => $event,
            'recap_type' => $recap->recap_type,
        ]);
    }

    public function deleteRecapDraft(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user || (int) ($user->role_id ?? 0) !== 5) {
            return $this->errorResponse('Ambassador access required', [], 403);
        }
        $validator = Validator::make($request->all(), ['event_id' => 'required|integer']);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', 422, new \stdClass());
        }

        $deleted = RecapDraft::where('ambassador_id', $user->id)
            ->where('event_id', (int) $request->event_id)
            ->delete();

        return $this->successResponse($deleted ? 'Draft deleted successfully.' : 'Draft not found.', [
            'deleted' => (bool) $deleted,
            'event_id' => (int) $request->event_id,
        ]);
    }

    public function addUpdateRecap(Request $request){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $recap = null;
        if ($request->filled('id')) {
            $recap = Recap::with('event.eventType')->find($request->id);
            if (! $recap) {
                return $this->errorResponse('Recap not found', [], 404);
            }
        }

        $event = $recap?->event;
        if (! $event && $request->filled('event_id')) {
            $event = Event::with('eventType')->find($request->event_id);
        }

        if (! $event) {
            return $this->errorResponse('Event not found', [], 404);
        }

        // Reuse an existing draft (recaps.is_draft) for this event so submitting a
        // saved draft transitions the SAME row to submitted instead of creating a
        // duplicate. Keeps web + app drafts unified on the recaps table.
        if (! $recap && ! $request->filled('id')) {
            $recap = Recap::where('event_id', $event->id)
                ->where('ambassador_id', $user->id)
                ->where('is_draft', true)
                ->first();
        }

        $recapType = $recap
            ? RecapFormService::resolveForRecap($recap)
            : RecapFormService::resolveFromEvent($event);

        if ($recapType === RecapFormService::TYPE_BEST) {
            return $this->errorResponse('BEST recaps must be submitted from the web recap form.', [], 422);
        }

        $validator = Validator::make($request->all(), RecapFormService::submitRules($recapType, $event));
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }

        $validated = $validator->validated();

        if (array_key_exists('products_sold', $validated)) {
            $validated['products_sold'] = ! empty($validated['products_sold'])
                ? json_encode($validated['products_sold'])
                : null;
        }

        if (! $recap) {
            $recap = new Recap();
            $recap->event_id = $event->id;
            $recap->ambassador_id = $user->id;
            $recap->created_by = $user->id;
            $recap->recap_type = $recapType;
        }

        foreach (RecapFormService::draftAllowedFields() as $field) {
            if (array_key_exists($field, $validated)) {
                $recap->{$field} = $validated[$field];
            }
        }

        $uploadFields = [
            'product_attachments1' => 'recap',
            'product_attachments2' => 'recap',
            'brand_photo' => 'recap',
            'receipt' => 'recap',
        ];

        foreach ($uploadFields as $requestKey => $folder) {
            if ($request->hasFile($requestKey)) {
                $image = $request->file($requestKey);
                if (! $image->isValid()) {
                    return $this->errorResponse('Invalid File', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
                $imagename = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
                $image->move(public_path($folder), $imagename);
                $recap->{$requestKey} = $folder.'/'.$imagename;
            }
        }

        if ($request->hasFile('consumer_photo')) {
            $savedPhotos = [];
            foreach ((array) $request->file('consumer_photo') as $consumerPhoto) {
                if ($consumerPhoto && $consumerPhoto->isValid()) {
                    $imagename = time().'_'.uniqid().'.'.$consumerPhoto->getClientOriginalExtension();
                    $consumerPhoto->move(public_path('recap'), $imagename);
                    $savedPhotos[] = 'recap/'.$imagename;
                }
            }
            if ($savedPhotos !== []) {
                $recap->consumer_photo = json_encode($savedPhotos);
            }
        }

        $savedOffPremisePhotos = $this->storeOffPremiseUploadsForApi($request, 'op_event_photos', $event->id, $recapType);
        if ($savedOffPremisePhotos !== []) {
            $recap->consumer_photo = json_encode(
                $this->mergeStoredAttachmentPaths($recap->consumer_photo, $savedOffPremisePhotos)
            );
        }

        $savedOffPremiseReceipts = $this->storeOffPremiseUploadsForApi($request, 'op_event_receipts', $event->id, $recapType);
        if ($savedOffPremiseReceipts !== []) {
            $recap->op_receipts = json_encode(
                $this->mergeStoredAttachmentPaths($recap->op_receipts, $savedOffPremiseReceipts)
            );
        }

        if (array_key_exists('buyback_amount', $validated)) {
            $recap->buyback_amount = ($validated['buyback_amount'] !== null && $validated['buyback_amount'] !== '')
                ? round((float) $validated['buyback_amount'], 2)
                : null;
        }

        if (array_key_exists('is_expedited_requested', $validated)) {
            $recap->is_expedited_requested = (bool) $validated['is_expedited_requested'];
            $recap->expedited_requested_at = $recap->is_expedited_requested ? now() : null;
        }

        $recap->is_draft = false;
        $recap->updated_by = $user->id;
        $recap->save();
        $recap->syncBuybackToEvent();
        app(ReviewNotificationService::class)->recapSubmitted($user, $recap->fresh(['event.brand', 'event.location']));

        if ($request->filled('id')) {
            return $this->successResponse('Updated Recap Successfully', $recap);
        }

        return $this->successResponse('Created Recap Successfully', $recap);
    }

    protected function brandsForRecapEvent(?Event $event)
    {
        $brandId = $event?->brand_id ? (int) $event->brand_id : 0;
        if ($brandId > 0) {
            $ids = Brand::descendantIdsIncludingSelfById($brandId);
            if (! empty($ids)) {
                return Brand::whereIn('id', $ids)->orderBy('brand_name')->get();
            }
        }

        return Brand::orderBy('brand_name')->get();
    }

    private function storeOffPremiseUploadsForApi(Request $request, string $field, int $eventId, string $recapType): array
    {
        if (! $request->hasFile($field)) {
            return [];
        }

        $paths = [];
        $eventFolder = (string) $eventId;
        $typeFolder = preg_replace('/[^A-Za-z0-9_-]/', '-', $recapType) ?: RecapFormService::TYPE_OFF_PREMISE;
        $relativeDirectory = "recap/{$eventFolder}/{$typeFolder}";
        File::ensureDirectoryExists(public_path($relativeDirectory));

        foreach ((array) $request->file($field, []) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path($relativeDirectory), $filename);
            $paths[] = $relativeDirectory.'/'.$filename;
        }

        return $paths;
    }

    /**
     * Append newly uploaded Off-Premise files without discarding files already
     * saved by a prior draft or recap update.
     *
     * @param  list<string>  $newPaths
     * @return list<string>
     */
    private function mergeStoredAttachmentPaths(mixed $storedValue, array $newPaths): array
    {
        $rawStoredValue = trim((string) ($storedValue ?? ''));
        $decodedStoredValue = json_decode($rawStoredValue, true);

        if (is_array($decodedStoredValue)) {
            $storedPaths = array_values(array_filter($decodedStoredValue, 'is_string'));
        } elseif (is_string($decodedStoredValue) && $decodedStoredValue !== '') {
            $storedPaths = [$decodedStoredValue];
        } else {
            // Preserve legacy recaps that stored a single unencoded path.
            $storedPaths = $rawStoredValue !== '' ? [$rawStoredValue] : [];
        }

        return array_values(array_unique(array_merge($storedPaths, $newPaths)));
    }
    
    public function storeGPSTracking(Request $request, EventCheckInService $checkInService){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validated = $request->validate([
            'event' => 'required|integer|exists:events,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'force_check_in' => 'sometimes|boolean',
            // mobile sends `data_time` (string); treat as optional and fall back to now.
            'data_time' => 'nullable|string|max:255',
        ]);

        $assign = AssignAmbassador::where([
            'event_id' => (int) $validated['event'],
            'ambassador_id' => (int) $user->id,
        ])->first();

        if (! $assign) {
            return $this->errorResponse('Ambassador not assigned to this event', [], 403);
        }

        $event = Event::with(['location', 'brand', 'eventType', 'department', 'distributor'])->find((int) $validated['event']);
        if (! $event) {
            return $this->errorResponse('Event not found', [], 404);
        }

        $result = $checkInService->processCheckIn(
            $user,
            $event,
            $assign,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (bool) ($validated['force_check_in'] ?? false),
            $validated['data_time'] ?? null
        );

        if (! ($result['ok'] ?? false)) {
            return $this->errorResponse(
                $result['message'] ?? 'Check-in could not be completed.',
                $result['code'] ?? 'check_in_failed',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                array_filter([
                    'requires_confirmation' => $result['requires_confirmation'] ?? false,
                    'distance_meters' => $result['distance_meters'] ?? null,
                    'allowed_radius_meters' => $result['allowed_radius_meters'] ?? null,
                ], static fn ($v) => $v !== null)
            );
        }

        /** @var AssignAmbassador $assignment */
        $assignment = $result['assignment'];

        return $this->successResponse('Check-in recorded successfully.', [
            'event' => (int) $validated['event'],
            'checked_in' => true,
            'first_check_in' => (bool) ($result['first_check_in'] ?? false),
            'is_at_event_location' => (bool) ($result['is_at_event_location'] ?? false),
            'forced_check_in' => (bool) ($result['forced_check_in'] ?? false),
            'distance_meters' => $result['distance_meters'] ?? null,
            'event_date_time' => $assignment->event_date_time,
        ]);
    }

    public function getReimbursements(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $query = Reimbursement::with(['event.brand', 'brand'])
            ->where('user_id', $user->id)
            ->when($request->input('search.value'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('reason', 'LIKE', "%{$search}%")
                        ->orWhere('brand_name', 'LIKE', "%{$search}%")
                        ->orWhere('status', 'LIKE', "%{$search}%");
                });
            });

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('created_at')
            ->offset((int) $request->input('start', 0))
            ->limit((int) $request->input('length', 10))
            ->get();

        $data = [];
        foreach ($rows as $reimbursement) {
            if ($reimbursement->event) {
                $brandEvent = ($reimbursement->event->brand->brand_name ?? '-').' (Event #'.$reimbursement->event_id.')';
            } else {
                $brandEvent = $reimbursement->brand->brand_name ?? $reimbursement->brand_name ?? '-';
            }

            $action = '<ion-icon name="eye" size="large" color="primary" id="'.$reimbursement->id.'"></ion-icon>';
            if ($reimbursement->status === 'rejected') {
                $action .= '<ion-icon name="create" size="large" color="success" id="edit-'.$reimbursement->id.'"></ion-icon>';
            }

            $data[] = [
                'id' => $reimbursement->id,
                'expense_date' => $reimbursement->expense_date ? $reimbursement->expense_date->format('M d, Y') : '-',
                'brand_event' => $brandEvent,
                'amount' => '$'.number_format((float) $reimbursement->amount, 2),
                'status' => ucfirst($reimbursement->status),
                'action' => $action,
            ];
        }

        return $this->successResponse('Data fetched successfully', [
            'draw' => (int) $request->input('draw'),
            'data' => $data,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'has_verified_bank' => $user->hasVerifiedBankAccount(),
        ]);
    }

    public function getReimbursementFormData(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $workedEventIds = AssignAmbassador::where('ambassador_id', $user->id)
            ->whereIn('status', ['0', '1'])
            ->pluck('event_id');

        $events = Event::with('brand')
            ->whereIn('id', $workedEventIds)
            ->orderByDesc('booked_date')
            ->get()
            ->map(function (Event $event) {
                $bookedDate = $event->booked_date
                    ? Carbon::parse($event->booked_date)->format('M d, Y')
                    : '-';

                return [
                    'id' => $event->id,
                    'label' => $bookedDate.' - '.($event->brand->brand_name ?? 'Event').' #'.$event->id,
                    'brand_name' => $event->brand->brand_name ?? null,
                ];
            })
            ->values();

        return $this->successResponse('Reimbursement form data loaded.', [
            'has_verified_bank' => $user->hasVerifiedBankAccount(),
            'brands' => Brand::orderBy('brand_name')->get(['id', 'brand_name']),
            'events' => $events,
        ]);
    }

    public function getReimbursementById(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $reimbursement = Reimbursement::with(['event.brand', 'brand'])
            ->where('user_id', $user->id)
            ->find((int) $request->id);

        if (! $reimbursement) {
            return $this->errorResponse('Reimbursement not found', [], 404);
        }

        return $this->successResponse('Reimbursement loaded.', $this->formatReimbursementForApi($reimbursement));
    }

    public function storeReimbursement(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        if (! $user->hasVerifiedBankAccount()) {
            return $this->errorResponse(
                'You must add and verify a bank account before requesting reimbursement.',
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $eventRule = $user->role_id == 5
            ? Rule::exists('assign_ambassadors', 'event_id')
                ->where(function ($query) use ($user) {
                    $query->where('ambassador_id', $user->id)
                        ->whereIn('status', ['0', '1']);
                })
            : Rule::exists('events', 'id');

        $validated = $request->validate([
            'event_id' => ['nullable', $eventRule],
            'brand_id' => 'nullable|exists:brands,id',
            'brand_name' => 'nullable|string|max:255',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01|max:99999.99',
            'reason' => 'required|string|max:1000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if (empty($validated['event_id']) && empty($validated['brand_id']) && empty($validated['brand_name'])) {
            return $this->errorResponse('Please provide either an event, brand, or brand name.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('reimbursements', 'public');
        }

        $reimbursement = Reimbursement::create([
            'user_id' => $user->id,
            'event_id' => $validated['event_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'expense_date' => $validated['expense_date'],
            'brand_name' => $validated['brand_name'] ?? null,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'receipt_path' => $receiptPath,
            'status' => 'pending',
        ]);

        app(ReviewNotificationService::class)->reimbursementSubmitted($user, $reimbursement);

        return $this->successResponse(
            'Reimbursement submitted successfully. A manager will review it.',
            $this->formatReimbursementForApi($reimbursement->fresh(['event.brand', 'brand']))
        );
    }

    public function updateReimbursement(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $reimbursement = Reimbursement::where('user_id', $user->id)->find((int) $request->id);
        if (! $reimbursement) {
            return $this->errorResponse('Reimbursement not found', [], 404);
        }

        if ($reimbursement->status !== 'rejected') {
            return $this->errorResponse('Only rejected reimbursements can be updated.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $user->hasVerifiedBankAccount()) {
            return $this->errorResponse(
                'You must add and verify a bank account before requesting reimbursement.',
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $eventRule = $user->role_id == 5
            ? Rule::exists('assign_ambassadors', 'event_id')
                ->where(function ($query) use ($user) {
                    $query->where('ambassador_id', $user->id)
                        ->whereIn('status', ['0', '1']);
                })
            : Rule::exists('events', 'id');

        $validated = $request->validate([
            'event_id' => ['nullable', $eventRule],
            'brand_id' => 'nullable|exists:brands,id',
            'brand_name' => 'nullable|string|max:255',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01|max:99999.99',
            'reason' => 'required|string|max:1000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if (empty($validated['event_id']) && empty($validated['brand_id']) && empty($validated['brand_name'])) {
            return $this->errorResponse('Please provide either an event, brand, or brand name.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $receiptPath = $reimbursement->receipt_path;
        if ($request->hasFile('receipt')) {
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            $receiptPath = $request->file('receipt')->store('reimbursements', 'public');
        }

        $reimbursement->update([
            'event_id' => $validated['event_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'expense_date' => $validated['expense_date'],
            'brand_name' => $validated['brand_name'] ?? null,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'receipt_path' => $receiptPath,
            'status' => 'pending',
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        app(ReviewNotificationService::class)->reimbursementUpdated($user, $reimbursement);

        return $this->successResponse(
            'Reimbursement updated and resubmitted for review.',
            $this->formatReimbursementForApi($reimbursement->fresh(['event.brand', 'brand']))
        );
    }

    private function formatReimbursementForApi(Reimbursement $reimbursement): array
    {
        if ($reimbursement->event) {
            $brandEvent = ($reimbursement->event->brand->brand_name ?? '-').' (Event #'.$reimbursement->event_id.')';
        } else {
            $brandEvent = $reimbursement->brand->brand_name ?? $reimbursement->brand_name ?? '-';
        }

        return [
            'id' => $reimbursement->id,
            'event_id' => $reimbursement->event_id,
            'brand_id' => $reimbursement->brand_id,
            'brand_name' => $reimbursement->brand_name,
            'brand_event' => $brandEvent,
            'expense_date' => $reimbursement->expense_date?->format('Y-m-d'),
            'expense_date_formatted' => $reimbursement->expense_date?->format('M d, Y'),
            'amount' => (float) $reimbursement->amount,
            'amount_formatted' => '$'.number_format((float) $reimbursement->amount, 2),
            'reason' => $reimbursement->reason,
            'status' => $reimbursement->status,
            'status_label' => ucfirst($reimbursement->status),
            'rejection_reason' => $reimbursement->rejection_reason,
            'receipt_url' => $reimbursement->receipt_path ? asset('storage/'.$reimbursement->receipt_path) : null,
            'submitted_at' => $reimbursement->created_at?->format('M d, Y H:i'),
            'paid_at' => $reimbursement->paid_at?->format('M d, Y'),
            'can_edit' => $reimbursement->status === 'rejected',
        ];
    }

    private function sortEventsForApi(Collection $events, string $sortOrder): Collection
    {
        $nowUtc = Carbon::now('UTC')->getTimestamp();

        return $events->sort(function (Event $left, Event $right) use ($sortOrder, $nowUtc) {
            [$leftBucket, $leftTimestamp] = $this->eventSortKey($left, $nowUtc);
            [$rightBucket, $rightTimestamp] = $this->eventSortKey($right, $nowUtc);

            if ($sortOrder === 'desc') {
                return [$rightTimestamp, $right->id] <=> [$leftTimestamp, $left->id];
            }

            if ($leftBucket !== $rightBucket) {
                return $leftBucket <=> $rightBucket;
            }

            return [$leftTimestamp, $left->id] <=> [$rightTimestamp, $right->id];
        })->values();
    }

    private function eventSortKey(Event $event, int $nowUtc): array
    {
        if (empty($event->start_time)) {
            return [2, PHP_INT_MAX];
        }

        $timezone = app(\App\Services\EventTimezoneService::class)->normalizeIana($event->timezone);
        $timestamp = Carbon::parse($event->start_time, $timezone)->utc()->getTimestamp();
        $bucket = $timestamp >= $nowUtc ? 0 : 1;

        return [$bucket, $timestamp];
    }

}
    

    
