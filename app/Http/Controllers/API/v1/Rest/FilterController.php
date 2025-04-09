<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Models\Shop;
use App\Models\Product;
use App\Http\Requests\FilterParamsRequest;
use App\Repositories\FilterRepository\FilterRepository;

class FilterController extends RestBaseController
{
    public function __construct(private FilterRepository $repository)
    {
        parent::__construct();
    }

    public function filter(FilterParamsRequest $request): array
    {
        $filter = $request->merge([
            'status'      => Product::PUBLISHED,
            'shop_status' => Shop::APPROVED
        ])->all();

        return $this->repository->filter($filter);
    }

    public function search(FilterParamsRequest $request): array
    {
        $validated                = $request->all();
        $validated['status']      = Product::PUBLISHED;
        $validated['shop_status'] = Shop::APPROVED;

        return $this->repository->filter($validated);
    }

}
