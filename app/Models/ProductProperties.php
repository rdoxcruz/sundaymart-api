<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ProductProperties
 *
 * @property int $id
 * @property int $product_id
 * @property int $category_id
 * @property string $locale
 * @property string $title
 * @property string $key
 * @property string|null $value
 * @property string|null $deleted_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereKey($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereProductId($value)
 * @method static Builder|self whereValue($value)
 * @method static Builder|self filter(array $filter = [])
 * @mixin Eloquent
 */
class ProductProperties extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public $timestamps = false;

    public function scopeFilter($query, array $filter = []): void
    {
        $query
            ->when(isset($filter['category_id']), fn($q) => $q->where('category_id', $filter['category_id']))
            ->when(isset($filter['product_id']),  fn($q) => $q->where('product_id',  $filter['product_id']))
            ->when(isset($filter['locale']),      fn($q) => $q->where('locale',      $filter['locale']))
            ->when(isset($filter['search']),      fn($q) => $q->where(function ($q) use($filter) {
                $q
                    ->where('title', 'like', '%' . $filter['value'] . '%')
                    ->orWhere('value', 'like', '%' . $filter['value'] . '%');
            }));
    }
}
