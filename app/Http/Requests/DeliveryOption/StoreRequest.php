<?php
declare(strict_types=1);

namespace App\Http\Requests\DeliveryOption;

use App\Http\Requests\BaseRequest;
use App\Models\DeliveryOption;
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
           'title'        => ['required', 'string', 'max:255'],
           'shop_id'      => ['required', 'int', Rule::exists('shops', 'id')],
           'price'        => ['required', 'numeric'],
           'price_per_km' => ['required', 'numeric'],
           'type'         => ['required', Rule::in(DeliveryOption::TYPES)],
           'time_from'    => ['required', 'numeric'],
           'time_to'      => ['required', 'numeric'],
        ];
    }
}
