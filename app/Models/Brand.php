<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BrandCategory;

class Brand extends Model
{
    protected $guarded = [];

    public function getBrandCategoriesIdAttribute($value)
    {
        return explode(',', $value);
    }

    /**
     * Alias accessor so brand_category_id always returns an array of IDs.
     */
    public function getBrandCategoryIdAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        return array_map('intval', explode(',', $value));
    }

    public function user() {
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_brand_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_brand_id');
    }

    /**
     * Many-to-many via pivot table (new canonical relationship).
     */
    public function brandCategories()
    {
        return $this->belongsToMany(
            BrandCategory::class,
            'brand_brand_category',
            'brand_id',
            'brand_category_id'
        )->withTimestamps();
    }

    public function allowedBillToClients()
    {
        return $this->belongsToMany(User::class, 'brand_bill_to_clients', 'brand_id', 'client_id')
            ->withTimestamps();
    }

    /**
     * Return IDs for this brand + all descendants (multi-level).
     *
     * @return list<int>
     */
    public function descendantIdsIncludingSelf(): array
    {
        return self::descendantIdsIncludingSelfById((int) $this->id);
    }

    /**
     * Return IDs for the given brand + all descendants (multi-level).
     *
     * @return list<int>
     */
    public static function descendantIdsIncludingSelfById(?int $brandId): array
    {
        if (! $brandId) {
            return [];
        }

        $all = [$brandId];
        $frontier = [$brandId];

        while (! empty($frontier)) {
            $children = self::query()
                ->whereIn('parent_brand_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $children = array_values(array_diff($children, $all));
            if (empty($children)) {
                break;
            }

            $all = array_values(array_unique(array_merge($all, $children)));
            $frontier = $children;
        }

        return $all;
    }
}
