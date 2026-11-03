<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use App\Models\State;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\EventLocation;
use App\Services\EventLocationService;
use App\Services\GoogleGeocodingService;
use Illuminate\Support\Facades\Log;

class EventLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $locations = EventLocation::orderBy('id','desc')->paginate(20);
        
        return view('admin.event-location.index',compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $states = State::get();
        return view('admin.event-location.create',compact('states'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, EventLocationService $locationService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // 'store_number' => 'required|string|max:255',
            'store_number' => 'nullable|string|max:255',
            'phone' => 'required',
            // 'location_payment_type' => 'required|string|max:255',
            'location_payment_type' => 'required|in:Check,ComData,ComData - Gas,Gift Card,No Payment Needed,Product Provided,Reimbursement',
            'address' => 'required|string|max:255',
            'city' => 'required',
            'state'=> 'required',
            'zip_code' => 'required|digits_between:5,6',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $data = new EventLocation();
        $data->name = $request->name;
        $data->store_number = $request->store_number;
        $data->phone = $request->phone;
        $data->location_payment_type = $request->location_payment_type;
        $data->state = $request->state;
        $data->city = $request->city;
        $data->zip_code = $request->zip_code;
        $data->address = $request->address;
        $data->latitude = $request->latitude;
        $data->longitude = $request->longitude;

        $venuePayload = $locationService->applyTimezoneToVenueData([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        $data->timezone = $venuePayload['timezone'] ?? null;
        $data->timezone_label = $venuePayload['timezone_label'] ?? null;
        $data->save();

        return redirect()->route('admin.event.locations')->with('success', 'Event Location added successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $location = EventLocation::findOrFail($id);
        return view('admin.event-location.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateLocation(Request $request, EventLocationService $locationService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // 'store_number' => 'required|string|max:255',
            'store_number' => 'nullable|string|max:255',
            'phone' => 'required',
            // 'location_payment_type' => 'required|string|max:255',
            'location_payment_type' => 'required|in:Check,ComData,ComData - Gas,Gift Card,No Payment Needed,Product Provided,Reimbursement',
            'address' => 'required|string|max:255',
            'city' => 'required',
            'state'=> 'required',
            'zip_code' => 'required|digits_between:5,6',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $id = $request->id;
        $data = EventLocation::find($id);
        $data->name = $request->name;
        $data->store_number = $request->store_number;
        $data->phone = $request->phone;
        $data->location_payment_type = $request->location_payment_type;
        $data->state = $request->state;
        $data->city = $request->city;
        $data->zip_code = $request->zip_code;
        $data->address = $request->address;
        $data->latitude = $request->latitude;
        $data->longitude = $request->longitude;

        $venuePayload = $locationService->applyTimezoneToVenueData([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        $data->timezone = $venuePayload['timezone'] ?? null;
        $data->timezone_label = $venuePayload['timezone_label'] ?? null;
        $data->save();

        return redirect()->route('admin.event.locations')->with('success', 'Location updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Geocode all Event Locations missing latitude or longitude.
     * Processes in chunks to support 1000+ records without memory issues.
     */
    public function syncLatLng(Request $request, GoogleGeocodingService $geocoder, EventLocationService $locationService)
    {
        $processed = 0;
        $updated   = 0;
        $failed    = 0;
        $skipped   = 0;

        EventLocation::query()
            ->where(function ($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', '')
                  ->orWhere('longitude', '');
            })
            ->chunkById(50, function ($locations) use (
                $geocoder, $locationService,
                &$processed, &$updated, &$failed, &$skipped
            ) {
                foreach ($locations as $location) {
                    if ($this->hasLatLng($location)) {
                        $skipped++;
                        Log::info('SyncLatLng: skipped (coordinates already present)', [
                            'event_location_id' => $location->id,
                        ]);
                        continue;
                    }

                    $processed++;

                    // Build address string
                    $addressParts = array_filter([
                        trim((string) ($location->address  ?? '')),
                        trim((string) ($location->city     ?? '')),
                        trim((string) ($location->state    ?? '')),
                        trim((string) ($location->zip_code ?? '')),
                    ]);

                    if (empty($addressParts)) {
                        $skipped++;
                        Log::info('SyncLatLng: skipped (no address)', [
                            'event_location_id' => $location->id,
                        ]);
                        continue;
                    }

                    $address = implode(', ', $addressParts);

                    $coords = $geocoder->geocodeAddress($address);

                    if ($coords === null) {
                        $failed++;
                        Log::warning('SyncLatLng: geocoding failed', [
                            'event_location_id' => $location->id,
                            'address'           => $address,
                        ]);
                        continue;
                    }

                    $location->latitude  = $coords['lat'];
                    $location->longitude = $coords['lng'];

                    // Also resolve timezone from the new coordinates
                    $tzPayload = $locationService->applyTimezoneToVenueData([
                        'latitude'  => $coords['lat'],
                        'longitude' => $coords['lng'],
                    ]);
                    $location->timezone       = $tzPayload['timezone']       ?? $location->timezone;
                    $location->timezone_label = $tzPayload['timezone_label'] ?? $location->timezone_label;

                    $location->save();
                    $updated++;

                    // Small delay to respect Google API rate limits
                    usleep(250000); // 250ms
                }
            });

        Log::info('SyncLatLng: complete', compact('processed', 'updated', 'failed', 'skipped'));

        $message = "Sync complete — Processed: {$processed} | Updated: {$updated} | Failed: {$failed} | Skipped: {$skipped}";

        return redirect()->back()->with('sync_result', [
            'processed' => $processed,
            'updated'   => $updated,
            'failed'    => $failed,
            'skipped'   => $skipped,
            'message'   => $message,
        ]);
    }

    private function hasLatLng(EventLocation $location): bool
    {
        return trim((string) ($location->latitude ?? '')) !== ''
            && trim((string) ($location->longitude ?? '')) !== '';
    }
}
