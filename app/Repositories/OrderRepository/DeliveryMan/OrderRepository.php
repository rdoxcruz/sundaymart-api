<?php

namespace App\Repositories\OrderRepository\DeliveryMan;

use App\Models\Language;
use App\Models\Order;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $data
     * @return LengthAwarePaginator
     */
    public function paginate(array $data = []): LengthAwarePaginator
    {
        return $this->model()
            ->filter($data)
            ->withCount('orderDetails')
            ->with([
                'currency',
                'transactions.paymentSystem',
                'transactions.children',
                'shop.translation' => fn($q) => $q->where('locale', $this->language),
                'user',
                'deliveryMan',
            ])
            ->orderBy(data_get($data, 'column', 'id'), data_get($data, 'sort', 'desc'))
            ->paginate(data_get($data, 'perPage', 10));
    }

    public function show(?int $id)
    {
        /** @var Order $order */
        $order  = $this->model();

        return $order
            ->with([
                'user',
                'myAddress',
                'review',
                'coupon',
                'shop:id,longitude,latitude,tax,price,price_per_km,background_img,logo_img,uuid,phone',
                'shop.translation' => fn($q) => $q->where('locale', $this->language),
                'transactions.paymentSystem' => function ($q) {
                    $q->select('id', 'tag', 'active');
                },
                'orderDetails.stock.stockExtras.group.translation' => function ($q) {
                    $q
						->select('id', 'extra_group_id', 'locale', 'title')
						->where('locale', $this->language);
                },
                'orderDetails.stock.countable.translation' => function ($q) {
                    $q
						->select('id', 'product_id', 'locale', 'title')
						->where('locale', $this->language);
                },
                'orderDetails.stock.countable.unit.translation' => fn($q) => $q
					->where('locale', $this->language),

                'orderDetails.replaceStock.countable.translation' => fn($q) => $q->select(['id', 'product_id', 'locale', 'title'])->where('locale', $this->language),
                'orderDetails.replaceStock.countable.unit.translation' => fn($q) => $q->where('locale', $this->language),
                'orderDetails.replaceStock.stockExtras.group.translation' => fn($q) => $q->select('id', 'extra_group_id', 'locale', 'title')->where('locale', $this->language),

                'orderDetails.children.replaceStock.countable.translation' => fn($q) => $q->select(['id', 'product_id', 'locale', 'title'])->where('locale', $this->language),
                'orderDetails.children.replaceStock.countable.unit.translation' => fn($q) => $q->where('locale', $this->language),
                'orderDetails.children.replaceStock.stockExtras.group.translation' => fn($q) => $q->select('id', 'extra_group_id', 'locale', 'title')->where('locale', $this->language),

                'currency',
                'deliveryOption',
			])
            ->find($id);
    }
}
