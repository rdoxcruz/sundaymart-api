<?php

namespace App\Repositories\OrderRepository;

use DB;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\Transaction;
use App\Traits\SetCurrency;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminOrderRepository extends CoreRepository
{
	use SetCurrency;

	/**
	 * @return string
	 */
	protected function getModelClass(): string
	{
		return Order::class;
	}

	/**
	 * @param array $filter
	 * @return LengthAwarePaginator
	 */
	public function ordersPaginate(array $filter = []): LengthAwarePaginator
	{
		/** @var Order $order */
		$order = $this->model();

		return $order
			->filter($filter)
			->withCount('orderDetails')
			->with([
				'user',
				'shop',
				'shop.translation' => fn($q) => $q
					->select('id', 'shop_id', 'locale', 'title')
					->where('locale', $this->language),
				'deliveryMan',
				'transactions.paymentSystem',
				'currency',
                'orderDetails'
			])
			->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
			->paginate(data_get($filter, 'perPage', 10));
	}

	/**
	 * @param array $filter
	 * @param string $paginate
	 * @return Paginator
	 */
	public function userOrdersPaginate(array $filter = [], string $paginate = 'simplePaginate'): Paginator
	{
		/** @var Order $order */
		$order = $this->model();

		return $order
			->withCount('orderDetails')
			->with([
				'user:id,lastname,firstname',
				'shop:id,longitude,latitude,tax,price,price_per_km,background_img,logo_img',
				'shop.translation' => fn($q) => $q
					->select('id', 'shop_id', 'locale', 'title')
					->where('locale', $this->language),
				'deliveryMan:id,lastname,firstname',
				'transactions.paymentSystem',
				'currency',
                'deliveryOption',
				'orderDetails'       => fn($q) => $q->whereNull('parent_id'),
				'orderDetails.stock' => fn($q) => $q->select('id', 'countable_id', 'countable_type'),
				'orderDetails.stock.countable.translation' => fn($q) => $q
					->where('locale', $this->language),
				'orderDetails.children.stock' => fn($q) => $q->select('id', 'countable_id', 'countable_type'),
				'orderDetails.children.stock.countable.translation' => fn($q) => $q
					->where('locale', $this->language),

                'orderDetails.replaceStock' => fn($q) => $q->select('id', 'countable_id', 'countable_type'),
                'orderDetails.replaceStock.countable.translation' => fn($q) => $q->select(['id', 'product_id', 'locale', 'title'])->where('locale', $this->language),

                'orderDetails.children.replaceStock' => fn($q) => $q->select('id', 'countable_id', 'countable_type'),
                'orderDetails.children.replaceStock.countable.translation' => fn($q) => $q->select(['id', 'product_id', 'locale', 'title'])->where('locale', $this->language),
			])
			->filter($filter)
			->$paginate(data_get($filter, 'perPage', 10));
	}

	/**
	 * @param string $id
	 * @param array $filter
	 * @return LengthAwarePaginator
	 */
	public function userOrder(string $id, array $filter = []): LengthAwarePaginator
	{
		/** @var User $user */
		$user = User::select(['id', 'uuid'])->where('uuid', $id)->first();

		$orders = Order::select(['user_id', 'id'])->where('user_id', $user?->id)->pluck('id')->toArray();

		return OrderDetail::with([
			'stock'    => fn($q) => $q->select('id', 'countable_id', 'countable_type'),
			'stock.stockExtras.group.translation' => fn($q) => $q->select('id', 'extra_group_id', 'locale', 'title')
				->where('locale', $this->language),
			'stock.countable' => fn($q) => $q->select('id', 'uuid', 'img', 'status', 'active'),
			'stock.countable.translation' => fn($q) => $q->select('id', 'product_id', 'locale', 'title')
				->where('locale', $this->language),
		])
			->whereHas('stock')
			->whereIn('order_id', $orders)
			->whereNull('parent_id')
			->groupBy(['stock_id'])
			->select([
				'stock_id',
				DB::raw('count(stock_id) as count'),
				DB::raw('sum(total_price) as total_price')
			])
			->orderBy('count', 'desc')
			->paginate(data_get($filter, 'perPage', 10));
	}

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function ordersPendingTransaction(array $filter = []): LengthAwarePaginator
    {
        /** @var Order $order */
        $order = $this->model();

        return $order
			->filter($filter)
			->withCount('orderDetails')
            ->with([
                'user',
                'shop',
                'shop.translation' => fn($q) => $q->select('id', 'shop_id', 'locale', 'title')
                    ->where('locale', $this->language),
                'deliveryMan',
                'transactions.paymentSystem',
                'currency',
            ])
            ->whereHas('transactions', fn($q) => $q->where('status', '!=', Transaction::STATUS_PAID)->orWhereHas('paymentSystem', fn($q) => $q->where('tag', '!=', Payment::TAG_CASH)))
            ->paginate(data_get($filter, 'perPage', 10));
    }

}
