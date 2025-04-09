<?php

namespace App\Repositories\ProductRepository;

use App\Helpers\Utility;
use App\Models\Product;
use App\Models\Language;
use App\Models\ProductProperties;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RestProductRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Product::class;
    }

    public function getWith(array $filter, ?string $locale = null): array
    {
        return [
            'stocks' => fn($q) => $q->where('addon', false)->where('quantity', '>', 0),
            'stocks.addons.addon' => fn($query) => $query->with([
                'stock'         => fn($q) => $q->where('addon', true)->where('quantity', '>', 0),
                'translation'   => fn($q) => $q->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language),
            ])
                ->whereHas('stock', fn($q) => $q->where('addon', true)->where('quantity', '>', 0))
                ->whereHas('translation', fn($q) => $q->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language)
                )
                ->where('active', true)
                ->where('status', data_get($filter, 'addon_status', Product::PUBLISHED)),
            'stocks.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true)->select([
                'id', 'expired_at', 'bonusable_type', 'bonusable_id',
                'bonus_quantity', 'value', 'type', 'status'
            ]),
            'stocks.bonus.stock',
            'stocks.bonus.stock.stockExtras',
            'stocks.bonus.stock.countable:id,uuid,tax,status,active,img,min_qty,max_qty,interval',
            'stocks.bonus.stock.countable.translation' => fn($q) => $q
                ->select('id', 'product_id', 'locale', 'title')
                ->where('locale', $this->language),
            'stocks.stockExtras.group.translation' => fn($q) => $q
                ->select('id', 'extra_group_id', 'locale', 'title')
                ->where('locale', $this->language),
            'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())->where('active', 1),
            'translation' => fn($q) => $q
                ->select('id', 'product_id', 'locale', 'title')
                ->where('locale', $this->language),
            'brand:id,title,img',
            'category' => fn($q) => $q->select('id', 'uuid'),
            'category.translation' => fn($q) => $q
                ->select('id', 'category_id', 'locale', 'title')
                ->where('locale', $this->language),
            'unit.translation' => fn($q) => $q
                ->select('id', 'unit_id', 'locale', 'title')
                ->where('locale', $this->language),
        ];
    }

    public function productsPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Product $product */
        $product   = $this->model();
        $locale    = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
        $latitude  = data_get($filter, 'address.latitude');
        $longitude = data_get($filter, 'address.longitude');

        return $product
            ->filter($filter)
            ->with($this->getWith($filter, $locale))
            ->whereHas('translation', fn($query) => $query
                ->select('id', 'product_id', 'locale', 'title')
                ->where('locale', $this->language)
            )
            ->select('*')
            ->when(!empty($latitude) && !empty($longitude), function (Builder $query) use ($latitude, $longitude, $filter) {
                $query
//                    ->where('latitude', '>', 0)
//                    ->where('longitude', '>', 0)
                    ->addSelect([
                        DB::raw("round(ST_Distance_Sphere(point(`longitude`, `latitude`), point($longitude, $latitude)) / 1000, 1) AS distance"),
                    ])
                    ->when(data_get($filter, 'column') === 'distance', function ($q) use ($filter) {
                        $q->orderBy('distance', $filter['sort'] ?? 'desc');
                    });
            })
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function productsMostSold(array $filter = [])
    {
        return $this->model()
            ->filter($filter)
            ->whereHas('translation', function ($q) {
                $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language);
            })
            ->withAvg('reviews', 'rating')
            ->withCount('orderDetails')
            ->withCount('stocks')
            ->with([
                'stock' => fn($q) => $q->where('quantity', '>', 0),
                'stock.stockExtras.group.translation' => fn($q) => $q
                    ->select('id', 'extra_group_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'translation' => fn($q) => $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'unit.translation' => fn($q) => $q
                    ->select('id', 'unit_id', 'locale', 'title')
                    ->where('locale', $this->language),
                ])
            ->whereHas('stock', function ($item) {
                $item->where('quantity', '>', 0);
            })
            ->whereHas('shop', function ($item) {
                $item->whereNull('deleted_at');
            })
            ->orderBy('order_details_count', 'desc')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function productsDiscount(array $filter = []): mixed
    {
        $profitable = data_get($filter, 'profitable') ? '=' : '>=';

        return $this->model()
            ->with([
                'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())->where('active', 1),
                'stocks' => fn($q) => $q->where('quantity', '>', 0),
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->select('id', 'extra_group_id', 'locale', 'title')
                    ->where('locale', $this->language),

                'translation' => fn($q) => $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language),

                'unit.translation' => fn($q) => $q
                    ->select('id', 'unit_id', 'locale', 'title')
                    ->where('locale', $this->language),
            ])
            ->withAvg('reviews', 'rating')
            ->whereActive(1)
            ->filter($filter)
            ->whereHas('discounts', function ($item) use ($profitable) {
                $item->where('active', 1)
                    ->whereDate('start', '<=', today())
                    ->whereDate('end', $profitable, today()->format('Y-m-d'));
            })
            ->whereHas('translation', function ($q) {
                $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language);
            })
            ->whereHas('stocks', function ($item) {
                $item->where('quantity', '>', 0);
            })
            ->whereHas('shop')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param string $uuid
     * @return Product|null
     */
    public function productByUUID(string $uuid): ?Product
    {
        /** @var Product $product */
        $product = $this->model();

        return $product
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with([
                'stocks' => fn($q) => $q->with([
                    'bonus' => fn($q) => $q
                        ->with([
                            'stock' => fn($q) => $q->where('quantity', '>', 0),
                            'stock.countable.translation' => fn($q) => $q
                                ->select('id', 'product_id', 'locale', 'title')
                                ->where('locale', $this->language)
                        ])
                        ->where('expired_at', '>', now())->where('status', true),
                ])->where('quantity', '>', 0),
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->select('id', 'extra_group_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'stocks.addons' => fn($q) => $q->whereHas('addon', fn($a) => $a->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))),
                'stocks.addons.addon' => fn($q) => $q
                    ->with([
                        'stock' => fn($q) => $q->where('quantity', '>', 0),
                        'translation' => fn($q) => $q
                            ->select('id', 'product_id', 'locale', 'title')
                            ->where('locale', $this->language)
                    ])
                    ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))
                    ->select([
                        'id',
                        'uuid',
                        'category_id',
                        'unit_id',
                        'img',
                        'active',
                        'status',
                        'min_qty',
                        'max_qty',
                        'interval',
                    ])
                    ->where('active', true)
                    ->where('addon', true)
                    ->where('status', Product::PUBLISHED),
                'discounts' => fn($q) => $q->where('end', '>=', now()),
                'stocks.galleries',
                'translation' => fn($q) => $q
                    ->where('locale', $this->language),
                'unit.translation' => fn($q) => $q
                    ->select('id', 'unit_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'shop:id,uuid,slug,logo_img',
                'shop.translation' => fn($q) => $q
                    ->select('id', 'shop_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'galleries',
                'properties',
            ])
            ->where('active', true)
            ->where('addon',  false)
            ->where('status', Product::PUBLISHED)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param string $slug
     * @return Product|null
     */
    public function productBySlug(string $slug): ?Product
    {
        /** @var Product $product */
        $product = $this->model();

        return $product
            ->whereHas('translation', fn($query) => $query
                ->select('id', 'product_id', 'locale', 'title')
                ->where('locale', $this->language)
            )
            ->with([
                'stocks' => fn($q) => $q->with([
                    'bonus' => fn($q) => $q
                        ->with([
                            'stock' => fn($q) => $q->where('quantity', '>', 0),
                            'stock.countable.translation' => fn($q) => $q
                                ->select('id', 'product_id', 'locale', 'title')
                                ->where('locale', $this->language)
                        ])
                        ->where('expired_at', '>', now())->where('status', true),
                ])->where('quantity', '>', 0),
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->select('id', 'extra_group_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'stocks.addons' => fn($q) => $q->whereHas('addon', fn($a) => $a->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))),
                'stocks.addons.addon' => fn($q) => $q
                    ->with([
                        'stock' => fn($q) => $q->where('quantity', '>', 0),
                        'translation' => fn($q) => $q
                            ->select('id', 'product_id', 'locale', 'title')
                            ->where('locale', $this->language)
                    ])
                    ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))
                    ->select([
                        'id',
                        'uuid',
                        'category_id',
                        'unit_id',
                        'img',
                        'active',
                        'status',
                        'min_qty',
                        'max_qty',
                        'interval',
                    ])
                    ->where('active', true)
                    ->where('addon', true)
                    ->where('status', Product::PUBLISHED),
                'stocks.galleries',
                'discounts' => fn($q) => $q->where('end', '>=', now()),
                'translation' => fn($q) => $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'unit.translation' => fn($q) => $q
                    ->select('id', 'unit_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'galleries',
                'properties',
            ])
            ->where('active', true)
            ->where('status', Product::PUBLISHED)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @param int $id
     * @return array
     */
    public function reviewsGroupByRating(int $id): array
    {
        return Utility::reviewsGroupRating([
            'reviewable_type' => Product::class,
            'reviewable_id'   => $id,
        ]);
    }

    /**
     * @param int $id
     * @param array $filter
     * @return Builder[]|\Illuminate\Database\Query\Builder[]|ProductProperties[]
     */
    public function propertiesByCategory(int $id, array $filter): array
    {
        $filter['category_id'] = $id;
//        $filter['locale']      = $this->language;

        $items = ProductProperties::filter($filter)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        $data = [];

        foreach ($items as $item) {
            $data[$item->locale][] = $item;
        }

        return array_values($data);
    }
}
