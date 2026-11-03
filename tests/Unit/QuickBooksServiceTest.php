<?php

namespace Tests\Unit;

use App\Models\QuickBooksAccount;
use App\Models\PaymentMethod;
use App\Services\QuickBooksService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class QuickBooksServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('quickbooks_accounts')) {
            Schema::create('quickbooks_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('qb_account_id')->unique();
                $table->string('name');
                $table->string('account_type')->nullable();
                $table->string('account_sub_type')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('sync_token')->nullable();
                $table->string('currency_ref')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50);
                $table->string('quickbooks_payment_method_id')->nullable()->index();
                $table->string('quickbooks_account_id')->nullable()->index();
                $table->string('account_type', 50)->nullable();
                $table->string('detail_type', 100)->nullable();
                $table->decimal('opening_balance', 15, 2)->nullable();
                $table->date('as_of_date')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        QuickBooksAccount::query()->delete();
        PaymentMethod::query()->delete();
    }

    public function test_resolve_expense_payment_type_uses_check_for_bank_account_when_qb_returns_cash(): void
    {
        QuickBooksAccount::create([
            'qb_account_id' => '42',
            'name' => 'Checking',
            'account_type' => 'Bank',
            'account_sub_type' => 'Checking',
            'is_active' => true,
        ]);

        $service = $this->quickBooksServiceWithDataService(new class {});
        $expense = (object) [
            'PaymentType' => 'Cash',
            'AccountRef' => (object) ['value' => '42'],
        ];

        $this->assertSame('Check', $this->invokeResolveExpensePaymentType($service, $expense));
    }

    public function test_resolve_expense_payment_type_prefers_payment_method_ref_check_over_cash_payment_type(): void
    {
        $service = $this->quickBooksServiceWithDataService(new class {});
        $expense = (object) [
            'PaymentType' => 'Cash',
            'PaymentMethodRef' => (object) [
                'value' => '1',
                'name' => 'Check',
            ],
        ];

        $this->assertSame('Check', $this->invokeResolveExpensePaymentType($service, $expense));
    }

    public function test_resolve_expense_payment_type_supports_array_payment_method_ref(): void
    {
        $service = $this->quickBooksServiceWithDataService(new class {});
        $expense = (object) [
            'PaymentType' => 'Cash',
            'PaymentMethodRef' => [
                'value' => '1',
                'name' => 'Cheque',
            ],
        ];

        $this->assertSame('Check', $this->invokeResolveExpensePaymentType($service, $expense));
    }

    public function test_resolve_expense_payment_type_uses_credit_card_for_credit_card_account(): void
    {
        QuickBooksAccount::create([
            'qb_account_id' => '43',
            'name' => 'Corporate Card',
            'account_type' => 'Credit Card',
            'account_sub_type' => 'CreditCard',
            'is_active' => true,
        ]);

        $service = $this->quickBooksServiceWithDataService(new class {});
        $expense = (object) [
            'PaymentType' => 'Cash',
            'AccountRef' => (object) ['value' => '43'],
        ];

        $this->assertSame('CreditCard', $this->invokeResolveExpensePaymentType($service, $expense));
    }

    public function test_create_quickbooks_invoice_handles_exception_result_without_reading_id(): void
    {
        config(['cache.default' => 'array']);
        Cache::put('qb_access_token', 'test-token');
        Cache::put('qb_realm_id', 'test-realm');

        $service = $this->quickBooksServiceWithDataService(new class
        {
            public function Add($invoice)
            {
                return new \Exception('QuickBooks rejected invoice payload.');
            }

            public function getLastError()
            {
                return null;
            }
        });

        $result = $service->createQuickBooksInvoice($this->minimalInvoiceData());

        $this->assertNull($result);
        $this->assertSame('QuickBooks rejected invoice payload.', $service->getLastQbError());
    }

    public function test_create_quickbooks_invoice_recovers_deserialize_exception_result_by_doc_number(): void
    {
        config(['cache.default' => 'array']);
        Cache::put('qb_access_token', 'test-token');
        Cache::put('qb_realm_id', 'test-realm');
        Http::fakeSequence()
            ->push([
                'QueryResponse' => [
                    'Invoice' => [
                        [
                            'Id' => '123',
                            'SyncToken' => '0',
                            'DocNumber' => 'TEST-001',
                        ],
                    ],
                ],
            ]);

        $service = $this->quickBooksServiceWithDataService(new class
        {
            public function Add($invoice)
            {
                return new \Exception('Exception in deserialize ResponseBody.');
            }

            public function getLastError()
            {
                return null;
            }
        });

        $result = $service->createQuickBooksInvoice($this->minimalInvoiceData());

        $this->assertSame('123', $result->Id);
        $this->assertSame('0', $result->SyncToken);
        Http::assertSentCount(1);
    }

    public function test_create_quickbooks_invoice_uses_json_create_when_deserialize_recovery_lookup_misses(): void
    {
        config(['cache.default' => 'array']);
        Cache::put('qb_access_token', 'test-token');
        Cache::put('qb_realm_id', 'test-realm');
        Http::fakeSequence()
            ->push(['QueryResponse' => []])
            ->push([
                'Invoice' => [
                    'Id' => '456',
                    'SyncToken' => '0',
                    'DocNumber' => 'TEST-001',
                ],
            ]);

        $service = $this->quickBooksServiceWithDataService(new class
        {
            public function Add($invoice)
            {
                throw new \Exception('Exception in deserialize ResponseBody.');
            }

            public function getLastError()
            {
                return null;
            }
        });

        $result = $service->createQuickBooksInvoice($this->minimalInvoiceData());

        $this->assertSame('456', $result->Id);
        $this->assertSame('0', $result->SyncToken);
        Http::assertSentCount(2);
    }

    public function test_payment_method_account_id_for_expense_does_not_fall_back_to_config(): void
    {
        config(['quickbooks.bank_account_id' => '35']);

        $paymentMethod = PaymentMethod::create([
            'name' => 'Check',
            'code' => 'check',
            'account_type' => 'Bank',
            'is_default' => true,
            'active' => true,
        ]);

        $this->assertNull($paymentMethod->getQuickBooksAccountIdForExpense());
    }

    public function test_get_bank_account_id_does_not_fall_back_to_config(): void
    {
        config(['quickbooks.bank_account_id' => '35']);

        $service = $this->quickBooksServiceWithDataService(new class {});

        $this->assertNull($service->getBankAccountId());
    }

    private function quickBooksServiceWithDataService(object $dataService): QuickBooksService
    {
        $reflection = new ReflectionClass(QuickBooksService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $dataServiceProperty = $reflection->getProperty('dataService');
        $dataServiceProperty->setAccessible(true);
        $dataServiceProperty->setValue($service, $dataService);

        return $service;
    }

    private function invokeResolveExpensePaymentType(QuickBooksService $service, object $expense): string
    {
        $reflection = new ReflectionClass(QuickBooksService::class);
        $method = $reflection->getMethod('resolveExpensePaymentType');
        $method->setAccessible(true);

        return $method->invoke($service, $expense);
    }

    private function minimalInvoiceData(): array
    {
        return [
            'DocNumber' => 'TEST-001',
            'TxnDate' => '2026-09-07',
            'DueDate' => '2026-09-07',
            'CustomerRef' => ['value' => '1'],
            'Line' => [
                [
                    'Amount' => 10,
                    'DetailType' => 'SalesItemLineDetail',
                    'Description' => 'Test service',
                    'SalesItemLineDetail' => [
                        'ItemRef' => ['value' => '1', 'name' => 'Test service'],
                        'Qty' => 1,
                        'UnitPrice' => 10,
                        'TaxCodeRef' => ['value' => 'NON'],
                    ],
                ],
            ],
        ];
    }
}
