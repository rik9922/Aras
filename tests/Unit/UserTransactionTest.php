<?php

namespace Tests\Unit;

use App\Models\AmbassadorPayout;
use App\Models\DwollaFundingSource;
use App\Models\Reimbursement;
use App\Models\UserTransaction;
use PHPUnit\Framework\TestCase;

class UserTransactionTest extends TestCase
{
    public function test_it_labels_recap_payout_transactions(): void
    {
        $transaction = new UserTransaction([
            'type' => 'payout',
            'source_type' => AmbassadorPayout::class,
        ]);

        $this->assertSame('Event Pay', $transaction->type_label);
    }

    public function test_it_labels_reimbursement_transactions(): void
    {
        $transaction = new UserTransaction([
            'type' => 'reimbursement',
            'source_type' => Reimbursement::class,
        ]);

        $this->assertSame('Reimbursement', $transaction->type_label);
    }

    public function test_it_normalizes_paid_transaction_statuses(): void
    {
        $completedTransaction = new UserTransaction(['status' => 'completed']);
        $paidTransaction = new UserTransaction(['status' => 'paid']);

        $this->assertSame('Paid', $completedTransaction->status_label);
        $this->assertSame('Paid', $paidTransaction->status_label);
    }

    public function test_it_reads_bank_details_from_transaction_meta(): void
    {
        $transaction = new UserTransaction([
            'meta' => [
                'bank_name' => 'Test Bank',
                'account_last4' => '6789',
            ],
        ]);

        $this->assertSame('Test Bank', $transaction->bank_name);
        $this->assertSame('6789', $transaction->bank_account_last4);
    }

    public function test_it_reads_bank_details_from_payout_funding_source(): void
    {
        $fundingSource = new DwollaFundingSource([
            'bank_name' => 'Event Pay Bank',
            'account_number' => '1234567890',
        ]);

        $payout = new AmbassadorPayout();
        $payout->setRelation('fundingSource', $fundingSource);

        $transaction = new UserTransaction([
            'source_type' => AmbassadorPayout::class,
        ]);
        $transaction->setRelation('source', $payout);

        $this->assertSame('Event Pay Bank', $transaction->bank_name);
        $this->assertSame('7890', $transaction->bank_account_last4);
    }

    public function test_it_reads_bank_details_from_reimbursement_funding_source(): void
    {
        $fundingSource = new DwollaFundingSource([
            'bank_name' => 'Reimbursement Bank',
            'account_number' => '9876543210',
        ]);

        $reimbursement = new Reimbursement();
        $reimbursement->setRelation('fundingSource', $fundingSource);

        $transaction = new UserTransaction([
            'source_type' => Reimbursement::class,
        ]);
        $transaction->setRelation('source', $reimbursement);

        $this->assertSame('Reimbursement Bank', $transaction->bank_name);
        $this->assertSame('3210', $transaction->bank_account_last4);
    }
}
