<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id'               => [
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at')
            ],
            'payment_id'            => [
                'integer',
                Rule::exists('payments', 'id')->whereNull('deleted_at')
            ],
            'currency_id'           => 'required|integer|exists:currencies,id',
            'rate'                  => 'numeric',
            'shop_id'               => [
                'required',
                'integer',
                Rule::exists('shops', 'id')->whereNull('deleted_at')
            ],
            'delivery_fee'          => 'nullable|numeric',
            'delivery_type'         => ['required', Rule::in(Order::DELIVERY_TYPES)],
            'coupon'                => 'nullable|string',
            'location'              => 'array',
            'location.latitude'     => 'numeric',
            'location.longitude'    => 'numeric',
            'address'               => 'array',
            'address_id'            => ['integer', Rule::exists('user_addresses', 'id')],
            'phone'                 => 'string',
            'email'                 => 'string',
            'username'              => 'string',
            'cash_change'           => 'string',
            'delivery_date'         => 'date|date_format:Y-m-d H:i:s',
            'note'                  => 'nullable|string|max:191',
            'cart_id'               => 'integer|exists:carts,id',
            'notes'                 => 'array',
            'notes.*'               => 'string|max:255',
            'images'                => 'array',
            'images.*'              => 'string',
			'bonus'                 => 'boolean',
			'tip_type'              => 'in:fix,percent',
			'tips'                  => 'numeric|min:0',
            'from_wallet_price'     => 'numeric',
//			'delivery_point_id'     => [
//                request('delivery_type') === Order::POINT ? 'required' : 'nullable',
//                'integer',
//                Rule::exists('delivery_points', 'id')
//            ],
			'delivery_option_id'     => [
                request('delivery_type') === Order::DELIVERY ? 'required' : 'nullable',
                'integer',
                Rule::exists('delivery_options', 'id')->where('shop_id', request('shop_id'))
            ],
			'products'              => 'nullable|array',
            'products.*.stock_id'   =>  [
                'integer',
                Rule::exists('stocks', 'id')
					->whereNull('deleted_at')
            ],
            'products.*.quantity'   => 'numeric',
            'products.*.note'       => 'nullable|string|max:255',

            'products.*.replace_stock_id' => [
                'integer',
                Rule::exists('stocks', 'id'),
            ],
            'products.*.replace_quantity'   => 'integer',
            'products.*.replace_note'       => 'string',

			'products.*.addons'     => 'array',
			'products.*.addons.*.stock_id'  => [
				'integer',
				Rule::exists('stocks', 'id')
					->where('addon', 1)
					->whereNull('deleted_at')
			],
			'products.*.addons.*.quantity'  => ['integer'],

            'products.*.addons.*.replace_stock_id' => [
                'integer',
                Rule::exists('stocks', 'id'),
            ],
            'products.*.addons.*.replace_quantity' => 'integer',
            'products.*.addons.*.replace_note' => 'string',
        ];
    }
}
