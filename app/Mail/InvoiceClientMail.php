<?php

namespace App\Mail;

use App\Models\Invoice;

class InvoiceClientMail extends ArasBrandedMail
{
    public function __construct(public Invoice $invoice)
    {
        $clientName  = $invoice->customer_name ?? 'Client';
        $invoiceNum  = $invoice->doc_number ?: ('INV-' . $invoice->id);
        $total       = '$' . number_format((float) $invoice->total_amount, 2);
        $dueDate     = $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—';

        // Receipt percent from billing party client profile
        $receiptPercent = (int) ($invoice->billingParty?->client?->clientProfile?->default_receipt_percent ?? 0);

        $lines = '';
        $displaySubtotal = 0.0;
        foreach ($invoice->lineItems as $li) {
            // Use clean service name — never expose internal calculation descriptions
            $label  = $li->service?->name ?? $li->description;
            $amount = (float) $li->amount;
            $displaySubtotal += $amount;

            $lines .= '<tr>
                <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;">' . e($label) . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;text-align:right;">$' . number_format($amount, 2) . '</td>
            </tr>';

            // Receipt Percent row immediately after Buyback
            if ($li->service && mb_strtolower($li->service->name) === 'buyback' && $receiptPercent > 0 && $amount > 0) {
                $receiptAmt       = round($amount * ($receiptPercent / 100), 2);
                $displaySubtotal += $receiptAmt;
                $lines .= '<tr style="background:#f9fafb;">
                    <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;font-style:italic;color:#4b5563;">Receipt Percent (' . $receiptPercent . '%)</td>
                    <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;text-align:right;font-style:italic;color:#4b5563;">$' . number_format($receiptAmt, 2) . '</td>
                </tr>';
            }
        }

        $subtotalRow = (round($displaySubtotal, 2) != (float)$invoice->total_amount)
            ? '<tr><td style="padding:6px 10px;text-align:right;color:#6b7280;">Subtotal</td><td style="padding:6px 10px;text-align:right;font-weight:600;">$' . number_format($displaySubtotal, 2) . '</td></tr>'
            : '';
        $taxRow = '<tr><td style="padding:6px 10px;text-align:right;color:#6b7280;">Tax</td><td style="padding:6px 10px;text-align:right;color:#9ca3af;font-style:italic;">Calculated separately</td></tr>';
        $totalRow = '<tr style="border-top:2px solid #0f2745;"><td style="padding:8px 10px;text-align:right;font-weight:800;font-size:14px;color:#0f2745;">Total</td><td style="padding:8px 10px;text-align:right;font-weight:800;font-size:14px;color:#0f2745;">' . $total . '</td></tr>';

        $tableHtml = $lines ? '
        <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:13px;">
            <thead>
                <tr style="background:#0f2745;">
                    <th style="padding:9px 10px;text-align:left;color:#fff;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">Description</th>
                    <th style="padding:9px 10px;text-align:right;color:#fff;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">Amount</th>
                </tr>
            </thead>
            <tbody>' . $lines . '</tbody>
            <tfoot>' . $subtotalRow . $taxRow . $totalRow . '</tfoot>
        </table>' : '';

        $body = '
        <p>Please find your invoice details below.</p>
        <table style="width:100%;font-size:13px;margin-bottom:12px;">
            <tr><td style="color:#6b7280;padding:3px 0;width:130px;">Invoice #</td><td><strong>' . e($invoiceNum) . '</strong></td></tr>
            <tr><td style="color:#6b7280;padding:3px 0;">Invoice Date</td><td>' . ($invoice->invoice_date ? $invoice->invoice_date->format('M d, Y') : '—') . '</td></tr>
            <tr><td style="color:#6b7280;padding:3px 0;">Due Date</td><td><strong style="color:#dc2626;">' . $dueDate . '</strong></td></tr>
            <tr><td style="color:#6b7280;padding:3px 0;">Total Amount</td><td><strong>' . $total . '</strong></td></tr>
        </table>
        ' . $tableHtml . '
        ' . ($invoice->notes_to_customer ? '<p style="margin-top:12px;font-size:13px;color:#374151;"><strong>Note:</strong> ' . e($invoice->notes_to_customer) . '</p>' : '') . '
        <p style="margin-top:16px;font-size:13px;color:#6b7280;">If you have any questions about this invoice, please contact us.</p>';

        parent::__construct(
            subjectLine:   'Invoice #' . $invoiceNum . ' from Aras Promotions',
            recipientName: $clientName,
            bodyHtml:      $body,
            emailTitle:    'Invoice #' . $invoiceNum,
        );
    }
}
