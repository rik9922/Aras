<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\ServiceController;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\QuickBooksAccount;
use App\Models\QuickBooksTerm;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function setting()
    {
        // Get all settings
        $mapSetting                = $this->getSettingData('map');
        $stripSetting              = $this->getSettingData('stripe');
        $quickBookSetting          = $this->getSettingData('quickbook');
        $dwollaBalanceAlertSetting = $this->getSettingData('dwolla_balance_alert');

        // ── QuickBooks connection status ──────────────────────────────────
        $quickbooksConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');
        $qbRealmId           = Cache::get('qb_realm_id');

        // ── QuickBooks Chart of Accounts ─────────────────────────────────
        $totalAccounts   = QuickBooksAccount::count();
        $lastAccountSync = QuickBooksAccount::latest('updated_at')->value('updated_at');
        $accountsSynced  = $totalAccounts > 0;

        // ── Services ─────────────────────────────────────────────────────
        // Ensure mandatory services exist (auto-create if missing)
        (new ServiceController(app(\App\Services\QuickBooksService::class)))->ensureSystemServicesExistPublic();

        $mandatoryNames    = array_map('mb_strtolower', array_keys(ServiceController::SYSTEM_SERVICES));
        $allActiveServices = Service::where('is_active', true)->get();
        $mandatoryServices = $allActiveServices->filter(
            fn ($s) => in_array(mb_strtolower($s->name), $mandatoryNames, true)
        );
        $customServices = $allActiveServices->reject(
            fn ($s) => in_array(mb_strtolower($s->name), $mandatoryNames, true)
        );
        $totalServices      = $allActiveServices->count();
        $syncedServices     = $allActiveServices->whereNotNull('qb_item_id')->count();
        $pendingServices    = $allActiveServices->whereNull('qb_item_id')->count();
        // Must have all 7 mandatory services AND every one must have a qb_item_id.
        // isEmpty() alone would return true on an empty collection (falsely showing "All Synced").
        $mandatoryAllSynced = $mandatoryServices->count() >= count($mandatoryNames)
            && $mandatoryServices->whereNull('qb_item_id')->isEmpty();

        // ── QuickBooks Terms ──────────────────────────────────────────────
        $totalTerms   = QuickBooksTerm::count();
        $lastTermSync = QuickBooksTerm::latest('updated_at')->value('updated_at');
        $termsSynced  = $totalTerms > 0;

        // ── Payment Methods ────────────────────────────────────────────
        $totalPaymentMethods   = PaymentMethod::where('active', true)->count();
        $lastPaymentMethodSync = PaymentMethod::where('active', true)->latest('updated_at')->value('updated_at');
        $paymentMethodsSynced  = $totalPaymentMethods > 0;

        // ── Expense Categories ─────────────────────────────────────────
        // Auto-create default 'Ambassador Payouts' category if missing
        app(\App\Services\QuickBooksService::class)->ensureDefaultExpenseCategory();

        $allExpenseCategories     = ExpenseCategory::orderByDesc('is_default')->orderBy('name')->get();
        $totalExpenseCategories   = $allExpenseCategories->count();
        $syncedExpenseCategories  = $allExpenseCategories->whereNotNull('quickbooks_account_id')->count();
        $pendingExpenseCategories = $allExpenseCategories->whereNull('quickbooks_account_id')->count();
        $lastExpenseCategorySync  = ExpenseCategory::whereNotNull('quickbooks_account_id')->latest('updated_at')->value('updated_at');
        $expenseCategoriesSynced  = $syncedExpenseCategories > 0;

        // ── Setup readiness (expanded) ─────────────────────────────────
        // All 6 steps must be complete for full invoice + expense readiness
        $qbSetupReady = $quickbooksConnected
            && $accountsSynced
            && $termsSynced
            && $paymentMethodsSynced
            && $expenseCategoriesSynced
            && $mandatoryAllSynced;

        return view('admin.setting.index', compact(
            'mapSetting', 'stripSetting', 'quickBookSetting',
            'quickbooksConnected', 'dwollaBalanceAlertSetting',
            'qbRealmId', 'totalAccounts', 'lastAccountSync', 'accountsSynced',
            'mandatoryServices', 'customServices',
            'totalServices', 'syncedServices', 'pendingServices',
            'mandatoryAllSynced', 'qbSetupReady',
            'totalTerms', 'lastTermSync', 'termsSynced',
            'totalPaymentMethods', 'lastPaymentMethodSync', 'paymentMethodsSynced',
            'allExpenseCategories', 'totalExpenseCategories',
            'syncedExpenseCategories', 'pendingExpenseCategories',
            'lastExpenseCategorySync', 'expenseCategoriesSynced'
        ));
    }

    /*************  ✨ Windsurf Command ⭐  *************/
    /**
     * Get a setting value by name.
     *
     * @param string $name
     * @return object
     */
    /*******  ff5e675a-df1b-4a25-8e62-cf1f4e9b7e91  *******/
    private function getSettingData($name)
    {
        $setting = Setting::where('name', $name)->first();

        if ($setting) {
            return json_decode($setting->value);
        }

        return (object) [];
    }

    public function updateSetting(Request $request)
    {

        $setting = $request->setting;

        // Validate based on setting type
        switch ($setting) {
            case 'map':
                $validator = Validator::make($request->all(), [
                    'public_key' => 'required|string|max:255',
                    'server_key' => 'nullable|string|max:255',
                    'zoom' => 'required|numeric|min:1|max:20',
                ], [
                    'public_key.required' => 'The Map API Key is required.',
                    'zoom.required' => 'The Zoom Level is required.',
                    'zoom.numeric' => 'The Zoom Level must be a number.',
                    'zoom.min' => 'The Zoom Level must be at least 1.',
                    'zoom.max' => 'The Zoom Level must not be greater than 20.',
                ]);
                break;

            case 'stripe':
                $validator = Validator::make($request->all(), [
                    'public_key' => 'required|string|max:255',
                    'secret_key' => 'required|string|max:255',
                ], [
                    'public_key.required' => 'The Stripe API Key is required.',
                    'secret_key.required' => 'The Stripe Secret Key is required.',
                ]);
                break;

            case 'quickbook':
                $validator = Validator::make($request->all(), [
                    'public_key' => 'required|string|max:255',
                    'secret_key' => 'required|string|max:255',
                ], [
                    'public_key.required' => 'The QuickBook API Key is required.',
                    'secret_key.required' => 'The QuickBook Secret Key is required.',
                ]);
                break;
            case 'dwolla_balance_alert':
                $validator = Validator::make($request->all(), [
                    'fixed_floor_amount' => 'nullable|numeric|min:0',
                ], [
                    'fixed_floor_amount.numeric' => 'Bottom line limit must be a valid number.',
                    'fixed_floor_amount.min' => 'Bottom line limit cannot be negative.',
                ]);
                break;
            case 'event_invoice_tax':
                $validator = Validator::make($request->all(), [
                    'taxable' => 'nullable|boolean',
                ]);
                break;
            default:
                return redirect()->back()->with('error', 'Invalid setting type.');
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Get or create the setting
            $settingRecord = Setting::firstOrNew(['name' => $setting]);

            // Prepare data for saving
            $data = [];

            switch ($setting) {
                case 'map':
                    $data = [
                        'public_key' => $request->input('public_key'),
                        'server_key' => $request->input('server_key'),
                        'zoom' => $request->input('zoom'),
                    ];
                    break;

                case 'stripe':
                case 'quickbook':
                    $data = [
                        'public_key' => $request->input('public_key'),
                        'secret_key' => $request->input('secret_key'),
                    ];

                    // For stripe, add currency
                    if ($setting === 'stripe') {
                        $data['currency'] = 'USD';
                    }
                    break;
                case 'dwolla_balance_alert':
                    $fixedFloor = $request->input('fixed_floor_amount');
                    $data = [
                        'fixed_floor_amount' => ($fixedFloor === null || $fixedFloor === '')
                            ? null
                            : round((float) $fixedFloor, 2),
                    ];
                    break;
                case 'event_invoice_tax':
                    $data = [
                        'tax_code' => $request->boolean('taxable') ? 'TAX' : 'NON',
                    ];
                    break;

            }

            // Save setting data as JSON
            $settingRecord->value = json_encode($data);
            $settingRecord->save();
            $activeTab = $request->input('active_tab');
            return redirect()->back()->with('active_tab', $activeTab)->with('success', ucfirst($setting) . ' settings updated successfully.');
            return redirect()->back()->with('success', ucfirst($setting) . ' settings updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while saving settings: ' . $e->getMessage());
        }
    }
}
