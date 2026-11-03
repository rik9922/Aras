<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbassadorLiquorLicense extends Model
{
    protected $fillable = [
        'user_id',
        'has_liquor_license',
        'license_number',
        'issuing_state',
        'issued_at',
        'expires_at',
        'document_on_file',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'has_liquor_license' => 'boolean',
            'document_on_file' => 'boolean',
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
