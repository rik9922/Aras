<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\QuickBooksService;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with(['user', 'payout', 'reimbursement'])
            ->orderByDesc('txn_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.expenses.index', compact('expenses'));
    }

    public function sync(QuickBooksService $qbService)
    {
        $result = $qbService->syncExpenses();

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', 'Expense sync failed: '.$result['message']);
    }
}

