<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Market;
use App\Services\AmbassadorRecapListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmitRecapController extends Controller
{
    public function __construct(
        private readonly AmbassadorRecapListingService $recapListingService,
    ) {
    }

    public function index(Request $request) {
        $markets = Market::get();
        $brands = Brand::get();
        $events = $this->recapListingService
            ->submittedRecapEventsQuery(
                Auth::id(),
                $request->search,
                $request->filled('marketId') ? (int) $request->marketId : null,
            )
            ->get();

        $currentUserId = Auth::id();
        if ($request->ajax()) {
            return response()->json([
                'html' => view('ambassador.submit-recap.table', compact('events', 'markets', 'brands', 'currentUserId'))->render()
            ]);
        }

        return view('ambassador.submit-recap.index',compact('events','markets','brands', 'currentUserId'));
    }
}
