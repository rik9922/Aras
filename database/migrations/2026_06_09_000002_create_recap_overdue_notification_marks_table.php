<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recap_overdue_notification_marks')) {
            return;
        }

        Schema::create('recap_overdue_notification_marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_role', 32);
            $table->date('notified_date');
            $table->timestamps();

            $table->unique(
                ['event_id', 'recipient_id', 'recipient_role', 'notified_date'],
                'ronm_event_recipient_date_unique'
            );
            $table->index(['notified_date', 'recipient_role'], 'ronm_date_role_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recap_overdue_notification_marks');
    }
};
