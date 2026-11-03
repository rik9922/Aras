<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; margin: 20px 0; }
        .invoice-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Ambassador Payout Invoice</h2>
        </div>
        
        <div class="content">
            <p>Hello {{ $ambassador->name }},</p>
            
            <p>Your payout has been processed and an invoice has been generated.</p>
            
            <div class="invoice-details">
                <h3>Invoice Details</h3>
                <table>
                    <tr>
                        <th>Invoice Number:</th>
                        <td>{{ $invoice->doc_number ?? 'Pending' }}</td>
                    </tr>
                    <tr>
                        <th>Reference:</th>
                        <td>{{ $payout->reference }}</td>
                    </tr>
                    <tr>
                        <th>Event:</th>
                        <td>{{ $event->event_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Amount:</th>
                        <td>${{ number_format($payout->amount, 2) }} {{ $payout->currency }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>{{ ucfirst($payout->status) }}</td>
                    </tr>
                    <tr>
                        <th>Date:</th>
                        <td>{{ $payout->approved_at->format('M d, Y') }}</td>
                    </tr>
                </table>
            </div>
            
            <p>The payment will be transferred to your registered bank account shortly.</p>
            
            <p>If you have any questions, please contact our support team.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
