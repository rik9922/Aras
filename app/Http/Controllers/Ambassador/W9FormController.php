<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\W9Form;
use App\Services\QuickBooksService;
use App\Services\W9TinVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class W9FormController extends Controller
{
    protected $quickBooksService;

    public function __construct(QuickBooksService $quickBooksService)
    {
        $this->quickBooksService = $quickBooksService;
    }

    public function show()
    {
        $user = Auth::user();
        $w9Form = $user->w9Form;

        return view('ambassador.w9-form', compact('w9Form'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => ['nullable', 'string', 'max:255', 'required_if:is_ein,1'],
            'tax_classification' => 'required|in:individual,c_corp,s_corp,partnership,trust,llc,other',
            'llc_tax_classification' => 'nullable|string|max:255',
            'other_tax_classification' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
            'ssn_ein' => 'required|string',
            'is_ein' => 'required|boolean',
            'signature' => 'required|string|max:255',
        ], [
            'business_name.required_if' => 'Business / Company Name is required when using an EIN.',
        ]);

        // ✅ Extract 9 digits and format for QuickBooks
        $taxId = preg_replace('/[^0-9]/', '', $validated['ssn_ein']);

        if (strlen($taxId) !== 9) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['ssn_ein' => 'Tax ID must be exactly 9 digits. Please check for missing or extra numbers.']);
        }

        $user = Auth::user();

        // QuickBooks accepts: "12-3456789" (EIN) OR "123456789" (SSN numeric only)
        $qbTaxId = $validated['is_ein']
            ? substr($taxId, 0, 2).'-'.substr($taxId, 2)  // "12-3456789"
            : $taxId;  // "123456789"

        $verifier = app(W9TinVerificationService::class);
        $verify = $verifier->verify($taxId, (bool) $validated['is_ein']);
        if (! ($verify['ok'] ?? false)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['ssn_ein' => $verify['error'] ?? 'Tax ID appears invalid. Please verify and try again.']);
        }

        try {
            DB::beginTransaction();

            $w9FormData = array_merge($validated, [
                'user_id' => $user->id,
                'signature_date' => now(),
                'formatted_tax_id' => $qbTaxId,
                'verification_status' => 'verified',
                'verification_provider' => $verify['provider'] ?? 'basic',
                'verified_at' => now(),
                'verification_error' => null,
            ]);

            $w9Form = W9Form::updateOrCreate(
                ['user_id' => $user->id],
                $w9FormData
            );

            // Vendor name: "Last Name, First Name" for individuals; business name for EIN
            $vendorDisplayName = $validated['is_ein']
                ? ($validated['business_name'] ?? $validated['name'] ?? trim($user->last_name.', '.$user->first_name))
                : trim($user->last_name.', '.$user->first_name);

            // ✅ Create QuickBooks vendor
            $vendorId = $this->quickBooksService->createVendor([
                'name' => $vendorDisplayName,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'zip_code' => $validated['zip_code'],
                'tax_id' => $qbTaxId,  //
            ]);

            if ($vendorId) {
                $w9Form->update(['quickbooks_vendor_id' => $vendorId]);
                $user->update([
                    'quickbooks_vendor_id' => $vendorId,
                    'w9_submitted' => true,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success',
                'w-9 submitted! Vendor created (ID: '.($vendorId ?? 'N/A').
                '). Tax ID: '.$qbTaxId.'. Admin: Manually enable 1099 checkbox.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('W9 submission failed: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Failed to submit W-9. Please try again.');
        }
    }
}
