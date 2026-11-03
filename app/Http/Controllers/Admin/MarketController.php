<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventType;
use App\Models\Market;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    private function routePrefix(): string
    {
        return (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
    }

    public function index(Request $request)
    {
        $showInactive = $request->boolean('show_inactive');

        if ($request->ajax()) {
            $search = (string) $request->search;
            $markets = Market::query();
            if (! $showInactive) {
                $markets->active();
            }
            if ($search !== '') {
                $markets->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('location', 'like', "%$search%");
                });
            }

            $markets = $markets->ordered()->paginate(20);
        
            return response()->json(['markets' => $markets]);
        }
        $markets = Market::query()
            ->when(! $showInactive, fn ($query) => $query->active())
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.market.index', compact('markets', 'showInactive'));
    }

    public function create()
    {
        $eventTypes = EventType::orderBy('name')->get();
        return view('admin.market.create', compact('eventTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:'.implode(',', [Market::STATUS_ACTIVE, Market::STATUS_INACTIVE]),
            'event_type_ids' => 'nullable|array',
            'event_type_ids.*' => 'exists:event_types,id',
        ]);

        $market = Market::create($request->only(['name', 'location', 'state', 'description', 'latitude', 'longitude', 'status', 'region']));
        if ($request->filled('event_type_ids')) {
            $market->eventTypes()->sync($request->event_type_ids);
        }
        return redirect()->route($this->routePrefix().'.markets')->with('success', 'Market Added Successfully.');
    }

    public function show(Market $market)
    {
        return $market;
    }


    public function edit($id)
    {
        $market = Market::with('eventTypes')->findOrFail($id);
        $eventTypes = EventType::orderBy('name')->get();
        return view('admin.market.edit', compact('market', 'eventTypes'));
    }

    public function updateMarket(Request $request,)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:'.implode(',', [Market::STATUS_ACTIVE, Market::STATUS_INACTIVE]),
            'event_type_ids' => 'nullable|array',
            'event_type_ids.*' => 'exists:event_types,id',
        ]);

        $id = $request->id;
        $market = Market::findOrFail($id);
        $market->update($request->only(['name', 'location', 'state', 'description', 'latitude', 'longitude', 'status', 'region']));
        $market->eventTypes()->sync($request->event_type_ids ?? []);
        return redirect()->route($this->routePrefix().'.markets')->with('success', 'Market updated Successfully.');
    }

    public function destroy(Request $request, Market $market)
    {
        $market->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Market deleted successfully']);
        }

        return redirect()->route($this->routePrefix().'.markets')->with('success', 'Market deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'market_ids' => 'required|array|min:1',
            'market_ids.*' => 'required|integer|distinct|exists:markets,id',
        ]);

        $deletedCount = Market::whereKey($validated['market_ids'])->delete();
        $marketLabel = $deletedCount === 1 ? 'market' : 'markets';
        $message = $deletedCount.' '.$marketLabel.' deleted successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'deleted_count' => $deletedCount]);
        }

        return redirect()->route($this->routePrefix().'.markets')->with('success', $message);
    }
}
