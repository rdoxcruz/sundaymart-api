<?php

namespace App\Models;

use App\Traits\Notification;
use Database\Factories\OrderDetailFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * App\Models\OrderDetail
 *
 * @property int $id
 * @property int $order_id
 * @property int $stock_id
 * @property int $parent_id
 * @property float $origin_price
 * @property float $total_price
 * @property float $tax
 * @property float $discount
 * @property float $rate_discount
 * @property int $quantity
 * @property string $note
 * @property string $status
 * @property int $replace_stock_id
 * @property int $replace_quantity
 * @property int $replace_note
 * @property string $transaction_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 * @property-read Stock $stock
 * @property-read int $rate_total_price
 * @property-read int $rate_origin_price
 * @property-read int $rate_tax
 * @property-read boolean $bonus
 * @property-read self $parent
 * @property-read Collection|self[] $children
 * @property-read Stock|null $replaceStock
 * @method static OrderDetailFactory factory(...$parameters)
 * @method static Builder|self filter($array)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDiscount($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereOrderId($value)
 * @method static Builder|self whereOriginPrice($value)
 * @method static Builder|self whereQuantity($value)
 * @method static Builder|self whereStockId($value)
 * @method static Builder|self whereTax($value)
 * @method static Builder|self whereTotalPrice($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class OrderDetail extends Model
{
    use HasFactory, Notification;

    protected $guarded = ['id'];

    const STATUS_NEW      = 'new';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_READY    = 'ready';
    const STATUS_ENDED    = 'ended';
    const STATUS_CANCELED = 'canceled';

    const STATUSES = [
        self::STATUS_NEW      => self::STATUS_NEW,
        self::STATUS_ACCEPTED => self::STATUS_ACCEPTED,
        self::STATUS_READY    => self::STATUS_READY,
        self::STATUS_ENDED    => self::STATUS_ENDED,
        self::STATUS_CANCELED => self::STATUS_CANCELED,
    ];

    const MERGE_STATUSES = [
        self::STATUS_NEW      => self::STATUS_NEW,
        self::STATUS_ACCEPTED => self::STATUS_ACCEPTED,
        self::STATUS_READY    => self::STATUS_READY,
        self::STATUS_CANCELED => self::STATUS_CANCELED,
    ];

	const TRANSACTION_STATUS_PROGRESS = 'progress';
	const TRANSACTION_STATUS_WAITING  = 'waiting';
	const TRANSACTION_STATUS_PAID     = 'paid';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class)->withTrashed();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function replaceStock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'replace_stock_id')->withTrashed();
    }

    public function getRateTotalPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_price * $this->order->rate;
        }

        return $this->total_price;
    }

    public function getRateDiscountAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->discount * $this->order->rate;
        }

        return $this->discount;
    }

    public function getRateOriginPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->origin_price * $this->order->rate;
        }

        return $this->origin_price;
    }

    public function getRateTaxAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->tax * $this->order->rate;
        }

        return $this->tax;
    }

    /**
     * @param $query
     * @param $filter
     * @return void
     */
    public function scopeFilter($query, $filter): void
    {
        $column = data_get($filter, 'column', 'id');

        if (!Schema::hasColumn('orders', $column)) {
            $column = 'id';
        }

        $query
            ->when(data_get($filter, 'user_id'), function ($q, $userId) {
                $q->whereHas('order', function ($query) use($userId) {
                    $query->where('user_id', $userId);
                });
            })
            ->when(data_get($filter, 'shop_ids'), function ($q, $shopIds) {
                $q->whereHas('order', function ($query) use($shopIds) {
                    $query->whereIn('shop_id', $shopIds);
                });
            })
            ->when(data_get($filter, 'shop_id'), function ($q, $shopId) {
                $q->whereHas('order', fn($q) => $q->where('shop_id', $shopId));
            })
            ->when(data_get($filter, 'status'),     fn($q, $status) 	=> $q->where('status', $status))
            ->when(data_get($filter, 'statuses'),   fn($q, $statuses)   => $q->whereIn('status', $statuses))
            ->orderBy($column, data_get($filter, 'sort', 'desc'));
    }
}
