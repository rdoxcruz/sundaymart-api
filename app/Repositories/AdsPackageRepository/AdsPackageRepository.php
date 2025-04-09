<?php

namespace App\Repositories\AdsPackageRepository;

use App\Models\AdsPackage;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class AdsPackageRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return AdsPackage::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return AdsPackage::filter($filter)
            ->with([
                'translation' => fn($query) => $query->where('locale', $this->language),
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param AdsPackage $model
     * @return AdsPackage
     */
    public function show(AdsPackage $model): AdsPackage
    {
        return $model->loadMissing([
            'banner.translation' => fn($query) => $query
                ->select(['id', 'banner_id', 'locale', 'title'])
                ->where('locale', $this->language),
            'shopAdsPackages.shop:id',
            'shopAdsPackages.transactions',
            'shopAdsPackages.shop.translation' => fn($query) => $query
                ->select(['id', 'shop_id', 'locale', 'title'])
                ->where('locale', $this->language),
            'translation' => fn($query) => $query->where('locale', $this->language),
            'translations',
        ]);
    }

}
