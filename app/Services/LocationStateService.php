<?php

namespace App\Services;

use App\Models\EventLocation;
use Illuminate\Support\Collection;

class LocationStateService
{
    public function options(string $search = ''): Collection
    {
        $needle = mb_strtolower(trim($search));

        return collect(config('us_states'))->map(fn ($name, $code) => (object) [
            'id' => $code, 'state' => $name, 'name' => $name, 'short_name' => $code,
        ])->filter(fn ($state) => $needle === '' || str_contains(mb_strtolower($state->name), $needle) || str_contains(strtolower($state->short_name), $needle))
            ->sortBy('name')->values();
    }

    public function aliases(mixed $value): array
    {
        // Older mobile versions sent the ID of a location as the state selection.
        if (ctype_digit((string) $value)) {
            $value = EventLocation::find($value)?->state ?? $value;
        }
        $value = strtoupper(trim((string) $value));
        $state = $this->options()->first(fn ($state) => $state->id === $value || strtoupper($state->name) === $value);

        return $state ? [$state->id, strtoupper($state->name)] : [$value];
    }
}
