<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\MissingDwollaProfileDataException;
use App\Http\Controllers\Controller;
use App\Models\AmbassadorPayout;
use App\Models\DwollaFundingSource;
use App\Models\Reimbursement;
use App\Models\UserTransaction;
use App\Services\Dwolla\CustomerService;
use App\Services\Dwolla\FundingSourceService;
use App\Trait\FormatResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class BankingController extends Controller
{
    use FormatResponseTrait;

    public function __construct(
        protected CustomerService $customerService,
        protected FundingSourceService $fundingSourceService,
    ) {}

    /**
     * GET /api/banking/status
     * Returns bank setup status, Dwolla customer status, and funding sources.
     */
    public function status(): JsonResponse
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User not found.', [], 403);
        }

        $user->load('dwollaCustomer.fundingSources');

        $customer = $user->dwollaCustomer;
        $customer?->ensureVerifiedDefaultFundingSource();
        $customer?->load('fundingSources');
        $sources = $customer?->fundingSources ?? collect();

        $mappedSources = $sources
            ->where('status', '!=', 'removed')
            ->values()
            ->map(fn (DwollaFundingSource $s) => $this->mapSource($s));

        $defaultSource = $mappedSources->first(
            fn (array $source) => $source['is_default'] === true && $source['status'] === 'verified'
        );

        return $this->successResponse('Banking status.', [
            'bank_setup_status'       => $user->getBankSetupStatus(),
            'bank_setup_status_label' => $user->getBankSetupStatusLabel(),
            'has_dwolla_customer'     => (bool) $customer,
            'dwolla_customer_status'  => $customer?->status,
            'missing_profile_fields'  => $customer
                ? []
                : array_values($this->customerService->getMissingDwollaFields($user)),
            'funding_sources'         => $mappedSources,
            'default_source'          => $defaultSource,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User not found.', [], 403);
        }

        $perPage = max(1, min((int) $request->input('per_page', 10), 50));
        $transactions = UserTransaction::where('user_id', $user->id)
            ->orderByDesc('occurred_at')
            ->paginate($perPage);

        $transactions->getCollection()->loadMorph('source', [
            AmbassadorPayout::class => ['fundingSource'],
            Reimbursement::class => ['fundingSource'],
        ]);

        $totalEarning = UserTransaction::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'paid'])
            ->sum('amount');

        return $this->successResponse('Transactions fetched successfully.', [
            'total_earning' => (float) $totalEarning,
            'transactions' => $transactions->getCollection()->values()->map(function (UserTransaction $transaction, int $index) use ($transactions) {
                $eventId = $transaction->meta['event_id'] ?? null;

                return [
                    'row_number' => $transactions->firstItem() + $index,
                    'type' => $transaction->type,
                    'type_label' => $transaction->type_label,
                    'amount' => (float) $transaction->amount,
                    'status' => $transaction->status,
                    'status_label' => $transaction->status_label,
                    'dwolla_transfer_id' => $transaction->dwolla_transfer_id,
                    'bank_name' => $transaction->bank_name,
                    'bank_account_last4' => $transaction->bank_account_last4,
                    'event_id' => $eventId,
                    'event_name' => $eventId ? ($transaction->meta['event_name'] ?? ('Event #'.$eventId)) : null,
                    'occurred_at' => $transaction->occurred_at?->toIso8601String(),
                    'created_at' => $transaction->created_at?->toIso8601String(),
                    'display_date' => ($transaction->occurred_at ?? $transaction->created_at)?->format('d M Y, h:i A'),
                ];
            }),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * POST /api/banking/setup
     * Create the Dwolla customer for the authenticated ambassador.
     * Accepts profile fields that may still be missing (first_name, last_name, dob,
     * legal_address, legal_city, legal_state_id, legal_zip_code).
     */
    public function setup(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User not found.', [], 403);
        }

        if ($user->dwollaCustomer) {
            return $this->successResponse('Payment account already set up.', [
                'bank_setup_status' => $user->getBankSetupStatus(),
            ]);
        }

        // Accept missing profile fields if provided
        $missingKeys = $this->customerService->getMissingDwollaFieldKeys($user);

        if ($missingKeys !== []) {
            $rules = [
                'first_name'      => 'required|string|max:255',
                'last_name'       => 'required|string|max:255',
                'dob'             => 'required|date|before:today',
                'legal_address'   => 'required|string|max:255',
                'legal_city'      => 'required|string|max:255',
                'legal_state_id'  => 'required|exists:states,id',
                'legal_zip_code'  => 'required|numeric|digits_between:5,6',
            ];

            $toValidate = collect($rules)->only($missingKeys)->all();
            $validated  = $request->validate($toValidate);

            foreach ($validated as $field => $value) {
                $user->{$field} = $value;
                // Mirror into non-legal counterparts where they exist
                $mirror = [
                    'legal_address'  => 'address',
                    'legal_city'     => 'city',
                    'legal_state_id' => 'state_id',
                    'legal_zip_code' => 'zip_code',
                ];
                if (isset($mirror[$field])) {
                    $user->{$mirror[$field]} = $value;
                }
            }

            if (in_array('first_name', $missingKeys, true) || in_array('last_name', $missingKeys, true)) {
                $user->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            }

            $user->save();
        }

        try {
            $this->customerService->create($user->fresh());
        } catch (MissingDwollaProfileDataException $e) {
            return $this->errorResponse(
                'Missing required fields: ' . implode(', ', array_values($e->missingFields)),
                'validation_error',
                422
            );
        } catch (\Throwable $e) {
            Log::error('BankingController::setup failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Payment account setup could not be completed. Please try again.',
                'server_error',
                500
            );
        }

        return $this->successResponse('Payment account created. You can now add a bank account.', [
            'bank_setup_status' => $user->fresh()->getBankSetupStatus(),
        ]);
    }

    /**
     * POST /api/banking/add
     * Add a bank account (creates Dwolla customer first if not yet done).
     */
    public function add(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User not found.', [], 403);
        }

        if (! $user->dwollaCustomer) {
            if (! $this->customerService->hasRequiredDwollaData($user)) {
                return $this->errorResponse(
                    'Please complete payment account setup before adding a bank account.',
                    'setup_required',
                    422
                );
            }

            try {
                $this->customerService->create($user);
                $user->refresh();
            } catch (MissingDwollaProfileDataException $e) {
                return $this->errorResponse(
                    'Missing required fields: ' . implode(', ', array_values($e->missingFields)),
                    'validation_error',
                    422
                );
            } catch (\Throwable $e) {
                Log::error('BankingController::add — customer creation failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);

                return $this->errorResponse(
                    'Could not create payment account. Please try again.',
                    'server_error',
                    500
                );
            }
        }

        $validated = $request->validate([
            'bank_name'            => 'required|string|max:255',
            'account_holder_name'  => 'required|string|max:255',
            'routing_number'       => 'required|string|size:9',
            'account_number'       => 'required|string|max:17',
            'account_type'         => 'required|in:checking,savings',
            'set_as_default'       => 'sometimes|boolean',
        ]);

        $bankData = [
            'routingNumber'   => $validated['routing_number'],
            'accountNumber'   => $validated['account_number'],
            'bankAccountType' => $validated['account_type'],
            'name'            => $validated['bank_name'],
        ];

        try {
            $source = $this->fundingSourceService->create(
                $user->dwollaCustomer,
                $bankData,
                (bool) ($validated['set_as_default'] ?? false)
            );
        } catch (\Throwable $e) {
            Log::error('BankingController::add — funding source creation failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->errorResponse(
                $e->getMessage() ?: 'Could not add bank account. Please check your details and try again.',
                'bank_error',
                422
            );
        }

        return $this->successResponse('Bank account added. Please verify using the two micro-deposit amounts.', [
            'source' => $this->mapSource($source),
        ]);
    }

    /**
     * POST /api/banking/verify
     * Submit two micro-deposit amounts to verify a funding source.
     */
    public function verify(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User not found.', [], 403);
        }

        $validated = $request->validate([
            'funding_source_id' => 'required|integer|exists:dwolla_funding_sources,id',
            'amount1'           => 'required|numeric|min:0.01|max:0.99',
            'amount2'           => 'required|numeric|min:0.01|max:0.99',
        ]);

        $source = DwollaFundingSource::findOrFail($validated['funding_source_id']);

        // Security: ensure this source belongs to the authenticated user
        if ($source->dwollaCustomer->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized.', [], 403);
        }

        if (! in_array($source->status, ['unverified', 'awaiting_verification', 'added', 'microdeposits_completed', 'failed', 'verification_failed'], true)) {
            return $this->errorResponse(
                'This bank account cannot be verified at this stage.',
                'invalid_status',
                422
            );
        }

        DB::beginTransaction();

        try {
            $this->fundingSourceService->verifyMicroDeposits(
                $source->dwolla_funding_source_id,
                $validated['amount1'],
                $validated['amount2']
            );

            $source->update([
                'status' => 'awaiting_verification',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $source->update(['status' => 'failed']);

            Log::warning('BankingController::verify failed', [
                'user_id'           => $user->id,
                'funding_source_id' => $source->id,
                'error'             => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Verification failed. Please check the amounts and try again.',
                'verification_failed',
                422
            );
        }

        return $this->successResponse('Bank account verified successfully.', [
            'source' => $this->mapSource($source->fresh()),
        ]);
    }

    /**
     * POST /api/banking/set-default/{id}
     * Mark a funding source as the default.
     */
    public function setDefault(int $id): JsonResponse
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User not found.', [], 403);
        }

        $source = DwollaFundingSource::findOrFail($id);

        if ($source->dwollaCustomer->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized.', [], 403);
        }

        if ($source->status !== 'verified') {
            return $this->errorResponse(
                'Only verified bank accounts can be set as default.',
                'invalid_status',
                422
            );
        }

        DB::transaction(function () use ($source) {
            DwollaFundingSource::where('dwolla_customer_id', $source->dwolla_customer_id)
                ->update(['is_default' => false]);
            $source->update(['is_default' => true]);
        });

        return $this->successResponse('Default bank account updated.', [
            'source' => $this->mapSource($source->fresh()),
        ]);
    }

    /**
     * DELETE /api/banking/remove/{id}
     * Deactivate / remove a funding source.
     */
    public function remove(int $id): JsonResponse
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User not found.', [], 403);
        }

        $source = DwollaFundingSource::findOrFail($id);

        if ($source->dwollaCustomer->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized.', [], 403);
        }

        try {
            $this->fundingSourceService->deactivate($source);
        } catch (\Throwable $e) {
            Log::error('BankingController::remove failed', [
                'user_id' => $user->id,
                'source'  => $id,
                'error'   => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Could not remove bank account. Please try again.',
                'server_error',
                500
            );
        }

        return $this->successResponse('Bank account removed.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function mapSource(DwollaFundingSource $source): array
    {
        $accountNumber = (string) ($source->account_number ?? '');
        $masked = strlen($accountNumber) >= 4
            ? '****' . substr($accountNumber, -4)
            : '****';

        return [
            'id'                      => $source->id,
            'bank_name'               => $source->bank_name,
            'account_type'            => $source->account_type,
            'account_number_masked'   => $masked,
            'status'                  => $source->status,
            'status_label'            => $this->statusLabel($source->status),
            'is_default'              => (bool) $source->is_default && $source->status === 'verified',
            'can_verify'              => in_array($source->status, ['unverified', 'awaiting_verification', 'added', 'microdeposits_completed', 'failed', 'verification_failed'], true),
            'can_set_default'         => $source->status === 'verified',
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'unverified', 'added'   => 'Pending Verification',
            'awaiting_verification' => 'Awaiting Verification',
            'microdeposits_initiated' => 'Microdeposits Sent',
            'microdeposits_completed' => 'Ready to Verify',
            'verified'              => 'Verified',
            'failed'                => 'Verification Failed',
            'removed'               => 'Removed',
            default                 => ucfirst($status),
        };
    }
}
