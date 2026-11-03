<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickBooksTerm extends Model
{
    protected $table = 'quickbooks_terms';

    protected $guarded = [];

    /**
     * Find a term by its display name (case-insensitive).
     * Returns the first active match.
     */
    /**
     * Find a term by display name.
     * Both the stored DB value and the supplied name are trimmed and lowercased
     * so whitespace variations never cause a false lookup miss.
     */
    public static function findByName(string $name): ?self
    {
        $needle = mb_strtolower(trim($name));

        return static::where('is_active', true)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$needle])
            ->first();
    }

    /**
     * Resolve the QuickBooks Term ID (qb_term_id) for a given term name.
     * Returns null when not found — callers must distinguish between
     * "terms not synced at all" and "this specific term has no match".
     */
    public static function resolveQbTermId(string $termName): ?string
    {
        $term = static::findByName($termName);

        return $term?->qb_term_id;
    }
}
