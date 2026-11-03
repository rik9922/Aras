<?php

namespace App\Models;

use App\Services\EventTimezoneService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Brand;
class Event extends Model
{
    use SoftDeletes;


    protected $table = 'events';
    protected $guarded = [];

    protected $dates = ['deleted_at'];

    public function getBookedByDisplayAttribute(): string
    {
        $raw = $this->booked_by;
        if (is_array($raw)) {
            $list = $raw;
        } else {
            $decoded = json_decode((string) $raw, true);
            $list = is_array($decoded) ? $decoded : (empty($raw) ? [] : [$raw]);
        }

        return implode(', ', array_values(array_filter(
            array_map(fn ($value) => trim((string) $value), $list),
            fn ($value) => $value !== ''
        )));
    }

    public function getDaynameAttribute()
    {
        if (empty($this->start_time)) {
            return '';
        }
        // Parse in the event's own timezone so late-night events in negative-offset
        // zones don't roll back to the previous day when parsed as UTC.
        $iana = app(EventTimezoneService::class)->normalizeIana($this->timezone);
        return Carbon::parse($this->start_time, $iana)->format('D');
    }

    public function getFormattedDateAttribute()
    {
        // Show the event's scheduled date (from start_time), not the booked_date.
        // booked_date is the date the record was created; start_time is the actual
        // event date. Parse in the event timezone so the date is never shifted.
        if (empty($this->start_time)) {
            return '';
        }
        $iana = app(EventTimezoneService::class)->normalizeIana($this->timezone);
        return Carbon::parse($this->start_time, $iana)->format('M d, Y');
    }

    public function getFormattedTimeAttribute()
    {
        $timezones = app(EventTimezoneService::class);
        $iana = $timezones->normalizeIana($this->timezone);
        $label = $this->timezone_label ?: $timezones->abbreviationAt($iana, $this->start_time);
        $startTime = Carbon::parse($this->start_time, $iana);
        $endTime = Carbon::parse($this->end_time, $iana);

        $timeRange = '<b>From:</b> ' . $startTime->format('h:i A') . ' <b>To:</b> ' . $endTime->format('h:i A') . ' ' . $label;

        if (($this->admin_status ?? null) === 'cancelled_2_3_business_days_fee_15') {
            $totalHours = '00:00 Hrs';
        } else {
            $duration = $startTime->diff($endTime);
            $totalHours = str_pad($duration->h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($duration->i, 2, '0', STR_PAD_LEFT) . ' Hrs';
        }

        return $timeRange . ', <b>Total hour:</b> ' . $totalHours;
    }

    public function getFormattedTimeWithDateAttribute(): string
    {
        $formatted = app(EventTimezoneService::class)->formatEventTimeRange($this);

        return $formatted ?? '';
    }

    public function getTotalHoursAttribute()
    {
        // $15 cancellation fee should display 0 hours in the portal.
        if (($this->admin_status ?? null) === 'cancelled_2_3_business_days_fee_15') {
            return '00:00:00';
        }

        $eventTimezone = app(EventTimezoneService::class)->normalizeIana($this->timezone);
        $startTime = Carbon::parse($this->start_time, $eventTimezone);
        $endTime = Carbon::parse($this->end_time, $eventTimezone);
        $duration = $startTime->diff($endTime);
        $totalHours = str_pad($duration->h, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($duration->i, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($duration->s, 2, '0', STR_PAD_LEFT);

        return $totalHours;
    }

    /**
     * Billable minutes = event duration (minutes) × assigned BA count.
     */
    public function getBillableMinutesAttribute(): int
    {
        // $15 cancellation fee should not show billable hours.
        if (($this->admin_status ?? null) === 'cancelled_2_3_business_days_fee_15') {
            return 0;
        }

        $eventTimezone = app(EventTimezoneService::class)->normalizeIana($this->timezone);
        $startRaw = $this->start_time;
        $endRaw = $this->end_time;
        if (! $startRaw || ! $endRaw) {
            return 0;
        }

        $start = Carbon::parse($startRaw, $eventTimezone);
        $end = Carbon::parse($endRaw, $eventTimezone);
        $durationMinutes = $start->diffInMinutes($end);

        $ambassadorCount = count(array_filter(explode(',', (string) ($this->ambassador_id ?? ''))));
        // When no ambassadors are assigned yet (event just created), default to 1
        // so the event duration is shown as billable hours rather than 0.
        if ($ambassadorCount < 1) {
            $ambassadorCount = 1;
        }

        return $durationMinutes * $ambassadorCount;
    }

    /**
     * Billable hours formatted as "HH:MM Hrs".
     */
    public function getBillableHoursFormattedAttribute(): string
    {
        $minutes = (int) ($this->billable_minutes ?? 0);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT) . ' Hrs';
    }


    public function getAmbassadorAmount($wagePerHour)
    {
        $eventTimezone = app(EventTimezoneService::class)->normalizeIana($this->timezone);
        $startTime = Carbon::parse($this->start_time, $eventTimezone);
        $endTime = Carbon::parse($this->end_time, $eventTimezone);

        $minutes = $startTime->diffInMinutes($endTime);
        $hours = $minutes / 60;

        return round($hours * $wagePerHour, 2);
    }

    /**
     * True when the event's scheduled start instant has been reached.
     * Interprets stored start_time in the event's timezone (same convention as formatted time accessors).
     */
    public function hasRecapStartTimePassed(): bool
    {
        if (empty($this->start_time)) {
            return false;
        }

        $eventTimezone = app(EventTimezoneService::class)->normalizeIana($this->timezone);
        $start = Carbon::parse($this->start_time, $eventTimezone);

        return Carbon::now($eventTimezone)->greaterThanOrEqualTo($start);
    }

    // public function getAmbassadorIdAttribute($value)
    // {
    //     if(!empty($value)){
    //         $ambassadorId =  explode(',', $value);
    //         if($ambassadorId && is_array($ambassadorId)) {
    //             $ambassadors  = User::whereIn('id',$ambassadorId)->get();
    //             return $ambassadors;
    //         }
    //         return [];
    //     }
    //     return [];
    // }

    public function ambassador()
    {
        return $this->belongsToMany(User::class, 'assign_ambassadors', 'event_id', 'ambassador_id')->wherePivot('status', '1');
    }

    public function venueType()
    {
        return $this->belongsTo(Venue::class);
    }

    public function department()
    {
        return $this->belongsTo(EventDepartment::class);
    }

    public function distributor()
    {
        return $this->belongsTo(EventDistributor::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    /**
     * Filter events by a selected brand ID.
     * If the brand is a parent brand, include all descendant brands automatically.
     */
    public function scopeForBrandOrDescendants($query, $brandId)
    {
        $brandId = (int) $brandId;
        if (! $brandId) {
            return $query;
        }

        $ids = Brand::descendantIdsIncludingSelfById($brandId);
        if (count($ids) <= 1) {
            return $query->where('brand_id', $brandId);
        }

        return $query->whereIn('brand_id', $ids);
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class, 'event_type_id', 'id');
    }

    public function resolveRecapType(): string
    {
        return \App\Services\RecapFormService::resolveFromEvent($this);
    }

    public function location()
    {
        // BUG-6 (MC cmon1a7u4005d3r1h5cdct2vh): the admin event-create form
        // populates the Venue dropdown from EventLocation rows ($states in the
        // controller), so events.venue_name stores an event_locations.id. The
        // relation was previously pointing at User::class, which caused the
        // home/list event page to display the wrong address (whichever User row
        // happened to share the id) instead of the chosen venue's address.
        return $this->belongsTo(EventLocation::class, 'event_location_id', 'id');
    }

    public function eventAttachments()
    {
        return $this->hasMany(Attachement::class, 'event_id', 'id');
    }

    public function assigneAmbassador()
    {
        return $this->hasMany(AssignAmbassador::class, 'event_id', 'id');
    }

    /**
     * Ambassador applications awaiting admin approval (status 0 / "0" / NULL).
     */
    public function pendingAmbassadorApplications()
    {
        return $this->hasMany(AssignAmbassador::class, 'event_id', 'id')
            ->where(function ($q) {
                $q->where('status', 0)
                    ->orWhere('status', '0')
                    ->orWhereNull('status');
            });
    }

    public function assigneAmbassadors()
    {
        return $this->hasOne(AssignAmbassador::class, 'event_id', 'id');
    }


    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }

    public function billingParties()
    {
        return $this->hasMany(EventBillingParty::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryBillingParty()
    {
        return $this->hasOne(EventBillingParty::class)->where('is_primary', true);
    }

    /**
     * Get billing party count for QuickBooks split logic.
     */
    public function getBillingPartyCount(): int
    {
        $count = $this->billingParties()->count();
        if ($count > 0) {
            return $count;
        }
        return $this->client_id ? 1 : 0;
    }

    /**
     * Get amounts for QB transfer (labor, buyback, cups). Used when splitting across billing parties.
     */
    public function getLaborAmount(): float
    {
        return (float) ($this->labor_amount ?? 0);
    }

    public function getBuybackAmount(): float
    {
        return (float) ($this->buyback_amount ?? 0);
    }

    public function getCupsAmount(): float
    {
        return (float) ($this->cups_amount ?? 0);
    }

    public function recap()
    {
        return $this->hasOne(Recap::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoiceLinks()
    {
        return $this->hasMany(InvoiceEventLink::class, 'event_id', 'id');
    }

    public function getIsInvoiceCreatedAttribute(): bool
    {
        if ($this->invoices()->exists()) {
            return true;
        }

        return $this->invoiceLinks()->exists();
    }

    public function getInvoiceIdAttribute()
    {
        $invoiceId = $this->invoices()->latest('id')->value('id');
        if ($invoiceId) {
            return $invoiceId;
        }

        return $this->invoiceLinks()->latest('id')->value('invoice_id');
    }

    public function assignAmbassadors()
    {
        return $this->hasOne(AssignAmbassador::class, 'event_id', 'id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id', 'id');
    }

    public function markets()
    {
        return $this->belongsToMany(Market::class, 'event_market', 'event_id', 'market_id')->withTimestamps();
    }

    public function isThcEvent()
    {
        return $this->eventType && $this->eventType->name === 'Portal THC Event';
    }

    public function budgetApprover()
    {
        return $this->belongsTo(User::class, 'budget_approval_resolved_by', 'id');
    }

    /**
     * Total billable for invoicing: labor + buyback + cups.
     */
    public function getBillableTotalAttribute(): float
    {
        return round(
            (float) ($this->labor_amount ?? 0)
            + (float) ($this->buyback_amount ?? 0)
            + (float) ($this->cups_amount ?? 0),
            2
        );
    }

}
