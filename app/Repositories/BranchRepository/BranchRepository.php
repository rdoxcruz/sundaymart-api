<?php

namespace App\Repositories\BranchRepository;

use App\Models\Branch;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BranchRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Branch::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var Branch $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($q) => $q->where('locale', $this->language),
                'shop.translation' => fn($q) => $q->where('locale', $this->language),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param Branch $model
     * @return Branch|null
     */
    public function show(Branch $model): Branch|null
    {
        return $model->loadMissing([
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language),
            'shop.translation' => fn($q) => $q->where('locale', $this->language),
        ]);
    }

    /**
     * @param int $id
     * @return Model|Collection|Builder|array|null
     */
    public function showById(int $id): Model|Collection|Builder|array|null
	{
        return Branch::with([
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language),
            'shop.translation' => fn($q) => $q->where('locale', $this->language),
        ])->find($id);
    }
}
