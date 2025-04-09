<?php

namespace App\Repositories\ExtraRepository;

use App\Models\ExtraGroup;
use App\Models\Language;
use App\Repositories\CoreRepository;

class ExtraGroupRepository extends CoreRepository
{
	protected function getModelClass(): string
	{
		return ExtraGroup::class;
	}

	public function extraGroupList(array $filter = [])
	{
		return $this->model()
			->with([
				'shop:id,uuid',
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
                    ->where('locale', $this->language),

				'translation' => fn($q) => $q
					->when(data_get($filter, 'search'), fn ($q, $search) => $q->where('title', 'LIKE', "%$search%"))
                    ->where('locale', $this->language)
			])
			->whereHas('translation', fn($q) => $q
				->when(data_get($filter, 'search'), fn ($q, $search) => $q->where('title', 'LIKE', "%$search%"))
				->where(function ($q) {
					$q->where('locale', $this->language);
				})
			)
			->when(data_get($filter, 'active'),  fn($q, $active) => $q->where('active', $active))
			->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where(function ($query) use ($shopId, $filter) {

				$query->where('shop_id', $shopId);

				if (!isset($filter['is_admin'])) {
					$query->orWhereNull('shop_id');
				}

			}))
			->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed())
			->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
			->paginate(data_get($filter, 'perPage', 10));
	}

	public function extraGroupDetails(int $id)
	{
		return $this->model()
			->with([
				'shop:id,uuid',
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language),

				'translation' => fn($q) => $q->where('locale', $this->language)
			])
			->whereHas('translation', fn($q) => $q->where('locale', $this->language))
			->find($id);
	}

}
