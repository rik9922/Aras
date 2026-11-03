<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventBillingParty;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\QuickBooksAccount;
use App\Models\QuickBooksTerm;
use App\Models\Service;
use App\Models\User;
use App\Models\W9Form;
use App\Helper\QuickBooksHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Purchase;

class QuickBooksService
{
    private $dataService;

    private $retryCount = 0;

    private string $lastQbError = '';

    private const MAX_RETRIES = 1;

    /** Return the last QB error message captured during an API call. */
    public function getLastQbError(): string
    {
        return $this->lastQbError;
    }

    private function setLastQbError(string $message): void
    {
        $this->lastQbError = $message;
    }

    private function quickBooksErrorMessage($error, ?\Throwable $exception = null): string
    {
        $parts = [];

        if ($error) {
            foreach (['getIntuitErrorMessage', 'getIntuitErrorDetail', 'getOAuthHelperError'] as $method) {
                if (method_exists($error, $method)) {
                    $value = trim((string) ($error->$method() ?? ''));
                    if ($value !== '') {
                        $parts[] = $value;
                    }
                }
            }

            if (method_exists($error, 'getHttpStatusCode')) {
                $status = trim((string) ($error->getHttpStatusCode() ?? ''));
                if ($status !== '') {
                    $parts[] = 'HTTP '.$status;
                }
            }

            if (method_exists($error, 'getResponseBody')) {
                $body = trim((string) ($error->getResponseBody() ?? ''));
                if ($body !== '') {
                    Log::error('QuickBooks raw error response', ['response_body' => mb_substr($body, 0, 4000)]);

                    $decoded = json_decode($body, true);
                    $faultError = $decoded['Fault']['Error'][0] ?? null;
                    if (is_array($faultError)) {
                        $message = trim((string) ($faultError['Message'] ?? ''));
                        $detail = trim((string) ($faultError['Detail'] ?? ''));
                        if ($message !== '') {
                            $parts[] = $message;
                        }
                        if ($detail !== '') {
                            $parts[] = $detail;
                        }
                    } elseif (preg_match('/<Message>(.*?)<\/Message>/s', $body, $matches)) {
                        $parts[] = html_entity_decode(trim($matches[1]));
                    }
                }
            }
        }

        if ($exception) {
            $exceptionMessage = trim($exception->getMessage());
            if (preg_match('/deserialize ResponseBody/i', $exceptionMessage)) {
                $parts[] = 'QuickBooks returned a response that the SDK could not parse. Check the application log for the raw QuickBooks response body.';
            } elseif ($exceptionMessage !== '') {
                $parts[] = $exceptionMessage;
            }
        }

        $parts = array_values(array_unique(array_filter($parts)));

        return $parts ? implode(' | ', $parts) : 'QuickBooks API request failed.';
    }

    public function __construct()
    {
        $this->initializeDataService();
    }

    private function initializeDataService()
    {
        $config = config('quickbooks');

        $this->dataService = DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => $config['client_id'],
            'ClientSecret' => $config['client_secret'],
            'RedirectURI' => $config['redirect_uri'],
            'scope' => $config['scope'],
            'baseUrl' => $config['base_url'],
        ]);

        $realmId = Cache::get('qb_realm_id');
        if ($realmId) {
            // Note: throwExceptionOnError expects a Boolean type from the SDK, but bool works in practice
            @$this->dataService->throwExceptionOnError(true);
            $this->setAccessToken();
        }
    }

    private function setAccessToken()
    {
        $accessToken = Cache::get('qb_access_token');
        $refreshToken = Cache::get('qb_refresh_token');
        $realmId = Cache::get('qb_realm_id');
        $accessTokenExpiresAt = Cache::get('qb_access_token_expires_at');

        // Check if token is expired or about to expire (within 5 minutes) and refresh if needed
        if ($accessTokenExpiresAt && (now()->greaterThan($accessTokenExpiresAt) || now()->addMinutes(5)->greaterThan($accessTokenExpiresAt))) {
            if (! $this->refreshTokenSynchronously()) {
                Log::warning('Failed to refresh QuickBooks token synchronously');

                return;
            }
            // Reload tokens after refresh
            $accessToken = Cache::get('qb_access_token');
            $refreshToken = Cache::get('qb_refresh_token');
        }

        if ($accessToken && $refreshToken && $realmId) {
            $config = config('quickbooks');
            $accessTokenExpiresAt = Cache::get('qb_access_token_expires_at');
            $accessExpiresIn = $accessTokenExpiresAt ? max(1, $accessTokenExpiresAt->diffInSeconds(now())) : 3600;
            $refreshExpiresIn = 8640000; // 100 days in seconds

            $accessTokenObj = new OAuth2AccessToken(
                $config['client_id'],
                $config['client_secret'],
                $accessToken,
                $refreshToken,
                $accessExpiresIn,
                $refreshExpiresIn
            );
            $accessTokenObj->setRealmID($realmId);
            $this->dataService->updateOAuth2Token($accessTokenObj);
        }
    }

    /**
     * Refresh QuickBooks token synchronously
     *
     * @return bool True if refresh was successful, false otherwise
     */
    private function refreshTokenSynchronously(): bool
    {
        if (! Cache::has('qb_refresh_token')) {
            Log::warning('No QuickBooks refresh token available');

            return false;
        }

        try {
            $config = config('quickbooks');
            $dataService = DataService::Configure([
                'auth_mode' => 'oauth2',
                'ClientID' => $config['client_id'],
                'ClientSecret' => $config['client_secret'],
                'RedirectURI' => $config['redirect_uri'],
                'scope' => $config['scope'],
                'baseUrl' => $config['base_url'],
            ]);

            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            $refreshToken = Cache::get('qb_refresh_token');

            $refreshedAccessToken = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken($refreshToken);

            Cache::put('qb_access_token', $refreshedAccessToken->getAccessToken(), 3600);
            Cache::put('qb_refresh_token', $refreshedAccessToken->getRefreshToken(), now()->addDays(100));
            Cache::put('qb_access_token_expires_at', now()->addSeconds($refreshedAccessToken->getAccessTokenValidationPeriodInSeconds()), 3600);

            Log::info('QuickBooks token refreshed successfully');

            // Reinitialize data service with new token
            $this->initializeDataService();

            return true;
        } catch (\Exception $e) {
            Log::error('QuickBooks token refresh failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Check if an error is related to token expiration
     *
     * @param  mixed  $error  The error object or message
     */
    private function isTokenExpiredError($error): bool
    {
        if (! $error) {
            return false;
        }

        $errorMessage = '';
        if (is_object($error) && method_exists($error, 'getIntuitErrorMessage')) {
            $errorMessage = strtolower($error->getIntuitErrorMessage());
        } elseif (is_string($error)) {
            $errorMessage = strtolower($error);
        }

        $tokenExpiredKeywords = [
            'unauthorized',
            'invalid_grant',
            'token expired',
            'access token',
            'authentication',
            '401',
            'invalid_token',
            'expired_token',
        ];

        foreach ($tokenExpiredKeywords as $keyword) {
            if (strpos($errorMessage, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Preview the next incremental QuickBooks customer Id (max of QB + portal + 1).
     *
     * @return array{success: bool, next_id?: string, message?: string}
     */
    public function previewNextCustomerId(): array
    {
        if (! QuickBooksHelper::isConnected()) {
            return ['success' => false, 'message' => 'QuickBooks is not connected.'];
        }

        try {
            $this->ensureValidToken();

            return [
                'success' => true,
                'next_id' => (string) $this->computeNextCustomerId(),
            ];
        } catch (\Throwable $e) {
            Log::error('QuickBooks preview next customer id failed: '.$e->getMessage());

            return ['success' => false, 'message' => 'Unable to fetch the next QuickBooks customer ID. Please try again.'];
        }
    }

    /**
     * Allocate the next customer Id under lock (use when persisting a new Bill To).
     *
     * @return array{success: bool, next_id?: string, message?: string}
     */
    public function allocateNextCustomerId(): array
    {
        if (! QuickBooksHelper::isConnected()) {
            return ['success' => false, 'message' => 'QuickBooks is not connected.'];
        }

        try {
            $lock = Cache::lock('qb_customer_id_allocate', 15);

            return $lock->block(10, function () {
                $this->ensureValidToken();
                $nextId = (string) $this->computeNextCustomerId();

                if (Client::where('quickbooks_customer_id', $nextId)->exists()) {
                    return [
                        'success' => false,
                        'message' => 'QuickBooks customer ID '.$nextId.' is already in use. Please refresh and try again.',
                    ];
                }

                return ['success' => true, 'next_id' => $nextId];
            });
        } catch (\Throwable $e) {
            Log::error('QuickBooks allocate next customer id failed: '.$e->getMessage());

            return ['success' => false, 'message' => 'Unable to allocate a QuickBooks customer ID. Please try again.'];
        }
    }

    /**
     * Highest numeric customer Id from QuickBooks and portal Bill Tos, plus one.
     */
    protected function computeNextCustomerId(): int
    {
        $max = $this->maxNumericCustomerIdFromPortal();

        $qbMax = $this->maxNumericCustomerIdFromQuickBooks();
        if ($qbMax !== null) {
            $max = max($max, $qbMax);
        }

        return $max + 1;
    }

    protected function maxNumericCustomerIdFromPortal(): int
    {
        $max = 0;

        Client::query()
            ->whereNotNull('quickbooks_customer_id')
            ->pluck('quickbooks_customer_id')
            ->each(function ($id) use (&$max) {
                $id = trim((string) $id);
                if ($id !== '' && ctype_digit($id)) {
                    $max = max($max, (int) $id);
                }
            });

        return $max;
    }

    protected function maxNumericCustomerIdFromQuickBooks(): ?int
    {
        $customers = $this->dataService->Query('SELECT Id FROM Customer MAXRESULTS 1000');

        if ($error = $this->dataService->getLastError()) {
            if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->maxNumericCustomerIdFromQuickBooks();
                }
            }

            throw new \RuntimeException($error->getIntuitErrorMessage());
        }

        $this->retryCount = 0;

        if (! $customers) {
            return null;
        }

        if (! is_array($customers)) {
            $customers = [$customers];
        }

        $max = null;
        foreach ($customers as $customer) {
            $id = isset($customer->Id) ? trim((string) $customer->Id) : '';
            if ($id !== '' && ctype_digit($id)) {
                $numeric = (int) $id;
                $max = $max === null ? $numeric : max($max, $numeric);
            }
        }

        return $max;
    }

    public function createCustomer($clientData)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping customer creation');

            return null;
        }

        // Ensure token is valid before making API call
        $this->ensureValidToken();

        try {
            $customer = new \QuickBooksOnline\API\Data\IPPCustomer;
            $customer->FullyQualifiedName = $clientData['name'];
            $customer->DisplayName = $clientData['name'];
            $customer->CompanyName = $clientData['company_name'] ?? $clientData['name'];

            // Primary Email — maps to users.email
            $primaryEmail = trim((string) ($clientData['email'] ?? ''));
            if ($primaryEmail !== '') {
                $customer->PrimaryEmailAddr = new \QuickBooksOnline\API\Data\IPPEmailAddress;
                $customer->PrimaryEmailAddr->Address = $primaryEmail;
            }

            // CC Emails — sourced from clients.cc_emails (JSON array).
            // The QuickBooks Customer object does NOT support a persistent CC recipients field.
            // These addresses are stored locally on the Client profile and will be used when
            // emailing invoices (QB Invoice payload supports EmailCc on send), not here.
            // We intentionally skip setting anything on the QB Customer for CC emails.

            if (! empty($clientData['phone'])) {
                $customer->PrimaryPhone = new \QuickBooksOnline\API\Data\IPPTelephoneNumber;
                $customer->PrimaryPhone->FreeFormNumber = $clientData['phone'];
            }

            if (! empty($clientData['address'])) {
                $customer->BillAddr = new \QuickBooksOnline\API\Data\IPPPhysicalAddress;
                $customer->BillAddr->Line1 = $clientData['address'];
                $customer->BillAddr->Country = 'USA';
            }

            $resultingCustomer = $this->dataService->Add($customer);

            if ($error = $this->dataService->getLastError()) {
                // Check if error is due to token expiration
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    Log::info('Token expired during API call, refreshing and retrying...');
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        // Retry the operation
                        return $this->createCustomer($clientData);
                    }
                }

                Log::error('QuickBooks customer creation failed: '.$error->getIntuitErrorMessage());
                $this->retryCount = 0; // Reset retry count

                return null;
            }

            $this->retryCount = 0; // Reset retry count on success
            Log::info('QuickBooks customer created successfully', ['customer_id' => $resultingCustomer->Id]);

            return $resultingCustomer->Id;

        } catch (\Exception $e) {
            // Check if exception is due to token expiration
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                Log::info('Token expired during API call (exception), refreshing and retrying...');
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    // Retry the operation
                    return $this->createCustomer($clientData);
                }
            }

            Log::error('QuickBooks customer creation error: '.$e->getMessage());
            $this->retryCount = 0; // Reset retry count

            return null;
        }
    }

    public function updateCustomer(string $customerId, array $clientData): bool
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping customer update', ['customer_id' => $customerId]);

            return false;
        }

        $this->ensureValidToken();

        try {
            $existing = $this->dataService->FindById('Customer', $customerId);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    Log::info('Token expired during customer lookup, refreshing and retrying...');
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->updateCustomer($customerId, $clientData);
                    }
                }

                Log::error('QuickBooks customer lookup failed: '.$error->getIntuitErrorMessage(), ['customer_id' => $customerId]);
                $this->retryCount = 0;

                return false;
            }

            if (! $existing) {
                Log::error('QuickBooks customer update failed: customer not found', ['customer_id' => $customerId]);

                return false;
            }

            $payload = [
                'sparse' => 'true',
                'DisplayName' => $clientData['name'],
                'FullyQualifiedName' => $clientData['name'],
                'CompanyName' => $clientData['company_name'] ?? $clientData['name'],
            ];

            $primaryEmail = trim((string) ($clientData['email'] ?? ''));
            if ($primaryEmail !== '') {
                $payload['PrimaryEmailAddr'] = ['Address' => $primaryEmail];
            }

            if (! empty($clientData['phone'])) {
                $payload['PrimaryPhone'] = ['FreeFormNumber' => $clientData['phone']];
            }

            if (! empty($clientData['address'])) {
                $payload['BillAddr'] = [
                    'Line1' => $clientData['address'],
                    'Country' => 'USA',
                ];
            }

            $customer = Customer::update($existing, $payload);
            $this->dataService->Update($customer);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    Log::info('Token expired during customer update, refreshing and retrying...');
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->updateCustomer($customerId, $clientData);
                    }
                }

                Log::error('QuickBooks customer update failed: '.$error->getIntuitErrorMessage(), ['customer_id' => $customerId]);
                $this->retryCount = 0;

                return false;
            }

            $this->retryCount = 0;
            Log::info('QuickBooks customer updated successfully', ['customer_id' => $customerId]);

            return true;
        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                Log::info('Token expired during customer update (exception), refreshing and retrying...');
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->updateCustomer($customerId, $clientData);
                }
            }

            Log::error('QuickBooks customer update error: '.$e->getMessage(), ['customer_id' => $customerId]);
            $this->retryCount = 0;

            return false;
        }
    }

    /**
     * Fetch all invoices from QuickBooks and sync with local database
     */
    public function syncInvoices()
    {
        $this->setLastQbError('');

        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping invoice sync');
            $this->setLastQbError('QuickBooks is not connected.');

            return false;
        }

        try {
            $this->ensureValidToken();
        } catch (\Exception $e) {
            Log::error('QuickBooks invoice sync token validation failed: '.$e->getMessage());
            $this->setLastQbError('QuickBooks token validation failed: '.$e->getMessage());

            return false;
        }

        try {
            $invoices = $this->fetchInvoicesFromQuickBooks();

            if ($invoices) {
                foreach ($invoices as $qbInvoice) {
                    try {
                        $this->saveInvoiceToDatabase($qbInvoice);
                    } catch (\Throwable $e) {
                        $qbInvoiceId = $qbInvoice->Id ?? 'unknown';
                        $message = "Failed to save QuickBooks invoice {$qbInvoiceId}: ".$e->getMessage();
                        Log::error($message, [
                            'qb_invoice_id' => $qbInvoiceId,
                            'doc_number' => $qbInvoice->DocNumber ?? null,
                        ]);
                        $this->setLastQbError($message);

                        return false;
                    }
                }
            }

            $this->retryCount = 0;
            Log::info('QuickBooks invoices synced successfully');

            return true;

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->syncInvoices();
                }
            }
            Log::error('QuickBooks invoice sync error: '.$e->getMessage());
            $this->setLastQbError('QuickBooks invoice sync error: '.$e->getMessage());

            return false;
        }
    }

    private function fetchInvoicesFromQuickBooks(): array
    {
        $allInvoices = [];
        $startPosition = 1;
        $maxResults = 100;

        do {
            $query = "SELECT * FROM Invoice STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
            $payload = $this->queryQuickBooksJson($query);
            $invoices = $payload['QueryResponse']['Invoice'] ?? [];

            if (isset($invoices['Id'])) {
                $invoices = [$invoices];
            }

            foreach ($invoices as $invoice) {
                $allInvoices[] = json_decode(json_encode($invoice));
            }

            $count = count($invoices);
            $startPosition += $maxResults;
        } while ($count === $maxResults);

        return $allInvoices;
    }

    private function queryQuickBooksJson(string $query): array
    {
        $realmId = Cache::get('qb_realm_id');
        $url = rtrim($this->quickBooksApiBaseUrl(), '/')."/v3/company/{$realmId}/query?minorversion=65";

        $response = Http::withToken(Cache::get('qb_access_token'))
            ->acceptJson()
            ->withBody($query, 'application/text')
            ->timeout(30)
            ->post($url);

        if ($response->unauthorized() && $this->retryCount < self::MAX_RETRIES) {
            $this->retryCount++;
            if ($this->refreshTokenSynchronously()) {
                return $this->queryQuickBooksJson($query);
            }
        }

        if ($response->failed()) {
            $message = $this->quickBooksResponseErrorMessage($response->json(), $response->body());
            throw new \RuntimeException("QuickBooks invoice fetch failed: {$message}");
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new \RuntimeException('QuickBooks returned a non-JSON response while fetching invoices.');
        }

        return $json;
    }

    private function quickBooksApiBaseUrl(): string
    {
        return strcasecmp((string) config('quickbooks.base_url'), 'development') === 0
            ? 'https://sandbox-quickbooks.api.intuit.com'
            : 'https://quickbooks.api.intuit.com';
    }

    private function quickBooksResponseErrorMessage(?array $json, string $body): string
    {
        $error = $json['Fault']['Error'][0] ?? $json['Fault']['Error'] ?? null;
        if (is_array($error)) {
            return trim(implode(' ', array_filter([
                $error['Message'] ?? null,
                $error['Detail'] ?? null,
                isset($error['code']) ? 'Code: '.$error['code'] : null,
            ])));
        }

        return trim($body) !== '' ? trim($body) : 'HTTP request failed.';
    }

    private function createQuickBooksInvoiceViaJson(array $data)
    {
        $realmId = Cache::get('qb_realm_id');
        $url = rtrim($this->quickBooksApiBaseUrl(), '/')."/v3/company/{$realmId}/invoice?minorversion=65";

        $response = Http::withToken(Cache::get('qb_access_token'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($url, $data);

        if ($response->unauthorized() && $this->retryCount < self::MAX_RETRIES) {
            $this->retryCount++;
            if ($this->refreshTokenSynchronously()) {
                return $this->createQuickBooksInvoiceViaJson($data);
            }
        }

        $json = $response->json();

        if ($response->failed()) {
            $this->lastQbError = $this->quickBooksResponseErrorMessage(is_array($json) ? $json : null, $response->body());
            Log::error('QuickBooks invoice JSON creation failed: '.$this->lastQbError, [
                'status' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 4000),
            ]);
            $this->retryCount = 0;

            return null;
        }

        if (! is_array($json) || empty($json['Invoice']['Id'])) {
            $this->lastQbError = 'QuickBooks returned an unexpected invoice creation response.';
            Log::error('QuickBooks invoice JSON creation returned unexpected response', [
                'status' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 4000),
            ]);
            $this->retryCount = 0;

            return null;
        }

        $this->retryCount = 0;

        return json_decode(json_encode($json['Invoice']));
    }

    private function findQuickBooksInvoiceByDocNumber(?string $docNumber)
    {
        $docNumber = trim((string) $docNumber);
        if ($docNumber === '') {
            return null;
        }

        try {
            $escapedDocNumber = str_replace("'", "\\'", $docNumber);
            $payload = $this->queryQuickBooksJson("SELECT * FROM Invoice WHERE DocNumber = '{$escapedDocNumber}' MAXRESULTS 1");
            $invoice = $payload['QueryResponse']['Invoice'][0] ?? $payload['QueryResponse']['Invoice'] ?? null;

            if (is_array($invoice) && ! empty($invoice['Id'])) {
                return json_decode(json_encode($invoice));
            }
        } catch (\Throwable $e) {
            Log::warning('QuickBooks invoice lookup by DocNumber failed before JSON fallback', [
                'doc_number' => $docNumber,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function recoverQuickBooksInvoiceAfterDeserializeFailure(array $data)
    {
        Log::warning('QuickBooks SDK could not deserialize invoice creation response; checking QuickBooks by DocNumber before JSON REST fallback', [
            'doc_number' => $data['DocNumber'] ?? null,
        ]);

        $existingInvoice = $this->findQuickBooksInvoiceByDocNumber($data['DocNumber'] ?? null);
        if ($existingInvoice) {
            return $existingInvoice;
        }

        return $this->createQuickBooksInvoiceViaJson($data);
    }

    /**
     * Save QuickBooks invoice to local database
     */
    private function saveInvoiceToDatabase($qbInvoice)
    {
        $qbCustomerId = $qbInvoice->CustomerRef->value ?? null;
        $qbCustomerName = $qbInvoice->CustomerRef->name ?? null;

        $existing = null;
        if (isset($qbInvoice->Id)) {
            $existing = Invoice::query()->where('qb_invoice_id', $qbInvoice->Id)->first();
        }

        $prevCustomerId = $existing?->customer_id ? (string) $existing->customer_id : null;

        $resolvedCustomerName = null;
        if ($qbCustomerId) {
            $resolvedCustomerName = getUserNameByQBCustomerId($qbCustomerId);
        }
        $resolvedCustomerName = $resolvedCustomerName ?: ($qbCustomerName ?: 'Unknown');

        $lineItems = [];
        if (isset($qbInvoice->Line)) {
            foreach ($qbInvoice->Line as $line) {
                if (isset($line->SalesItemLineDetail)) {
                    $lineItems[] = [
                        'description' => $line->Description ?? '',
                        'quantity' => $line->SalesItemLineDetail->Qty ?? 0,
                        'unit_price' => $line->SalesItemLineDetail->UnitPrice ?? 0,
                        'amount' => $line->Amount ?? 0,
                        'item_ref' => $line->SalesItemLineDetail->ItemRef ?? null,
                    ];
                }
            }
        }

        $billingAddress = null;
        if (isset($qbInvoice->BillAddr)) {
            $billingAddress = [
                'line1' => $qbInvoice->BillAddr->Line1 ?? '',
                'city' => $qbInvoice->BillAddr->City ?? '',
                'country_sub_division_code' => $qbInvoice->BillAddr->CountrySubDivisionCode ?? '',
                'postal_code' => $qbInvoice->BillAddr->PostalCode ?? '',
                'country' => $qbInvoice->BillAddr->Country ?? '',
            ];
        }

        $shippingAddress = null;
        if (isset($qbInvoice->ShipAddr)) {
            $shippingAddress = [
                'line1' => $qbInvoice->ShipAddr->Line1 ?? '',
                'city' => $qbInvoice->ShipAddr->City ?? '',
                'country_sub_division_code' => $qbInvoice->ShipAddr->CountrySubDivisionCode ?? '',
                'postal_code' => $qbInvoice->ShipAddr->PostalCode ?? '',
                'country' => $qbInvoice->ShipAddr->Country ?? '',
            ];
        }

        $invoice = Invoice::updateOrCreate(
            ['qb_invoice_id' => $qbInvoice->Id],
            [
                'source' => \App\Models\Invoice::SOURCE_QUICKBOOKS,
                'doc_number' => $this->resolveSyncedInvoiceDocNumber($qbInvoice, $existing),
                'customer_name' => $resolvedCustomerName,
                'customer_id' => $qbCustomerId,
                'total_amount' => $qbInvoice->TotalAmt ?? 0,
                'balance' => $qbInvoice->Balance ?? 0,
                'due_date' => isset($qbInvoice->DueDate) ? \Carbon\Carbon::parse($qbInvoice->DueDate) : null,
                'invoice_date' => isset($qbInvoice->TxnDate) ? \Carbon\Carbon::parse($qbInvoice->TxnDate) : null,
                'status' => $qbInvoice->TxnStatus ?? 'Draft',
                'currency_code' => $qbInvoice->CurrencyRef->value ?? 'USD',
                'line_items' => $lineItems,
                'billing_address' => $billingAddress,
                'shipping_address' => $shippingAddress,
                'email_status' => $qbInvoice->EmailStatus ?? null,
                'print_status' => $qbInvoice->PrintStatus ?? null,
                'private_note' => $qbInvoice->PrivateNote ?? null,
                'customer_memo' => $qbInvoice->CustomerMemo->value ?? null,
                'sync_token' => $qbInvoice->SyncToken ?? null,
                'created_time' => isset($qbInvoice->MetaData->CreateTime) ? \Carbon\Carbon::parse($qbInvoice->MetaData->CreateTime) : null,
                'last_updated_time' => isset($qbInvoice->MetaData->LastUpdatedTime) ? \Carbon\Carbon::parse($qbInvoice->MetaData->LastUpdatedTime) : null,
            ]
        );

        // If the Bill To (CustomerRef) changed in QB, reflect it back into the portal event
        // only when we can do so safely (single billing party).
        $newCustomerId = $qbCustomerId ? (string) $qbCustomerId : null;
        if ($prevCustomerId !== null && $newCustomerId !== null && $prevCustomerId !== $newCustomerId) {
            $this->maybeSyncBillToChangeToPortalEvent($invoice, $prevCustomerId, $newCustomerId);
        }

        return $invoice->id;
    }

    private function resolveSyncedInvoiceDocNumber($qbInvoice, ?Invoice $existing): string
    {
        $qbInvoiceId = trim((string) ($qbInvoice->Id ?? ''));
        $docNumber = trim((string) ($qbInvoice->DocNumber ?? ''));

        if ($docNumber === '') {
            return 'QB-' . ($qbInvoiceId !== '' ? $qbInvoiceId : uniqid('invoice-', false));
        }

        $conflict = Invoice::query()
            ->where('doc_number', $docNumber)
            ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
            ->exists();

        if (! $conflict) {
            return $docNumber;
        }

        return $qbInvoiceId !== ''
            ? "{$docNumber}-QB-{$qbInvoiceId}"
            : $docNumber . '-QB-' . uniqid('', false);
    }

    /**
     * Update portal event Bill To based on QB invoice customer change.
     * Safety rules:
     * - Only applies when invoice is linked to an event.
     * - Only updates events that have a single Bill To (legacy client_id or exactly one billing party).
     * - Skips multi-party events to avoid corrupting split billing configuration.
     */
    private function maybeSyncBillToChangeToPortalEvent(Invoice $invoice, string $oldQbCustomerId, string $newQbCustomerId): void
    {
        if (! $invoice->event_id) {
            return;
        }

        $event = Event::query()->with('billingParties')->find($invoice->event_id);
        if (! $event) {
            return;
        }

        $clientProfile = Client::query()->where('quickbooks_customer_id', $newQbCustomerId)->with('user')->first();
        $newClient = $clientProfile?->user;
        if (! $newClient) {
            Log::warning('QB invoice customer changed but no portal client matches new QB customer id', [
                'qb_invoice_id' => $invoice->qb_invoice_id,
                'event_id' => $invoice->event_id,
                'old_qb_customer_id' => $oldQbCustomerId,
                'new_qb_customer_id' => $newQbCustomerId,
            ]);
            return;
        }

        $parties = $event->billingParties;
        if ($parties && $parties->count() > 1) {
            Log::info('QB invoice customer changed; skipping portal Bill To update for multi-party event', [
                'qb_invoice_id' => $invoice->qb_invoice_id,
                'event_id' => $event->id,
                'billing_party_count' => $parties->count(),
            ]);
            return;
        }

        if ($parties && $parties->count() === 1) {
            $party = $parties->first();
            $party->client_id = $newClient->id;
            $party->is_primary = true;
            $party->save();

            $event->client_id = $newClient->id;
            $event->save();

            Log::info('Updated portal event Bill To from QB invoice change (single billing party)', [
                'qb_invoice_id' => $invoice->qb_invoice_id,
                'event_id' => $event->id,
                'new_client_id' => $newClient->id,
            ]);
            return;
        }

        // Legacy: no billing parties stored; use client_id.
        $event->client_id = $newClient->id;
        $event->save();

        // Also ensure there isn't a stale billing party row.
        EventBillingParty::query()->where('event_id', $event->id)->delete();

        Log::info('Updated portal event Bill To from QB invoice change (legacy client_id)', [
            'qb_invoice_id' => $invoice->qb_invoice_id,
            'event_id' => $event->id,
            'new_client_id' => $newClient->id,
        ]);
    }

    /**
     * Save QuickBooks expense (Purchase) to local database
     */
    private function saveExpenseToDatabase($qbExpense, array $relations = []): int
    {

        $expense = Expense::updateOrCreate(
            ['qb_expense_id' => $qbExpense->Id],
            array_merge([
                'vendor_name' => $this->quickBooksReferenceName($qbExpense->EntityRef ?? null),
                'vendor_id' => $this->quickBooksReferenceValue($qbExpense->EntityRef ?? null),
                'txn_date' => isset($qbExpense->TxnDate) ? \Carbon\Carbon::parse($qbExpense->TxnDate) : null,
                'total_amount' => $qbExpense->TotalAmt ?? 0,
                'currency_code' => $qbExpense->CurrencyRef->value ?? 'USD',
                'payment_type' => $this->resolveExpensePaymentType($qbExpense),
                'private_note' => $qbExpense->PrivateNote ?? null,
                'description' => $this->expenseLineDescription($qbExpense),
                'created_time' => isset($qbExpense->MetaData->CreateTime) ? \Carbon\Carbon::parse($qbExpense->MetaData->CreateTime) : null,
                'last_updated_time' => isset($qbExpense->MetaData->LastUpdatedTime) ? \Carbon\Carbon::parse($qbExpense->MetaData->LastUpdatedTime) : null,
            ], $relations)
        );

        return $expense->id;
    }

    public function syncExpenseByQuickBooksId(string $quickBooksExpenseId): array
    {
        $quickBooksExpenseId = trim($quickBooksExpenseId);

        if ($quickBooksExpenseId === '') {
            return ['success' => false, 'message' => 'Missing QuickBooks expense ID.', 'synced' => 0];
        }

        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping single expense sync', [
                'qb_expense_id' => $quickBooksExpenseId,
            ]);

            return ['success' => false, 'message' => 'QuickBooks not connected', 'synced' => 0];
        }

        try {
            $this->ensureValidToken();

            $qbExpense = $this->dataService->FindById('Purchase', $quickBooksExpenseId);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->syncExpenseByQuickBooksId($quickBooksExpenseId);
                    }
                }

                $message = $this->quickBooksErrorMessage($error);
                Log::error('QuickBooks single expense sync failed: '.$message, [
                    'qb_expense_id' => $quickBooksExpenseId,
                ]);
                $this->retryCount = 0;

                return ['success' => false, 'message' => $message, 'synced' => 0];
            }

            if (! $qbExpense || empty($qbExpense->Id)) {
                return ['success' => false, 'message' => 'QuickBooks expense not found.', 'synced' => 0];
            }

            $expenseId = $this->saveExpenseToDatabase($qbExpense);
            $this->retryCount = 0;

            Log::info('QuickBooks expense synced from webhook', [
                'qb_expense_id' => $quickBooksExpenseId,
                'expense_id' => $expenseId,
            ]);

            return ['success' => true, 'message' => 'Expense synced from QuickBooks.', 'synced' => 1, 'expense_id' => $expenseId];
        } catch (\Throwable $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->syncExpenseByQuickBooksId($quickBooksExpenseId);
                }
            }

            Log::error('QuickBooks single expense sync exception: '.$e->getMessage(), [
                'qb_expense_id' => $quickBooksExpenseId,
            ]);
            $this->retryCount = 0;

            return ['success' => false, 'message' => $e->getMessage(), 'synced' => 0];
        }
    }

    public function deleteLocalExpenseByQuickBooksId(string $quickBooksExpenseId): array
    {
        $quickBooksExpenseId = trim($quickBooksExpenseId);

        if ($quickBooksExpenseId === '') {
            return ['success' => false, 'message' => 'Missing QuickBooks expense ID.', 'synced' => 0];
        }

        $deleted = Expense::where('qb_expense_id', $quickBooksExpenseId)->delete();

        Log::info('QuickBooks expense delete synced from webhook', [
            'qb_expense_id' => $quickBooksExpenseId,
            'deleted' => $deleted,
        ]);

        return ['success' => true, 'message' => 'Expense delete synced from QuickBooks.', 'synced' => $deleted];
    }

    public function syncInvoiceByQuickBooksId(string $quickBooksInvoiceId): array
    {
        $qbInvoice = $this->findQuickBooksEntityById('Invoice', $quickBooksInvoiceId);
        if (! $qbInvoice || empty($qbInvoice->Id)) {
            return ['success' => false, 'message' => 'QuickBooks invoice not found.', 'synced' => 0];
        }

        $invoiceId = $this->saveInvoiceToDatabase($qbInvoice);

        return ['success' => true, 'message' => 'Invoice synced from QuickBooks.', 'synced' => 1, 'invoice_id' => $invoiceId];
    }

    public function markLocalInvoiceDeletedByQuickBooksId(string $quickBooksInvoiceId): array
    {
        $updated = Invoice::where('qb_invoice_id', trim($quickBooksInvoiceId))->update([
            'status' => 'Deleted',
            'balance' => 0,
            'last_updated_time' => now(),
        ]);

        return ['success' => true, 'message' => 'Invoice delete synced from QuickBooks.', 'synced' => $updated];
    }

    public function syncCustomerByQuickBooksId(string $quickBooksCustomerId): array
    {
        $qbCustomer = $this->findQuickBooksEntityById('Customer', $quickBooksCustomerId);
        if (! $qbCustomer || empty($qbCustomer->Id)) {
            return ['success' => false, 'message' => 'QuickBooks customer not found.', 'synced' => 0];
        }

        $client = Client::where('quickbooks_customer_id', (string) $qbCustomer->Id)->with('user')->first();
        if (! $client) {
            $email = trim((string) ($qbCustomer->PrimaryEmailAddr->Address ?? ''));
            if ($email !== '') {
                $client = Client::whereHas('user', fn ($query) => $query->whereRaw('LOWER(email) = ?', [mb_strtolower($email)]))
                    ->with('user')
                    ->first();
            }
        }
        if (! $client) {
            return ['success' => true, 'status' => 'ignored', 'message' => 'QuickBooks customer is not linked to a portal client.', 'synced' => 0];
        }

        $billAddr = $qbCustomer->BillAddr ?? null;
        $client->update([
            'company_name' => $qbCustomer->CompanyName ?? $client->company_name,
            'address' => $billAddr->Line1 ?? $client->address,
            'city' => $billAddr->City ?? $client->city,
            'state' => $billAddr->CountrySubDivisionCode ?? $client->state,
            'zip_code' => $billAddr->PostalCode ?? $client->zip_code,
            'country' => $billAddr->Country ?? $client->country,
            'quickbooks_customer_id' => (string) $qbCustomer->Id,
            'qb_customer_sync_status' => 'synced',
            'qb_customer_synced_at' => now(),
            'qb_customer_last_sync_error' => null,
        ]);

        if ($client->user) {
            $client->user->update(array_filter([
                'name' => $qbCustomer->DisplayName ?? null,
                'email' => $qbCustomer->PrimaryEmailAddr->Address ?? null,
                'phone' => $qbCustomer->PrimaryPhone->FreeFormNumber ?? null,
            ], fn ($value) => $value !== null && $value !== ''));
        }

        return ['success' => true, 'message' => 'Customer synced from QuickBooks.', 'synced' => 1, 'client_id' => $client->id];
    }

    public function unlinkLocalCustomerByQuickBooksId(string $quickBooksCustomerId): array
    {
        $updated = Client::where('quickbooks_customer_id', trim($quickBooksCustomerId))->update([
            'quickbooks_customer_id' => null,
            'qb_customer_sync_status' => 'failed',
            'qb_customer_last_sync_error' => 'Customer was deleted in QuickBooks.',
        ]);

        return ['success' => true, 'message' => 'Customer delete synced from QuickBooks.', 'synced' => $updated];
    }

    public function syncVendorByQuickBooksId(string $quickBooksVendorId): array
    {
        $qbVendor = $this->findQuickBooksEntityById('Vendor', $quickBooksVendorId);
        if (! $qbVendor || empty($qbVendor->Id)) {
            return ['success' => false, 'message' => 'QuickBooks vendor not found.', 'synced' => 0];
        }

        $vendorId = (string) $qbVendor->Id;
        $email = trim((string) ($qbVendor->PrimaryEmailAddr->Address ?? ''));
        $w9Form = W9Form::where('quickbooks_vendor_id', $vendorId)->first();
        $user = $w9Form?->user;

        if (! $user && Schema::hasColumn('users', 'quickbooks_vendor_id')) {
            $user = User::where('quickbooks_vendor_id', $vendorId)->first();
        }

        if (! $user && $email !== '') {
            $user = User::where('role_id', 5)->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
            $w9Form = $user?->w9Form;
        }

        if (! $user && ! $w9Form) {
            return ['success' => true, 'status' => 'ignored', 'message' => 'QuickBooks vendor is not linked to a portal W-9 or ambassador.', 'synced' => 0];
        }

        if ($w9Form) {
            $w9Form->update([
                'quickbooks_vendor_id' => $vendorId,
                'is_1099_vendor' => isset($qbVendor->Vendor1099) ? (bool) $qbVendor->Vendor1099 : $w9Form->is_1099_vendor,
            ]);
        }

        if ($user && Schema::hasColumn('users', 'quickbooks_vendor_id')) {
            $updates = ['quickbooks_vendor_id' => $vendorId];
            if (Schema::hasColumn('users', 'w9_submitted')) {
                $updates['w9_submitted'] = true;
            }
            $user->update($updates);
        }

        return ['success' => true, 'message' => 'Vendor/W-9 linkage synced from QuickBooks.', 'synced' => 1];
    }

    public function unlinkLocalVendorByQuickBooksId(string $quickBooksVendorId): array
    {
        $quickBooksVendorId = trim($quickBooksVendorId);
        $synced = W9Form::where('quickbooks_vendor_id', $quickBooksVendorId)->update([
            'quickbooks_vendor_id' => null,
            'is_1099_vendor' => false,
        ]);

        if (Schema::hasColumn('users', 'quickbooks_vendor_id')) {
            $synced += User::where('quickbooks_vendor_id', $quickBooksVendorId)->update(['quickbooks_vendor_id' => null]);
        }

        return ['success' => true, 'message' => 'Vendor delete synced from QuickBooks.', 'synced' => $synced];
    }

    public function syncAccountByQuickBooksId(string $quickBooksAccountId): array
    {
        $qbAccount = $this->findQuickBooksEntityById('Account', $quickBooksAccountId);
        if (! $qbAccount || empty($qbAccount->Id)) {
            return ['success' => false, 'message' => 'QuickBooks account not found.', 'synced' => 0];
        }

        $this->saveAccountToDatabase($qbAccount);

        return ['success' => true, 'message' => 'Account synced from QuickBooks.', 'synced' => 1];
    }

    public function deactivateLocalAccountByQuickBooksId(string $quickBooksAccountId): array
    {
        $quickBooksAccountId = trim($quickBooksAccountId);
        $synced = QuickBooksAccount::where('qb_account_id', $quickBooksAccountId)->update(['is_active' => false]);
        $synced += PaymentMethod::where('quickbooks_account_id', $quickBooksAccountId)->update(['active' => false]);
        ExpenseCategory::where('quickbooks_account_id', $quickBooksAccountId)->update(['quickbooks_account_id' => null]);

        return ['success' => true, 'message' => 'Account delete synced from QuickBooks.', 'synced' => $synced];
    }

    public function syncServiceItemByQuickBooksId(string $quickBooksItemId): array
    {
        $qbItem = $this->findQuickBooksEntityById('Item', $quickBooksItemId);
        if (! $qbItem || empty($qbItem->Id)) {
            return ['success' => false, 'message' => 'QuickBooks item not found.', 'synced' => 0];
        }

        if (isset($qbItem->Type) && strcasecmp((string) $qbItem->Type, 'Service') !== 0) {
            return ['success' => true, 'status' => 'ignored', 'message' => 'QuickBooks item is not a Service item.', 'synced' => 0];
        }

        $name = $this->normalizeServiceName((string) ($qbItem->Name ?? 'QuickBooks Service '.$qbItem->Id));
        $service = Service::where('qb_item_id', (string) $qbItem->Id)->first()
            ?: Service::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?: new Service();

        $service->fill([
            'qb_item_id' => (string) $qbItem->Id,
            'name' => $name,
            'description' => $qbItem->Description ?? null,
            'rate' => $qbItem->UnitPrice ?? null,
            'taxable' => isset($qbItem->Taxable) ? (bool) $qbItem->Taxable : false,
            'sync_token' => $qbItem->SyncToken ?? null,
            'is_active' => isset($qbItem->Active) ? (bool) $qbItem->Active : true,
        ])->save();

        return ['success' => true, 'message' => 'Service item synced from QuickBooks.', 'synced' => 1];
    }

    public function deactivateLocalServiceByQuickBooksItemId(string $quickBooksItemId): array
    {
        $updated = Service::where('qb_item_id', trim($quickBooksItemId))->update(['is_active' => false]);

        return ['success' => true, 'message' => 'Service item delete synced from QuickBooks.', 'synced' => $updated];
    }

    public function syncTermByQuickBooksId(string $quickBooksTermId): array
    {
        $qbTerm = $this->findQuickBooksEntityById('Term', $quickBooksTermId);
        if (! $qbTerm || empty($qbTerm->Id)) {
            return ['success' => false, 'message' => 'QuickBooks term not found.', 'synced' => 0];
        }

        QuickBooksTerm::updateOrCreate(
            ['qb_term_id' => (string) $qbTerm->Id],
            [
                'name' => $qbTerm->Name ?? '',
                'sync_token' => $qbTerm->SyncToken ?? null,
                'is_active' => isset($qbTerm->Active) ? (bool) $qbTerm->Active : true,
            ]
        );

        return ['success' => true, 'message' => 'Term synced from QuickBooks.', 'synced' => 1];
    }

    public function deactivateLocalTermByQuickBooksId(string $quickBooksTermId): array
    {
        $updated = QuickBooksTerm::where('qb_term_id', trim($quickBooksTermId))->update(['is_active' => false]);

        return ['success' => true, 'message' => 'Term delete synced from QuickBooks.', 'synced' => $updated];
    }

    public function syncPaymentMethodByQuickBooksId(string $quickBooksPaymentMethodId): array
    {
        $qbPaymentMethod = $this->findQuickBooksEntityById('PaymentMethod', $quickBooksPaymentMethodId);
        if (! $qbPaymentMethod || empty($qbPaymentMethod->Id)) {
            return ['success' => false, 'message' => 'QuickBooks payment method not found.', 'synced' => 0];
        }

        PaymentMethod::updateOrCreate(
            ['quickbooks_payment_method_id' => (string) $qbPaymentMethod->Id],
            [
                'name' => $qbPaymentMethod->Name ?? 'QuickBooks Payment Method '.$qbPaymentMethod->Id,
                'code' => $this->paymentMethodCode($qbPaymentMethod->Name ?? $qbPaymentMethod->Id),
                'active' => isset($qbPaymentMethod->Active) ? (bool) $qbPaymentMethod->Active : true,
            ]
        );

        return ['success' => true, 'message' => 'Payment method synced from QuickBooks.', 'synced' => 1];
    }

    public function deactivateLocalPaymentMethodByQuickBooksId(string $quickBooksPaymentMethodId): array
    {
        $updated = PaymentMethod::where('quickbooks_payment_method_id', trim($quickBooksPaymentMethodId))->update(['active' => false]);

        return ['success' => true, 'message' => 'Payment method delete synced from QuickBooks.', 'synced' => $updated];
    }

    public function syncInvoicesLinkedToPaymentByQuickBooksId(string $quickBooksPaymentId): array
    {
        $qbPayment = $this->findQuickBooksEntityById('Payment', $quickBooksPaymentId);
        if (! $qbPayment || empty($qbPayment->Id)) {
            return ['success' => false, 'message' => 'QuickBooks payment not found.', 'synced' => 0];
        }

        $invoiceIds = $this->linkedInvoiceIdsFromPayment($qbPayment);
        if (empty($invoiceIds)) {
            return ['success' => true, 'status' => 'ignored', 'message' => 'QuickBooks payment has no linked invoices.', 'synced' => 0];
        }

        $synced = 0;
        foreach ($invoiceIds as $invoiceId) {
            $result = $this->syncInvoiceByQuickBooksId($invoiceId);
            if ($result['success'] ?? false) {
                $synced++;
            }
        }

        return ['success' => true, 'message' => "Synced {$synced} invoice(s) linked to QuickBooks payment.", 'synced' => $synced];
    }

    private function findQuickBooksEntityById(string $entityName, string $entityId)
    {
        $entityId = trim($entityId);
        if ($entityId === '') {
            throw new \InvalidArgumentException("Missing QuickBooks {$entityName} ID.");
        }

        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            throw new \RuntimeException('QuickBooks not connected.');
        }

        $this->ensureValidToken();

        try {
            $entity = $this->dataService->FindById($entityName, $entityId);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->findQuickBooksEntityById($entityName, $entityId);
                    }
                }

                $message = $this->quickBooksErrorMessage($error);
                $this->retryCount = 0;
                throw new \RuntimeException($message);
            }

            $this->retryCount = 0;

            return $entity;
        } catch (\Throwable $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->findQuickBooksEntityById($entityName, $entityId);
                }
            }

            $this->retryCount = 0;
            throw $e;
        }
    }

    private function saveAccountToDatabase($account): void
    {
        QuickBooksAccount::updateOrCreate(
            ['qb_account_id' => (string) $account->Id],
            [
                'name' => $account->Name ?? '',
                'account_type' => $account->AccountType ?? null,
                'account_sub_type' => $account->AccountSubType ?? null,
                'is_active' => isset($account->Active) ? (bool) $account->Active : true,
                'sync_token' => $account->SyncToken ?? null,
                'currency_ref' => $account->CurrencyRef->value ?? null,
            ]
        );

        $accountType = (string) ($account->AccountType ?? '');
        if (in_array(strtolower($accountType), ['bank', 'credit card', 'creditcard'], true)) {
            PaymentMethod::updateOrCreate(
                ['quickbooks_account_id' => (string) $account->Id],
                [
                    'name' => $account->Name ?? 'QuickBooks Account '.$account->Id,
                    'account_type' => $accountType,
                    'detail_type' => $account->AccountSubType ?? null,
                    'opening_balance' => isset($account->CurrentBalance) ? (float) $account->CurrentBalance : null,
                    'as_of_date' => now()->toDateString(),
                    'code' => $this->mapQbAccountTypeToCode($accountType, $account),
                    'active' => isset($account->Active) ? (bool) $account->Active : true,
                ]
            );
        }

        if (strcasecmp($accountType, 'Expense') === 0) {
            ExpenseCategory::updateOrCreate(
                ['quickbooks_account_id' => (string) $account->Id],
                [
                    'name' => $account->Name ?? 'QuickBooks Expense Account '.$account->Id,
                    'description' => 'Synced from QuickBooks Chart of Accounts.',
                    'detail_type' => $account->AccountSubType ?? null,
                ]
            );
        }
    }

    private function paymentMethodCode($name): string
    {
        $code = preg_replace('/[^a-z0-9]+/', '_', mb_strtolower((string) $name));
        $code = trim((string) $code, '_');

        return $code !== '' ? $code : 'quickbooks_payment_method';
    }

    private function linkedInvoiceIdsFromPayment($qbPayment): array
    {
        $lines = $qbPayment->Line ?? [];
        if (! is_array($lines)) {
            $lines = [$lines];
        }

        $invoiceIds = [];
        foreach ($lines as $line) {
            $linkedTxns = $line->LinkedTxn ?? [];
            if (! is_array($linkedTxns)) {
                $linkedTxns = [$linkedTxns];
            }

            foreach ($linkedTxns as $linkedTxn) {
                $type = strtolower((string) ($linkedTxn->TxnType ?? ''));
                $id = trim((string) ($linkedTxn->TxnId ?? ''));

                if ($type === 'invoice' && $id !== '') {
                    $invoiceIds[] = $id;
                }
            }
        }

        return array_values(array_unique($invoiceIds));
    }

    private function expenseLineDescription($qbExpense): ?string
    {
        $lines = $qbExpense->Line ?? null;
        if (! $lines) {
            return null;
        }

        if (! is_array($lines)) {
            $lines = [$lines];
        }

        foreach ($lines as $line) {
            $description = trim((string) ($line->Description ?? ''));
            if ($description !== '') {
                return $description;
            }
        }

        return null;
    }

    private function resolveExpensePaymentType($qbExpense): string
    {
        $paymentType = trim((string) ($qbExpense->PaymentType ?? ''));
        $paymentMethodName = $this->quickBooksReferenceName($qbExpense->PaymentMethodRef ?? null);
        $paymentMethodType = $this->paymentMethodNameToPaymentType($paymentMethodName);

        if ($paymentMethodType) {
            return $paymentMethodType;
        }

        $paymentMethodId = $this->quickBooksReferenceValue($qbExpense->PaymentMethodRef ?? null);
        if ($paymentMethodId) {
            $paymentMethod = PaymentMethod::where('quickbooks_payment_method_id', (string) $paymentMethodId)->first();

            if ($paymentMethod) {
                return $paymentMethod->quickbooks_payment_type;
            }
        }

        $accountRef = $this->quickBooksReferenceValue($qbExpense->AccountRef ?? null);

        if ($accountRef) {
            $account = QuickBooksAccount::where('qb_account_id', (string) $accountRef)->first();

            if ($account) {
                $accountType = strtolower((string) $account->account_type);

                if ($accountType === 'credit card' || $accountType === 'creditcard') {
                    return 'CreditCard';
                }

                if ($accountType === 'bank' && ($paymentType === '' || strtolower($paymentType) === 'cash')) {
                    return 'Check';
                }
            }
        }

        return $paymentType !== '' ? $paymentType : '--';
    }

    private function quickBooksReferenceValue($reference): ?string
    {
        if (is_object($reference)) {
            return isset($reference->value) ? (string) $reference->value : null;
        }

        if (is_array($reference)) {
            return isset($reference['value']) ? (string) $reference['value'] : null;
        }

        if (is_scalar($reference)) {
            $value = trim((string) $reference);

            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function quickBooksReferenceName($reference): ?string
    {
        if (is_object($reference)) {
            return isset($reference->name) ? trim((string) $reference->name) : null;
        }

        if (is_array($reference)) {
            return isset($reference['name']) ? trim((string) $reference['name']) : null;
        }

        return null;
    }

    private function paymentMethodNameToPaymentType(?string $name): ?string
    {
        $normalized = strtolower(trim((string) $name));

        return match ($normalized) {
            'check', 'cheque' => 'Check',
            'credit card', 'creditcard', 'card' => 'CreditCard',
            'cash' => 'Cash',
            default => null,
        };
    }

    /**
     * Ensure an Expense-type Account exists in QuickBooks for a given category name and optional detail type,
     * and return its ID. This does not use the global cached ID; it is specific per name.
     */
    public function ensureNamedExpenseAccount(string $name, ?string $detailType = null): ?string
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks ensureNamedExpenseAccount skipped - not connected');

            return null;
        }

        $this->ensureValidToken();

        return $this->findOrCreateExpenseAccountByName($name, $detailType);
    }

    /**
     * Sync all QuickBooks expenses (Purchases) into local database.
     */
    public function syncExpenses(): array
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping expense sync');

            return ['success' => false, 'message' => 'QuickBooks not connected', 'synced' => 0];
        }

        $this->ensureValidToken();

        try {
            $query = 'SELECT * FROM Purchase MAXRESULTS 1000';
            $expenses = $this->dataService->Query($query);

            if ($error = $this->dataService->getLastError()) {
                Log::error('QuickBooks expense sync failed: '.$error->getIntuitErrorMessage());

                return ['success' => false, 'message' => $error->getIntuitErrorMessage(), 'synced' => 0];
            }

            $count = 0;

            if ($expenses) {
                foreach ($expenses as $qbExpense) {
                    // Only sync cash/expense-style purchases; skip non-standard rows defensively
                    if (! isset($qbExpense->Id)) {
                        continue;
                    }

                    $this->saveExpenseToDatabase($qbExpense);
                    $count++;
                }
            }

            Log::info('QuickBooks expenses synced', ['synced' => $count]);

            return ['success' => true, 'message' => "Synced {$count} expense(s) from QuickBooks.", 'synced' => $count];
        } catch (\Exception $e) {
            Log::error('QuickBooks expense sync exception: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage(), 'synced' => 0];
        }
    }

    /**
     * Ensure the token is valid before making API calls
     */
    private function ensureValidToken(): void
    {
        $accessTokenExpiresAt = Cache::get('qb_access_token_expires_at');

        // Refresh if expired or expiring within 5 minutes
        if ($accessTokenExpiresAt && (now()->greaterThan($accessTokenExpiresAt) || now()->addMinutes(5)->greaterThan($accessTokenExpiresAt))) {
            $this->refreshTokenSynchronously();
        }
    }

    public function createQuickBooksInvoice($data)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping invoice creation');

            return null;
        }

        $this->ensureValidToken();

        try {
            $invoice = new \QuickBooksOnline\API\Data\IPPInvoice;

            if (isset($data['DocNumber'])) {
                $invoice->DocNumber = $data['DocNumber'];
            }
            if (isset($data['TxnDate'])) {
                $invoice->TxnDate = $data['TxnDate'];
            }
            if (isset($data['DueDate'])) {
                $invoice->DueDate = $data['DueDate'];
            }
            if (isset($data['CustomerRef'])) {
                $invoice->CustomerRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
                $invoice->CustomerRef->value = $data['CustomerRef']['value'];
            }
            if (isset($data['BillEmail'])) {
                $invoice->BillEmail = new \QuickBooksOnline\API\Data\IPPEmailAddress;
                $invoice->BillEmail->Address = $data['BillEmail']['Address'];
            }
            // CC recipients (clients.cc_emails) — set on Invoice, not on Customer.
            // QuickBooks Customer objects do not support persistent CC email fields.
            // BillEmailCc is supported on Invoice and will be visible when the
            // Admin opens or reviews the invoice inside QuickBooks.
            if (isset($data['BillEmailCc'])) {
                $invoice->BillEmailCc = new \QuickBooksOnline\API\Data\IPPEmailAddress;
                $invoice->BillEmailCc->Address = $data['BillEmailCc']['Address'];
            }
            if (isset($data['BillAddr'])) {
                $invoice->BillAddr = new \QuickBooksOnline\API\Data\IPPPhysicalAddress;
                foreach ($data['BillAddr'] as $key => $value) {
                    if ($value) {
                        $invoice->BillAddr->$key = $value;
                    }
                }
            }
            if (isset($data['ShipAddr'])) {
                $invoice->ShipAddr = new \QuickBooksOnline\API\Data\IPPPhysicalAddress;
                foreach ($data['ShipAddr'] as $key => $value) {
                    if ($value) {
                        $invoice->ShipAddr->$key = $value;
                    }
                }
            }
            if (isset($data['PrivateNote'])) {
                $invoice->PrivateNote = $data['PrivateNote'];
            }
            if (isset($data['CustomerMemo'])) {
                $invoice->CustomerMemo = new \QuickBooksOnline\API\Data\IPPMemoRef;
                $invoice->CustomerMemo->value = $data['CustomerMemo']['value'] ?? $data['CustomerMemo'];
            }
            if (isset($data['CurrencyRef'])) {
                $invoice->CurrencyRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
                $invoice->CurrencyRef->value = $data['CurrencyRef']['value'];
            }
            if (isset($data['SalesTermRef'])) {
                $invoice->SalesTermRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
                $invoice->SalesTermRef->value = $data['SalesTermRef']['value'];
            }

            // Handle Line items
            $lines = [];
            if (isset($data['Line'])) {
                foreach ($data['Line'] as $lineData) {
                    $line = new \QuickBooksOnline\API\Data\IPPLine;
                    if (isset($lineData['Amount'])) {
                        $line->Amount = $lineData['Amount'];
                    }
                    if (isset($lineData['DetailType'])) {
                        $line->DetailType = $lineData['DetailType'];
                    }
                    if (isset($lineData['Description'])) {
                        $line->Description = $lineData['Description'];
                    }

                    if ($line->DetailType === 'SalesItemLineDetail' && isset($lineData['SalesItemLineDetail'])) {
                        $detail = new \QuickBooksOnline\API\Data\IPPSalesItemLineDetail;
                        if (isset($lineData['SalesItemLineDetail']['ItemRef'])) {
                            $itemRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
                            $itemRef->value = $lineData['SalesItemLineDetail']['ItemRef']['value'];
                            if (isset($lineData['SalesItemLineDetail']['ItemRef']['name'])) {
                                $itemRef->name = $lineData['SalesItemLineDetail']['ItemRef']['name'];
                            }
                            $detail->ItemRef = $itemRef;
                        }
                        if (isset($lineData['SalesItemLineDetail']['Qty'])) {
                            $detail->Qty = $lineData['SalesItemLineDetail']['Qty'];
                        }
                        if (isset($lineData['SalesItemLineDetail']['UnitPrice'])) {
                            $detail->UnitPrice = $lineData['SalesItemLineDetail']['UnitPrice'];
                        }
                        if (isset($lineData['SalesItemLineDetail']['TaxCodeRef'])) {
                            $taxCodeRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
                            $taxCodeRef->value = $lineData['SalesItemLineDetail']['TaxCodeRef']['value'];
                            $detail->TaxCodeRef = $taxCodeRef;
                        }
                        $line->SalesItemLineDetail = $detail;
                    } elseif ($line->DetailType === 'DiscountLineDetail' && isset($lineData['DiscountLineDetail'])) {
                        $detail = new \QuickBooksOnline\API\Data\IPPDiscountLineDetail;
                        if (isset($lineData['DiscountLineDetail']['PercentBased'])) {
                            $detail->PercentBased = $lineData['DiscountLineDetail']['PercentBased'];
                        }
                        if (isset($lineData['DiscountLineDetail']['DiscountPercent'])) {
                            $detail->DiscountPercent = $lineData['DiscountLineDetail']['DiscountPercent'];
                        }
                        if (isset($lineData['DiscountLineDetail']['DiscountAccountRef'])) {
                            $discountAccountRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
                            $discountAccountRef->value = $lineData['DiscountLineDetail']['DiscountAccountRef']['value'] ?? null;
                            $detail->DiscountAccountRef = $discountAccountRef;
                        }
                        $line->DiscountLineDetail = $detail;
                    }

                    $lines[] = $line;
                }
            }
            $invoice->Line = $lines;

            // Handle TxnTaxDetail
            if (isset($data['TxnTaxDetail'])) {
                $txnTax = new \QuickBooksOnline\API\Data\IPPTxnTaxDetail;
                if (isset($data['TxnTaxDetail']['TotalTax'])) {
                    $txnTax->TotalTax = $data['TxnTaxDetail']['TotalTax'];
                }

                $taxLines = [];
                if (isset($data['TxnTaxDetail']['TaxLine'])) {
                    foreach ($data['TxnTaxDetail']['TaxLine'] as $taxLineData) {
                        $taxLine = new \QuickBooksOnline\API\Data\IPPTaxLine;
                        if (isset($taxLineData['Amount'])) {
                            $taxLine->Amount = $taxLineData['Amount'];
                        }
                        if (isset($taxLineData['DetailType'])) {
                            $taxLine->DetailType = $taxLineData['DetailType'];
                        }

                        if (isset($taxLineData['TaxLineDetail'])) {
                            $taxDetail = new \QuickBooksOnline\API\Data\IPPTaxLineDetail;
                            if (isset($taxLineData['TaxLineDetail']['TaxRateRef'])) {
                                $taxRateRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
                                $taxRateRef->value = $taxLineData['TaxLineDetail']['TaxRateRef']['value'];
                                $taxDetail->TaxRateRef = $taxRateRef;
                            }
                            if (isset($taxLineData['TaxLineDetail']['PercentBased'])) {
                                $taxDetail->PercentBased = $taxLineData['TaxLineDetail']['PercentBased'];
                            }
                            if (isset($taxLineData['TaxLineDetail']['TaxPercent'])) {
                                $taxDetail->TaxPercent = $taxLineData['TaxLineDetail']['TaxPercent'];
                            }
                            if (isset($taxLineData['TaxLineDetail']['NetAmountTaxable'])) {
                                $taxDetail->NetAmountTaxable = $taxLineData['TaxLineDetail']['NetAmountTaxable'];
                            }
                            $taxLine->TaxLineDetail = $taxDetail;
                        }

                        $taxLines[] = $taxLine;
                    }
                }
                $txnTax->TaxLine = $taxLines;
                $invoice->TxnTaxDetail = $txnTax;
            }

            $resultingInvoice = $this->dataService->Add($invoice);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    Log::info('Token expired during API call, refreshing and retrying...');
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->createQuickBooksInvoice($data);
                    }
                }

                $this->lastQbError = $this->quickBooksErrorMessage($error);
                Log::error('QuickBooks invoice creation failed: '.$this->lastQbError);
                $this->retryCount = 0;

                return null;
            }

            $this->retryCount = 0;

            if (! $resultingInvoice || $resultingInvoice instanceof \Throwable || empty($resultingInvoice->Id)) {
                if ($resultingInvoice instanceof \Throwable && preg_match('/deserialize ResponseBody/i', $resultingInvoice->getMessage())) {
                    return $this->recoverQuickBooksInvoiceAfterDeserializeFailure($data);
                }

                $this->lastQbError = $this->quickBooksErrorMessage(
                    $this->dataService->getLastError(),
                    $resultingInvoice instanceof \Throwable ? $resultingInvoice : null
                );
                Log::error('QuickBooks invoice creation failed: '.$this->lastQbError);

                return null;
            }

            Log::info('QuickBooks invoice created successfully', ['invoice_id' => $resultingInvoice->Id]);

            return $resultingInvoice;

        } catch (\Throwable $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                Log::info('Token expired during API call (exception), refreshing and retrying...');
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->createQuickBooksInvoice($data);
                }
            }

            if (preg_match('/deserialize ResponseBody/i', $e->getMessage())) {
                return $this->recoverQuickBooksInvoiceAfterDeserializeFailure($data);
            }

            $this->lastQbError = $this->quickBooksErrorMessage($this->dataService->getLastError(), $e);
            Log::error('QuickBooks invoice creation error: '.$this->lastQbError);
            $this->retryCount = 0;

            return null;
        }
    }

    /**
     * Create one QuickBooks invoice for a single billing party with Labor, Buyback, Cups line items.
     * Used when transferring events with multiple billing parties (labor and buyback split evenly; cups to primary or split).
     *
     * @param  \App\Models\User  $client  The billing party (client)
     * @param  array{ labor: float, buyback: float, cups: float }  $amounts  Line amounts for this party
     * @param  array{ invoice_date: string, due_date: string, doc_number?: string, private_note?: string }  $options
     * @return \QuickBooksOnline\API\Data\IPPInvoice|null
     */
    public function createInvoiceForEventBillingParty($client, array $amounts, array $options = [])
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping billing party invoice');

            return null;
        }

        $qbCustomerId = $client->clientProfile?->quickbooks_customer_id ?? null;
        if (! $qbCustomerId) {
            Log::warning('Client missing QuickBooks customer ID', ['client_id' => $client->id]);

            return null;
        }

        $this->ensureValidToken();

        $defaultItemId = config('quickbooks.default_item_id', '1');
        $lines = [];

        foreach ([
            'labor'        => 'Labour Charge',
            'buyback'      => 'Buyback',
            'cups'         => 'Cup Charge',
            'parking_fee'  => 'Parking Fee',
            'expedite_fee' => 'Expedite Fee',
            'shipping_fee' => 'Shipping/Postage Fee',
            'drive_time'   => 'Drive-time Pay',
        ] as $key => $label) {
            $amt = (float) ($amounts[$key] ?? 0);
            if ($amt <= 0) {
                continue;
            }
            $lines[] = [
                'Amount' => $amt,
                'DetailType' => 'SalesItemLineDetail',
                'Description' => $label,
                'SalesItemLineDetail' => [
                    'ItemRef' => ['value' => $defaultItemId],
                    'Qty' => 1,
                    'UnitPrice' => $amt,
                ],
            ];
        }

        if (empty($lines)) {
            Log::info('No positive amounts for billing party invoice, skipping', ['client_id' => $client->id]);

            return null;
        }

        $invoiceData = [
            'TxnDate' => $options['invoice_date'] ?? date('Y-m-d'),
            'DueDate' => $options['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'CustomerRef' => ['value' => $qbCustomerId],
            'Line' => $lines,
            'PrivateNote' => $options['private_note'] ?? '',
            'CurrencyRef' => ['value' => $options['currency_code'] ?? 'USD'],
        ];

        if (! empty($options['doc_number'])) {
            $invoiceData['DocNumber'] = $options['doc_number'];
        }

        return $this->createQuickBooksInvoice($invoiceData);
    }

    public function createAttachable($attachableData)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping attachable creation');

            return null;
        }

        $this->ensureValidToken();

        try {
            $attachable = new \QuickBooksOnline\API\Data\IPPAttachable;

            if (isset($attachableData['FileName'])) {
                $attachable->FileName = $attachableData['FileName'];
            }
            if (isset($attachableData['ContentType'])) {
                $attachable->ContentType = $attachableData['ContentType'];
            }
            if (isset($attachableData['Content'])) {
                $attachable->Content = $attachableData['Content'];
            }

            if (isset($attachableData['AttachableRef'])) {
                $refs = [];
                foreach ($attachableData['AttachableRef'] as $refData) {
                    $ref = new \QuickBooksOnline\API\Data\IPPAttachableRef;
                    if (isset($refData['EntityRef'])) {
                        $entityRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
                        $entityRef->type = $refData['EntityRef']['type'];
                        $entityRef->value = $refData['EntityRef']['value'];
                        $ref->EntityRef = $entityRef;
                    }
                    $refs[] = $ref;
                }
                $attachable->AttachableRef = $refs;
            }

            $resultingAttachable = $this->dataService->Add($attachable);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    Log::info('Token expired during API call, refreshing and retrying...');
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->createAttachable($attachableData);
                    }
                }

                Log::error('QuickBooks attachable creation failed: '.$error->getIntuitErrorMessage());
                $this->retryCount = 0;

                return null;
            }

            $this->retryCount = 0;
            Log::info('QuickBooks attachable created successfully', ['attachable_id' => $resultingAttachable->Id]);

            return $resultingAttachable;

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                Log::info('Token expired during API call (exception), refreshing and retrying...');
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->createAttachable($attachableData);
                }
            }

            Log::error('QuickBooks attachable creation error: '.$e->getMessage());
            $this->retryCount = 0;

            return null;
        }
    }

    public function sendInvoice($invoiceId)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping invoice send');

            return false;
        }

        $this->ensureValidToken();

        try {
            $invoice = $this->dataService->FindById('Invoice', $invoiceId);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    Log::info('Token expired during API call, refreshing and retrying...');
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->sendInvoice($invoiceId);
                    }
                }

                Log::error('QuickBooks invoice fetch for send failed: '.$error->getIntuitErrorMessage());
                $this->retryCount = 0;

                return false;
            }

            $result = $this->dataService->sendEmail($invoice);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    Log::info('Token expired during API call, refreshing and retrying...');
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->sendInvoice($invoiceId);
                    }
                }

                Log::error('QuickBooks invoice send failed: '.$error->getIntuitErrorMessage());
                $this->retryCount = 0;

                return false;
            }

            $this->retryCount = 0;
            Log::info('QuickBooks invoice sent successfully', ['invoice_id' => $invoiceId]);

            return true;

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                Log::info('Token expired during API call (exception), refreshing and retrying...');
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->sendInvoice($invoiceId);
                }
            }

            Log::error('QuickBooks invoice send error: '.$e->getMessage());
            $this->retryCount = 0;

            return false;
        }
    }

    /**
     * Create invoice for ambassador payout
     */
    public function createInvoiceForPayout($payout)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping invoice creation');

            return null;
        }

        $this->ensureValidToken();

        try {
            $ambassador = $payout->user;

            if (! $ambassador->quickbooks_customer_id) {
                $customerId = $this->createCustomer([
                    'name' => $ambassador->name,
                    'company_name' => $ambassador->name,
                    'email' => $ambassador->email,
                    'phone' => $ambassador->phone,
                    'address' => $ambassador->address,
                ]);

                if ($customerId) {
                    $ambassador->update(['quickbooks_customer_id' => $customerId]);
                } else {
                    Log::error('Failed to create QuickBooks customer for ambassador: '.$ambassador->id);

                    return null;
                }
            }

            $invoice = new \QuickBooksOnline\API\Data\IPPInvoice;

            $invoice->CustomerRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
            $invoice->CustomerRef->value = $ambassador->quickbooks_customer_id;

            $invoice->TxnDate = date('Y-m-d');
            $invoice->DueDate = date('Y-m-d', strtotime('+30 days'));

            $line = new \QuickBooksOnline\API\Data\IPPLine;
            $line->Amount = $payout->amount;
            $line->DetailType = 'SalesItemLineDetail';

            $salesItemLineDetail = new \QuickBooksOnline\API\Data\IPPSalesItemLineDetail;
            $salesItemLineDetail->Qty = 1;
            $salesItemLineDetail->UnitPrice = $payout->amount;

            $itemRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
            $itemRef->value = '1';
            $salesItemLineDetail->ItemRef = $itemRef;

            $line->SalesItemLineDetail = $salesItemLineDetail;
            $line->Description = 'Ambassador Payout - Event: '.($payout->event->event_name ?? 'N/A').' - Ref: '.$payout->reference;

            $invoice->Line = [$line];
            $invoice->PrivateNote = 'Payout Reference: '.$payout->reference;

            $resultingInvoice = $this->dataService->Add($invoice);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->createInvoiceForPayout($payout);
                    }
                }
                Log::error('QuickBooks invoice creation failed: '.$error->getIntuitErrorMessage());
                $this->retryCount = 0;

                return null;
            }

            $this->retryCount = 0;
            $invoiceId = $this->saveInvoiceToDatabase($resultingInvoice);
            Log::info('QuickBooks invoice created for payout', ['payout_id' => $payout->id, 'invoice_id' => $invoiceId]);

            return $invoiceId;

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->createInvoiceForPayout($payout);
                }
            }
            Log::error('QuickBooks invoice creation error: '.$e->getMessage());
            $this->retryCount = 0;

            return null;
        }
    }

    public function createExpenseForPayout($payout)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping expense creation');

            return null;
        }

        $this->ensureValidToken();

        try {
            $defaultPaymentMethod = PaymentMethod::getDefault();
            $bankAccount = null;
            $bankAccountId = $defaultPaymentMethod?->getQuickBooksAccountIdForExpense();
            $paymentType = $defaultPaymentMethod?->quickbooks_payment_type ?? null;

            if (! $bankAccountId) {
                $bankAccount = QuickBooksAccount::getBankAccount();
                $bankAccountId = $bankAccount?->qb_account_id;
                $paymentType = ($bankAccount && strtolower($bankAccount->account_type ?? '') === 'credit card') ? 'CreditCard' : 'Check';
            }

            if (! $bankAccountId) {
                Log::error('[QB Expense] No payment account configured. Sync payment methods from QuickBooks and choose a default account with a QuickBooks account id.');

                return null;
            }

            $defaultCategory = ExpenseCategory::where('is_default', true)->first();
            $expenseAccountId = $defaultCategory?->quickbooks_account_id ?: $this->ensureExpenseAccountExists();
            if (! $expenseAccountId) {
                Log::error('QuickBooks expense account missing and could not be created');

                return null;
            }

            // quickbooks_vendor_id lives on w9_forms, not on users.
            // Load the ambassador with their W9 so we can read the correct vendor ID.
            $ambassador = $payout->user->load('w9Form');
            $w9 = $ambassador->w9Form;

            // Resolve vendor ID: prefer W9 record, fall back to creating a new vendor.
            $vendorId = $w9?->quickbooks_vendor_id ?? null;

            if (! $vendorId) {
                Log::info('[QB Expense] Ambassador has no vendor ID on W9 — creating vendor', [
                    'user_id'    => $ambassador->id,
                    'has_w9'     => (bool) $w9,
                ]);

                // Build vendor payload — include tax_id from W9 for 1099 tracking
                $vendorPayload = [
                    'name'    => trim(($ambassador->last_name ?? '').', '.($ambassador->first_name ?? $ambassador->name)),
                    'email'   => $ambassador->email,
                    'phone'   => $ambassador->phone ?? '',
                ];

                if ($w9) {
                    $vendorPayload['tax_id']   = $w9->ssn_ein ?? null;
                    $vendorPayload['address']  = $w9->address ?? '';
                    $vendorPayload['city']     = $w9->city ?? '';
                    $vendorPayload['state']    = $w9->state ?? '';
                    $vendorPayload['zip_code'] = $w9->zip_code ?? '';
                }

                $vendorId = $this->createVendor($vendorPayload);

                if ($vendorId) {
                    // Save back to w9_forms (canonical location)
                    if ($w9) {
                        $w9->update(['quickbooks_vendor_id' => $vendorId]);
                    }
                    Log::info('[QB Expense] Vendor created and saved to W9', [
                        'user_id'   => $ambassador->id,
                        'vendor_id' => $vendorId,
                    ]);
                } else {
                    Log::error('[QB Expense] Failed to create QuickBooks vendor for ambassador payout', [
                        'user_id' => $ambassador->id,
                        'payout_id' => $payout->id,
                    ]);

                    return null;
                }
            }

            Log::info('[QB Expense] Using vendor for payout expense', [
                'payout_id' => $payout->id,
                'user_id'   => $ambassador->id,
                'vendor_id' => $vendorId,
            ]);

            $event = $payout->event;

            // Format: "January 2025 Event Pay - Event ID 12345"
            $dateForMonth = $event->booked_date ?? $event->start_time ?? now();
            $description = date('F Y', strtotime($dateForMonth)).' Event Pay - Event ID '.$event->id;

            // Use payout completed date if available; otherwise fall back to "today"
            $txnDate = $payout->completed_at
                ? $payout->completed_at->format('Y-m-d')
                : date('Y-m-d');

            $expenseData = [
                'TxnDate' => $txnDate,
                // Payment side: must be a Bank or Credit Card account
                'AccountRef' => [
                    'value' => (string) $bankAccountId,
                ],
                // How it was paid – derived from the bank account type in quickbooks_accounts
                'PaymentType' => $paymentType,
                'PrivateNote' => $description,
                'EntityRef' => [
                    'value' => $vendorId,
                    'type'  => 'Vendor',
                ],
                'Line' => [
                    [
                        'Amount' => $payout->amount,
                        'DetailType' => 'AccountBasedExpenseLineDetail',
                        'Description' => $description,
                        'AccountBasedExpenseLineDetail' => [
                            // Expense side: your expense account (e.g. Ambassador Payouts)
                            'AccountRef' => [
                                'value' => $expenseAccountId,
                            ],
                        ],
                    ],
                ],
            ];

            $expense = Purchase::create($expenseData);

            Log::info('[QB Expense] Sending Purchase to QuickBooks', [
                'payout_id'        => $payout->id,
                'vendor_id'        => $vendorId,
                'bank_account_id'  => $bankAccountId,
                'expense_account'  => $expenseAccountId,
                'amount'           => $payout->amount,
                'txn_date'         => $txnDate,
            ]);

            $result = $this->dataService->Add($expense);

            if ($error = $this->dataService->getLastError()) {
                $errMsg = $this->quickBooksErrorMessage($error);
                Log::error('[QB Expense] QuickBooks payout expense failed', [
                    'payout_id'       => $payout->id,
                    'bank_account_id' => $bankAccountId,
                    'expense_account' => $expenseAccountId,
                    'error'           => $errMsg,
                ]);

                if ($this->isInvalidQuickBooksAccountReference($errMsg) && $this->retryCount < self::MAX_RETRIES) {
                    $this->refreshQuickBooksAccountsAfterInvalidReference($bankAccountId, $expenseAccountId, $this->invalidQuickBooksAccountId($errMsg));
                    $this->retryCount++;

                    return $this->createExpenseForPayout($payout);
                }

                $this->retryCount = 0;

                return null;
            }

            $this->saveExpenseToDatabase($result, [
                'user_id' => $ambassador->id,
                'payout_id' => $payout->id,
            ]);

            Log::info('QuickBooks payout expense created', [
                'payout_id' => $payout->id,
            ]);

            return $result->Id;
        } catch (\Exception $e) {
            if ($this->isInvalidQuickBooksAccountReference($e->getMessage()) && $this->retryCount < self::MAX_RETRIES) {
                $this->refreshQuickBooksAccountsAfterInvalidReference(
                    $bankAccountId ?? null,
                    $expenseAccountId ?? null,
                    $this->invalidQuickBooksAccountId($e->getMessage())
                );
                $this->retryCount++;

                return $this->createExpenseForPayout($payout);
            }

            Log::error('QuickBooks payout expense error: '.$e->getMessage());
            $this->retryCount = 0;

            return null;
        }
    }

    private function isInvalidQuickBooksAccountReference(string $message): bool
    {
        return str_contains($message, 'Invalid Reference Id')
            && str_contains($message, 'Accounts element id')
            && str_contains($message, 'not found');
    }

    private function invalidQuickBooksAccountId(string $message): ?string
    {
        if (preg_match('/Accounts element id\s+([^\s<]+)/', $message, $matches)) {
            return (string) $matches[1];
        }

        return null;
    }

    private function refreshQuickBooksAccountsAfterInvalidReference(?string $bankAccountId, ?string $expenseAccountId, ?string $invalidAccountId): void
    {
        Log::warning('[QB Expense] Refreshing QuickBooks account references after invalid account id', [
            'bank_account_id' => $bankAccountId,
            'expense_account_id' => $expenseAccountId,
            'invalid_account_id' => $invalidAccountId,
        ]);

        Cache::forget('qb_expense_account_id');
        $this->syncChartOfAccounts();

        if ($bankAccountId && (! $invalidAccountId || (string) $bankAccountId === (string) $invalidAccountId)) {
            QuickBooksAccount::where('qb_account_id', (string) $bankAccountId)
                ->update(['is_active' => false]);
        }

        if ($expenseAccountId && (! $invalidAccountId || (string) $expenseAccountId === (string) $invalidAccountId)) {
            $isActiveExpenseAccount = QuickBooksAccount::where('qb_account_id', (string) $expenseAccountId)
                ->where('is_active', true)
                ->exists();

            if (! $isActiveExpenseAccount) {
                ExpenseCategory::where('quickbooks_account_id', (string) $expenseAccountId)
                    ->update(['quickbooks_account_id' => null]);
            }
        }
    }

    /**
     * Create QuickBooks expense for reimbursement.
     * Format: "January 2025 Reimbursement - Event ID 12345" (excluded from 1099)
     */
    public function createExpenseForReimbursement(\App\Models\Reimbursement $reimbursement)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping reimbursement expense');

            return null;
        }

        $this->ensureValidToken();

        try {
            $defaultPaymentMethod = PaymentMethod::getDefault();
            $bankAccountId = $defaultPaymentMethod?->getQuickBooksAccountIdForExpense();
            if (! $bankAccountId) {
                Log::error('QuickBooks reimbursement expense failed: no payment account. Sync payment methods from QuickBooks and choose a default account with a QuickBooks account id.');

                return null;
            }

            $defaultCategory = ExpenseCategory::where('is_default', true)->first();
            $expenseAccountId = $defaultCategory?->quickbooks_account_id ?: $this->ensureExpenseAccountExists();
            if (! $expenseAccountId) {
                Log::error('QuickBooks expense account missing and could not be created');

                return null;
            }

            $user = $reimbursement->user;

            if (! $user->quickbooks_vendor_id) {
                $vendorId = $this->createVendor([
                    'name' => trim($user->last_name.', '.$user->first_name),
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                ]);

                if ($vendorId) {
                    $user->update(['quickbooks_vendor_id' => $vendorId]);
                } else {
                    Log::error('Failed to create QuickBooks vendor for reimbursement: '.$user->id);

                    return null;
                }
            }

            $description = $reimbursement->description_for_quickbooks;

            // Use paid date if available; otherwise fall back to expense date
            $txnDate = $reimbursement->paid_at
                ? $reimbursement->paid_at->format('Y-m-d')
                : $reimbursement->expense_date->format('Y-m-d');

            $expenseData = [
                'TxnDate' => $txnDate,
                // Payment side: must be a Bank or Credit Card account
                'AccountRef' => [
                    'value' => (string) $bankAccountId,
                ],
            // How it was paid – use system payment method (default: Check)
            'PaymentType' => PaymentMethod::getDefault()?->quickbooks_payment_type ?? 'Check',
                'PrivateNote' => $description,
                'EntityRef' => [
                    'value' => $user->quickbooks_vendor_id,
                    'type' => 'Vendor',
                ],
                'Line' => [
                    [
                        'Amount' => $reimbursement->amount,
                        'DetailType' => 'AccountBasedExpenseLineDetail',
                        'Description' => $description,
                        'AccountBasedExpenseLineDetail' => [
                            // Expense side: your expense account (e.g. Cost of Labor 1150040006)
                            'AccountRef' => [
                                'value' => $expenseAccountId,
                            ],
                        ],
                    ],
                ],
            ];

            $expense = Purchase::create($expenseData);

            $result = $this->dataService->Add($expense);

            if ($error = $this->dataService->getLastError()) {
                Log::error('QuickBooks reimbursement expense failed: '.$error->getIntuitErrorMessage());

                return null;
            }

            $this->saveExpenseToDatabase($result, [
                'user_id' => $user->id,
                'reimbursement_id' => $reimbursement->id,
            ]);

            Log::info('QuickBooks reimbursement expense created', ['reimbursement_id' => $reimbursement->id]);

            return $result->Id;
        } catch (\Exception $e) {
            Log::error('QuickBooks reimbursement expense error: '.$e->getMessage());

            return null;
        }
    }

    public function createQuickBooksService($data)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected');

            return null;
        }

        $this->ensureValidToken();

        try {
            $name = $this->normalizeServiceName((string) ($data['name'] ?? ''));
            $data['name'] = $name;

            // Check if service already exists
            $existingItem = $this->findServiceByName($name);
            if ($existingItem) {
                Log::info('Service already exists in QuickBooks, using existing', [
                    'id' => $existingItem->Id,
                    'name' => $name,
                ]);

                return $existingItem;
            }

            $item = new \QuickBooksOnline\API\Data\IPPItem;
            $item->Name = $name;
            $item->Type = 'Service';
            $item->Description = $data['description'] ?? '';

            $item->IncomeAccountRef = new \QuickBooksOnline\API\Data\IPPReferenceType;
            $item->IncomeAccountRef->value = $this->getIncomeAccountId() ?? '1';

            $item->UnitPrice = $data['rate'] ?? 0;
            $item->Taxable = (bool) $data['taxable'];

            $resultingItem = $this->dataService->Add($item);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->createQuickBooksService($data);
                    }
                }
                Log::error('QuickBooks service creation failed: '.$error->getIntuitErrorMessage());
                $this->retryCount = 0;

                return null;
            }

            $this->retryCount = 0;
            Log::info('QuickBooks service created successfully', [
                'name' => $name,
                'id' => $resultingItem->Id ?? null,
            ]);

            return $resultingItem;

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->createQuickBooksService($data);
                }
            }
            Log::error('QuickBooks service creation error: '.$e->getMessage());
            $this->retryCount = 0;

            return null;
        }
    }

    private function findServiceByName($name)
    {
        try {
            $name = $this->normalizeServiceName((string) $name);
            $query = "SELECT * FROM Item WHERE Name = '".addslashes($name)."' AND Type = 'Service'";
            $items = $this->dataService->Query($query);

            if ($error = $this->dataService->getLastError()) {
                return null;
            }

            return $items ? $items[0] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeServiceName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return $name;
    }

    public function updateQuickBooksService($qbItemId, $syncToken, $data)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected');

            return null;
        }

        $this->ensureValidToken();

        try {
            $data['name'] = $this->normalizeServiceName((string) ($data['name'] ?? ''));

            $item = $this->dataService->FindById('Item', $qbItemId);

            if ($error = $this->dataService->getLastError()) {
                Log::error('QuickBooks service fetch failed: '.$error->getIntuitErrorMessage());

                return null;
            }

            $item->Name = $data['name'];
            $item->Description = $data['description'] ?? '';
            $item->UnitPrice = $data['rate'] ?? 0;
            $item->Taxable = (bool) $data['taxable'];

            $resultingItem = $this->dataService->Update($item);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->updateQuickBooksService($qbItemId, $syncToken, $data);
                    }
                }
                Log::error('QuickBooks service update failed: '.$error->getIntuitErrorMessage());
                $this->retryCount = 0;

                return null;
            }

            $this->retryCount = 0;

            return $resultingItem;

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->updateQuickBooksService($qbItemId, $syncToken, $data);
                }
            }
            Log::error('QuickBooks service update error: '.$e->getMessage());
            $this->retryCount = 0;

            return null;
        }
    }

    public function getCustomerTransactions($customerId)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected');

            return [];
        }

        $this->ensureValidToken();

        try {
            $query = "SELECT * FROM Invoice WHERE CustomerRef = '{$customerId}' ORDERBY TxnDate DESC";
            $invoices = $this->dataService->Query($query);

            if ($error = $this->dataService->getLastError()) {
                Log::error('QuickBooks transaction fetch failed: '.$error->getIntuitErrorMessage());

                return [];
            }

            return $invoices ?? [];

        } catch (\Exception $e) {
            Log::error('QuickBooks transaction fetch error: '.$e->getMessage());

            return [];
        }
    }

    public function createVendor($vendorData)
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks not connected, skipping vendor creation');

            return null;
        }

        $this->ensureValidToken();

        try {
            $vendor = new \QuickBooksOnline\API\Data\IPPVendor;
            $vendor->DisplayName = $vendorData['name'];
            $vendor->CompanyName = $vendorData['name'];

            // Email
            if (! empty($vendorData['email'])) {
                $vendor->PrimaryEmailAddr = new \QuickBooksOnline\API\Data\IPPEmailAddress;
                $vendor->PrimaryEmailAddr->Address = $vendorData['email'];
            }

            // Phone
            if (! empty($vendorData['phone'])) {
                $vendor->PrimaryPhone = new \QuickBooksOnline\API\Data\IPPTelephoneNumber;
                $vendor->PrimaryPhone->FreeFormNumber = $vendorData['phone'];
            }

            // Address
            if (! empty($vendorData['address'])) {
                $vendor->BillAddr = new \QuickBooksOnline\API\Data\IPPPhysicalAddress;
                $vendor->BillAddr->Line1 = $vendorData['address'];
                $vendor->BillAddr->City = $vendorData['city'] ?? '';
                $vendor->BillAddr->CountrySubDivisionCode = $vendorData['state'] ?? '';
                $vendor->BillAddr->PostalCode = $vendorData['zip_code'] ?? '';
                $vendor->BillAddr->Country = 'USA';
            }

            // ✅ TAX ID - required so QuickBooks considers W-9 info present
            if (! empty($vendorData['tax_id'])) {
                // Validate QuickBooks format
                if (! preg_match('/^\d{9}$|^\d{2}-\d{7}$/', $vendorData['tax_id'])) {
                    Log::error('❌ Invalid Tax ID format for QuickBooks: '.$vendorData['tax_id']);

                    return null;
                }
                $vendor->TaxIdentifier = $vendorData['tax_id'];
                Log::info('✅ TaxIdentifier set: '.$vendorData['tax_id']);
            } else {
                Log::warning('❌ No tax_id provided');
            }

            // ✅ Mark vendor as 1099 contractor so they appear under Contractors in QuickBooks
            $vendor->Vendor1099 = true;

            Log::info('Vendor object before API call', [
                'DisplayName' => $vendor->DisplayName,
                'TaxIdentifier' => $vendor->TaxIdentifier ?? 'NOT SET',
            ]);

            $resultingVendor = $this->dataService->Add($vendor);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    Log::info('Token expired, retrying...');
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->createVendor($vendorData);
                    }
                }
                Log::error('vendor creation failed: '.$error->getIntuitErrorMessage());
                $this->retryCount = 0;

                return null;
            }

            // ✅ Verify creation & Tax ID (expect masked: XXXXX4465)
            $fetchedVendor = $this->dataService->FindById('Vendor', $resultingVendor->Id);
            Log::info('vendor CREATED SUCCESSFULLY', [
                'vendor_id' => $resultingVendor->Id,
                'TaxIdentifier_saved' => $fetchedVendor->TaxIdentifier ?? 'NOT SAVED',
                'masked_tax_id_note' => 'XXXXX = NORMAL (security masking)',
            ]);

            $this->retryCount = 0;

            return $resultingVendor->Id;

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->createVendor($vendorData);
                }
            }
            Log::error('vendor creation exception: '.$e->getMessage());
            $this->retryCount = 0;

            return null;
        }
    }

    /**
     * Sync existing QuickBooks vendors to portal users to avoid duplicates on launch.
     * Matches by DisplayName (e.g. "Lastname, Firstname") or PrimaryEmailAddr.
     */
    // ─────────────────────────────────────────────────────────────────────────
    // Chart of Accounts — Dynamic Account Management
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch the complete Chart of Accounts from QuickBooks and store locally.
     * Upserts by qb_account_id — no duplicates, updates existing records.
     *
     * @return array{success: bool, message: string, synced: int}
     */
    public function syncChartOfAccounts(): array
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            return ['success' => false, 'message' => 'QuickBooks is not connected.', 'synced' => 0];
        }

        $this->ensureValidToken();

        try {
            $accounts = $this->dataService->Query('SELECT * FROM Account MAXRESULTS 1000');

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->syncChartOfAccounts();
                    }
                }
                $this->retryCount = 0;
                return ['success' => false, 'message' => $error->getIntuitErrorMessage(), 'synced' => 0];
            }

            if (! $accounts) {
                return ['success' => true, 'message' => 'No accounts found in QuickBooks.', 'synced' => 0];
            }

            if (! is_array($accounts)) {
                $accounts = [$accounts];
            }

            $count = 0;
            $syncedAccountIds = [];
            foreach ($accounts as $account) {
                if (! isset($account->Id)) {
                    continue;
                }

                $syncedAccountIds[] = (string) $account->Id;

                QuickBooksAccount::updateOrCreate(
                    ['qb_account_id' => (string) $account->Id],
                    [
                        'name'             => $account->Name ?? '',
                        'account_type'     => $account->AccountType ?? null,
                        'account_sub_type' => $account->AccountSubType ?? null,
                        'is_active'        => isset($account->Active) ? (bool) $account->Active : true,
                        'sync_token'       => $account->SyncToken ?? null,
                        'currency_ref'     => $account->CurrencyRef->value ?? null,
                    ]
                );
                $count++;
            }

            QuickBooksAccount::whereNotIn('qb_account_id', $syncedAccountIds)->update(['is_active' => false]);

            $this->retryCount = 0;
            Log::info('QuickBooks Chart of Accounts synced', ['count' => $count]);

            return ['success' => true, 'message' => "Synced {$count} account(s) from QuickBooks Chart of Accounts.", 'synced' => $count];

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->syncChartOfAccounts();
                }
            }
            $this->retryCount = 0;
            Log::error('QuickBooks Chart of Accounts sync error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'synced' => 0];
        }
    }

    /**
     * Fetch all Terms from QuickBooks and store/update locally.
     * No hardcoded IDs — syncs by qb_term_id so IDs stay current after
     * Sandbox resets, production company switches, or QB reconnects.
     *
     * @return array{success: bool, message: string, synced: int}
     */
    public function syncTerms(): array
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            return ['success' => false, 'message' => 'QuickBooks is not connected.', 'synced' => 0];
        }

        $this->ensureValidToken();

        try {
            $terms = $this->dataService->Query('SELECT * FROM Term MAXRESULTS 1000');

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->syncTerms();
                    }
                }
                $this->retryCount = 0;
                return ['success' => false, 'message' => $error->getIntuitErrorMessage(), 'synced' => 0];
            }

            if (! $terms) {
                return ['success' => true, 'message' => 'No terms found in QuickBooks.', 'synced' => 0];
            }

            if (! is_array($terms)) {
                $terms = [$terms];
            }

            $count = 0;
            foreach ($terms as $term) {
                if (! isset($term->Id)) {
                    continue;
                }

                QuickBooksTerm::updateOrCreate(
                    ['qb_term_id' => (string) $term->Id],
                    [
                        'name'       => $term->Name ?? '',
                        'sync_token' => $term->SyncToken ?? null,
                        'is_active'  => isset($term->Active) ? (bool) $term->Active : true,
                    ]
                );
                $count++;
            }

            $this->retryCount = 0;
            Log::info('QuickBooks Terms synced', ['count' => $count]);

            return ['success' => true, 'message' => "Synced {$count} term(s) from QuickBooks.", 'synced' => $count];

        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->syncTerms();
                }
            }
            $this->retryCount = 0;
            Log::error('QuickBooks Terms sync error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'synced' => 0];
        }
    }

    /**
     * Return the locally synced Income account ID for use in QB API calls.
     * Falls back to config value if no accounts have been synced yet.
     */
    public function getIncomeAccountId(): ?string
    {
        $account = QuickBooksAccount::getIncomeAccount();
        if ($account) {
            return $account->qb_account_id;
        }

        // Backward-compatible fallback
        $fallback = config('quickbooks.income_account_id');
        return $fallback ? (string) $fallback : null;
    }

    /**
     * Return the locally synced Expense account ID for use in QB API calls.
     * Falls back to config value if no accounts have been synced yet.
     */
    public function getExpenseAccountId(): ?string
    {
        $account = QuickBooksAccount::getExpenseAccount();
        if ($account) {
            return $account->qb_account_id;
        }

        // Backward-compatible fallback
        $fallback = config('quickbooks.expense_account_id');
        return $fallback ? (string) $fallback : null;
    }

    /**
     * Return the locally synced Bank account ID for use in QB API calls.
     */
    public function getBankAccountId(): ?string
    {
        $account = QuickBooksAccount::getBankAccount();
        if ($account) {
            return $account->qb_account_id;
        }

        return null;
    }

    public function syncVendorsFromQuickBooks(): array
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            return ['success' => false, 'message' => 'QuickBooks not connected', 'matched' => 0];
        }

        $this->ensureValidToken();

        try {
            $query = 'SELECT * FROM Vendor';
            $vendors = $this->dataService->Query($query);

            if ($error = $this->dataService->getLastError()) {
                return ['success' => false, 'message' => $error->getIntuitErrorMessage(), 'matched' => 0];
            }

            $matched = 0;
            $ambassadors = \App\Models\User::where('role_id', 5)->get();

            foreach ($vendors as $vendor) {
                $displayName = $vendor->DisplayName ?? '';
                $email = optional($vendor->PrimaryEmailAddr)->Address ?? '';

                foreach ($ambassadors as $user) {
                    if ($user->quickbooks_vendor_id) {
                        continue; // Already linked
                    }

                    $portalName = trim(($user->last_name ?? '').', '.($user->first_name ?? ''));
                    $nameMatch = strcasecmp(trim($displayName), $portalName) === 0;
                    $emailMatch = $email && strcasecmp(trim($email), trim($user->email ?? '')) === 0;

                    if ($nameMatch || $emailMatch) {
                        $user->update(['quickbooks_vendor_id' => $vendor->Id]);
                        if ($user->w9Form) {
                            $user->w9Form->update(['quickbooks_vendor_id' => $vendor->Id]);
                        }
                        $matched++;
                        break;
                    }
                }
            }

            return ['success' => true, 'message' => "Synced {$matched} vendor(s) to portal users.", 'matched' => $matched];
        } catch (\Exception $e) {
            Log::error('QB vendor sync error: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage(), 'matched' => 0];
        }
    }

    /**
     * Map QuickBooks Account (Bank/Credit Card) to our internal code.
     */
    private function mapQbAccountTypeToCode(string $accountType, $qbAccount): string
    {
        $type = strtoupper($accountType);
        if ($type === 'CREDIT CARD' || $type === 'CREDITCARD') {
            return 'credit_card';
        }
        $subType = strtoupper((string) ($qbAccount->AccountSubType ?? ''));
        if (str_contains($subType, 'CHECKING') || str_contains((string) ($qbAccount->Name ?? ''), 'check')) {
            return 'check';
        }
        if (str_contains($subType, 'SAVINGS')) {
            return 'bank';
        }

        return $type === 'BANK' ? 'bank' : 'credit_card';
    }

    /**
     * Sync payment methods from QuickBooks (first sync). Syncs from QB Account entity
     * (Bank & Credit Card accounts) so app matches QuickBooks: Account Name, Account Type,
     * Detail Type, Opening Balance, As of.
     */
    /**
     * Ensure the mandatory default Expense Category "Ambassador Payouts" exists locally.
     * If it already exists (with or without a QB account ID), it is left untouched.
     * This runs before syncExpenseCategories so Admins never need to create it manually.
     */
    public function ensureDefaultExpenseCategory(): void
    {
        $exists = \App\Models\ExpenseCategory::whereRaw('LOWER(name) = ?', ['ambassador payouts'])->exists();
        if (! $exists) {
            \App\Models\ExpenseCategory::create([
                'name'                  => 'Ambassador Payouts',
                'description'           => 'Records payouts made to ambassadors after completing promotional events.',
                'detail_type'           => 'Cost of Labor',
                'quickbooks_account_id' => null,   // will be populated on next sync
                'is_default'            => true,
            ]);
            Log::info('ensureDefaultExpenseCategory: created default Ambassador Payouts category');
        }
    }

    public function syncPaymentMethodsFromQuickBooks(): array
    {
        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            return ['success' => false, 'message' => 'QuickBooks not connected', 'synced' => 0];
        }

        $this->ensureValidToken();

        try {
            $query = "SELECT * FROM Account WHERE AccountType IN ('Bank', 'Credit Card')";
            $qbAccounts = $this->dataService->Query($query);

            if ($error = $this->dataService->getLastError()) {
                return ['success' => false, 'message' => $error->getIntuitErrorMessage(), 'synced' => 0];
            }

            $synced = 0;

            if ($qbAccounts) {
                $list = is_array($qbAccounts) ? $qbAccounts : [$qbAccounts];
                foreach ($list as $qb) {
                    if (empty($qb->Id)) {
                        continue;
                    }
                    $name = $qb->Name ?? 'Unknown';
                    $accountType = (string) ($qb->AccountType ?? '');
                    $detailType = (string) ($qb->AccountSubType ?? '');
                    $currentBalance = isset($qb->CurrentBalance) ? (float) $qb->CurrentBalance : null;

                    PaymentMethod::updateOrCreate(
                        ['quickbooks_account_id' => (string) $qb->Id],
                        [
                            'name' => $name,
                            'account_type' => $accountType,
                            'detail_type' => $detailType ?: null,
                            'opening_balance' => $currentBalance,
                            'as_of_date' => now()->toDateString(),
                            'code' => $this->mapQbAccountTypeToCode($accountType, $qb),
                            'quickbooks_account_id' => (string) $qb->Id,
                            'active' => true,
                        ]
                    );
                    $synced++;
                }
            }

            if (! PaymentMethod::where('is_default', true)->exists()) {
                PaymentMethod::query()->update(['is_default' => false]);
                $first = PaymentMethod::where('active', true)->first();
                if ($first) {
                    $first->update(['is_default' => true]);
                } else {
                    PaymentMethod::create([
                        'name' => 'Check',
                        'code' => 'check',
                        'account_type' => 'Bank',
                        'is_default' => true,
                        'active' => true,
                    ]);
                    $synced++;
                }
            }

            return ['success' => true, 'message' => "Synced {$synced} payment account(s) from QuickBooks.", 'synced' => $synced];
        } catch (\Exception $e) {
            Log::error('QB payment methods sync error: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage(), 'synced' => 0];
        }
    }

    /**
     * Ensure a usable Expense-type Account exists in QuickBooks and return its ID.
     * This is global per QuickBooks realm (not per user) and is cached to avoid duplicate creation.
     */
    /**
     * Ensure a usable Expense-type Account exists in QuickBooks and return its ID.
     * Now with FULL logging so you always see what happened.
     */
    public function ensureExpenseAccountExists(): ?string
    {
        if (!Cache::has('qb_access_token') || !Cache::has('qb_realm_id')) {
            Log::warning('QuickBooks ensureExpenseAccountExists skipped - not connected');
            return null;
        }

        $this->ensureValidToken();

        Log::info('ensureExpenseAccountExists started', [
            'account_name' => config('quickbooks.expense_account_name', 'Ambassador Payouts')
        ]);

        // 1. Cached (fast path)
        $cached = Cache::get('qb_expense_account_id');
        if ($cached) {
            Log::info('✅ Using CACHED QuickBooks expense account', ['id' => $cached]);
            return (string) $cached;
        }

        // 2. Configured ID (from config/quickbooks.php)
        $configuredId = config('quickbooks.expense_account_id');
        if ($configuredId) {
            $configuredId = (string) $configuredId;
            if ($this->quickBooksAccountExistsById($configuredId)) {
                Log::info('✅ Using CONFIGURED QuickBooks expense account', ['id' => $configuredId]);
                Cache::put('qb_expense_account_id', $configuredId, now()->addDays(90));
                return $configuredId;
            }
            Log::warning('Configured expense_account_id is invalid/missing - falling back to name lookup', ['id' => $configuredId]);
            Cache::forget('qb_expense_account_id');
        }

        // 3. Find or create by name
        $accountName = config('quickbooks.expense_account_name', 'Ambassador Payouts');
        $accountId = $this->findOrCreateExpenseAccountByName($accountName, null);

        if ($accountId) {
            Cache::put('qb_expense_account_id', $accountId, now()->addDays(90));
            Log::info('✅ QuickBooks expense account ready', ['id' => $accountId, 'name' => $accountName]);
        } else {
            Log::error('❌ Failed to find or create QuickBooks expense account');
        }

        return $accountId ? (string) $accountId : null;
    }

    private function quickBooksAccountExistsById(string $accountId): bool
    {
        try {
            $account = $this->dataService->FindById('Account', $accountId);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->quickBooksAccountExistsById($accountId);
                    }
                }
                $this->logQuickBooksError('configured account check', $error);
                $this->retryCount = 0;
                return false;
            }

            $this->retryCount = 0;
            return (bool) $account && isset($account->Id);
        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->quickBooksAccountExistsById($accountId);
                }
            }
            Log::error('QuickBooks configured account check exception', ['message' => $e->getMessage()]);
            $this->retryCount = 0;
            return false;
        }
    }

    private function findOrCreateExpenseAccountByName(string $accountName, ?string $detailType = null): ?string
    {
        try {
            $safeName = addslashes($accountName);
            $query = "SELECT * FROM Account WHERE Name = '{$safeName}' MAXRESULTS 10";
            $accounts = $this->dataService->Query($query);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->findOrCreateExpenseAccountByName($accountName);
                    }
                }
                $this->logQuickBooksError('account lookup', $error);
                $this->retryCount = 0;
                return null;
            }

            // Reuse existing (prefer Expense type)
            if ($accounts) {
                $list = is_array($accounts) ? $accounts : [$accounts];
                foreach ($list as $acc) {
                    $type = $acc->AccountType ?? $acc->account_type ?? null;
                    if (!isset($acc->Id))
                        continue;

                    if (strcasecmp((string) $type, 'Expense') === 0) {
                        Log::info('✅ Reusing existing Expense account', ['id' => $acc->Id, 'name' => $accountName, 'type' => $type]);
                        $this->retryCount = 0;
                        return (string) $acc->Id;
                    }
                }
                // Name exists but not Expense type
                $first = $list[0];
                if (isset($first->Id)) {
                    Log::warning('⚠️ Reusing account with SAME NAME but NOT Expense type (may cause issues)', [
                        'id' => $first->Id,
                        'name' => $accountName,
                        'actual_type' => $first->AccountType ?? 'unknown'
                    ]);
                    $this->retryCount = 0;
                    return (string) $first->Id;
                }
            }

            // === CREATE NEW (fixed version) ===
            $account = new \QuickBooksOnline\API\Data\IPPAccount;
            $account->Name = $accountName;
            $account->AccountType = 'Expense';
            if ($detailType) {
                $account->AccountSubType = $detailType; // e.g. CostOfLabor, Travel, etc.
            }

            $result = $this->dataService->Add($account);

            if ($error = $this->dataService->getLastError()) {
                if ($this->isTokenExpiredError($error) && $this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    if ($this->refreshTokenSynchronously()) {
                        return $this->findOrCreateExpenseAccountByName($accountName, $detailType);
                    }
                }
                $this->logQuickBooksError('expense account creation', $error);
                $this->retryCount = 0;
                return null;
            }

            $this->retryCount = 0;
            Log::info('✅ NEW QuickBooks expense account CREATED', [
                'id' => $result->Id ?? null,
                'name' => $accountName
            ]);

            return $result && isset($result->Id) ? (string) $result->Id : null;
        } catch (\Exception $e) {
            if ($this->isTokenExpiredError($e) && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                if ($this->refreshTokenSynchronously()) {
                    return $this->findOrCreateExpenseAccountByName($accountName, $detailType);
                }
            }
            Log::error('QuickBooks expense account ensure exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->retryCount = 0;
            return null;
        }
    }

    private function logQuickBooksError(string $context, $error): void
    {
        $msg = method_exists($error, 'getIntuitErrorMessage') ? $error->getIntuitErrorMessage() : (string) $error;
        $code = method_exists($error, 'getIntuitErrorCode') ? $error->getIntuitErrorCode() : null;
        Log::error("QuickBooks {$context} failed", ['message' => $msg, 'code' => $code]);
    }
}
