<?php

namespace App\Repositories\ReceiptRepository;

use App\Models\Language;
use App\Models\Receipt;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReceiptRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Receipt::class;
    }

    public function paginate($filter = []): LengthAwarePaginator
    {
        /** @var Receipt $models */
        $models = $this->model();

        return $models
            ->filter($filter)
            ->with([
                'translation'  => fn($q) => $q->select('locale', 'title', 'receipt_id')
                    ->where('locale', $this->language),
                'ingredient'  => fn($q) => $q->select('locale', 'title', 'receipt_id')
                    ->where('locale', $this->language),
                'instruction'  => fn($q) => $q->select('locale', 'title', 'receipt_id')
                    ->where('locale', $this->language),
                'nutritions.translation'  => fn($q) => $q->select('locale', 'title', 'nutrition_id')
                    ->where('locale', $this->language),
                'shop:id,logo_img,uuid',
                'shop.translation'  => fn($q) => $q->select('locale', 'title', 'shop_id')
                    ->where('locale', $this->language),
                'category.translation'  => fn($q) => $q->select('locale', 'title', 'category_id')
                    ->where('locale', $this->language),
                'translations',
                'ingredients',
                'instructions',
                'nutritions.translations',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function restPaginate($filter = []): LengthAwarePaginator
    {
        /** @var Receipt $models */
        $models = $this->model();

        return $models
            ->filter($filter)
            ->with([
                'shop:id,logo_img,uuid',
                'translation'            => fn($q) => $q->where('locale', $this->language),
                'ingredient'             => fn($q) => $q->select('locale', 'title', 'receipt_id')->where('locale', $this->language),
                'instruction'            => fn($q) => $q->select('locale', 'title', 'receipt_id')->where('locale', $this->language),
                'nutritions.translation' => fn($q) => $q->where('locale', $this->language),
                'shop.translation'       => fn($q) => $q->select('locale', 'title', 'shop_id')->where('locale', $this->language),
                'category.translation'   => fn($q) => $q->select('locale', 'title', 'category_id')->where('locale', $this->language),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function show(Receipt $model): Receipt
    {
        return $model->loadMissing([
            'galleries',
            'translation' => fn($q) => $q->where('locale', $this->language),
            'ingredient'  => fn($q) => $q->where('locale', $this->language),
            'instruction' => fn($q) => $q->where('locale', $this->language),
            'nutritions.translation' => fn($q) => $q->where('locale', $this->language),
            'translations',
            'ingredients',
            'instructions',
            'nutritions.translations',
            'shop',
            'shop.translation'  => fn($q) => $q->select('locale', 'title', 'shop_id')
               ->where('locale', $this->language),
            'category.translation'  => fn($q) => $q->select('locale', 'title', 'category_id')
               ->where('locale', $this->language),
            'stocks.countable:id,active,addon,img',
            'stocks.countable.translation' => fn($q) => $q->select('locale', 'title', 'product_id')
               ->where('locale', $this->language),
            'stocks.countable.unit.translation' => fn($q) => $q->where('locale', $this->language),
        ]);
    }

    public function restShow(Receipt $model): Receipt
    {
        return $model->loadMissing([
            'galleries',
            'translation' => fn($q) => $q->where('locale', $this->language),
            'ingredient'  => fn($q) => $q->where('locale', $this->language),
            'instruction' => fn($q) => $q->where('locale', $this->language),
            'nutritions.translation' => fn($q) => $q->where('locale', $this->language),
            'shop',
            'shop.translation'  => fn($q) => $q->select('locale', 'title', 'shop_id')
               ->where('locale', $this->language),
            'category.translation'  => fn($q) => $q->select('locale', 'title', 'category_id')
               ->where('locale', $this->language),
            'stocks.countable',
            'stocks.countable.translation' => fn($q) => $q->where('locale', $this->language),
            'stocks.countable.unit.translation' => fn($q) => $q->where('locale', $this->language),
        ]);
    }
}
