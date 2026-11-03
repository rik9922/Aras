<?php

namespace App\Services;

use App\Models\AmbassadorPayout;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AmbassadorNotificationService
{
    public const TYPE_EVENT_CHECK_IN = 'ambassador_event_check_in';
    public const TYPE_PAYMENT_SETUP_COMPLETED = 'payment_setup_completed';
    public const TYPE_EVENT_PAYOUT_RECEIVED = 'event_payout_received';
    public const TYPE_REIMBURSEMENT_PAYMENT_RECEIVED = 'reimbursement_payment_received';

    public function __construct(
        private readonly NotificationDispatchService $notificationDispatch
    ) {}

    public function eventCheckIn(User $ambassador, Event $event, AssignAmbassador $assignment): void
    {
        $eventName = (string) ($event->event_name ?? 'Event');

        $this->dispatchOnce(
            null,
            (int) $ambassador->id,
            self::TYPE_EVENT_CHECK_IN,
            'Event Check-in Complete',
            "You have checked in to {$eventName}.",
            (int) $assignment->id
        );
    }

    public function paymentSetupCompleted(User $ambassador): void
    {
        $this->dispatchOnce(
            null,
            (int) $ambassador->id,
            self::TYPE_PAYMENT_SETUP_COMPLETED,
            'Payment Setup Completed',
            'Your payment setup has been completed.',
            (int) $ambassador->id
        );
    }

    public function eventPayoutReceived(AmbassadorPayout $payout): void
    {
        $payout->loadMissing(['user', 'event']);

        if (! $payout->user) {
            return;
        }

        $amount = number_format((float) $payout->amount, 2);
        $eventName = $payout->event?->event_name;
        $message = 'Your ambassador payment for $'.$amount
            .($eventName ? ' - '.$eventName : '')
            .' has been completed. Ref: '.$payout->reference;

        $this->dispatchOnce(
            null,
            (int) $payout->user_id,
            self::TYPE_EVENT_PAYOUT_RECEIVED,
            'Ambassador Payment Completed',
            $message,
            (int) $payout->id
        );
    }

    public function reimbursementPaymentReceived(Reimbursement $reimbursement): void
    {
        $reimbursement->loadMissing(['user', 'event']);

        if (! $reimbursement->user) {
            return;
        }

        $amount = number_format((float) $reimbursement->amount, 2);
        $eventName = $reimbursement->event?->event_name;
        $message = 'Your reimbursement for $'.$amount
            .($eventName ? ' - '.$eventName : '')
            .' has been completed. Ref: REIMB-'.$reimbursement->id;

        $this->dispatchOnce(
            null,
            (int) $reimbursement->user_id,
            self::TYPE_REIMBURSEMENT_PAYMENT_RECEIVED,
            'Reimbursement Payment Completed',
            $message,
            (int) $reimbursement->id
        );
    }

    private function dispatchOnce(
        ?int $senderId,
        int $receiverId,
        string $type,
        string $title,
        string $message,
        int $bookingId
    ): void {
        if ($this->alreadySent($receiverId, $type, $bookingId)) {
            return;
        }

        try {
            $this->notificationDispatch->dispatch(
                $senderId,
                $receiverId,
                $type,
                $title,
                $message,
                null,
                $bookingId
            );
        } catch (\Throwable $e) {
            Log::warning('Ambassador notification failed', [
                'receiver_id' => $receiverId,
                'type' => $type,
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function alreadySent(int $receiverId, string $type, int $bookingId): bool
    {
        if (! Schema::hasTable('notifications')) {
            return false;
        }

        return Notification::query()
            ->where('receiver_id', $receiverId)
            ->where('type', $type)
            ->where('booking_id', $bookingId)
            ->exists();
    }
}
