<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceDetail extends Model
{
    protected $table = 'device_details';

    protected $fillable = [
        'user_id',
        'device_token',
        'platform',
        'app_version',
        'is_user_loggedin',
        'time_zone',
    ];

    /**
     * Keep exactly one device_details row per user — update if present, create otherwise.
     */
    public static function upsertForUser(int $userId, array $attributes): self
    {
        $record = static::updateOrCreate(
            ['user_id' => $userId],
            $attributes
        );

        // Safety net before/at unique index enforcement.
        static::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $record->id)
            ->delete();

        return $record->fresh();
    }

    /**
     * Clear push registration for a user (logout / opt-out).
     */
    public static function clearForUser(int $userId): void
    {
        static::query()
            ->where('user_id', $userId)
            ->update([
                'device_token' => null,
                'is_user_loggedin' => 0,
            ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
