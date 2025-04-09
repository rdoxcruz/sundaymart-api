<?php

namespace App\Repositories\OrderRepository;

use App\Models\Language;
use App\Models\OrderDetail;
use App\Repositories\CoreRepository;

class OrderDetailRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return OrderDetail::class;
    }

    public function paginate(array $filter = [])
    {
		return $this->model()
            ->filter($filter)
            ->with([
                'stock',
                'order:id,delivery_type',
                'order.currency',
                'stock.countable.translation' => function ($q) {
                    $q
						->select('id', 'product_id', 'locale', 'title')
                        ->where('locale', $this->language);
                },
                'stock.countable.unit.translation' => function ($q) {
                    $q->where('locale', $this->language);
                },
                'children.stock.countable.translation' => function ($q) {
                    $q
						->select('id', 'product_id', 'locale', 'title')
                        ->where('locale', $this->language);
                },
                'children.stock.countable.unit.translation' => function ($q) {
                    $q->where('locale', $this->language);
                },

                'replaceStock' => fn($q) => $q->select('id', 'countable_id', 'countable_type'),
                'replaceStock.countable.translation' => fn($q) => $q->select(['id', 'product_id', 'locale', 'title'])->where('locale', $this->language),

                'children.replaceStock' => fn($q) => $q->select('id', 'countable_id', 'countable_type'),
                'children.replaceStock.countable.translation' => fn($q) => $q->select(['id', 'product_id', 'locale', 'title'])->where('locale', $this->language),
            ])
            ->whereNull('parent_id')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function orderDetailById(int $id)
    {
        return $this->model()->find($id);
    }

}
