<?php

namespace App\Mail;

use App\Models\AmbassadorPayout;
use App\Models\Reimbursement;

class AmbassadorInvoiceMail extends ArasBrandedMail
{
    public function __construct(
        public AmbassadorPayout|Reimbursement $payment,
    ) {
        $isReimbursement = $payment instanceof Reimbursement;
        $reference = $isReimbursement
            ? 'REIMB-'.$payment->id
            : $payment->reference;

        parent::__construct(
            subjectLine: ($isReimbursement ? 'Reimbursement Payment Completed - ' : 'Ambassador Payout Completed - ').$reference,
            recipientName: self::resolveFirstName($payment->user),
            bodyHtml: view('email.partials.ambassador-invoice-body', [
                'payment' => $payment,
                'payout' => $isReimbursement ? null : $payment,
                'reimbursement' => $isReimbursement ? $payment : null,
                'ambassador' => $payment->user,
                'event' => $payment->event,
                'brand' => $isReimbursement ? $payment->brand : $payment->event?->brand,
                'fundingSource' => $payment->fundingSource,
                'invoice' => $isReimbursement ? null : $payment->invoice,
            ])->render(),
            emailTitle: $isReimbursement ? 'Reimbursement Payment Completed' : 'Ambassador Payout Completed',
        );
    }
}
