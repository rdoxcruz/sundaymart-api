<?php

namespace App\Repositories\ParcelOptionRepository;

use App\Models\Language;
use App\Models\ParcelOption;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Schema;

class ParcelOptionRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ParcelOption::class;
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function paginate(array $filter = []): Paginator
    {
        /** @var ParcelOption $model */
        $model  = $this->model();
        $column = data_get($filter, 'column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('parcel_options', $column) ? $column : 'id';
        }

        return $model
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)
            ])
            ->orderBy($column, data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param ParcelOption $parcelOption
     * @return ParcelOption
     */
    public function show(ParcelOption $parcelOption): ParcelOption
    {
        return $parcelOption
            ->loadMissing([
                'translations',
                'translation' => fn($q) => $q->where('locale', $this->language)
            ]);
    }

    /**
     * @param int $id
     * @return ParcelOption|null
     */
    public function showById(int $id): ?ParcelOption
    {
        $parcelOption = ParcelOption::find($id);

        return $parcelOption?->loadMissing([
                'translations',
                'translation' => fn($q) => $q->where('locale', $this->language)
            ]);
    }

}
