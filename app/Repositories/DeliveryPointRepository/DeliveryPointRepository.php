<?php
declare(strict_types=1);

namespace App\Repositories\DeliveryPointRepository;

use App\Models\DeliveryPoint;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryPointRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return DeliveryPoint::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        return DeliveryPoint::filter($filter)
            ->with([
                'translation' => fn($query) => $query->where('locale', $this->language),
                'translations',
                'workingDays',
                'closedDates',
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param DeliveryPoint $deliveryPoint
     * @return DeliveryPoint
     */
    public function show(DeliveryPoint $deliveryPoint): DeliveryPoint
    {
        return $this->loadShow($deliveryPoint);
    }

    /**
     * @param int $id
     * @return DeliveryPoint|null
     */
    public function showById(int $id): ?DeliveryPoint
    {
        $model = DeliveryPoint::find($id);

        if (!$model) {
            return null;
        }

        return $this->loadShow($model);
    }

    private function loadShow(DeliveryPoint $model): DeliveryPoint
    {
        return $model->loadMissing([
            'galleries',
            'workingDays',
            'closedDates',
            'translation' => fn($query) => $query->where('locale', $this->language),
            'translations'
        ]);
    }

}
