<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Invoice source constants
    public const SOURCE_APP         = 'app';
    public const SOURCE_QUICKBOOKS  = 'quickbooks';

    protected $casts = [
        'line_items'        => 'array',
        'billing_address'   => 'array',
        'shipping_address'  => 'array',
        'total_amount'      => 'decimal:2',
        'subtotal'          => 'decimal:2',
        'balance'           => 'decimal:2',
        'tax_rate'          => 'decimal:2',
        'tax_amount'        => 'decimal:2',
        'due_date'          => 'date',
        'invoice_date'      => 'date',
        'receipt_required'  => 'boolean',
        'created_time'      => 'datetime',
        'last_updated_time' => 'datetime',
        'qb_published_at'   => 'datetime',
        'draft_saved_at'    => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function billingParty()
    {
        return $this->belongsTo(\App\Models\EventBillingParty::class, 'billing_party_id');
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
    }

    public function isPublished(): bool
    {
        return $this->qb_publish_status === 'success' && $this->qb_invoice_id !== null;
    }

    public function isDraft(): bool
    {
        return $this->qb_publish_status === null || $this->qb_publish_status === 'pending';
    }

    /**
     * Generate the next globally unique invoice number.
     * Format: INV-{YEAR}-{SEQUENCE}
     * Race-condition safe: uses a DB-level lock so concurrent requests
     * never produce the same number.
     *
     * @return string  e.g. "INV-2026-0042"
     */
    public static function nextDocNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'INV-' . $year . '-';

        return \Illuminate\Support\Facades\DB::transaction(function () use ($prefix, $year) {
            // Lock the table for the duration of this transaction so no other
            // request can read the same MAX and produce a duplicate.
            $max = self::where('doc_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->get(['doc_number'])
                ->map(function ($inv) use ($prefix) {
                    $seq = substr($inv->doc_number, strlen($prefix));
                    return is_numeric($seq) ? (int) $seq : 0;
                })
                ->max() ?? 0;

            return $prefix . str_pad((int) $max + 1, 4, '0', STR_PAD_LEFT);
        });
    }
}
