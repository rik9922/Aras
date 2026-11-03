<?php

namespace App\Http\Controllers\Ambassador;

use App\Exceptions\MissingDwollaProfileDataException;
use App\Http\Controllers\Controller;
use App\Models\AmbassadorPayout;
use App\Models\DwollaFundingSource;
use App\Models\Reimbursement;
use App\Models\UserTransaction;
use App\Services\Dwolla\CustomerService;
use App\Services\Dwolla\DwollaService;
use App\Services\Dwolla\FundingSourceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DwollaController extends Controller
{
    protected $fundingSourceService;

    protected $customerService;

    public function __construct(FundingSourceService $fundingSourceService, CustomerService $customerService)
    {
        $this->fundingSourceService = $fundingSourceService;
        $this->customerService = $customerService;
    }

    public function bank(Request $request)
    {
        $user = $request->user();

        if (! $user->dwollaCustomer && ! $this->customerService->hasRequiredDwollaData($user)) {
            $profileIssues = $this->customerService->getMissingDwollaFields($user);

            return redirect()
                ->route('ambassador.dwolla.payment-setup')
                ->with('warning', 'Please correct your payment profile before adding a bank account: '
                    .implode(', ', array_values($profileIssues)).'.');
        }

        $user->dwollaCustomer?->ensureVerifiedDefaultFundingSource();
        $sources = $user->dwollaCustomer?->fundingSources ?? collect();

        return view('ambassador.dwolla.bank', compact('sources'));
    }

    public function paymentSetupForm(Request $request)
    {
        $user = $request->user();

        if ($user->dwollaCustomer) {
            return redirect()
                ->route('ambassador.dwolla.bank-account')
                ->with('success', 'Your payment account is already set up.');
        }

        $missingFields = $this->customerService->getMissingDwollaFields($user);
        $retrySetupOnly = $missingFields === [];

        return view('ambassador.dwolla.payment-setup', compact('user', 'missingFields', 'retrySetupOnly'));
    }

    public function completePaymentSetup(Request $request)
    {
        $user = $request->user()->fresh();

        if ($user->dwollaCustomer) {
            return redirect()
                ->route('ambassador.calendar')
                ->with('success', 'Your payment account is already set up.');
        }

        $missingKeys = $this->customerService->getMissingDwollaFieldKeys($user);

        if ($missingKeys === []) {
            try {
                $this->customerService->create($user);
                $request->session()->forget(['dwolla_payment_setup_required', 'dwolla_missing_fields']);

                return redirect()
                    ->route('ambassador.calendar')
                    ->with('success', 'Payment account setup complete. You can now add a bank account when you are ready.');
            } catch (MissingDwollaProfileDataException $e) {
                $missingKeys = array_keys($e->missingFields);
            } catch (\Throwable $e) {
                Log::error('Dwolla payment setup retry failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with(
                    'warning',
                    'Payment account setup could not be completed yet. Please try again shortly.'
                );
            }
        }

        $rules = [
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'dob'           => 'required|date|before_or_equal:'.now()->subYears(CustomerService::MINIMUM_CUSTOMER_AGE)->format('Y-m-d'),
            'legal_address' => 'required|string|max:255',
            'legal_city'    => 'required|string|max:255',
            'legal_state'   => 'required|string|max:3',   // short code from ambassadors table
            'legal_zip_code'=> ['required', 'string', 'regex:/^(\d{5}|\d{5}-\d{4}|\d{9})$/'],
        ];

        $fieldsToValidate = $missingKeys !== [] ? $missingKeys : array_keys($rules);
        $request->validate(collect($rules)->only($fieldsToValidate)->all(), [
            'dob.before_or_equal' => 'Date of birth must show you are at least '.CustomerService::MINIMUM_CUSTOMER_AGE.' years old.',
            'legal_zip_code.regex' => 'Legal ZIP code must be a valid 5-digit U.S. ZIP code.',
        ]);

        if (in_array('first_name', $missingKeys, true)) {
            $user->first_name = $request->first_name;
        }
        if (in_array('last_name', $missingKeys, true)) {
            $user->last_name = $request->last_name;
        }
        if (in_array('first_name', $missingKeys, true) || in_array('last_name', $missingKeys, true)) {
            $user->name = trim($user->first_name.' '.$user->last_name);
        }
        // identity fields remain on users table
        $user->save();

        // ambassador profile fields go to ambassadors table
        $profileUpdates = [];
        if (in_array('dob', $missingKeys, true)) {
            $profileUpdates['dob'] = $request->dob;
        }
        if (in_array('legal_address', $missingKeys, true)) {
            $profileUpdates['legal_address'] = $request->legal_address;
        }
        if (in_array('legal_city', $missingKeys, true)) {
            $profileUpdates['legal_city'] = $request->legal_city;
        }
        if (in_array('legal_state', $missingKeys, true)) {
            $profileUpdates['legal_state'] = strtoupper(trim((string) $request->legal_state));
        }
        if (in_array('legal_zip_code', $missingKeys, true)) {
            $profileUpdates['legal_zip_code'] = $this->customerService->normalizePostalCode($request->legal_zip_code);
        }
        if (! empty($profileUpdates)) {
            $user->ambassadorProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileUpdates
            );
        }

        try {
            $this->customerService->create($user->fresh());
        } catch (MissingDwollaProfileDataException $e) {
            return back()
                ->withInput()
                ->with('warning', 'Please provide all required information. Still missing: '
                    .implode(', ', array_values($e->missingFields)).'.');
        } catch (\Throwable $e) {
            Log::error('Dwolla payment setup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('warning', 'Your information was saved, but payment account setup could not be completed yet. Please try again shortly.');
        }

        $request->session()->forget(['dwolla_payment_setup_required', 'dwolla_missing_fields']);

        return redirect()
            ->route('ambassador.calendar')
            ->with('success', 'Payment account setup complete. You can now add a bank account when you are ready.');
    }

    public function addBank(Request $request)
    {
        $user = $request->user();

        if (! $user->dwollaCustomer) {
            if (! $this->customerService->hasRequiredDwollaData($user)) {
                $profileIssues = $this->customerService->getMissingDwollaFields($user);

                return redirect()
                    ->route('ambassador.dwolla.payment-setup')
                    ->with('warning', 'Please correct your payment profile before adding a bank account: '
                        .implode(', ', array_values($profileIssues)).'.');
            }

            try {
                $this->customerService->create($user);
                $user->refresh();
            } catch (MissingDwollaProfileDataException $e) {
                return redirect()
                    ->route('ambassador.dwolla.payment-setup')
                    ->with('warning', 'Please correct your payment profile before adding a bank account: '
                        .implode(', ', array_values($e->missingFields)).'.');
            }
        }

        // 2. Validate request
        $request->validate(
            [
                'bank_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'],
                'account_holder_name' => 'required|string|max:255',
                'routing_number' => 'required|string',
                'account_number' => 'required|string',
                'account_type' => 'required|in:checking,savings',
            ],
            [
                'bank_name.required' => 'Bank Name is required.',
                'bank_name.min' => 'Bank Name must be at least 2 characters.',
                'bank_name.max' => 'Bank Name may not be greater than 80 characters.',
                'bank_name.regex' => 'Bank Name may contain letters and single spaces only.',
            ]
        );

        // 3. Prepare bank data for Dwolla
        $bankData = [
            'routingNumber' => $request->routing_number,
            'accountNumber' => $request->account_number,
            'bankAccountType' => $request->account_type,
            'name' => $request->bank_name,
        ];

        try {
            // 4. Create funding source
            $this->fundingSourceService->create(
                $user->dwollaCustomer,
                $bankData,
                $request->boolean('set_as_default')
            );

            return back()->with('success', 'Bank account added successfully.');

        } catch (\Exception $e) {
            Log::error('Dwolla Bank Add Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $bankError = str_contains($e->getMessage(), 'Bank already exists') || $this->isDuplicateBankIntegrityError($e)
                ? 'This bank account has already been linked to your profile.'
                : 'Unable to add bank account. Please check your details and try again.';

            return back()->withErrors([
                'bank_error' => $bankError,
            ])->withInput();
        }
    }

    private function isDuplicateBankIntegrityError(\Exception $e): bool
    {
        return $e instanceof QueryException
            && $e->getCode() === '23000'
            && str_contains($e->getMessage(), 'dwolla_funding_source_id');
    }

    public function verifyForm(DwollaFundingSource $source)
    {
        abort_if(! in_array($source->status, ['unverified', 'awaiting_verification', 'added', 'microdeposits_completed'], true), 403);

        return view('ambassador.dwolla.bank-verify', compact('source'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'funding_source_id' => 'required|exists:dwolla_funding_sources,id',
            'amount1' => 'required|numeric|min:0.01',
            'amount2' => 'required|numeric|min:0.01',
        ]);

        $source = DwollaFundingSource::findOrFail($request->funding_source_id);

        DB::beginTransaction();

        try {
            $dwolla = app(DwollaService::class);

            $dwolla->verifyMicroDeposits(
                $source->dwolla_funding_source_id,
                [
                    'amount1' => $request->amount1,
                    'amount2' => $request->amount2,
                ]
            );

            $source->update([
                'status' => 'awaiting_verification',
            ]);

            DB::commit();

            return redirect()
                ->route('ambassador.dwolla.bank-account')
                ->with('info', 'Bank account awaiting verification.');

        } catch (\Exception $e) {
            DB::rollBack();
            $source->update([
                'status' => 'failed',
            ]);

            return back()->withErrors([
                'verification' => 'Verification failed. Please check the amounts and try again.',
            ]);
        }
    }

    public function setDefault(DwollaFundingSource $bank)
    {
        abort_if($bank->dwollaCustomer->user_id !== auth()->id(), 403);

        if ($bank->status !== 'verified') {
            return back()->with('error', 'Only verified bank accounts can be set as default.');
        }

        DB::transaction(function () use ($bank) {
            // Unset previous default
            DwollaFundingSource::where('dwolla_customer_id', $bank->dwolla_customer_id)
                ->update(['is_default' => false]);

            // Set this bank as default
            $bank->update(['is_default' => true]);
        });

        return back()->with('success', 'Default bank updated successfully.');
    }

    public function deleteBank(Request $request, DwollaFundingSource $fundingSource)
    {
        // Security check
        abort_if($fundingSource->dwollaCustomer->user_id !== $request->user()->id, 403);

        try {
            $this->fundingSourceService->deactivate($fundingSource);

            return back()->with('success', 'Bank account removed successfully.');

        } catch (\Exception $e) {
            Log::error('Dwolla Bank Delete Error', [
                'source_id' => $fundingSource->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'bank_error' => 'Unable to remove bank account. Please try again.',
            ]);
        }
    }

    // transactions
    public function transactions()
    {
        $userId = auth()->id();

        $transactions = UserTransaction::where('user_id', $userId)
            ->orderByDesc('occurred_at')
            ->paginate(10);

        $transactions->getCollection()->loadMorph('source', [
            AmbassadorPayout::class => ['fundingSource'],
            Reimbursement::class => ['fundingSource'],
        ]);

        // Total earnings = sum of positive amounts for completed/paid transactions
        $totalEarning = UserTransaction::where('user_id', $userId)
            ->whereIn('status', ['completed', 'paid'])
            ->sum('amount');

        return view('ambassador.dwolla.bank-transactions', compact('transactions', 'totalEarning'));
    }
}
