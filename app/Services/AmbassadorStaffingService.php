<?php

namespace App\Services;

use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AmbassadorStaffingService
{
    public const PINATA_BONUS_AMOUNT = 5;

    public function __construct(
        private readonly EventAssignmentNotificationService $eventAssignmentNotifications
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildStaffingRows(Event $event): array
    {
        $assignments = AssignAmbassador::query()
            ->with(['ambassador' => fn ($q) => $q->with('ambassadorProfile')])
            ->where('event_id', $event->id)
            ->orderByRaw("CASE WHEN status = '1' OR status = 1 THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($assignments as $assignment) {
            $ambassador = $assignment->ambassador;
            if (! $ambassador) {
                continue;
            }

            $isAssigned = $this->isAssignmentApproved($assignment);
            $rows[] = [
                'assign_id' => (int) $assignment->id,
                'ambassador_id' => (int) $assignment->ambassador_id,
                'name' => $ambassador->name ?: trim(($ambassador->first_name ?? '').' '.($ambassador->last_name ?? '')),
                'email' => $ambassador->email ?? '',
                'phone' => $ambassador->phone ?? '',
                'profile_image' => userProfileImageUrl($ambassador->getRawOriginal('profile_image')),
                'wage' => number_format((float) ($ambassador->wage ?? 0), 2, '.', ''),
                'assigned' => $isAssigned,
                'overbooked' => $this->isOverbooked((int) $assignment->ambassador_id, $event),
                'is_no_show' => (bool) ($assignment->is_no_show ?? false),
                'has_pinata' => (int) ($assignment->pinata_amount ?? 0) > 0,
                'override_pay' => (int) ($assignment->override_pay ?? 0),
                'additional_pay_reason' => (string) ($assignment->additional_pay_reason ?? ''),
                'additional_pay_note' => (string) ($assignment->additional_pay_note ?? ''),
            ];
        }

        return $rows;
    }

    public function isAssignmentApproved(AssignAmbassador $assignment): bool
    {
        $status = $assignment->status;

        return $status === 1 || $status === '1';
    }

    public function isOverbooked(int $ambassadorId, Event $event): bool
    {
        if (empty($event->start_time) || empty($event->end_time)) {
            return false;
        }

        $start = Carbon::parse($event->start_time);
        $end = Carbon::parse($event->end_time);

        return AssignAmbassador::query()
            ->where('ambassador_id', $ambassadorId)
            ->where('event_id', '!=', (int) $event->id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            })
            ->whereHas('event', function ($q) use ($start, $end) {
                $q->where('event_status', '!=', '2')
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->exists();
    }

    public function pinataAmountForRow(bool $eventPinataPay, bool $rowHasPinata): int
    {
        return ($eventPinataPay || $rowHasPinata) ? self::PINATA_BONUS_AMOUNT : 0;
    }

    /**
     * Persist staffing rows from the event form (create/update).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncStaffingFromForm(Event $event, array $rows, bool $eventPinataPay, array $removedAmbassadorIds = []): void
    {
        $removedAmbassadorIds = array_map('intval', array_filter($removedAmbassadorIds));
        $keptAmbassadorIds = [];

        if (! empty($removedAmbassadorIds)) {
            AssignAmbassador::query()
                ->where('event_id', $event->id)
                ->whereIn('ambassador_id', $removedAmbassadorIds)
                ->delete();
        }

        foreach ($rows as $row) {
            $ambassadorId = (int) ($row['ambassador_id'] ?? 0);
            if ($ambassadorId <= 0 || in_array($ambassadorId, $removedAmbassadorIds, true)) {
                continue;
            }

            $assignId = (int) ($row['assign_id'] ?? 0);
            $assignment = $assignId > 0
                ? AssignAmbassador::query()->where('event_id', $event->id)->where('id', $assignId)->first()
                : null;

            if (! $assignment) {
                $assignment = AssignAmbassador::query()
                    ->where('event_id', $event->id)
                    ->where('ambassador_id', $ambassadorId)
                    ->first();
            }

            $wasApproved = $assignment ? $this->isAssignmentApproved($assignment) : false;

            if (! $assignment) {
                $assignment = new AssignAmbassador;
                $assignment->event_id = $event->id;
                $assignment->ambassador_id = $ambassadorId;
            }

            $status = $row['status'] ?? $assignment->status ?? '0';
            $assignment->status = ($status === 1 || $status === '1') ? '1' : '0';
            $assignment->override_pay = (int) ($row['override_pay'] ?? $assignment->override_pay ?? 0);
            $assignment->additional_pay_reason = ! empty($row['additional_pay_reason'])
                ? (string) $row['additional_pay_reason']
                : null;
            $assignment->additional_pay_note = ! empty($row['additional_pay_note'])
                ? (string) $row['additional_pay_note']
                : null;
            $assignment->is_no_show = ! empty($row['is_no_show']);
            $rowPinata = ! empty($row['has_pinata']);
            $assignment->pinata_amount = ($rowPinata || $eventPinataPay)
                ? self::PINATA_BONUS_AMOUNT
                : 0;
            $assignment->save();

            if ($this->isAssignmentApproved($assignment)) {
                $keptAmbassadorIds[] = $ambassadorId;

                if (! $wasApproved) {
                    $this->eventAssignmentNotifications->notifyApprovedAssignment($assignment);
                }
            }
        }

        $event->ambassador_id = implode(',', array_unique($keptAmbassadorIds));
        $event->save();
    }

    /**
     * @param  array<int, string>  $ambassadorIds
     */
    public function attachNewAmbassadors(Event $event, array $ambassadorIds, array $overridePayMap, array $reasonMap, array $noteMap, bool $eventPinataPay, bool $approveImmediately = true): void
    {
        $existingCsv = array_filter(array_map('intval', explode(',', (string) ($event->ambassador_id ?? ''))));
        $approvedIds = $existingCsv;

        foreach ($ambassadorIds as $ambassadorId) {
            $ambassadorId = (int) $ambassadorId;
            if ($ambassadorId <= 0) {
                continue;
            }

            $assignment = AssignAmbassador::query()
                ->where('event_id', $event->id)
                ->where('ambassador_id', $ambassadorId)
                ->first();

            $wasApproved = $assignment ? $this->isAssignmentApproved($assignment) : false;

            if (! $assignment) {
                $assignment = new AssignAmbassador;
                $assignment->event_id = $event->id;
                $assignment->ambassador_id = $ambassadorId;
            }

            $overridePay = (int) ($overridePayMap[$ambassadorId] ?? $overridePayMap[(string) $ambassadorId] ?? 0);
            $assignment->override_pay = $overridePay;
            $assignment->additional_pay_reason = ! empty($reasonMap[$ambassadorId] ?? $reasonMap[(string) $ambassadorId] ?? null)
                ? (string) ($reasonMap[$ambassadorId] ?? $reasonMap[(string) $ambassadorId])
                : null;
            $assignment->additional_pay_note = ! empty($noteMap[$ambassadorId] ?? $noteMap[(string) $ambassadorId] ?? null)
                ? (string) ($noteMap[$ambassadorId] ?? $noteMap[(string) $ambassadorId])
                : null;
            $assignment->status = $approveImmediately ? '1' : ($assignment->status ?? '0');
            $assignment->is_no_show = false;
            $assignment->pinata_amount = $this->pinataAmountForRow($eventPinataPay, $eventPinataPay);
            $assignment->save();

            if ($approveImmediately) {
                $approvedIds[] = $ambassadorId;

                if (! $wasApproved) {
                    $this->eventAssignmentNotifications->notifyApprovedAssignment($assignment);
                }
            }
        }

        $event->ambassador_id = implode(',', array_values(array_unique(array_filter($approvedIds))));
        $event->save();
    }

    public function approveAssignment(AssignAmbassador $assignment, bool $applyEventPinata = true): void
    {
        $wasApproved = $this->isAssignmentApproved($assignment);
        $assignment->status = '1';
        if ($applyEventPinata && (int) ($assignment->pinata_amount ?? 0) === 0) {
            $event = $assignment->event;
            if ($event && (int) ($event->pinata_pay ?? 0) === 1) {
                $assignment->pinata_amount = self::PINATA_BONUS_AMOUNT;
            }
        }
        $assignment->save();

        $event = Event::find($assignment->event_id);
        if (! $event) {
            return;
        }

        $existingIds = array_filter(array_map('intval', explode(',', (string) ($event->ambassador_id ?? ''))));
        if (! in_array((int) $assignment->ambassador_id, $existingIds, true)) {
            $existingIds[] = (int) $assignment->ambassador_id;
        }
        $event->ambassador_id = implode(',', $existingIds);
        $event->save();

        if (! $wasApproved) {
            $this->eventAssignmentNotifications->notifyApprovedAssignment($assignment);
        }
    }

    public function removeAssignment(AssignAmbassador $assignment): void
    {
        $event = Event::find($assignment->event_id);
        $ambassadorId = (int) $assignment->ambassador_id;
        $assignment->delete();

        if (! $event) {
            return;
        }

        $existingIds = array_filter(array_map('intval', explode(',', (string) ($event->ambassador_id ?? ''))));
        $existingIds = array_values(array_diff($existingIds, [$ambassadorId]));
        $event->ambassador_id = implode(',', $existingIds);
        $event->save();
    }

    /**
     * Move an approved assignment back to pending (unassign from event roster).
     */
    public function unassignAssignment(AssignAmbassador $assignment): void
    {
        $assignment->status = '0';
        $assignment->save();

        $event = Event::find($assignment->event_id);
        if (! $event) {
            return;
        }

        $existingIds = array_filter(array_map('intval', explode(',', (string) ($event->ambassador_id ?? ''))));
        $existingIds = array_values(array_diff($existingIds, [(int) $assignment->ambassador_id]));
        $event->ambassador_id = implode(',', $existingIds);
        $event->save();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function decodeStaffingPayload(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
