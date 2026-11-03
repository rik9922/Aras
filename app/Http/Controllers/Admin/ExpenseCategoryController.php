<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Services\QuickBooksService;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        // Key = QuickBooks internal code, Value = human label
        $detailTypes = [
            'AdvertisingPromotional' => 'Advertising/Promotional',
            'Automobile' => 'Automobile',
            'BadDebts' => 'Bad Debts',
            'BankCharges' => 'Bank Charges',
            'CharitableContributions' => 'Charitable Contributions',
            'CostOfLabor' => 'Cost of Labor',
            'DuesSubscriptions' => 'Dues & subscriptions',
            'Entertainment' => 'Entertainment',
            'Insurance' => 'Insurance',
            'LegalProfessionalFees' => 'Legal & Professional Fees',
            'MealsAndEntertainment' => 'Meals & Entertainment',
            'OfficeExpenses' => 'Office/General Administrative Expenses',
            'OtherBusinessExpenses' => 'Other Business Expenses',
            'OtherMiscellaneousExpense' => 'Other Miscellaneous Expense',
            'PayrollExpenses' => 'Payroll Expenses',
            'RentOrLease' => 'Rent or Lease of Buildings',
            'RepairsMaintenance' => 'Repair & Maintenance',
            'ShippingFreightDelivery' => 'Shipping, Freight & Delivery',
            'SuppliesMaterials' => 'Supplies & Materials',
            'TaxesPaid' => 'Taxes Paid',
            'Travel' => 'Travel',
            'Utilities' => 'Utilities',
        ];

        return view('admin.expense-categories.index', compact('categories', 'detailTypes'));
    }

    public function store(Request $request, QuickBooksService $qbService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'detail_type' => 'required|string|max:100',
        ]);

        // Map internal code → human label (must stay in sync with index())
        $detailTypes = [
            'AdvertisingPromotional' => 'Advertising/Promotional',
            'Automobile' => 'Automobile',
            'BadDebts' => 'Bad Debts',
            'BankCharges' => 'Bank Charges',
            'CharitableContributions' => 'Charitable Contributions',
            'CostOfLabor' => 'Cost of Labor',
            'DuesSubscriptions' => 'Dues & subscriptions',
            'Entertainment' => 'Entertainment',
            'Insurance' => 'Insurance',
            'LegalProfessionalFees' => 'Legal & Professional Fees',
            'MealsAndEntertainment' => 'Meals & Entertainment',
            'OfficeExpenses' => 'Office/General Administrative Expenses',
            'OtherBusinessExpenses' => 'Other Business Expenses',
            'OtherMiscellaneousExpense' => 'Other Miscellaneous Expense',
            'PayrollExpenses' => 'Payroll Expenses',
            'RentOrLease' => 'Rent or Lease of Buildings',
            'RepairsMaintenance' => 'Repair & Maintenance',
            'ShippingFreightDelivery' => 'Shipping, Freight & Delivery',
            'SuppliesMaterials' => 'Supplies & Materials',
            'TaxesPaid' => 'Taxes Paid',
            'Travel' => 'Travel',
            'Utilities' => 'Utilities',
        ];

        $detailTypeCode = $data['detail_type'];
        $detailTypeLabel = $detailTypes[$detailTypeCode] ?? $detailTypeCode;

        // Ask QuickBooks for (or create) an Expense account for this category name + detail type CODE
        $qbAccountId = $qbService->ensureNamedExpenseAccount($data['name'], $detailTypeCode);

        if (! $qbAccountId) {
            return back()->with('error', 'Failed to create or find QuickBooks expense account. Please try again.');
        }

        $category = ExpenseCategory::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            // Store the human-friendly label in DB so UI shows readable value
            'detail_type' => $detailTypeLabel,
            'quickbooks_account_id' => $qbAccountId,
            'is_default' => ExpenseCategory::count() === 0, // first one becomes default
        ]);

        return redirect()->route('admin.expense-categories.index')
            ->with('success', 'Expense category created and synced to QuickBooks (Account ID: '.$qbAccountId.').');
    }

    public function setDefault(ExpenseCategory $expenseCategory)
    {
        ExpenseCategory::query()->update(['is_default' => false]);
        $expenseCategory->update(['is_default' => true]);

        return back()->with('success', 'Default expense category updated.');
    }
}

