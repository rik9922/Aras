<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\QuickBooksService;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(QuickBooksService $qbService)
    {
        $paymentMethods = PaymentMethod::orderByDesc('is_default')->orderBy('name')->get();

        return view('admin.payment-methods.index', compact('paymentMethods'));
    }

    /**
     * First sync: pull payment methods from QuickBooks, then redirect back.
     */
    public function sync(QuickBooksService $qbService)
    {
        $result = $qbService->syncPaymentMethodsFromQuickBooks();

        if ($result['success']) {
            return redirect()->route('admin.payment-methods.index')->with('success', $result['message']);
        }

        return redirect()->route('admin.payment-methods.index')->with('error', 'Sync failed: '.$result['message']);
    }

    public function setDefault(PaymentMethod $paymentMethod)
    {
        PaymentMethod::query()->update(['is_default' => false]);
        $paymentMethod->update(['is_default' => true]);

        return back()->with('success', 'Default payment method updated.');
    }

}
