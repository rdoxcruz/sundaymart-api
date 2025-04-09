<?php

namespace App\Services\OrderService;

use App\Helpers\Admin\Utility;
use App\Helpers\ResponseError;
use App\Models\Bonus;
use App\Models\Cart;
use App\Models\NotificationUser;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\PushNotification;
use App\Models\Stock;
use App\Models\UserCart;
use App\Models\WalletHistory;
use App\Services\CartService\CartService;
use App\Services\CoreService;
use App\Services\WalletHistoryService\WalletHistoryService;
use App\Traits\Notification;
use Throwable;

class OrderDetailService extends CoreService
{
	use Notification;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return OrderDetail::class;
    }

    public function create(Order $order, array $collection, ?array $notes = []): Order
    {

		if (empty($order->table_id)) {
			foreach ($order->orderDetails as $orderDetail) {

				$orderDetail?->stock?->increment('quantity', $orderDetail?->quantity);

				$orderDetail?->forceDelete();

			}
		}

        return $this->update($order, $collection, $notes);
    }

    public function update(Order $order, $collection, ?array $notes = []): Order
	{
        $replaceDifferent = [];

        foreach ($collection as $item) {

            /** @var Stock $stock */
            $stock = Stock::with([
                'countable:id,status,shop_id,active,min_qty,max_qty,tax,img,interval',
                'countable.discounts' => fn($q) => $q
                    ->where('start', '<=', today())
                    ->where('end', '>=', today())
                    ->where('active', 1)
            ])
                ->find(data_get($item, 'stock_id'));

            if (!$stock?->countable?->active || $stock?->countable?->status != Product::PUBLISHED) {
                continue;
            }

            $actualQuantity = $this->actualQuantity($stock, data_get($item, 'quantity', 0));

            if (empty($actualQuantity) || $actualQuantity <= 0) {
                continue;
            }

			data_set($item, 'quantity', $actualQuantity);
			data_set($item, 'note', data_get($notes, $stock->id, ''));

			$addons = (array)data_get($item, 'addons', []);

            $replaceStock = null;

            if (isset($item['replace_stock_id'])) {

                $replaceStock = Stock::find($item['replace_stock_id']);

                $this->replaceCalculate($order, $stock, $item, $replaceStock, $replaceDifferent);

            }

			/** @var OrderDetail $orderDetail */
            $orderDetail = $order->orderDetails()->updateOrCreate(
                [
                    'stock_id' => !empty($replaceStock) ? $replaceStock->id : $stock->id,
                    'bonus'    => $item['bonus'] ?? false,
                ],
                $this->setItemParams($item, $stock)
            );

			$stock->decrement('quantity', $actualQuantity);

			foreach ($addons as $addon) {

				/** @var Stock $addonStock */
				$addonStock = Stock::with([
					'countable:id,status,shop_id,active,min_qty,max_qty,tax,img,interval',
					'countable.discounts' => fn($q) => $q
						->where('start', '<=', today())
						->where('end', '>=', today())
						->where('active', 1)
				])
					->find(data_get($addon, 'stock_id'));

				if (!$addonStock) {
					continue;
				}

				$actualQuantity = $this->actualQuantity($addonStock, data_get($addon, 'quantity', 0));

				if (empty($actualQuantity) || $actualQuantity <= 0) {
					continue;
				}

				$addon['quantity']  = $actualQuantity;
				$addon['parent_id'] = $orderDetail->id;

                $replaceStock = null;

                if (isset($addon['replace_stock_id'])) {

                    $replaceStock = Stock::find($addon['replace_stock_id']);

                    $this->replaceCalculate($order, $stock, $addon, $replaceStock, $replaceDifferent);

                }

                /** @var OrderDetail $orderDetail */
                $orderDetail = $order->orderDetails()->updateOrCreate(
                    [
                        'stock_id' => !empty($replaceStock) ? $replaceStock->id : $stock->id,
                        'bonus'    => $addon['bonus'] ?? false,
                    ],
                    $this->setItemParams($addon, $addonStock)
                );

				$addonStock->decrement('quantity', $actualQuantity);

				Utility::calculateInventory($stock);
			}

        }

        try {
            $this->replaceUpdate($order, $replaceDifferent);
        } catch (Throwable) {}

        return $order;
    }

    public function createOrderUser(Order $order, int $cartId, ?array $notes = []): Order
    {
        /** @var Cart $cart */
        $cart = clone Cart::with([
            'userCarts.cartDetails:id,user_cart_id,stock_id,price,discount,quantity',
            'userCarts.cartDetails.stock.bonus' => fn($q) => $q->where('expired_at', '>', now()),
            'shop',
            'shop.bonus' => fn($q) => $q->where('expired_at', '>', now()),
        ])
            ->select('id', 'total_price', 'shop_id')
            ->find($cartId);

        (new CartService)->calculateTotalPrice($cart);

        $cart = clone Cart::with([
            'shop',
            'userCarts.cartDetails' => fn($q) => $q->whereNull('parent_id'),
            'userCarts.cartDetails.stock.countable',
            'userCarts.cartDetails.children.stock.countable',
        ])->find($cart->id);

        if (empty($cart?->userCarts)) {
            return $order;
        }

        foreach ($cart->userCarts as $userCart) {

            $cartDetails = $userCart->cartDetails;

            if (empty($cartDetails)) {
                $userCart->delete();
                continue;
            }

            foreach ($cartDetails as $cartDetail) {

                /** @var UserCart $userCart */
                $stock = $cartDetail->stock;

                $cartDetail->setAttribute('note', data_get($notes, $stock->id, ''));

                /** @var OrderDetail $parent */

                $parent = $order->orderDetails()->create($this->setItemParams($cartDetail, $stock));

                $stock->decrement('quantity', $cartDetail->quantity);

                foreach ($cartDetail->children as $addon) {

                    $stock = $addon->stock;

                    $addon->setAttribute('parent_id', $parent?->id);

                    $addon->setAttribute('note', data_get($notes, $stock->id, ''));

                    $order->orderDetails()->create($this->setItemParams($addon, $stock));

                    $stock->decrement('quantity', $addon->quantity);
                }

            }

        }

        $cart->delete();

        return $order;

    }

    /**
     * @param Order $order
     * @param array $replaceDifferent
     * @return void
     * @throws Throwable
     */
    private function replaceUpdate(Order $order, array $replaceDifferent): void
    {

        if (count($replaceDifferent) <= 0) {
            return;
        }

        $totalPrice = 0;

        foreach ($replaceDifferent as $item) {
            $totalPrice = $item['type'] === 'topup' ? $totalPrice + $item['price'] : $totalPrice - $item['price'];
        }

        (new WalletHistoryService)->create([
            'type'   => $totalPrice < 0 ? 'topup' : 'withdraw',
            'price'  => (double)str_replace('-', '', (string)$totalPrice),
            'note'   => __('errors.' . ResponseError::REPLACE_PRODUCT, ['id' => $order->id], $order->user?->lang ?? $this->language),
            'status' => WalletHistory::PAID,
            'user'   => $order->user,
        ]);

        $notification = $order->user
            ?->notifications
            ?->where('type', \App\Models\Notification::PUSH)
            ?->first();

        /** @var NotificationUser $notification */
        if (!$notification?->notification?->active) {
            return;
        }

        $key  = $totalPrice > 0 ? ResponseError::WALLET_TOP_UP : ResponseError::WALLET_WITHDRAW;
        $type = $totalPrice > 0 ? PushNotification::WALLET_TOP_UP : PushNotification::WALLET_WITHDRAW;

        $this->sendNotification(
            $order->user->user->firebase_token ?? [],
            __("errors.$key", ['sender' => 'system'], $order->user?->lang ?? $this->language),
            __("errors.$key", ['sender' => 'system'], $order->user?->lang ?? $this->language),
            [
                'id'     => $order->user->id,
                'price'  => $totalPrice,
                'type'   => $type
            ],
            [$order->user_id]
        );
    }

    /**
     * @param Order $order
     * @param Stock $stock
     * @param Stock|null $replaceStock
     * @param array $item
     * @param array $replaceDifferent
     * @return void
     */
    private function replaceCalculate(
        Order $order,
        Stock $stock,
        array $item,
        ?Stock $replaceStock = null,
        array &$replaceDifferent = []
    ): void
    {
        if (empty($replaceStock)) {
            return;
        }

        $replaceBonusStock = Bonus::where([
            ['bonusable_type', Stock::class],
            ['bonusable_id', $replaceStock->id],
            ['expired_at', '>', now()],
            ['type', Bonus::TYPE_COUNT],
        ])
            ->first();

        if (!empty($replaceBonusStock?->id)) {
            $order->orderDetails()
                ->where(['bonus' => true, 'stock_id' => $replaceBonusStock->bonus_stock_id])
                ->delete();
        }

        $replaceDifferent[$replaceStock->id] = [
            'type'  => 'topup',
            'price' => $stock->total_price - $replaceStock->total_price,
        ];

        if ($replaceStock->total_price > $stock->total_price) {
            $replaceDifferent[$replaceStock->id] = [
                'type'  => 'withdraw',
                'price' => $replaceStock->total_price - $stock->total_price,
            ];
        }

        $bonusStock = Bonus::where([
            ['bonusable_type', Stock::class],
            ['bonusable_id', $stock->id],
            ['expired_at', '>', now()],
            ['type', Bonus::TYPE_COUNT],
        ])
            ->first();

        if (!$bonusStock?->id || !$bonusStock?->stock?->id) {
            return;
        }

        $bonusItem = [
            'quantity' => $bonusStock->bonus_quantity * (int)floor($item['quantity'] / $bonusStock->value)
        ];

        /** @var OrderDetail $orderDetail */
        $order->orderDetails()->updateOrCreate(
            [
                'stock_id'  => $bonusStock->bonus_stock_id,
                'parent_id' => $item['parent_id'] ?? null,
            ],
            $this->setItemParams($bonusItem, $bonusStock?->stock)
        );

    }

    private function setItemParams($item, ?Stock $stock): array
    {

        $quantity  = data_get($item, 'quantity', 0);

        if (data_get($item, 'bonus')) {

            data_set($item, 'origin_price', 0);
            data_set($item, 'total_price', 0);
            data_set($item, 'tax', 0);
            data_set($item, 'discount', 0);

        } else {

            $originPrice = $stock?->price * $quantity;

            $discount    = $stock?->actual_discount * $quantity;

            $tax         = $stock?->tax_price * $quantity;

            $totalPrice  = $originPrice - $discount + $tax;

            data_set($item, 'origin_price', $originPrice);
            data_set($item, 'total_price', max($totalPrice,0));
            data_set($item, 'tax', $tax);
            data_set($item, 'discount', $discount);
        }

        return [
            'note'         => data_get($item, 'note', 0),
            'origin_price' => data_get($item, 'origin_price', 0),
            'tax'          => data_get($item, 'tax', 0),
            'discount'     => data_get($item, 'discount', 0),
            'total_price'  => data_get($item, 'total_price', 0),
            'stock_id'     => data_get($item, 'stock_id'),
            'parent_id'    => data_get($item, 'parent_id'),
            'quantity'     => $quantity,
            'bonus'        => data_get($item, 'bonus', false),

            'replace_stock_id'  => data_get($item, 'replace_stock_id'),
            'replace_quantity'  => data_get($item, 'replace_quantity'),
            'replace_note'      => data_get($item, 'replace_note'),
        ];
    }

    /**
     * @param Stock|null $stock
     * @param $quantity
     * @return mixed
     */
    private function actualQuantity(?Stock $stock, $quantity): mixed
    {

        $countable = $stock?->countable;

        if ($quantity < ($countable?->min_qty ?? 0)) {

            $quantity = $countable?->min_qty;

        } else if($quantity > ($countable?->max_qty ?? 0)) {

            $quantity = $countable?->max_qty;

        }

        return $quantity > $stock->quantity ? max($stock->quantity, 0) : $quantity;
    }

    public function statusUpdate(OrderDetail $orderDetail, ?string $status): array
    {
        if ($orderDetail->status == $status) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_252,
                'message' => __('errors.' . ResponseError::ERROR_252, locale: $this->language)
            ];
        }

        $orderDetail->update([
            'status' => $status
        ]);

		$orderDetail->children()->update([
            'status' => $status
        ]);

		return ['status' => true, 'message' => ResponseError::NO_ERROR, 'data' => $orderDetail];
    }

}
