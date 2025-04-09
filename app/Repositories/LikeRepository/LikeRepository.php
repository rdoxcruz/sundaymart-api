<?php
declare(strict_types=1);

namespace App\Repositories\LikeRepository;

use App\Models\Language;
use App\Models\Like;
use App\Models\Product;
use App\Models\Shop;
use App\Repositories\CoreRepository;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Schema;

class LikeRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Like::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        /** @var Like $model */
        $model  = $this->model();
        $column = data_get($filter, 'column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('likes', $column) ? $column : 'id';
        }

        return $model
            ->filter($filter)
            ->when(isset($filter['shop_id']), function (Builder $q) use ($filter) {
                if ($filter['type'] === 'product') {
                    $q->whereHasMorph('likable', Product::class, fn($q) => $q->where('shop_id', $filter['shop_id']));
                } else if ($filter['type'] === 'shop') {
                    $q->whereHasMorph('likable', Shop::class, fn($q) => $q->where('id', $filter['shop_id']));
                }
            })
            ->with($this->getWithByType(data_get($filter, 'type')))
            ->orderBy($column, data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    private function getWithByType(?string $type = 'product'): array
    {
        $filter = request()->all();
        $with   = ['likable'];

        if ($type === 'product') {
            $with = [
                'likable' => fn($q) => $q
                    ->when(isset($filter['shop_id']), function ($q) use ($filter) {
                        $q->where('shop_id', $filter['shop_id']);
                    })
                    ->actual($this->language),
                'likable.translation' => function ($q)  {
                    $q->where('locale', $this->language);
                },
                'likable.stocks' => fn($q) => $q->where('quantity', '>', 0),
                'likable.stocks.gallery',
                'likable.stocks.stockExtras.group.translation' => function ($q) {
                    $q->where('locale', $this->language);
                },
                'likable.stocks.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true),
                'likable.discounts' => fn($q) => $q
                    ->where('start', '<=', today())
                    ->where('end', '>=', today())
                    ->where('active', 1)
            ];
        }

        if ($type === 'shop') {

            $latitude  = data_get($filter, 'address.latitude');
            $longitude = data_get($filter, 'address.longitude');

            $with = [
                'likable' => fn($q) => $q
                    ->when(isset($filter['shop_id']), function ($q) use ($filter) {
                        $q->where('shop_id', $filter['shop_id']);
                    })
                    ->with([
                        'translation' => function ($query) use ($filter) {

                            $query->when(data_get($filter, 'not_lang'),
                                fn($q, $notLang) => $q->where('locale', '!=', data_get($filter, 'not_lang')),
                                fn($q) => $q->where('locale', '=', $this->language),
                            );

                        },
                        'bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true)
                            ->select([
                                'bonusable_type',
                                'bonusable_id',
                                'bonus_quantity',
                                'bonus_stock_id',
                                'expired_at',
                                'value',
                                'type',
                                'status',
                            ]),
                        'bonus.stock.countable:id,uuid',
                        'bonus.stock.countable.translation' => fn($q) => $q->where('locale', $this->language)
                            ->select('id', 'locale', 'title', 'product_id'),
                        'closedDates',
                        'workingDays' => fn($q) => $q->when(data_get($filter, 'work_24_7'),
                            fn($b) => $b->where('from', '01-00')->where('to', '>=', '23-00')
                        ),
                        'discounts' => fn($q) => $q
                            ->where('end', '>=', now())
                            ->where('active', 1)
                            ->select('id', 'shop_id', 'end', 'active'),
                    ])
                    ->whereHas('translation', function ($query) use ($filter) {

                        $query->when(data_get($filter, 'not_lang'),
                            fn($q, $notLang) => $q->where('locale', '!=', data_get($filter, 'not_lang')),
                            fn($q) => $q->where('locale', '=', $this->language),
                        );

                    })
                    ->select('*')
                    ->when(!empty($latitude) && !empty($longitude), function (Builder $query) use ($latitude, $longitude, $filter) {
                        $query
                            ->addSelect([
                                DB::raw("round(ST_Distance_Sphere(point(`longitude`, `latitude`), point($longitude, $latitude)) / 1000, 1) AS distance"),
                            ])
                            ->when(data_get($filter, 'column') === 'distance', function ($q) use ($filter) {
                                $q->orderBy('distance', $filter['sort'] ?? 'desc');
                            });
                    })
            ];
        }

        return $with;
    }
}
