<?php

namespace App\Http\Resources;

use App\Http\Resources\Cart\CartResource;
use App\Models\Order;
use App\Helpers\Utility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Order|JsonResource $this */

        $order = $this;
        $location = 0;

        if ($this->relationLoaded('shop')) {

            $shopLocation = [];
            $orderLocation = [];

            if (data_get($order->shop, 'location.latitude') && data_get($order->shop, 'location.longitude')) {
                $shopLocation = data_get($order->shop, 'location');
            }

            if (data_get($order->location, 'latitude') && data_get($order->location, 'longitude')) {
                $orderLocation = $order->location;
            }

            if (count($shopLocation) === 2 && count($orderLocation) === 2) {
                $location = (new Utility)->getDistance($shopLocation, $orderLocation);
            }

        }

        $couponPrice = 0;

        if ($this->relationLoaded('coupon')) {

            $couponPrice = $this->coupon?->price;

        }

        return [
            'id'                            => $this->when($this->id, $this->id),
            'user_id'                       => $this->when($this->user_id, $this->user_id),
            'total_price'                   => $this->when($this->rate_total_price, $this->rate_total_price),
            'origin_price'                  => $this->when($this->rate_total_price, $this->rate_total_price + $this->rate_total_discount - $this->rate_tax - $this->rate_delivery_fee - $this->rate_service_fee + $couponPrice - $this->rate_tips),
            'seller_fee'                    => $this->when($this->rate_total_price, $this->rate_total_price - $this->rate_delivery_fee - $this->rate_service_fee - $this->rate_commission_fee - $couponPrice - $this->point_histories_sum_price - $this->rate_tips),
            'coupon_price'                  => $this->when($couponPrice, $couponPrice),
            'rate'                          => $this->when($this->rate, $this->rate),
            'cash_change'                   => $this->when($this->cash_change, $this->cash_change),
            'note'                          => $this->when(isset($this->note), (string) $this->note),
            'order_details_count'           => $this->when($this->order_details_count, (int) $this->order_details_count),
            'order_details_sum_quantity'    => $this->when($this->order_details_sum_quantity, $this->order_details_sum_quantity),
            'tax'                           => $this->when($this->rate_tax, $this->rate_tax),
            'commission_fee'                => $this->when($this->rate_commission_fee, $this->rate_commission_fee),
            'service_fee'                   => $this->when($this->rate_service_fee, $this->rate_service_fee),
            'status'                        => $this->when($this->status, $this->status),
            'location'                      => $this->when($this->location, $this->location),
            'address'                       => $this->when($this->address, $this->address),
            'delivery_type'                 => $this->when($this->delivery_type, $this->delivery_type),
            'delivery_fee'                  => $this->when($this->rate_delivery_fee, $this->rate_delivery_fee),
            'delivery_date'                 => $this->when($this->delivery_date, $this->delivery_date),
            'delivery_data'                 => $this->when($this->delivery_data, $this->delivery_data),
            'phone'                         => $this->when($this->phone, $this->phone),
            'email'                         => $this->when($this->email, $this->email),
            'username'                      => $this->when($this->username, $this->username),
            'otp'                      		=> $this->when($this->otp, $this->otp),
            'receipt_discount'              => $this->when(request('receipt_discount'), request('receipt_discount') * ($this->rate <= 0 ? 1 : $this->rate)),
            'receipt_count'                 => $this->when(request('receipt_count'), request('receipt_count')),
            'current'                       => (bool)$this->current,
            'img'                           => $this->when($this->img, $this->img),
            'cart_id'                       => $this->when($this->cart_id, $this->cart_id),
            'image_after_delivered'         => $this->when($this->image_after_delivered, $this->image_after_delivered),
            'total_discount'                => $this->when($this->rate_total_discount, $this->rate_total_discount),
            'tips'                          => $this->when($this->rate_tips, $this->rate_tips),
            'coupon_sum_price'              => $this->when($this->rate_coupon_sum_price, $this->rate_coupon_sum_price),
            'point_history_sum_price'       => $this->when($this->point_histories_sum_price, $this->point_histories_sum_price),
            'created_at'                    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'                    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'km'                            => $this->when($location, $location),

            'deliveryman'                   => UserResource::make($this->whenLoaded('deliveryMan')),
            'shop'                          => ShopResource::make($this->whenLoaded('shop')),
            'repeat'                        => $this->whenLoaded('repeat'),
            'currency'                      => CurrencyResource::make($this->whenLoaded('currency')),
            'cart'                          => CartResource::make($this->whenLoaded('cart')),
            'delivery_option'               => DeliveryOptionResource::make($this->whenLoaded('deliveryOption')),
            'user'                          => UserResource::make($this->whenLoaded('user')),
            'details'                       => OrderDetailResource::collection($this->whenLoaded('orderDetails')),
            'transaction'                   => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions'                  => TransactionResource::collection($this->whenLoaded('transactions')),
            'review'                        => ReviewResource::make($this->whenLoaded('review')),
            'reviews'                       => ReviewResource::collection($this->whenLoaded('reviews')),
            'point_histories'               => PointResource::collection($this->whenLoaded('pointHistories')),
            'order_refunds'                 => OrderRefundResource::collection($this->whenLoaded('orderRefunds')),
            'coupon'                        => CouponResource::make($this->whenLoaded('coupon')),
            'galleries'                     => GalleryResource::collection($this->whenLoaded('galleries')),
            'logs'                          => ModelLogResource::collection($this->whenLoaded('logs')),
            'my_address'                    => UserAddressResource::make($this->whenLoaded('myAddress')),
            'payment_to_partner'			=> PaymentToPartnerResource::make($this->whenLoaded('paymentToPartner')),
            'payment_processes'				=> PaymentProcessResource::collection($this->whenLoaded('paymentProcesses')),
        ];
    }
}
