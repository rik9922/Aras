<?php

namespace App\Console\Commands;

use App\Services\Forecast\PayrollForecastService;
use Illuminate\Console\Command;

class ForecastPayrollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forecast:payroll {--days=14 : Forecast horizon in days} {--json : Output JSON only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compute payout forecast from ambassador payouts and reimbursements';

    public function handle(PayrollForecastService $service): int
    {
        $days = (int) $this->option('days');
        $forecast = $service->forecastForNextDays($days);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($forecast, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info("Payroll forecast for next {$forecast['days']} days ({$forecast['window_start']} -> {$forecast['window_end']})");
        $this->newLine();

        $this->table(
            ['Bucket', 'Statuses', 'Count', 'Total (USD)'],
            [
                [
                    'Ambassador Payouts',
                    implode(', ', $forecast['ambassador_payouts']['statuses']),
                    $forecast['ambassador_payouts']['count'],
                    number_format((float) $forecast['ambassador_payouts']['total'], 2),
                ],
                [
                    'Reimbursements',
                    implode(', ', $forecast['reimbursements']['statuses']),
                    $forecast['reimbursements']['count'],
                    number_format((float) $forecast['reimbursements']['total'], 2),
                ],
                [
                    'Grand Total',
                    '-',
                    $forecast['grand_count'],
                    number_format((float) $forecast['grand_total'], 2),
                ],
            ]
        );

        return self::SUCCESS;
    }
}
