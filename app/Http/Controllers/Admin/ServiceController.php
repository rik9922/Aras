<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\QuickBooksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    protected $quickBooksService;

    /**
     * The 7 mandatory System Services required for Invoice Generation.
     * Keys are service names; values are the default short descriptions.
     * These must always exist in the database. Never duplicated, case-insensitive.
     */
    public const SYSTEM_SERVICES = [
        'Labour Charge'       => 'Brand Ambassador labor charge for the event.',
        'Cup Charge'          => 'Cup charge billed for the event (Primary Billing Party only).',
        'Buyback'             => 'Product buyback reimbursement for the event.',
        'Expedite Fee'        => 'Expedited scheduling fee for the event.',
        'Shipping/Postage Fee'=> 'Shipping and postage charges for event materials.',
        'Parking Fee'         => 'Parking expenses incurred during the event.',
        'Drive-time Pay'      => 'Drive-time compensation for the Brand Ambassador.',
    ];

    public function __construct(QuickBooksService $quickBooksService)
    {
        $this->quickBooksService = $quickBooksService;
    }

    /**
     * Ensure all 7 mandatory System Services exist in the database.
     * Only creates missing ones — never overwrites or duplicates existing entries.
     * Does NOT sync to QuickBooks; that is handled separately.
     */
    public function ensureSystemServicesExistPublic(): void
    {
        $this->ensureSystemServicesExist();
    }

    protected function ensureSystemServicesExist(): void
    {
        foreach (self::SYSTEM_SERVICES as $name => $defaultDescription) {
            $service = Service::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if (! $service) {
                // Create missing mandatory service with default description
                Service::create([
                    'name'        => $name,
                    'rate'        => null,
                    'taxable'     => false,
                    'description' => $defaultDescription,
                    'qb_item_id'  => null,
                    'sync_token'  => null,
                    'is_active'   => true,
                ]);
            } elseif (empty($service->description)) {
                // Backfill description for existing services that are missing it
                $service->update(['description' => $defaultDescription]);
            }
        }
    }

    public function index()
    {
        // Ensure all 7 mandatory System Services exist before rendering
        $this->ensureSystemServicesExist();

        $services = Service::latest()->get();
        $isConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');

        return view('admin.services.service-list', [
            'services' => $services,
            'isConnected' => true,
        ]);
    }

    public function create()
    {
        return view('admin.services.service-create');
    }

    public function store(Request $request)
    {
        $normalizedName = preg_replace('/\s+/', ' ', trim((string) $request->input('name', ''))) ?? (string) $request->input('name', '');
        $request->merge(['name' => $normalizedName]);

        $existing = Service::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->first();
        if ($existing) {
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'service' => $existing, 'reused' => true]);
            }

            return back()->with('success', 'Service already exists; reusing existing service.')->withInput();
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string|max:1000',
        ]);

        $qbItem = $this->quickBooksService->createQuickBooksService([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? '',
            'rate'        => null,
            'taxable'     => false,
        ]);

        if (! $qbItem) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => ['quickbooks' => ['Failed to create service in QuickBooks']]], 422);
            }

            return back()->withErrors(['quickbooks' => 'Failed to create service in QuickBooks'])->withInput();
        }

        $service = Service::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'rate'        => null,
            'taxable'     => false,
            'qb_item_id'  => $qbItem->Id,
            'sync_token'  => $qbItem->SyncToken,
            'is_active'   => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'service' => $service]);
        }

        return redirect()->route('admin.services.index')->with('success', 'Service added and synced with QuickBooks');
    }

    public function edit(Service $service)
    {
        return view('admin.services.service-edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:services,name,'.$service->id,
            'description' => 'nullable|string|max:1000',
        ]);

        if ($service->qb_item_id) {
            $qbItem = $this->quickBooksService->updateQuickBooksService(
                $service->qb_item_id,
                $service->sync_token,
                [
                    'name'        => $validated['name'],
                    'description' => $validated['description'] ?? '',
                    'rate'        => null,
                    'taxable'     => false,
                ]
            );

            if (! $qbItem) {
                return back()->withErrors(['quickbooks' => 'Failed to update service in QuickBooks'])->withInput();
            }

            $service->update([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sync_token'  => $qbItem->SyncToken,
            ]);
        } else {
            $service->update([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);
        }

        return redirect()->route('admin.services.index')->with('success', 'Service updated successfully');
    }

    public function destroy(Service $service)
    {
        $service->update(['is_active' => false]);

        return back()->with('success', 'Service disabled successfully');
    }

    public function toggleStatus(Service $service)
    {
        $service->update(['is_active' => ! $service->is_active]);

        $message = $service->is_active ? 'Service enabled successfully' : 'Service disabled successfully';

        return back()->with('success', $message);
    }

    /**
     * Sync all unsynced services to QuickBooks.
     * Reuses the existing createQuickBooksService logic.
     */
    public function syncToQuickBooks(Request $request)
    {
        $isConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');

        if (! $isConnected) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'QuickBooks is not connected.'], 422);
            }

            return back()->with('error', 'QuickBooks is not connected. Please connect first.');
        }

        // Ensure all 7 mandatory System Services exist before syncing
        $this->ensureSystemServicesExist();

        $activeServices = Service::where('is_active', true)->get();

        $unsyncedServices = $activeServices->whereNull('qb_item_id');

        // Case 3: All active services already synced
        if ($unsyncedServices->isEmpty()) {
            $msg = 'All services are already synced with QuickBooks.';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'synced' => 0]);
            }

            return back()->with('success', $msg);
        }

        // Case 2: One or more unsynced — run sync
        $synced = 0;
        $failed = 0;

        foreach ($unsyncedServices as $service) {
            $qbItem = $this->quickBooksService->createQuickBooksService([
                'name'        => $service->name,
                'description' => $service->description ?? '',
                'rate'        => null,
                'taxable'     => false,
            ]);

            if ($qbItem) {
                $service->update([
                    'qb_item_id' => $qbItem->Id,
                    'sync_token' => $qbItem->SyncToken,
                ]);
                $synced++;
            } else {
                $failed++;
            }
        }

        if ($failed > 0) {
            $msg = "Sync completed: {$synced} synced, {$failed} failed. Check that QuickBooks is connected and try again.";

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'synced' => $synced, 'failed' => $failed]);
            }

            return back()->with('error', $msg);
        }

        $msg = "{$synced} service(s) synced to QuickBooks successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'synced' => $synced]);
        }

        return back()->with('success', $msg);
    }
}
