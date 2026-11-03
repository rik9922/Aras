<?php

namespace App\Observers;

use App\Models\AssignAmbassador;
use App\Services\EventAssignmentNotificationService;

class AssignAmbassadorObserver
{
    public function __construct(
        private readonly EventAssignmentNotificationService $assignmentNotificationService
    ) {}

    public function created(AssignAmbassador $assignment): void
    {
        if ($this->isApproved($assignment->status)) {
            $this->assignmentNotificationService->notifyApprovedAssignment($assignment);
        }
    }

    public function updated(AssignAmbassador $assignment): void
    {
        if (! $assignment->wasChanged('status')) {
            return;
        }

        if ($this->isApproved($assignment->status) && ! $this->isApproved($assignment->getOriginal('status'))) {
            $this->assignmentNotificationService->notifyApprovedAssignment($assignment);
        }
    }

    private function isApproved(mixed $status): bool
    {
        return $status === 1 || $status === '1';
    }
}
