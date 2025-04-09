<?php

namespace App\Repositories\InventoryRepository;

use App\Models\Inventory;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Inventory::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var Inventory $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($q) => $q->where('locale', $this->language),
				'shop:id',
                'shop.translation' => fn($q) => $q->where('locale', $this->language),
            ])
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param Inventory $model
     * @return Inventory|null
     */
    public function show(Inventory $model): Inventory|null
    {
        return $model->loadMissing([
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language),
			'shop:id',
			'shop.translation' => fn($q) => $q->where('locale', $this->language),
        ]);
    }
}
