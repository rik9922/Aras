<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedPayoutsJob;
use Illuminate\Console\Command;

class RetryFailedPayoutsCommand extends Command
{
    protected $signature = 'payouts:retry-failed';
    protected $description = 'Retry all failed ambassador payouts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Dispatch the job
        RetryFailedPayoutsJob::dispatch();
        $this->info('RetryFailedPayoutsJob dispatched!');
    }
}
