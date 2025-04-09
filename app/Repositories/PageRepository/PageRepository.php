<?php

namespace App\Repositories\PageRepository;

use App\Models\Language;
use App\Models\Page;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class PageRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Page::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var Page $model */
        $model = $this->model();

        if (data_get($filter, 'type') === Page::ALL_ABOUT) {
            unset($filter['type']);
            $filter['types'] = [Page::ABOUT, Page::ABOUT_SECOND, Page::ABOUT_THREE, Page::ABOUT_FOURTH];
        }

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($q) => $q->where('locale', $this->language),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param Page $model
     * @return Page|null
     */
    public function show(Page $model): Page|null
    {
        return $model->loadMissing([
            'galleries',
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language),
        ]);
    }

    /**
     * @param string $type
     * @return Model|null
     */
    public function showByType(string $type): ?Model
    {
        return Page::with([
            'galleries',
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language),
        ])
            ->where('type', $type)
            ->first();

    }
}
