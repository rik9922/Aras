<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Recap;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ReviewNotificationService
{
    public function __construct(
        private readonly NotificationDispatchService $notifications
    ) {}

    public function reimbursementSubmitted(User $submitter, Reimbursement $reimbursement): void
    {
        $this->notifyReimbursementReviewers(
            $submitter,
            $reimbursement,
            'Reimbursement Request Submitted',
            'reimbursement_submitted',
            'submitted'
        );
    }

    public function reimbursementUpdated(User $submitter, Reimbursement $reimbursement): void
    {
        $this->notifyReimbursementReviewers(
            $submitter,
            $reimbursement,
            'Reimbursement Request Updated',
            'reimbursement_updated',
            'updated and resubmitted'
        );
    }

    public function recapSubmitted(User $submitter, Recap $recap): void
    {
        $recap->loadMissing('event.brand', 'event.location');
        $event = $recap->event;
        $eventName = $event?->event_name ?: 'Event #'.$recap->event_id;

        foreach ($this->reviewers() as $reviewer) {
            $reviewUrl = (int) $reviewer->role_id === 1
                ? route('admin.view-recap', $recap->id)
                : route('manager.view-recap', $recap->id);

            $content = '<b>'.$this->submitterName($submitter).'</b> submitted a recap.'
                .'<br><br>'
                .'<b>Event:</b> '.$eventName.'<br>'
                .($recap->event_id ? '<b>Event ID:</b> '.$recap->event_id.'<br>' : '')
                .($event?->brand?->brand_name ? '<b>Brand:</b> '.$event->brand->brand_name.'<br>' : '')
                .($event?->location?->address ? '<b>Location:</b> '.$event->location->address.'<br>' : '')
                .'<br>'
                .'Open and review it here: <a href="'.$reviewUrl.'">'.$reviewUrl.'</a>';

            $this->dispatchOnce(
                (int) $submitter->id,
                (int) $reviewer->id,
                'recap_submitted',
                'Recap Submitted',
                $this->plainMessage($content),
                $content,
                (int) $recap->id
            );
        }
    }

    private function notifyReimbursementReviewers(
        User $submitter,
        Reimbursement $reimbursement,
        string $title,
        string $type,
        string $verb
    ): void {
        foreach ($this->reviewers() as $reviewer) {
            $reviewUrl = (int) $reviewer->role_id === 1
                ? route('admin.reimbursements.show', $reimbursement)
                : route('manager.reimbursements.show', $reimbursement);

            $content = 'A reimbursement request has been <b>'.$verb.'</b> by <b>'.$this->submitterName($submitter).'</b>.'
                .'<br><br>'
                .'<b>Amount:</b> $'.number_format((float) $reimbursement->amount, 2).'<br>'
                .'<b>Date:</b> '.$reimbursement->expense_date->format('M d, Y').'<br>'
                .($reimbursement->event_id ? '<b>Event ID:</b> '.$reimbursement->event_id.'<br>' : '')
                .'<br>'
                .'Open and review it here: <a href="'.$reviewUrl.'">'.$reviewUrl.'</a>';

            $this->dispatchOnce(
                (int) $submitter->id,
                (int) $reviewer->id,
                $type,
                $title,
                $this->plainMessage($content),
                $content,
                (int) $reimbursement->id
            );
        }
    }

    private function dispatchOnce(
        ?int $senderId,
        int $receiverId,
        string $type,
        string $title,
        string $message,
        ?string $emailContentHtml,
        ?int $bookingId
    ): void {
        if (Schema::hasTable('notifications') && $bookingId !== null) {
            $alreadyExists = Notification::query()
                ->where('receiver_id', $receiverId)
                ->where('type', $type)
                ->where('booking_id', $bookingId)
                ->exists();

            if ($alreadyExists) {
                return;
            }
        }

        $this->notifications->dispatch(
            $senderId,
            $receiverId,
            $type,
            $title,
            $message,
            $emailContentHtml,
            $bookingId
        );
    }

    private function reviewers()
    {
        return User::query()
            ->whereIn('role_id', [1, 6])
            ->get(['id', 'name', 'first_name', 'last_name', 'email', 'role_id']);
    }

    private function submitterName(User $submitter): string
    {
        $name = trim(($submitter->last_name ?? '').', '.($submitter->first_name ?? ''));

        return trim($name, ', ') ?: ($submitter->name ?? 'Ambassador');
    }

    private function plainMessage(string $content): string
    {
        $message = trim(preg_replace('/\s+/', ' ', strip_tags($content)));

        return mb_substr($message, 0, 200);
    }
}
