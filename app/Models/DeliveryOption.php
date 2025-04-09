<?php

namespace App\Models;

use Schema;
use Eloquent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\DeliveryOption
 *
 * @property int $id
 * @property string $title
 * @property int $shop_id
 * @property int $price
 * @property int $price_per_km
 * @property int $type
 * @property int $time_from
 * @property int $time_to
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Shop $shop
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self filter(array $filter)
 * @mixin Eloquent
 */
class DeliveryOption extends Model
{
    protected $guarded = ['id'];

    public const MINUTE = 'minute';
    public const HOUR   = 'hour';
    public const DAY    = 'day';

    public const TYPES = [
        self::MINUTE => self::MINUTE,
        self::HOUR   => self::HOUR,
        self::DAY    => self::DAY,
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeFilter($query, array $filter) {

        $column = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('delivery_options', $column) ? $column : 'id';
        }

        return $query
            ->when(data_get($filter, 'title'),     fn($q, $value) => $q->where('title', 'like', "%$value%"))
            ->when(data_get($filter, 'shop_id'),   fn($q, $value) => $q->where('shop_id', $value))
            ->when(data_get($filter, 'type'),      fn($q, $value) => $q->where('type', $value))
            ->when(data_get($filter, 'time_from'), fn($q, $value) => $q->where('time_from', '>=', $value))
            ->when(data_get($filter, 'time_to'),   fn($q, $value) => $q->where('time_to', '<=', $value))
            ->when($column, fn($q, $value) => $q->orderBy($column, $filter['sort'] ?? 'desc'));
    }
}
