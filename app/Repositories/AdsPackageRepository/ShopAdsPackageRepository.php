<?php

namespace App\Repositories\AdsPackageRepository;

use App\Models\Language;
use App\Models\ShopAdsPackage;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class ShopAdsPackageRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShopAdsPackage::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $column = data_get($filter, 'column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('shop_ads_packages', $column) ? $column : 'id';
        }

        return ShopAdsPackage::filter($filter)
            ->with([
                'shop:id',
                'shop.translation' => fn($query) => $query
                    ->select(['id', 'shop_id', 'locale', 'title'])
                    ->where('locale', $this->language),
                'adsPackage.translation' => fn($query) => $query->where('locale', $this->language),
                'transactions',
            ])
            ->orderBy($column, data_get($filter, 'sort', 'asc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param ShopAdsPackage $model
     * @return ShopAdsPackage
     */
    public function show(ShopAdsPackage $model): ShopAdsPackage
    {
        return $model->loadMissing([
            'banner.translation' => fn($query) => $query->where('locale', $this->language),
            'banner.shops:id',
            'banner.shops.translation' => fn($query) => $query
                ->select(['id', 'shop_id', 'locale', 'title'])
               ->where('locale', $this->language),
            'adsPackage.translation' => fn($query) => $query->where('locale', $this->language),
            'shop:id,logo_img',
            'shop.translation' => fn($query) => $query
                ->select(['id', 'shop_id', 'locale', 'title'])
               ->where('locale', $this->language),
            'transactions',
        ]);
    }

    /**
     * @param ShopAdsPackage $model
     * @return ShopAdsPackage
     */
    public function shopShow(ShopAdsPackage $model): ShopAdsPackage
    {
        return $model->loadMissing([
            'banner.translation' => fn($query) => $query->where('locale', $this->language),
            'adsPackage.translation' => fn($query) => $query->where('locale', $this->language),
            'shop:id,logo_img',
            'shop.translation' => fn($query) => $query
                ->select(['id', 'shop_id', 'locale', 'title'])
               ->where('locale', $this->language),
            'transactions',
        ]);
    }

}
