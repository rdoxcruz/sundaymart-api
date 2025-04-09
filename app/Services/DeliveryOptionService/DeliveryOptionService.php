<?php
declare(strict_types=1);

namespace App\Services\DeliveryOptionService;

use App\Helpers\ResponseError;
use App\Models\DeliveryOption;
use App\Services\CoreService;
use Throwable;

class DeliveryOptionService extends CoreService
{
    protected function getModelClass(): string
    {
        return DeliveryOption::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            $model = $this->model()->create($data);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $model,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'message' => ResponseError::ERROR_501, 'code' => ResponseError::ERROR_501];
        }
    }

    public function update(DeliveryOption $deliveryOption, array $data): array
    {
        try {

            $deliveryOption->update($data);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $deliveryOption,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => ResponseError::ERROR_501];
        }
    }

    public function delete(?array $ids = [], ?int $shopId = null) {

        $models = DeliveryOption::when($shopId, fn($q) => $q->where('shop_id', $shopId))->find((array)$ids);

        foreach ($models as $model) {
            $model->delete();
        }

    }
}
