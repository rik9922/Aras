<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\AssignAmbassador;
use App\Models\Brand;
use App\Models\Event;
use App\Models\Reimbursement;
use App\Services\ReviewNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReimbursementController extends Controller
{
    public function index()
    {
        $reimbursements = Reimbursement::with('event', 'brand')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $hasVerifiedBank = Auth::user()->hasVerifiedBankAccount();

        return view('ambassador.reimbursement.index', compact('reimbursements', 'hasVerifiedBank'));
    }

    public function create()
    {
        $user = Auth::user();
        $hasVerifiedBank = $user->hasVerifiedBankAccount();

        $brands = Brand::orderBy('brand_name')->get();

        // Events assigned to this ambassador (optional tagging)
        $workedEventIds = AssignAmbassador::where('ambassador_id', $user->id)
            ->whereIn('status', ['0', '1'])
            ->pluck('event_id');

        $assignableEvents = Event::with('brand')
            ->whereIn('id', $workedEventIds)
            ->orderBy('booked_date', 'desc')
            ->get();

        return view('ambassador.reimbursement.create', compact('brands', 'assignableEvents', 'hasVerifiedBank'));
    }

    public function store(Request $request)
    {
        if (! Auth::user()->hasVerifiedBankAccount()) {
            return redirect()->route('ambassador.reimbursements.create')
                ->with('error', 'You must add and verify a bank account before requesting reimbursement. Go to My Account → Bank Account, add your bank, and complete verification. Once approved, you can submit reimbursement requests.');
        }

        $eventRule = Auth::user()->role_id == 5
            ? Rule::exists('assign_ambassadors', 'event_id')
                ->where(function ($query) {
                    $query->where('ambassador_id', Auth::id())
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
            return redirect()->back()->withInput()->with('error', 'Please provide either an event, brand, or brand name.');
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('reimbursements', 'public');
        }

        $reimbursement = Reimbursement::create([
            'user_id' => Auth::id(),
            'event_id' => $validated['event_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'expense_date' => $validated['expense_date'],
            'brand_name' => $validated['brand_name'] ?? null,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'receipt_path' => $receiptPath,
            'status' => 'pending',
        ]);

        app(ReviewNotificationService::class)->reimbursementSubmitted(Auth::user(), $reimbursement);

        return redirect()->route('ambassador.reimbursements.index')
            ->with('success', 'Reimbursement submitted successfully. A manager will review it.');
    }

    public function show(Reimbursement $reimbursement)
    {
        if ($reimbursement->user_id !== Auth::id()) {
            abort(403);
        }

        $reimbursement->load('event', 'brand', 'reviewer');

        return view('ambassador.reimbursement.show', compact('reimbursement'));
    }

    /**
     * Allow BA to edit a rejected reimbursement and resubmit.
     */
    public function edit(Reimbursement $reimbursement)
    {
        if ($reimbursement->user_id !== Auth::id()) {
            abort(403);
        }

        if ($reimbursement->status !== 'rejected') {
            return redirect()
                ->route('ambassador.reimbursements.show', $reimbursement)
                ->with('error', 'Only rejected reimbursements can be edited.');
        }

        $user = Auth::user();
        $hasVerifiedBank = $user->hasVerifiedBankAccount();

        $brands = Brand::orderBy('brand_name')->get();

        $workedEventIds = AssignAmbassador::where('ambassador_id', $user->id)
            ->whereIn('status', ['0', '1'])
            ->pluck('event_id');

        $assignableEvents = Event::with('brand')
            ->whereIn('id', $workedEventIds)
            ->orderBy('booked_date', 'desc')
            ->get();

        return view('ambassador.reimbursement.edit', compact('reimbursement', 'brands', 'assignableEvents', 'hasVerifiedBank'));
    }

    public function update(Request $request, Reimbursement $reimbursement)
    {
        if ($reimbursement->user_id !== Auth::id()) {
            abort(403);
        }

        if ($reimbursement->status !== 'rejected') {
            return redirect()
                ->route('ambassador.reimbursements.show', $reimbursement)
                ->with('error', 'Only rejected reimbursements can be updated.');
        }

        if (! Auth::user()->hasVerifiedBankAccount()) {
            return redirect()->route('ambassador.reimbursements.edit', $reimbursement)
                ->with('error', 'You must add and verify a bank account before requesting reimbursement. Go to My Account → Bank Account, add your bank, and complete verification. Once approved, you can submit reimbursement requests.');
        }

        $eventRule = Auth::user()->role_id == 5
            ? Rule::exists('assign_ambassadors', 'event_id')
                ->where(function ($query) {
                    $query->where('ambassador_id', Auth::id())
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
            return redirect()->back()->withInput()->with('error', 'Please provide either an event, brand, or brand name.');
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

        app(ReviewNotificationService::class)->reimbursementUpdated(Auth::user(), $reimbursement);

        return redirect()->route('ambassador.reimbursements.show', $reimbursement)
            ->with('success', 'Reimbursement updated and resubmitted for review.');
    }
}
