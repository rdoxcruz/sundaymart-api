<?php
declare(strict_types=1);

namespace App\Repositories\DeliveryOptionRepository;

use App\Models\Language;
use App\Models\DeliveryOption;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryOptionRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return DeliveryOption::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        return DeliveryOption::filter($filter)
            ->with([
                'shop:id,uuid,slug,logo_img',
                'shop.translation' => fn($q) => $q
                    ->select('id', 'locale', 'title', 'shop_id')
                    ->where('locale', $this->language),
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param DeliveryOption $deliveryOption
     * @return DeliveryOption
     */
    public function show(DeliveryOption $deliveryOption): DeliveryOption
    {
        return $deliveryOption->load([
            'shop:id,uuid,slug,logo_img',
            'shop.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'shop_id')
                ->where('locale', $this->language),
        ]);
    }

    /**
     * @param int $id
     * @return ?DeliveryOption
     */
    public function showById(int $id): ?DeliveryOption
    {
        $model = DeliveryOption::find($id);

        return $model->load([
            'shop:id,uuid,slug,logo_img',
            'shop.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'shop_id')
                ->where('locale', $this->language),
        ]);
    }
}
