<?php

namespace Tests\Feature;

use App\Models\DwollaBalanceAlertSnapshot;
use App\Services\Dwolla\OperatingBalanceAlertService;
use App\Services\Dwolla\OperatingBalanceService;
use App\Services\Forecast\PayrollForecastService;
use App\Services\NotificationDispatchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DwollaBalanceAlertSnapshotTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function evaluate_persists_row_in_dwolla_balance_alert_snapshots(): void
    {
        if (! Schema::hasTable('dwolla_balance_alert_snapshots')) {
            $this->markTestSkipped('dwolla_balance_alert_snapshots table not migrated.');
        }

        config([
            'dwolla_balance_alert.enabled' => true,
            'dwolla_balance_alert.buffer_amount' => 2500.0,
            'dwolla_balance_alert.forecast_days' => 14,
        ]);

        $forecastPayload = [
            'window_start' => now()->startOfMinute()->toDateTimeString(),
            'window_end' => now()->addDays(14)->startOfMinute()->toDateTimeString(),
            'days' => 14,
            'currency' => 'USD',
            'ambassador_payouts' => [
                'statuses' => ['approved', 'queued', 'processing'],
                'count' => 0,
                'total' => 0.0,
                'breakdown_by_status' => [],
            ],
            'reimbursements' => [
                'statuses' => ['pending', 'approved'],
                'count' => 0,
                'total' => 0.0,
                'breakdown_by_status' => [],
            ],
            'grand_total' => 750.25,
            'grand_count' => 0,
        ];

        $forecast = Mockery::mock(PayrollForecastService::class);
        $forecast->shouldReceive('forecastForNextDays')
            ->once()
            ->with(14)
            ->andReturn($forecastPayload);

        $balanceSvc = Mockery::mock(OperatingBalanceService::class);
        $balanceSvc->shouldReceive('getOperatingBalance')
            ->once()
            ->andReturn([
                'available_balance' => 10000.0,
                'currency' => 'USD',
                'as_of' => now()->toIso8601String(),
            ]);

        $notify = Mockery::mock(NotificationDispatchService::class);
        $notify->shouldNotReceive('dispatch');

        $this->app->instance(PayrollForecastService::class, $forecast);
        $this->app->instance(OperatingBalanceService::class, $balanceSvc);
        $this->app->instance(NotificationDispatchService::class, $notify);

        $before = DwollaBalanceAlertSnapshot::count();

        $service = $this->app->make(OperatingBalanceAlertService::class);
        $result = $service->evaluate(14, false);

        $this->assertTrue($result['enabled']);
        $this->assertIsInt($result['snapshot_id']);
        $this->assertSame($before + 1, DwollaBalanceAlertSnapshot::count());

        $this->assertDatabaseHas('dwolla_balance_alert_snapshots', [
            'id' => $result['snapshot_id'],
            'forecast_days' => 14,
            'is_breach' => false,
        ]);

        $row = DwollaBalanceAlertSnapshot::findOrFail($result['snapshot_id']);
        $this->assertFalse($row->is_breach);
        $this->assertEqualsWithDelta(750.25, (float) $row->forecast_total, 0.01);
        $this->assertEqualsWithDelta(2500.0, (float) $row->buffer_amount, 0.01);
        $this->assertEqualsWithDelta(3250.25, (float) $row->required_minimum, 0.01);
        $this->assertEqualsWithDelta(10000.0, (float) $row->operating_balance, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $row->shortfall, 0.01);
        $this->assertNull($row->severity);
        $this->assertIsArray($row->meta);
        $this->assertArrayHasKey('forecast', $row->meta);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
