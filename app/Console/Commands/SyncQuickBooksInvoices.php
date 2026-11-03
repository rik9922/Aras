<?php

namespace App\Console\Commands;

use App\Services\QuickBooksService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncQuickBooksInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:sync-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync invoices from QuickBooks to local database';

    protected $quickBooksService;

    public function __construct(QuickBooksService $quickBooksService)
    {
        parent::__construct();
        $this->quickBooksService = $quickBooksService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!Cache::has('qb_access_token') || !Cache::has('qb_realm_id')) {
            $this->error('QuickBooks is not connected. Please connect first.');
            return 1;
        }

        $this->info('Starting QuickBooks invoice sync...');
        
        $success = $this->quickBooksService->syncInvoices();
        
        if ($success) {
            $this->info('QuickBooks invoices synced successfully!');
            return 0;
        } else {
            $this->error('Failed to sync QuickBooks invoices.');
            return 1;
        }
    }
}
