<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\ServiceController;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\QuickBooksAccount;
use App\Models\QuickBooksTerm;
use App\Models\Service;
use App\Services\QuickBooksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;

class QuickBooksController extends Controller
{
    private function resolveRedirectUri(): string
    {
        $config = config('quickbooks');
        $isManager = (int) (auth()->user()->role_id ?? 0) === 6;

        if ($isManager && ! empty($config['redirect_uri_manager'])) {
            return (string) $config['redirect_uri_manager'];
        }

        return (string) ($config['redirect_uri'] ?? '');
    }

    public function auth()
    {
        $config = config('quickbooks');
        $redirectUri = $this->resolveRedirectUri();
        Log::info('QuickBooks Config:', $config);

        $dataService = DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => $config['client_id'],
            'ClientSecret' => $config['client_secret'],
            'RedirectURI' => $redirectUri,
            'scope' => $config['scope'],
            'baseUrl' => $config['base_url'],
        ]);

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
        Log::info('Generated authUrl: '.$authUrl);

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $config = config('quickbooks');
        $redirectUri = $this->resolveRedirectUri();
        $dataService = DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => $config['client_id'],
            'ClientSecret' => $config['client_secret'],
            'RedirectURI' => $redirectUri,
            'scope' => $config['scope'],
            'baseUrl' => $config['base_url'],
        ]);

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

        $code = $request->get('code');
        $realmId = $request->get('realmId');

        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';

        if (empty($code) || empty($realmId)) {
            Log::error('Invalid QuickBooks callback parameters:', ['code' => $code, 'realmId' => $realmId]);

            return redirect('/'.$routePrefix)->with('error', 'Invalid QuickBooks callback parameters.');
        }

        try {
            $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($code, $realmId);
            $dataService->updateOAuth2Token($accessToken);

            // Store tokens using correct method names
            Cache::put('qb_access_token', $accessToken->getAccessToken(), 3600);
            Cache::put('qb_refresh_token', $accessToken->getRefreshToken(), now()->addDays(100));
            Cache::put('qb_realm_id', $realmId, now()->addDays(90));
            Cache::put('qb_access_token_expires_at', now()->addSeconds($accessToken->getAccessTokenValidationPeriodInSeconds()), 3600);
            Cache::put('qb_refresh_token_expires_at', now()->addDays(100), now()->addDays(90));

            Log::info('QuickBooks tokens stored successfully.');

            return redirect()->route($routePrefix.'.invoices')->with('success', 'QuickBooks connected successfully!');
        } catch (\Exception $e) {
            Log::error('QuickBooks callback error: '.$e->getMessage());

            return redirect('/'.$routePrefix)->with('error', 'QuickBooks authentication failed: '.$e->getMessage());
        }
    }

    public function invoices()
    {
        $config = config('quickbooks');
        $redirectUri = $this->resolveRedirectUri();
        $realmId = Cache::get('qb_realm_id');

        if (! $realmId) {
            return redirect('/admin/quickbooks/auth')->with('error', 'QuickBooks authentication required.');
        }

        // Refresh token if needed before making API call
        if (! $this->ensureValidToken()) {
            return redirect('/admin/quickbooks/auth')->with('error', 'QuickBooks token refresh failed. Please re-authenticate.');
        }

        $dataService = DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => $config['client_id'],
            'ClientSecret' => $config['client_secret'],
            'RedirectURI' => $redirectUri,
            'scope' => $config['scope'],
            'baseUrl' => $config['base_url'],
        ]);

        $dataService->setRealmId($realmId);

        $accessToken = Cache::get('qb_access_token');
        $refreshToken = Cache::get('qb_refresh_token');
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
        $dataService->updateOAuth2Token($accessTokenObj);

        // Retry logic for token expiration errors
        $maxRetries = 1;
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                $invoices = $dataService->Query('SELECT * FROM Invoice MAXRESULTS 100');

                if ($dataService->getLastError()) {
                    $error = $dataService->getLastError();
                    $errorMessage = strtolower($error->getIntuitErrorMessage());

                    // Check if error is due to token expiration
                    if (($this->isTokenExpiredError($error) || strpos($errorMessage, '401') !== false) && $retryCount < $maxRetries) {
                        Log::info('Token expired during invoice query, refreshing and retrying...');
                        if ($this->refreshToken($dataService)) {
                            $retryCount++;

                            continue; // Retry the query
                        }
                    }

                    Log::error('QuickBooks query error: '.$error->getIntuitErrorMessage());

                    return view('admin.invoices')->with('error', 'Error fetching invoices: '.$error->getIntuitErrorMessage());
                }

                return view('admin.invoices', compact('invoices'));

            } catch (\Exception $e) {
                $errorMessage = strtolower($e->getMessage());

                // Check if exception is due to token expiration
                if (($this->isTokenExpiredError($e) || strpos($errorMessage, '401') !== false || strpos($errorMessage, 'unauthorized') !== false) && $retryCount < $maxRetries) {
                    Log::info('Token expired during invoice query (exception), refreshing and retrying...');
                    if ($this->refreshToken($dataService)) {
                        $retryCount++;

                        continue; // Retry the query
                    }
                }

                Log::error('QuickBooks invoices error: '.$e->getMessage());

                return view('admin.invoices')->with('error', 'Error fetching invoices: '.$e->getMessage());
            }
        }

        return view('admin.invoices')->with('error', 'Failed to fetch invoices after retries.');
    }

    /**
     * Ensure QuickBooks token is valid, refresh if needed
     *
     * @return bool True if token is valid or was successfully refreshed
     */
    private function ensureValidToken(): bool
    {
        $accessTokenExpiresAt = Cache::get('qb_access_token_expires_at');

        // Refresh if expired or expiring within 5 minutes
        if ($accessTokenExpiresAt && (now()->greaterThan($accessTokenExpiresAt) || now()->addMinutes(5)->greaterThan($accessTokenExpiresAt))) {
            return $this->refreshToken();
        }

        return true;
    }

    /**
     * Refresh QuickBooks token
     *
     * @param  DataService|null  $dataService  Optional DataService instance to update
     * @return bool True if refresh was successful
     */
    private function refreshToken(?DataService $dataService = null): bool
    {
        $refreshToken = Cache::get('qb_refresh_token');
        if (! $refreshToken) {
            Log::error('QuickBooks refresh token missing');

            return false;
        }

        try {
            $config = config('quickbooks');
            $redirectUri = $this->resolveRedirectUri();

            if (! $dataService) {
                $dataService = DataService::Configure([
                    'auth_mode' => 'oauth2',
                    'ClientID' => $config['client_id'],
                    'ClientSecret' => $config['client_secret'],
                    'RedirectURI' => $redirectUri,
                    'scope' => $config['scope'],
                    'baseUrl' => $config['base_url'],
                ]);
            }

            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            $refreshedAccessToken = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken($refreshToken);

            if (! $refreshedAccessToken) {
                Log::error('QuickBooks token refresh returned null');

                return false;
            }

            $dataService->updateOAuth2Token($refreshedAccessToken);

            Cache::put('qb_access_token', $refreshedAccessToken->getAccessToken(), 3600);
            Cache::put('qb_refresh_token', $refreshedAccessToken->getRefreshToken(), now()->addDays(100));
            Cache::put('qb_access_token_expires_at', now()->addSeconds($refreshedAccessToken->getAccessTokenValidationPeriodInSeconds()), 3600);
            Cache::put('qb_refresh_token_expires_at', now()->addDays(100), now()->addDays(90));

            Log::info('QuickBooks token refreshed successfully in controller');

            return true;

        } catch (\Exception $e) {
            Log::error('QuickBooks token refresh error: '.$e->getMessage());

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
        } elseif (is_object($error) && method_exists($error, 'getMessage')) {
            $errorMessage = strtolower($error->getMessage());
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

    public function syncVendors(QuickBooksService $qbService)
    {
        $result = $qbService->syncVendorsFromQuickBooks();

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', 'Sync failed: '.$result['message']);
    }

    /**
     * Fetch the complete Chart of Accounts from QuickBooks and store locally.
     * Admin/Manager can call this anytime to refresh accounts after a reconnect or Sandbox reset.
     */
    public function syncAccounts(QuickBooksService $qbService)
    {
        $result = $qbService->syncChartOfAccounts();

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', 'Chart of Accounts sync failed: '.$result['message']);
    }

    /**
     * Fetch all Payment Terms from QuickBooks and store locally.
     * Must be run after every QuickBooks reconnect or Sandbox reset so that
     * SalesTermRef IDs remain accurate for the connected company.
     */
    public function syncTerms(QuickBooksService $qbService)
    {
        $result = $qbService->syncTerms();

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', 'Terms sync failed: '.$result['message']);
    }

    /**
     * Sync Payment Methods (Bank/Credit Card accounts) from QuickBooks.
     * Reuses the existing syncPaymentMethodsFromQuickBooks() in QuickBooksService.
     */
    public function syncPaymentMethods(QuickBooksService $qbService)
    {
        $result = $qbService->syncPaymentMethodsFromQuickBooks();

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', 'Payment Methods sync failed: '.$result['message']);
    }

    /**
     * Ensure the default Expense Category exists, then sync all Expense Categories
     * to QuickBooks (creates/updates accounts via ensureNamedExpenseAccount).
     * Reuses existing QuickBooksService::ensureNamedExpenseAccount() — no new QB logic.
     */
    public function syncExpenseCategories(QuickBooksService $qbService)
    {
        // Ensure the mandatory default category exists before syncing
        $qbService->ensureDefaultExpenseCategory();

        $categories = ExpenseCategory::whereNull('quickbooks_account_id')->get();
        $synced = 0;
        $failed = 0;

        foreach ($categories as $category) {
            // Map human-readable label back to code for QB sync
            $detailTypeCode = $this->labelToDetailTypeCode($category->detail_type);
            $qbAccountId = $qbService->ensureNamedExpenseAccount($category->name, $detailTypeCode);
            if ($qbAccountId) {
                $category->update(['quickbooks_account_id' => $qbAccountId]);
                $synced++;
            } else {
                $failed++;
            }
        }

        // Also refresh already-synced categories (they may have changed on QB side)
        $alreadySynced = ExpenseCategory::whereNotNull('quickbooks_account_id')->count();

        $msg = "Expense Categories synced: {$synced} new";
        if ($alreadySynced > 0) {
            $msg .= ", {$alreadySynced} already synced";
        }
        if ($failed > 0) {
            return redirect()->back()->with('error', "{$failed} category(ies) failed to sync. {$msg}.");
        }

        return redirect()->back()->with('success', $msg . '.');
    }

    /**
     * Map human-readable detail type label back to QB code.
     * Mirrors the mapping in ExpenseCategoryController::store().
     */
    private function labelToDetailTypeCode(string $label): ?string
    {
        $map = [
            'Advertising/Promotional'               => 'AdvertisingPromotional',
            'Automobile'                             => 'Automobile',
            'Bad Debts'                              => 'BadDebts',
            'Bank Charges'                           => 'BankCharges',
            'Charitable Contributions'               => 'CharitableContributions',
            'Cost of Labor'                          => 'CostOfLabor',
            'Dues & subscriptions'                   => 'DuesSubscriptions',
            'Entertainment'                          => 'Entertainment',
            'Insurance'                              => 'Insurance',
            'Legal & Professional Fees'              => 'LegalProfessionalFees',
            'Meals & Entertainment'                  => 'MealsAndEntertainment',
            'Office/General Administrative Expenses' => 'OfficeExpenses',
            'Other Business Expenses'                => 'OtherBusinessExpenses',
            'Other Miscellaneous Expense'            => 'OtherMiscellaneousExpense',
            'Payroll Expenses'                       => 'PayrollExpenses',
            'Rent or Lease of Buildings'             => 'RentOrLease',
            'Repair & Maintenance'                   => 'RepairsMaintenance',
            'Shipping, Freight & Delivery'           => 'ShippingFreightDelivery',
            'Supplies & Materials'                   => 'SuppliesMaterials',
            'Taxes Paid'                             => 'TaxesPaid',
            'Travel'                                 => 'Travel',
            'Utilities'                              => 'Utilities',
        ];
        return $map[$label] ?? null;
    }

    /**
     * Dedicated QuickBooks Setup page — single place for all QB preparation steps.
     */
    public function setup(ServiceController $serviceController)
    {
        $prefix = (auth()->user()?->role_id == 6) ? 'manager' : 'admin';

        // ── Connection ──────────────────────────────────────────────────────
        $isConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');
        $realmId     = Cache::get('qb_realm_id');

        // ── Chart of Accounts ───────────────────────────────────────────────
        $totalAccounts   = QuickBooksAccount::count();
        $lastAccountSync = QuickBooksAccount::latest('updated_at')->value('updated_at');

        // ── Services ────────────────────────────────────────────────────────
        // Ensure mandatory services exist before rendering
        $serviceController->ensureSystemServicesExistPublic();

        $mandatoryNames    = array_map('mb_strtolower', array_keys(ServiceController::SYSTEM_SERVICES));
        $allActiveServices = Service::where('is_active', true)->get();
        $mandatoryServices = $allActiveServices->filter(
            fn ($s) => in_array(mb_strtolower($s->name), $mandatoryNames, true)
        );
        $customServices = $allActiveServices->reject(
            fn ($s) => in_array(mb_strtolower($s->name), $mandatoryNames, true)
        );

        $totalServices   = $allActiveServices->count();
        $syncedServices  = $allActiveServices->whereNotNull('qb_item_id')->count();
        $pendingServices = $allActiveServices->whereNull('qb_item_id')->count();

        $mandatoryAllSynced = $mandatoryServices->whereNull('qb_item_id')->isEmpty();

        // ── Setup status checklist ───────────────────────────────────────────
        $accountsSynced = $totalAccounts > 0;
        $setupReady     = $isConnected && $accountsSynced && $mandatoryAllSynced;

        return view('admin.quickbooks.setup', compact(
            'prefix',
            'isConnected',
            'realmId',
            'totalAccounts',
            'lastAccountSync',
            'mandatoryServices',
            'customServices',
            'totalServices',
            'syncedServices',
            'pendingServices',
            'mandatoryAllSynced',
            'accountsSynced',
            'setupReady'
        ));
    }
}
