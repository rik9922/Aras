<?php

namespace App\Services\Dwolla;

use App\Exceptions\MissingDwollaProfileDataException;
use App\Models\DwollaCustomer;
use App\Models\User;
use App\Services\AmbassadorNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    public const MINIMUM_CUSTOMER_AGE = 18;

    /** @var array<string, string> */
    public const DWOLLA_PROFILE_FIELDS = [
        'first_name'    => 'First name',
        'last_name'     => 'Last name',
        'email'         => 'Email',
        'dob'           => 'Date of birth',
        'legal_address' => 'Legal street address',
        'legal_city'    => 'Legal city',
        'legal_state'   => 'Legal state',   // short code (NY, CA) from ambassadors table
        'legal_zip_code'=> 'Legal ZIP code',
    ];

    public function create(User $user): DwollaCustomer
    {
        if ($user->dwollaCustomer) {
            return $user->dwollaCustomer;
        }

        $profileIssues = $this->getMissingDwollaFields($user);

        if ($profileIssues !== []) {
            throw new MissingDwollaProfileDataException($profileIssues);
        }

        DB::beginTransaction();

        try {
            $client = new DwollaClient;
            $dateOfBirth = $this->normalizeDateOfBirth($user->dob);
            $postalCode = $this->normalizePostalCode($user->legal_zip_code);

            $payload = [
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'type' => 'personal',
                'ipAddress' => request()->ip() ?? '127.0.0.1',

                // Address — reads via User proxy accessor from ambassadors table
                'address1'    => $user->legal_address,
                'city'        => $user->legal_city,
                'state'       => strtoupper(trim((string) $user->legal_state)),  // short code from ambassadors.legal_state
                'postalCode'  => $postalCode,

                // Identity
                'dateOfBirth' => $dateOfBirth,
                'ssn' => '1234', // sandbox only
            ];

            Log::info('Dwolla customer payload', $payload);

            $response = $client->post('/customers', $payload);

            // ❗ Fail fast if Dwolla rejected request
            if (! $response->successful()) {
                Log::error('Dwolla customer creation failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                throw new \Exception('Dwolla customer creation failed.');
            }

            $location = $response->header('Location');

            if (! $location) {
                Log::error('Dwolla missing Location header', [
                    'response' => $response->json(),
                ]);

                throw new \Exception('Dwolla did not return customer ID.');
            }

            $dwollaCustomerId = basename($location);

            Log::info('Dwolla customer created', [$response]);

            $dwollaCustomer = DwollaCustomer::create([
                'user_id' => $user->id,
                'dwolla_customer_id' => $dwollaCustomerId,
                'status' => 'created',
                // 'raw_response' => $response->json(),
            ]);

            DB::commit();

            app(AmbassadorNotificationService::class)->paymentSetupCompleted($user);

            return $dwollaCustomer;

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Dwolla customer transaction failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // bubble up to controller
        }
    }

    public function hasRequiredDwollaData(User $user): bool
    {
        return $this->getMissingDwollaFieldKeys($user) === [];
    }

    /**
     * @return list<string>
     */
    public function getMissingDwollaFieldKeys(User $user): array
    {
        return array_keys($this->getDwollaProfileIssues($user));
    }

    /**
     * @return array<string, string> Field key => user-facing correction
     */
    public function getDwollaProfileIssues(User $user): array
    {
        $issues = [];

        foreach (self::DWOLLA_PROFILE_FIELDS as $field => $label) {
            if (empty($user->{$field})) {
                $issues[$field] = $label;
            }
        }

        if (! empty($user->dob)) {
            $dateOfBirth = $this->normalizeDateOfBirth($user->dob);

            if (! $dateOfBirth) {
                $issues['dob'] = 'Date of birth (enter a valid date)';
            } elseif (Carbon::parse($dateOfBirth)->gt(now()->subYears(self::MINIMUM_CUSTOMER_AGE))) {
                $issues['dob'] = 'Date of birth (must show you are at least '.self::MINIMUM_CUSTOMER_AGE.' years old)';
            }
        }

        if (! empty($user->legal_zip_code) && ! $this->normalizePostalCode($user->legal_zip_code)) {
            $issues['legal_zip_code'] = 'Legal ZIP code (enter a valid 5-digit U.S. ZIP code)';
        }

        return $issues;
    }

    /**
     * @return array<string, string> Field key => human-readable label
     */
    public function getMissingDwollaFields(User $user): array
    {
        return $this->getDwollaProfileIssues($user);
    }

    public function normalizePostalCode(mixed $postalCode): ?string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $postalCode));

        if (strlen($digits) === 5) {
            return $digits;
        }

        if (strlen($digits) === 9) {
            return substr($digits, 0, 5);
        }

        return null;
    }

    private function normalizeDateOfBirth(mixed $dateOfBirth): ?string
    {
        try {
            return Carbon::parse($dateOfBirth)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
