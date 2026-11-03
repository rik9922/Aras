<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EventService {

    public function markEndedEventsCompletedForApi(): void
    {
        Event::query()
            ->whereNotNull('end_time')
            ->where(function ($query) {
                $query->whereNull('event_status')
                    ->orWhereNotIn('event_status', ['1', '2']);
            })
            ->select(['id', 'end_time', 'timezone', 'event_status'])
            ->chunkById(200, function ($events) {
                foreach ($events as $event) {
                    if (! $this->hasEventEndedInTimezone($event)) {
                        continue;
                    }

                    $event->event_status = '1';
                    $event->saveQuietly();
                }
            });
    }

    private function hasEventEndedInTimezone(Event $event): bool
    {
        if (empty($event->end_time)) {
            return false;
        }

        $eventTimezone = app(EventTimezoneService::class)->normalizeIana($event->timezone);
        $end = Carbon::parse($event->end_time, $eventTimezone);

        return Carbon::now($eventTimezone)->greaterThanOrEqualTo($end);
    }

    public function brandTotalEventHour() {
        $brandId = Auth::user()->brand?->id;
        $getEvents = Event::query()->forBrandOrDescendants($brandId)->get();

        $totalMinutes = 0;

        foreach($getEvents as $event) {
            $startTime = Carbon::parse($event->start_time);
            $endTime = Carbon::parse($event->end_time);
            $diffInMinutes = $startTime->diffInMinutes($endTime);
            $totalMinutes += $diffInMinutes;
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $totalHours = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Hrs';

        return $totalHours;
    }

    public function getTotalHoursLocation() {
        $getEvents = Event::where('event_location_id',Auth::id())->get();
        $totalMinutes = 0;

        foreach($getEvents as $event) {
            $startTime = Carbon::parse($event->start_time);
            $endTime = Carbon::parse($event->end_time);
            $diffInMinutes = $startTime->diffInMinutes($endTime);
            $totalMinutes += $diffInMinutes;
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $totalHours = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Hrs';

        return $totalHours;
    }

    // public function getTotalHoursClient() {
    //     $getEvents = Event::where('client_id',Auth::id())->get();
    //     $totalMinutes = 0;

    //     foreach($getEvents as $event) {
    //         $startTime = Carbon::parse($event->start_time);
    //         $endTime = Carbon::parse($event->end_time);
    //         $diffInMinutes = $startTime->diffInMinutes($endTime);
    //         $totalMinutes += $diffInMinutes;
    //     }

    //     $hours = floor($totalMinutes / 60);
    //     $minutes = $totalMinutes % 60;

    //     $totalHours = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Hrs';

    //     return $totalHours;
    // }
    
    public function getTotalHoursClient() {
        $getEvents = Event::where('client_id', Auth::id())->get();
        $totalMinutes = 0;

        foreach($getEvents as $event) {
            $startTime = Carbon::parse($event->start_time);
            $endTime = Carbon::parse($event->end_time);
    
            // If the end time is earlier, swap them.
            if ($endTime->lt($startTime)) {
                $temp = $startTime;
                $startTime = $endTime;
                $endTime = $temp;
            }
    
            $diffInMinutes = $startTime->diffInMinutes($endTime);
            $totalMinutes += $diffInMinutes;
        }
    
        // Convert total minutes to hours and minutes.
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
    
        // Ensure proper formatting.
        $totalHours = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Hrs';
    
        return $totalHours;
    }
    
    
    public function getTotalEventHour()
    {
        $totalMinutes = 0;
        $eventMinutes = 0;

        // Avoid loading the entire events table into memory (can exceed 128MB on large datasets).
        Event::query()
            ->select(['id', 'start_time', 'end_time', 'ambassador_id'])
            ->orderBy('id')
            ->chunkById(1000, function ($events) use (&$totalMinutes, &$eventMinutes) {
                foreach ($events as $event) {
                    $startTime = Carbon::parse($event->start_time);
                    $endTime = Carbon::parse($event->end_time);
                    $diffInMinutes = $startTime->diffInMinutes($endTime);

                    // Count ambassadors
                    $ambassadorCount = count(array_filter(explode(',', (string) ($event->ambassador_id ?? ''))));

                    $eventMinutes += $diffInMinutes; // pure event duration
                    $totalMinutes += $diffInMinutes * $ambassadorCount; // multiplied by ambassador count
                }
            });
    
        // Format function
        $formatTime = function ($minutes) {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($remainingMinutes, 2, '0', STR_PAD_LEFT);
        };
    
        return [
            'event_hours' => $formatTime($eventMinutes),
            'total_hours' => $formatTime($totalMinutes),
        ];
    }

}
