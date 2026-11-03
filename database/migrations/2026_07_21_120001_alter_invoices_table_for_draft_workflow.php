<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            // ── 1. Relax the existing qb_invoice_id unique constraint ──────────
            // qb_invoice_id is null until the invoice is published to QuickBooks.
            $table->dropUnique(['qb_invoice_id']);
            $table->string('qb_invoice_id')->nullable()->change();

            // ── 2. Billing Party linkage ───────────────────────────────────────
            // Each invoice belongs to exactly one billing party.
            // Nullable for backward compatibility with legacy invoices.
            $table->foreignId('billing_party_id')
                ->nullable()
                ->after('event_id')
                ->constrained('event_billing_parties')
                ->nullOnDelete();

            // ── 3. Invoice metadata ────────────────────────────────────────────
            $table->string('terms')->nullable()->after('currency_code')
                ->comment('Payment terms: Net 30, Net 15, Due on Receipt, etc.');

            $table->string('po_number')->nullable()->after('terms')
                ->comment('Purchase Order number');

            $table->text('notes_to_customer')->nullable()->after('customer_memo')
                ->comment('Customer-facing notes shown on the invoice');

            // ── 4. Tax breakdown ───────────────────────────────────────────────
            $table->decimal('subtotal', 10, 2)->nullable()->after('total_amount')
                ->comment('Line items total before tax');

            $table->string('tax_code')->nullable()->after('subtotal')
                ->comment('e.g. SALES TAX, TAX, NON');

            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_code')
                ->comment('Tax rate percentage applied');

            $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate')
                ->comment('Calculated tax amount');

            // ── 5. QuickBooks publish tracking ─────────────────────────────────
            $table->string('qb_publish_status')->nullable()->after('sync_token')
                ->comment('pending | success | failed');

            $table->text('qb_publish_error')->nullable()->after('qb_publish_status')
                ->comment('Error message from QB on publish failure');

            $table->timestamp('qb_published_at')->nullable()->after('qb_publish_error')
                ->comment('When this invoice was successfully pushed to QuickBooks');

            // ── 6. Draft workflow tracking ─────────────────────────────────────
            $table->timestamp('draft_saved_at')->nullable()->after('qb_published_at')
                ->comment('When this invoice was last saved as a draft');

            // ── 7. Indexes ─────────────────────────────────────────────────────
            $table->index('billing_party_id');
            $table->index('qb_publish_status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['billing_party_id']);
            $table->dropIndex(['qb_publish_status']);

            $table->dropConstrainedForeignId('billing_party_id');

            $table->dropColumn([
                'terms',
                'po_number',
                'notes_to_customer',
                'subtotal',
                'tax_code',
                'tax_rate',
                'tax_amount',
                'qb_publish_status',
                'qb_publish_error',
                'qb_published_at',
                'draft_saved_at',
            ]);

            // Restore original non-nullable unique constraint
            $table->string('qb_invoice_id')->nullable(false)->change();
            $table->unique('qb_invoice_id');
        });
    }
};
