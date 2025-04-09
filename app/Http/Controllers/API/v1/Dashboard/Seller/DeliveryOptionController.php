<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Models\DeliveryOption;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DeliveryOptionResource;
use App\Http\Requests\DeliveryOption\StoreRequest;
use App\Services\DeliveryOptionService\DeliveryOptionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Repositories\DeliveryOptionRepository\DeliveryOptionRepository;

class DeliveryOptionController extends SellerBaseController
{

    public function __construct(
        private DeliveryOptionRepository $repository,
        private DeliveryOptionService $service
    )
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return DeliveryOptionResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

    /**
     * Display the specified resource.
     *
     * @param DeliveryOption $deliveryOption
     * @return JsonResponse
     */
    public function show(DeliveryOption $deliveryOption): JsonResponse
    {
        if ($deliveryOption->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: request('language', 'en'))
            ]);
        }

        $model = $this->repository->show($deliveryOption);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $model
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DeliveryOption $deliveryOption
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(DeliveryOption $deliveryOption, StoreRequest $request): JsonResponse
    {
        if ($deliveryOption->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: request('language', 'en'))
            ]);
        }

        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->service->update($deliveryOption, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

}
