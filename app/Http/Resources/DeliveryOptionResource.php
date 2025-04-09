<?php

namespace App\Http\Resources;

use App\Helpers\Utility;
use App\Models\DeliveryOption;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var DeliveryOption|JsonResource $this */

        $deliveryFee = 0;

        if (request('shop_address')) {
            $helper      = new Utility;
            $km          = $helper->getDistance(request('shop_address'), request('address'));

            $deliveryFee = $helper->getPriceByDistance($km, $this, (float)request('rate', 1));
        }

        return [
            'id'            => $this->id,
            'title'         => $this->when($this->title,        $this->title),
            'shop_id'       => $this->when($this->shop_id,      $this->shop_id),
            'price'         => $this->when($this->price,        $this->price),
            'price_per_km'  => $this->when($this->price_per_km, $this->price_per_km),
            'type'          => $this->when($this->type,         $this->type),
            'time_from'     => $this->when($this->time_from,    $this->time_from),
            'time_to'       => $this->when($this->time_to,      $this->time_to),
            'delivery_fee'  => $deliveryFee,
            'created_at'    => $this->when($this->created_at,   $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at,   $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            //Relations
            'shop'          => ShopResource::make($this->whenLoaded('shop'))
        ];
    }
}
