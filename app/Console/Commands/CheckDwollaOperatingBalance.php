<?php

namespace App\Console\Commands;

use App\Services\Dwolla\OperatingBalanceAlertService;
use Illuminate\Console\Command;

class CheckDwollaOperatingBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dwolla:check-operating-balance {--days=14 : Forecast horizon in days} {--force-notify : Ignore cooldown and send alerts} {--json : Output JSON only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Dwolla operating balance against payout forecast and notify on shortfall';

    public function handle(OperatingBalanceAlertService $service): int
    {
        try {
            $result = $service->evaluate(
                (int) $this->option('days'),
                (bool) $this->option('force-notify')
            );

            if ((bool) $this->option('json')) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return self::SUCCESS;
            }

            if (! ($result['enabled'] ?? true)) {
                $this->warn('Dwolla balance alerts are disabled by configuration.');
                return self::SUCCESS;
            }

            $this->info(sprintf(
                'Operating: %.2f | Required: %.2f | Forecast: %.2f | Breach: %s | Severity: %s | Notified: %s',
                (float) $result['operating_balance'],
                (float) $result['required_minimum'],
                (float) $result['forecast_total'],
                ($result['is_breach'] ?? false) ? 'yes' : 'no',
                (string) ($result['severity'] ?? '-'),
                ($result['notified'] ?? false) ? 'yes' : 'no'
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Dwolla operating balance check failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
