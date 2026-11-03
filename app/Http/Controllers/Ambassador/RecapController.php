<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\AssignAmbassador;
use App\Models\Brand;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Recap;
use App\Models\User;
use App\Services\BEST\EventPushService;
use App\Services\AmbassadorRecapListingService;
use App\Services\RecapFormService;
use App\Services\ReviewNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RecapController extends Controller
{
    public function __construct(
        private readonly AmbassadorRecapListingService $recapListingService,
    ) {
    }

    /**
     * BAs may not start or edit recaps until the event start (in event timezone). Admins/managers bypass.
     */
    private function assertAmbassadorCanWorkOnRecap(Event $event): void
    {
        if ((string) (Auth::user()->role_id ?? '') !== '5') {
            return;
        }

        if ((string) $event->event_status === '2') {
            abort(403, 'This event was cancelled.');
        }

        if ((string) $event->recap_needed !== '1') {
            abort(403, 'A recap is not required for this event.');
        }

        $assigned = AssignAmbassador::where('ambassador_id', Auth::id())
            ->where('event_id', $event->id)
            ->exists();

        if (! $assigned) {
            abort(403, 'You are not assigned to this event.');
        }

        if (! $event->hasRecapStartTimePassed()) {
            abort(403, 'You can start the recap after the event start time.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $events = $this->recapListingService->openRecapEvents(Auth::id());

        $draftsByEventId = Recap::where('ambassador_id', Auth::id())
            ->where('is_draft', true)
            ->get()
            ->keyBy('event_id');
        if ($request->id) {
            if ($request->id != 'all') {
                $events = $this->recapListingService->openRecapEvents(
                    Auth::id(),
                    (int) $request->id
                );
            }

            return response()->json([
                'html' => view('ambassador.recap.recap_table', compact('events', 'draftsByEventId'))->render(),
            ]);
        }

        $quickbooksRequirements = $this->quickBooksRecapRequirements(Auth::user());

        return view('ambassador.recap.index', compact('events', 'draftsByEventId', 'quickbooksRequirements'));
    }

    public function drafts(Request $request)
    {
        $drafts = Recap::with(['event.brand', 'event.location'])
            ->where('ambassador_id', Auth::id())
            ->where('is_draft', true)
            ->latest('updated_at')
            ->get();

        return view('ambassador.recap.drafts', compact('drafts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            return response()->json([
                'view' => view('ambassador.recap.ajax-create')->render(),
            ]);
        }
        $locations = User::where('role_id', 4)->get();
        $event_id = $request->id;
        $event = Event::with('eventType')->select(
            'id',
            'event_name',
            'integrate_with_best',
            'best_event_data',
            'is_thc_event',
            'event_type_id',
            'start_time',
            'timezone',
            'event_status',
            'recap_needed',
            'kit',
            'brand_id'
        )->findOrFail($request->id);

        $this->assertAmbassadorCanWorkOnRecap($event);
        $eventTypes = EventType::get();
        $recapType = RecapFormService::resolveFromEvent($event);
        $quickbooksRequirements = $this->quickBooksRecapRequirements(Auth::user());

        // Bug 1: scope the "What type of product were you selling?" dropdown
        // to the event's brand subtree (parent brand + all descendants) so the
        // ambassador only sees the SKUs that belong to their event. Falls back
        // to all brands if the event has no brand_id (legacy data).
        $brands = $this->brandsForEvent($event);

        if ($recapType === RecapFormService::TYPE_BEST) {
            return view('ambassador.recap.create-best', compact('brands', 'locations', 'event', 'eventTypes', 'quickbooksRequirements'));
        }

        if ($recapType === RecapFormService::TYPE_OFF_PREMISE) {
            return view('ambassador.recap.create-off-premise', compact('event', 'recapType', 'quickbooksRequirements'));
        }

        return view('ambassador.recap.create', compact('brands', 'locations', 'event', 'eventTypes', 'recapType', 'quickbooksRequirements'));
    }

    /**
     * Resolve the brand options shown on a recap form.
     *
     * If the event has a brand_id, return that brand plus all of its
     * descendants (multi-level), ordered by brand_name. Otherwise fall back
     * to every brand so a recap form is never empty.
     */
    protected function brandsForEvent(?Event $event)
    {
        $brandId = $event?->brand_id ? (int) $event->brand_id : 0;
        if ($brandId > 0) {
            $ids = Brand::descendantIdsIncludingSelfById($brandId);
            if (! empty($ids)) {
                return Brand::whereIn('id', $ids)
                    ->orderBy('brand_name')
                    ->get();
            }
        }

        return Brand::orderBy('brand_name')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, EventPushService $eventPushService)
    {
        // Get event to check if it's THC event
        $event = Event::find($request->event_id);
        if (! $event) {
            return redirect()->back()->withInput()->with('error', 'Event not found.');
        }

        $this->assertAmbassadorCanWorkOnRecap($event);
        abort_if((int) Auth::user()->role_id === 5 && Recap::where('event_id', $event->id)->where('ambassador_id', Auth::id())->where('is_approved', true)->exists(), 403, 'This recap has already been approved.');
        $this->syncDemographicPercentages($request);

        if ($request->boolean('save_as_draft')) {
            $this->upsertDraftRecap($request, $event);

            return redirect()
                ->route('ambassador.recap-drafts')
                ->with('success', 'Draft saved.');
        }

        $this->assertQuickBooksRequirementsCompletedForSubmit(Auth::user());

        $event->loadMissing('eventType');
        $recapType = RecapFormService::resolveFromEvent($event);

        if ($recapType === RecapFormService::TYPE_OFF_PREMISE) {
            return $this->storeOffPremise($request, $event, $recapType);
        }

        $recap = Recap::where('event_id', $event->id)->where('ambassador_id', Auth::id())->where('is_draft', true)->first();
        $rules = RecapFormService::submitRules($recapType, $event, $recap);

        $validatedData = $request->validate($rules, RecapFormService::validationMessages());
        $this->normalizeBuybackAmount($validatedData);

        $validatedData['backup_brand_used'] = (bool) (int) $validatedData['backup_brand_used'];
        if (! $validatedData['backup_brand_used']) {
            $validatedData['backup_brand_name'] = null;
        }

        if (! ((bool) optional($event)->kit)) {
            $validatedData['giveaway_count'] = null;
            $validatedData['giveaway_comments'] = null;
        }

        // Handle file uploads
        if ($request->hasFile('product_attachments1')) {
            $product_attachments1 = $request->file('product_attachments1');
            $filename = time().'_'.uniqid().'.'.$product_attachments1->getClientOriginalExtension();
            $product_attachments1->move(public_path('recap'), $filename);
            $validatedData['product_attachments1'] = 'recap/'.$filename;
        }

        if ($request->hasFile('product_attachments2')) {
            $product_attachments2 = $request->file('product_attachments2');
            $filename = time().'_'.uniqid().'.'.$product_attachments2->getClientOriginalExtension();
            $product_attachments2->move(public_path('recap'), $filename);
            $validatedData['product_attachments2'] = 'recap/'.$filename;
        }

        if ($request->hasFile('brand_photo')) {
            $brand_photo = $request->file('brand_photo');
            $filename = time().'_'.uniqid().'.'.$brand_photo->getClientOriginalExtension();
            $brand_photo->move(public_path('recap'), $filename);
            $validatedData['brand_photo'] = 'recap/'.$filename;
        }

        if ($request->hasFile('consumer_photo')) {
            $consumerPhotos = $request->file('consumer_photo');
            $savedPhotos = [];
            foreach ($consumerPhotos as $consumerPhoto) {
                if ($consumerPhoto->isValid()) {
                    $filename = time().'_'.uniqid().'.'.$consumerPhoto->getClientOriginalExtension();
                    $consumerPhoto->move(public_path('recap'), $filename);
                    $savedPhotos[] = 'recap/'.$filename;
                }
            }
            $validatedData['consumer_photo'] = json_encode($savedPhotos);
        }

        if ($request->hasFile('receipt')) {
            $receipt = $request->file('receipt');
            $filename = time().'_'.uniqid().'.'.$receipt->getClientOriginalExtension();
            $receipt->move(public_path('recap'), $filename);
            $validatedData['receipt'] = 'recap/'.$filename;
        }
        $validatedData['event_id'] = $request->event_id;
        $validatedData['state_id'] = $request->state_id;
        $validatedData['ambassador_id'] = Auth::id();
        $validatedData['created_by'] = Auth::id();
        $validatedData['is_draft'] = false;

        // Phase 1 Expedited Pay: persist request flag (no payment logic yet)
        $isExpeditedRequested = $request->boolean('is_expedited_requested');
        $validatedData['is_expedited_requested'] = $isExpeditedRequested;
        $validatedData['expedited_requested_at']  = $isExpeditedRequested ? now() : null;

        // Handle THC products for THC events
        if ($event && $event->is_thc_event && $request->has('thc_products')) {
            $validatedData['thc_products_sold'] = json_encode($request->thc_products);
            // Remove thc_products from validatedData as it's not a database column
            unset($validatedData['thc_products']);
        }

        // For BEST events: validate BEST fields and save the payload for upload on approval
        if (! empty($request->input('integrate_with_best'))) {
            $request->validate($eventPushService->recapBestValidationRules());

            $payload = $eventPushService->buildRecapSamplingPayloadFromRequest($request);

            $validatedData['best_payload'] = json_encode($payload);
        }

        // If a draft exists, submit it; otherwise create a new recap
        $recap = Recap::where('event_id', $validatedData['event_id'])
            ->where('ambassador_id', $validatedData['ambassador_id'])
            ->where('is_draft', true)
            ->first();

        if ($recap) {
            $validatedData['updated_by'] = Auth::id();
            $validatedData['recap_type'] = $recap->recap_type ?: $recapType;
            $recap->update($validatedData);
        } else {
            $validatedData['recap_type'] = $recapType;
            $recap = Recap::create($validatedData);
        }

        $recap->refresh();
        $recap->syncBuybackToEvent();

        if (! empty($validatedData['best_payload'])) {
            Log::info('BEST recap payload stored for approval-time submission', [
                'recap_id' => $recap->id,
                'event_id' => $recap->event_id,
                'best_action' => EventPushService::RECAP_SAMPLING_ACTION,
                'best_endpoint' => $eventPushService->recapSamplingEndpoint(),
                'payload' => $eventPushService->safeRecapSamplingPayload(json_decode($validatedData['best_payload'], true) ?: []),
                'best_call_attempted' => false,
                'best_accepted' => false,
            ]);
        }

        // Send mail

        app(ReviewNotificationService::class)->recapSubmitted(Auth::user(), $recap);

        return redirect()->route('ambassador.submitted-recap')->with('success', 'Recap has been successfully saved.');
    }

    public function editRecap($id)
    {
        $recap = Recap::with('event.eventType')->where(['id' => $id])->firstOrFail();
        abort_unless($recap->canBeEditedBy(Auth::user()), 403, 'This recap is approved or you do not have permission to edit it.');
        if ($recap->event) {
            $this->assertAmbassadorCanWorkOnRecap($recap->event);
        }
        $locations = User::where('role_id', 4)->get();
        $brands = $this->brandsForEvent($recap->event);
        $recap_id = $id;
        $recapType = RecapFormService::resolveForRecap($recap);
        $quickbooksRequirements = $this->quickBooksRecapRequirements(Auth::user());

        if ($recapType === RecapFormService::TYPE_OFF_PREMISE) {
            return view('ambassador.recap.edit-off-premise', compact('recap', 'recap_id', 'recapType', 'quickbooksRequirements'));
        }

        return view('ambassador.recap.edit', compact('recap', 'brands', 'locations', 'recap_id', 'recapType', 'quickbooksRequirements'));
    }

    /**
     * update existing recaps
     */
    public function updateRecap(Request $request)
    {
        // Get the recap to check if it's for a THC event
        $recap = Recap::where('id', $request->recap_id)->first();
        if (! $recap) {
            return redirect()->back()->with('error', 'Recap not found.');
        }
        abort_unless($recap->canBeEditedBy(Auth::user()), 403, 'This recap is approved or you do not have permission to edit it.');

        $event = $recap->event;
        if ($event) {
            $this->assertAmbassadorCanWorkOnRecap($event);
        }
        $this->syncDemographicPercentages($request);

        if ($request->boolean('save_as_draft')) {
            $this->updateDraftRecap($recap, $request, $event);

            return redirect()
                ->route('ambassador.recap-drafts')
                ->with('success', 'Draft saved.');
        }

        $this->assertQuickBooksRequirementsCompletedForSubmit(Auth::user());

        $recapType = RecapFormService::resolveForRecap($recap);

        if ($recapType === RecapFormService::TYPE_OFF_PREMISE) {
            return $this->updateOffPremise($request, $recap, $recapType);
        }

        $rules = RecapFormService::submitRules($recapType, $event, $recap);

        $validatedData = $request->validate($rules, RecapFormService::validationMessages());
        $this->normalizeBuybackAmount($validatedData);

        $validatedData['backup_brand_used'] = (bool) (int) $validatedData['backup_brand_used'];
        if (! $validatedData['backup_brand_used']) {
            $validatedData['backup_brand_name'] = null;
        }

        if (! ((bool) optional($event)->kit)) {
            $validatedData['giveaway_count'] = null;
            $validatedData['giveaway_comments'] = null;
        }

        if ($request->hasFile('product_attachments1')) {
            $product_attachments1 = $request->file('product_attachments1');
            $filename = time().'_'.uniqid().'.'.$product_attachments1->getClientOriginalExtension();
            $product_attachments1->move(public_path('recap'), $filename);
            $validatedData['product_attachments1'] = 'recap/'.$filename;
        }

        if ($request->hasFile('product_attachments2')) {
            $product_attachments2 = $request->file('product_attachments2');
            $filename = time().'_'.uniqid().'.'.$product_attachments2->getClientOriginalExtension();
            $product_attachments2->move(public_path('recap'), $filename);
            $validatedData['product_attachments2'] = 'recap/'.$filename;
        }

        if ($request->hasFile('brand_photo')) {
            $brand_photo = $request->file('brand_photo');
            $filename = time().'_'.uniqid().'.'.$brand_photo->getClientOriginalExtension();
            $brand_photo->move(public_path('recap'), $filename);
            $validatedData['brand_photo'] = 'recap/'.$filename;
        }

        // if ($request->hasFile('consumer_photo')) {
        //     $consumer_photo = $request->file('consumer_photo');
        //     $filename = time() . '_' . uniqid() . '.' . $consumer_photo->getClientOriginalExtension();
        //     $consumer_photo->move(public_path('recap'), $filename);
        //     $validatedData['consumer_photo'] = 'recap/' . $filename;
        // }

        if ($request->hasFile('consumer_photo')) {
            $consumerPhotos = $request->file('consumer_photo');
            $savedPhotos = [];  // Array to hold file paths of the uploaded photos

            // Loop through each file and process it
            foreach ($consumerPhotos as $consumerPhoto) {
                if ($consumerPhoto->isValid()) {
                    // Create a unique filename
                    $filename = time().'_'.uniqid().'.'.$consumerPhoto->getClientOriginalExtension();

                    // Move the file to the desired folder (e.g., 'recap')
                    $consumerPhoto->move(public_path('recap'), $filename);

                    // Store the path (relative to the public directory)
                    $savedPhotos[] = 'recap/'.$filename;
                }
            }
            $validatedData['consumer_photo'] = json_encode($savedPhotos);
        }

        if ($request->hasFile('receipt')) {
            $receipt = $request->file('receipt');
            $filename = time().'_'.uniqid().'.'.$receipt->getClientOriginalExtension();
            $receipt->move(public_path('recap'), $filename);
            $validatedData['receipt'] = 'recap/'.$filename;
        }

        // $validatedData['event_id'] = $request->event_id;
        $validatedData['state_id'] = $request->state_id;
        $validatedData['updated_by'] = Auth::id();
        $validatedData['is_draft'] = false;

        // Phase 1 Expedited Pay: persist request flag (no payment logic yet)
        $isExpeditedRequested = $request->boolean('is_expedited_requested');
        $validatedData['is_expedited_requested'] = $isExpeditedRequested;
        $validatedData['expedited_requested_at']  = $isExpeditedRequested ? now() : null;

        // Handle THC products for THC events
        if ($event && $event->is_thc_event && $request->has('thc_products')) {
            $validatedData['thc_products_sold'] = json_encode($request->thc_products);
            // Remove thc_products from validatedData as it's not a database column
            unset($validatedData['thc_products']);
        }

        $recap->update($validatedData);
        $recap->refresh();
        $recap->syncBuybackToEvent();

        app(ReviewNotificationService::class)->recapSubmitted(Auth::user(), $recap);
        if (Auth::id() == '1') {
            return redirect()->route('admin.recaps')->with('success', 'Recap has been successfully updated.');
        }

        return redirect()->route('ambassador.submitted-recap')->with('success', 'Recap has been successfully updated.');
    }

    private function upsertDraftRecap(Request $request, ?Event $event): void
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        abort_if(Recap::where('event_id', $request->event_id)->where('ambassador_id', Auth::id())->where('is_approved', true)->exists(), 403, 'This recap has already been approved.');

        $recap = Recap::where('event_id', $request->event_id)
            ->where('ambassador_id', Auth::id())
            ->where('is_draft', true)
            ->first();

        if (! $recap) {
            $recap = new Recap();
            $recap->event_id = $request->event_id;
            $recap->ambassador_id = Auth::id();
            $recap->created_by = Auth::id();
            $event->loadMissing('eventType');
            $recap->recap_type = RecapFormService::resolveFromEvent($event);
        }

        $this->updateDraftRecap($recap, $request, $event);
    }

    private function updateDraftRecap(Recap $recap, Request $request, ?Event $event): void
    {
        abort_unless($recap->canBeEditedBy(Auth::user()), 403, 'Approved recaps cannot be edited by ambassadors.');
        $allowed = RecapFormService::draftAllowedFields();

        $data = [];
        foreach ($allowed as $key) {
            if ($request->has($key)) {
                $data[$key] = $request->input($key);
            }
        }

        if ($request->has('buyback_amount')) {
            $this->normalizeBuybackAmount($data);
        }

        if ($request->has('is_expedited_requested')) {
            $data['is_expedited_requested'] = $request->boolean('is_expedited_requested');
            $data['expedited_requested_at'] = $data['is_expedited_requested'] ? now() : null;
        }

        if (array_key_exists('backup_brand_used', $data)) {
            $data['backup_brand_used'] = (bool) (int) $data['backup_brand_used'];
            if (! $data['backup_brand_used']) {
                $data['backup_brand_name'] = null;
            }
        }

        if ($request->hasFile('product_attachments1')) {
            $f = $request->file('product_attachments1');
            $filename = time().'_'.uniqid().'.'.$f->getClientOriginalExtension();
            $f->move(public_path('recap'), $filename);
            $data['product_attachments1'] = 'recap/'.$filename;
        }

        if ($request->hasFile('product_attachments2')) {
            $f = $request->file('product_attachments2');
            $filename = time().'_'.uniqid().'.'.$f->getClientOriginalExtension();
            $f->move(public_path('recap'), $filename);
            $data['product_attachments2'] = 'recap/'.$filename;
        }

        if ($request->hasFile('brand_photo')) {
            $f = $request->file('brand_photo');
            $filename = time().'_'.uniqid().'.'.$f->getClientOriginalExtension();
            $f->move(public_path('recap'), $filename);
            $data['brand_photo'] = 'recap/'.$filename;
        }

        if ($request->hasFile('consumer_photo')) {
            $consumerPhotos = $request->file('consumer_photo');
            $savedPhotos = [];
            foreach ($consumerPhotos as $consumerPhoto) {
                if ($consumerPhoto->isValid()) {
                    $filename = time().'_'.uniqid().'.'.$consumerPhoto->getClientOriginalExtension();
                    $consumerPhoto->move(public_path('recap'), $filename);
                    $savedPhotos[] = 'recap/'.$filename;
                }
            }
            $data['consumer_photo'] = json_encode($savedPhotos);
        }

        if ($request->hasFile('receipt')) {
            $f = $request->file('receipt');
            $filename = time().'_'.uniqid().'.'.$f->getClientOriginalExtension();
            $f->move(public_path('recap'), $filename);
            $data['receipt'] = 'recap/'.$filename;
        }

        if ($event && $event->is_thc_event && $request->has('thc_products')) {
            $data['thc_products_sold'] = json_encode($request->input('thc_products'));
        }

        // Off Premise file uploads for draft
        if ($request->hasFile('op_event_photos')) {
            $savedPhotos = [];
            foreach ($request->file('op_event_photos') as $photo) {
                if ($photo->isValid()) {
                    $filename = time().'_'.uniqid().'.'.$photo->getClientOriginalExtension();
                    $photo->move(public_path('recap'), $filename);
                    $savedPhotos[] = 'recap/'.$filename;
                }
            }
            $data['consumer_photo'] = json_encode($savedPhotos);
        }

        if ($request->hasFile('op_event_receipts')) {
            $savedReceipts = [];
            foreach ($request->file('op_event_receipts') as $file) {
                if ($file->isValid()) {
                    $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                    $file->move(public_path('recap'), $filename);
                    $savedReceipts[] = 'recap/'.$filename;
                }
            }
            $data['op_receipts'] = json_encode($savedReceipts);
        }

        // JSON-encode products_sold if submitted as array
        if (isset($data['products_sold']) && is_array($data['products_sold'])) {
            $data['products_sold'] = json_encode($data['products_sold']);
        }

        // Normalize off_premise boolean checkboxes
        foreach (['store_aware_of_demo','store_prepared_for_demo','product_on_sale','coupons_rebates_available','enough_product_in_stock','case_display'] as $boolField) {
            if (array_key_exists($boolField, $data)) {
                $data[$boolField] = (bool) $data[$boolField];
            }
        }

        $data['is_draft'] = true;
        $data['updated_by'] = Auth::id();

        $recap->fill($data);
        $recap->save();
    }

    private function storeOffPremise(Request $request, Event $event, string $recapType)
    {
        $recap = Recap::where('event_id', $event->id)->where('ambassador_id', Auth::id())->where('is_draft', true)->first();
        $validatedData = $request->validate(
            RecapFormService::offPremiseRules($event, $recap),
            RecapFormService::validationMessages()
        );

        if (!empty($validatedData['products_sold'])) {
            $validatedData['products_sold'] = json_encode($validatedData['products_sold']);
        } else {
            $validatedData['products_sold'] = null;
        }

        $savedPhotos = $this->storeOffPremiseUploads($request, 'op_event_photos', $event->id, $recapType);
        if ($savedPhotos !== []) {
            $validatedData['consumer_photo'] = json_encode($savedPhotos);
        }
        unset($validatedData['op_event_photos']);
        unset($validatedData['existing_op_event_photos'], $validatedData['existing_op_event_receipts'], $validatedData['off_premise_attachments_present']);

        $savedReceipts = $this->storeOffPremiseUploads($request, 'op_event_receipts', $event->id, $recapType);
        if ($savedReceipts !== []) {
            $validatedData['op_receipts'] = json_encode($savedReceipts);
        }
        unset($validatedData['op_event_receipts']);

        $validatedData['event_id'] = $request->event_id;
        $validatedData['ambassador_id'] = Auth::id();
        $validatedData['created_by'] = Auth::id();
        $validatedData['is_draft'] = false;
        $validatedData['recap_type'] = $recapType;

        $isExpeditedRequested = $request->boolean('is_expedited_requested');
        $validatedData['is_expedited_requested'] = $isExpeditedRequested;
        $validatedData['expedited_requested_at'] = $isExpeditedRequested ? now() : null;

        foreach (['store_aware_of_demo','store_prepared_for_demo','product_on_sale','coupons_rebates_available','enough_product_in_stock','case_display'] as $boolField) {
            $validatedData[$boolField] = (bool) ($validatedData[$boolField] ?? false);
        }

        abort_if(Recap::where('event_id', $request->event_id)->where('ambassador_id', Auth::id())->where('is_approved', true)->exists(), 403, 'This recap has already been approved.');

        $recap = Recap::where('event_id', $request->event_id)
            ->where('ambassador_id', Auth::id())
            ->where('is_draft', true)
            ->first();

        if ($recap) {
            $validatedData['updated_by'] = Auth::id();
            $recap->update($validatedData);
        } else {
            $recap = Recap::create($validatedData);
        }

        $recap->refresh();
        $recap->syncBuybackToEvent();
        app(ReviewNotificationService::class)->recapSubmitted(Auth::user(), $recap);

        return redirect()->route('ambassador.submitted-recap')->with('success', 'Recap has been successfully saved.');
    }

    private function syncDemographicPercentages(Request $request): void
    {
        if ($request->filled('demographics_male')) {
            $request->merge([
                'demographics_female' => 100 - (int) $request->input('demographics_male'),
            ]);
        }
    }

    private function quickBooksRecapRequirements(?User $user): array
    {
        if (! $user || (string) ($user->role_id ?? '') !== '5') {
            return [
                'show' => false,
                'ready' => true,
                'w9_completed' => true,
                'quickbooks_1099_completed' => true,
            ];
        }

        $user->loadMissing('w9Form');
        $w9Form = $user->w9Form;

        $w9Completed = (bool) $w9Form;
        $quickbooksVendorId = $w9Form?->quickbooks_vendor_id ?? $user->quickbooks_vendor_id ?? null;
        $quickbooks1099Completed = $w9Completed
            && ! empty($quickbooksVendorId)
            && (bool) ($w9Form?->is_1099_vendor ?? false);

        return [
            'show' => true,
            'ready' => $w9Completed && $quickbooks1099Completed,
            'w9_completed' => $w9Completed,
            'quickbooks_1099_completed' => $quickbooks1099Completed,
        ];
    }

    private function assertQuickBooksRequirementsCompletedForSubmit(?User $user): void
    {
        $requirements = $this->quickBooksRecapRequirements($user);

        if ($requirements['ready'] ?? false) {
            return;
        }

        throw ValidationException::withMessages([
            'quickbooks_requirements' => 'Complete your W-9 and wait for Admin to finish the required 1099 setup in QuickBooks before submitting a recap.',
        ]);
    }

    private function updateOffPremise(Request $request, Recap $recap, string $recapType)
    {
        $validatedData = $request->validate(
            RecapFormService::offPremiseRules($recap->event, $recap),
            RecapFormService::validationMessages()
        );

        if (!empty($validatedData['products_sold'])) {
            $validatedData['products_sold'] = json_encode($validatedData['products_sold']);
        } else {
            $validatedData['products_sold'] = null;
        }

        $retainedPhotos = $this->retainedOffPremiseAttachments(
            $recap,
            'consumer_photo',
            $request->input('existing_op_event_photos'),
            ! $request->boolean('off_premise_attachments_present')
        );
        $photos = array_merge($retainedPhotos, $this->storeOffPremiseUploads($request, 'op_event_photos', $recap->event_id, $recapType));
        $validatedData['consumer_photo'] = json_encode($photos);
        unset($validatedData['op_event_photos']);

        $retainedReceipts = $this->retainedOffPremiseAttachments(
            $recap,
            'op_receipts',
            $request->input('existing_op_event_receipts'),
            ! $request->boolean('off_premise_attachments_present')
        );
        $receipts = array_merge($retainedReceipts, $this->storeOffPremiseUploads($request, 'op_event_receipts', $recap->event_id, $recapType));
        $validatedData['op_receipts'] = json_encode($receipts);
        unset($validatedData['op_event_receipts']);
        unset($validatedData['existing_op_event_photos'], $validatedData['existing_op_event_receipts'], $validatedData['off_premise_attachments_present']);

        $validatedData['updated_by'] = Auth::id();
        $validatedData['is_draft'] = false;
        $validatedData['recap_type'] = $recapType;

        $isExpeditedRequested = $request->boolean('is_expedited_requested');
        $validatedData['is_expedited_requested'] = $isExpeditedRequested;
        $validatedData['expedited_requested_at'] = $isExpeditedRequested ? now() : null;

        foreach (['store_aware_of_demo','store_prepared_for_demo','product_on_sale','coupons_rebates_available','enough_product_in_stock','case_display'] as $boolField) {
            $validatedData[$boolField] = (bool) ($validatedData[$boolField] ?? false);
        }

        $recap->update($validatedData);
        $recap->refresh();
        $recap->syncBuybackToEvent();

        app(ReviewNotificationService::class)->recapSubmitted(Auth::user(), $recap);

        if (Auth::id() == '1') {
            return redirect()->route('admin.recaps')->with('success', 'Recap has been successfully updated.');
        }

        return redirect()->route('ambassador.submitted-recap')->with('success', 'Recap has been successfully updated.');
    }

    /**
     * Store valid off-premise upload files and return their public paths.
     *
     * @return list<string>
     */
    private function storeOffPremiseUploads(Request $request, string $field, int|string $eventId, string $recapType): array
    {
        $paths = [];
        $eventFolder = (string) (int) $eventId;
        $typeFolder = preg_replace('/[^A-Za-z0-9_-]/', '-', $recapType) ?: 'off_premise';
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
     * Return only attachment paths that already belong to this recap.
     * Clients that have not adopted the edit UI preserve every existing
     * attachment by omitting its form marker.
     *
     * @param mixed $requestedPaths
     * @return list<string>
     */
    private function retainedOffPremiseAttachments(
        Recap $recap,
        string $column,
        mixed $requestedPaths,
        bool $preserveAllWhenMissing,
    ): array
    {
        $stored = json_decode((string) ($recap->{$column} ?? ''), true);
        $stored = is_array($stored)
            ? array_values(array_filter($stored, 'is_string'))
            : [];

        if ($preserveAllWhenMissing) {
            return $stored;
        }

        $requestedPaths = is_array($requestedPaths) ? $requestedPaths : [];

        return array_values(array_intersect($stored, $requestedPaths));
    }

    public function applyEvent(Request $request)
    {
        $event = new AssignAmbassador;
        $event->event_id = $request->event_id;
        $event->ambassador_id = Auth::id();
        $event->status = '0';
        $event->save();

        return response()->json(['status' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function normalizeBuybackAmount(array &$data): void
    {
        if (! array_key_exists('buyback_amount', $data)) {
            return;
        }

        $raw = $data['buyback_amount'];
        $data['buyback_amount'] = ($raw !== null && $raw !== '')
            ? round((float) $raw, 2)
            : null;
    }

}
