<?php

namespace App\Http\Controllers\Admin;

use App\Helper\QuickBooksHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SyncAllClientsWithQuickBooks;
use App\Models\Event;
use App\Models\User;
use App\Trait\EnsuresQuickBooksCustomer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillController extends Controller
{
    use EnsuresQuickBooksCustomer;

    protected function billsIndexRouteName(Request $request): string
    {
        $name = $request->route()?->getName() ?? '';

        return match (true) {
            str_starts_with($name, 'manager.') => 'manager.bills',
            str_starts_with($name, 'admin.') => 'admin.bills',
            str_starts_with($name, 'brand.') => 'brand.bills',
            default => 'bills',
        };
    }

    /**
     * Route prefix for named routes like *.edit.client (admin/manager only).
     */
    protected function billsRoutePrefix(Request $request): string
    {
        return match ($this->billsIndexRouteName($request)) {
            'manager.bills' => 'manager',
            'admin.bills' => 'admin',
            'brand.bills' => 'brand',
            default => 'admin',
        };
    }

    protected function canManageBillables(): bool
    {
        $r = (int) (auth()->user()->role_id ?? 0);

        return in_array($r, [1, 6], true);
    }

    /**
     * @return Builder<User>
     */
    protected function billablesQuery(Request $request): Builder
    {
        $query = User::query()
            ->where('role_id', 3)
            ->with(['clientProfile.parentBillTo', 'clientProfile.childBillTos.user'])
            ->orderBy('name')
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($request->filled('name')) {
            $term = trim((string) $request->name);
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            });
        }

        if ($request->filled('qb_id')) {
            $qb = trim((string) $request->qb_id);
            $query->whereHas('clientProfile', function ($q) use ($qb) {
                $q->where('quickbooks_customer_id', 'like', '%'.$qb.'%');
            });
        }

        if ($request->boolean('active_only')) {
            $query->where(function ($q) {
                $q->where('status', '1')->orWhere('status', 1);
            });
        }

        if ($request->boolean('contract_received_only')) {
            $query->whereHas('clientProfile', function ($q) {
                $q->where('contract_recieved', 1)->orWhere('contract_recieved', '1');
            });
        }

        return $query;
    }

    /**
     * Bill To / billables list (clients with role Client).
     */
    public function index(Request $request)
    {
        $indexRouteName = $this->billsIndexRouteName($request);
        $routePrefix = $this->billsRoutePrefix($request);
        $billsIndexUrl = route($indexRouteName);
        $exportUrl = $this->canManageBillables() && in_array($indexRouteName, ['admin.bills', 'manager.bills'], true)
            ? route($routePrefix.'.bills.export')
            : null;

        $query = $this->billablesQuery($request);
        $totalFiltered = (clone $query)->count();
        $billables = $query->paginate(20)->withQueryString();
        $parentOptions = User::query()->where('role_id', 3)
            ->with('clientProfile')
            ->orderBy('name')->orderBy('first_name')->orderBy('last_name')->get();

        $quickbooksConnected = QuickBooksHelper::isConnected();

        // JSON fragment load (Bill tab). Use explicit query flag so we never return JSON on a normal page view.
        if ($request->boolean('bills_ajax')) {
            return response()->json([
                'html' => view('admin.bill.partials.bill-table-body', [
                    'billables' => $billables,
                    'routePrefix' => $routePrefix,
                    'canManage' => $this->canManageBillables(),
                    'quickbooksConnected' => $quickbooksConnected,
                    'parentOptions' => $parentOptions,
                ])->render(),
                'pagination' => $billables->withQueryString()->links('vendor.pagination.custom')->render(),
                'total' => $totalFiltered,
            ]);
        }

        $totalBillables = User::where('role_id', 3)->count();
        $unsyncedCount = User::where('role_id', 3)
            ->whereDoesntHave('clientProfile', fn ($q) => $q->whereNotNull('quickbooks_customer_id'))
            ->count();

        return view('admin.bill.index', [
            'billables' => $billables,
            'totalBillables' => $totalBillables,
            'unsyncedCount' => $unsyncedCount,
            'routePrefix' => $routePrefix,
            'billsIndexUrl' => $billsIndexUrl,
            'exportUrl' => $exportUrl,
            'canManage' => $this->canManageBillables(),
            'quickbooksConnected' => $quickbooksConnected,
            'parentOptions' => $parentOptions,
            'filters' => [
                'name' => $request->input('name', ''),
                'qb_id' => $request->input('qb_id', ''),
                'active_only' => $request->boolean('active_only'),
                'contract_received_only' => $request->boolean('contract_received_only'),
            ],
        ]);
    }

    /**
     * Apply explicitly supplied fields to multiple Bill To records.
     * QuickBooks IDs, email, roles, and relationship pivots are deliberately
     * excluded from this flow.
     */
    public function bulkUpdate(Request $request)
    {
        if (! $this->canManageBillables()) {
            abort(403);
        }

        $validated = $request->validate([
            'updates' => 'required|array|min:1|max:500',
            'updates.*.id' => 'required|integer|distinct',
            'updates.*.first_name' => 'sometimes|nullable|string|max:255',
            'updates.*.last_name' => 'sometimes|nullable|string|max:255',
            'updates.*.phone' => 'sometimes|nullable|string|max:30',
            'updates.*.status' => 'sometimes|in:0,1',
            'updates.*.address' => 'sometimes|nullable|string|max:255',
            'updates.*.city' => 'sometimes|nullable|string|max:100',
            'updates.*.state' => 'sometimes|nullable|string|max:100',
            'updates.*.zip_code' => 'sometimes|nullable|string|max:20',
            'updates.*.country' => 'sometimes|nullable|string|max:100',
            'updates.*.company_name' => 'sometimes|nullable|string|max:255',
            'updates.*.primary_billing_contact' => 'sometimes|nullable|string|max:255',
            'updates.*.notes' => 'sometimes|nullable|string',
            'updates.*.default_labor_amount' => 'sometimes|nullable|numeric|min:0',
            'updates.*.default_buyback_amount' => 'sometimes|nullable|numeric|min:0',
            'updates.*.default_cups_amount' => 'sometimes|nullable|numeric|min:0',
            'updates.*.default_receipt_percent' => 'sometimes|nullable|integer|min:0|max:100',
            'updates.*.contract_recieved' => 'sometimes|boolean',
            'updates.*.cc_emails' => 'sometimes|array',
            'updates.*.cc_emails.*' => 'email|max:255',
        ]);

        $userFields = ['first_name', 'last_name', 'phone', 'status'];
        $profileFields = ['address', 'city', 'state', 'zip_code', 'country', 'company_name',
            'primary_billing_contact', 'notes', 'default_labor_amount', 'default_buyback_amount',
            'default_cups_amount', 'default_receipt_percent', 'contract_recieved', 'cc_emails'];

        $sharedFields = $profileFields;

        $changedUserIds = [];

        DB::transaction(function () use ($validated, $userFields, $profileFields, $sharedFields, &$changedUserIds): void {

            foreach ($validated['updates'] as $change) {
                $user = User::where('role_id', 3)->whereKey((int) $change['id'])->firstOrFail();
                $changedUserIds[] = $user->id;
                $userPayload = array_intersect_key($change, array_flip($userFields));
                if (array_key_exists('first_name', $userPayload) || array_key_exists('last_name', $userPayload)) {
                    $first = array_key_exists('first_name', $userPayload) ? $userPayload['first_name'] : $user->first_name;
                    $last = array_key_exists('last_name', $userPayload) ? $userPayload['last_name'] : $user->last_name;
                    $userPayload['name'] = trim($first.' '.$last);
                }
                if ($userPayload !== []) $user->update($userPayload);

                $profilePayload = array_intersect_key($change, array_flip($profileFields));
                if ($profilePayload !== []) {
                    $profile = $user->clientProfile ?? new \App\Models\Client(['user_id' => $user->id]);
                    $profile->fill($profilePayload);
                    $profile->save();

                    // A parent is the source of truth for shared billing fields. Never
                    // copy QuickBooks IDs (or relationship pivots) to children.
                    if ($profile->childBillTos()->exists()) {
                        $sharedPayload = array_intersect_key($profilePayload, array_flip($sharedFields));
                        if ($sharedPayload !== []) {
                            $profile->childBillTos()->update($sharedPayload);
                            $changedUserIds = array_merge(
                                $changedUserIds,
                                $profile->childBillTos()->pluck('user_id')->all()
                            );
                        }
                    }
                }
            }
        });

        User::query()
            ->whereIn('id', array_unique($changedUserIds))
            ->with('clientProfile')
            ->get()
            ->each(fn (User $user) => $this->queueQuickBooksCustomerSyncIfReady($user));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'updated' => count($validated['updates'])]);
        }

        return redirect()->back()->with('success', count($validated['updates']).' Bill To record(s) updated.');
    }

    /** Assign or clear a child Bill To's parent account. */
    public function updateParent(Request $request, User $client)
    {
        if (! $this->canManageBillables() || (int) $client->role_id !== 3) {
            abort(403);
        }

        $validated = $request->validate([
            'parent_bill_to_id' => 'nullable|integer',
        ]);
        $parentId = $validated['parent_bill_to_id'] ?? null;
        if ($parentId !== null) {
            if ((int) $parentId === (int) $client->id) {
                return response()->json(['message' => 'A Bill To cannot be its own parent.'], 422);
            }
            $parent = User::where('role_id', 3)->findOrFail($parentId);
            // Walk ancestors to prevent cycles, including indirect cycles.
            $seen = [(int) $client->id];
            $cursor = $parent;
            while ($cursor) {
                if (in_array((int) $cursor->id, $seen, true)) {
                    return response()->json(['message' => 'This parent assignment would create a cycle.'], 422);
                }
                $seen[] = (int) $cursor->id;
                $cursor = $cursor->clientProfile?->parentBillTo;
            }
        }

        $profile = $client->clientProfile ?? new \App\Models\Client(['user_id' => $client->id]);
        $profile->parent_bill_to_id = $parentId;
        $profile->save();

        return response()->json(['success' => true, 'parent_bill_to_id' => $parentId]);
    }

    /**
     * Bulk sync all unsynced clients to QuickBooks.
     */
    public function syncAllClients()
    {
        if (! QuickBooksHelper::isConnected()) {
            return redirect()->back()->with('error', 'Please connect to QuickBooks before syncing Bill To records.');
        }

        SyncAllClientsWithQuickBooks::dispatch();

        return redirect()->back()->with('success', 'QuickBooks sync started! Clients will be synced in the background.');
    }

    /**
     * Sync a single client to QuickBooks.
     */
    public function syncClient(User $client)
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

    /**
     * AJAX: monthly event stats for a client (used by Monthly Summary modal).
     */
    public function monthlyStats(Request $request, User $client)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month.'-01')->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $base = Event::query()
            ->whereBetween('start_time', [$start, $end])
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                    ->orWhereHas('billingParties', fn ($bq) => $bq->where('client_id', $client->id));
            });

        $submitted   = (clone $base)->whereHas('recap')->count();
        $notRecapped = (clone $base)->where('recap_needed', 1)->where(fn ($q) => $q->whereDoesntHave('recap')->orWhere('recap_status', 'overdue'))->count();
        $noShows     = (clone $base)->where(fn ($q) => $q->where('admin_status', 'no_show')->orWhereHas('recap', fn ($rq) => $rq->where('manager_no_show', 1)))->count();
        $cancelled   = (clone $base)->where('admin_status', 'like', 'cancelled_%')->count();
        $late        = (clone $base)->whereHas('recap', fn ($rq) => $rq->whereRaw('recaps.created_at > DATE_ADD(events.end_time, INTERVAL 24 HOUR)'))->count();

        return response()->json([
            'month'        => $start->format('F Y'),
            'submitted'    => $submitted,
            'not_recapped' => $notRecapped,
            'no_shows'     => $noShows,
            'late'         => $late,
            'cancelled'    => $cancelled,
        ]);
    }

    /**
     * CSV export of filtered billables (admin/manager routes only).
     */
    public function export(Request $request): StreamedResponse
    {
        if (! $this->canManageBillables()) {
            abort(403);
        }

        $rows = $this->billablesQuery($request)->get();

        $filename = 'billables_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Phone', 'QuickBooks ID', 'Status', 'Contract Received', 'Address']);
            foreach ($rows as $u) {
                $name = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
                if ($name === '') {
                    $name = (string) ($u->name ?? '');
                }
                $profile  = $u->clientProfile;
                $active   = ((string) $u->status === '1' || (int) $u->status === 1) ? 'Active' : 'Inactive';
                $contract = $profile && ((int) $profile->contract_recieved === 1 || (string) $profile->contract_recieved === '1') ? 'Yes' : 'No';
                fputcsv($out, [
                    $name,
                    $u->email,
                    $u->phone,
                    $profile?->quickbooks_customer_id,
                    $active,
                    $contract,
                    $profile?->address,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
