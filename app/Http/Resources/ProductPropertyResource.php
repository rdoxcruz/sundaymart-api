<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\ProductProperties;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ProductProperties|JsonResource $this */
        return [
            'locale'      => $this->locale,
            'category_id' => $this->category_id,
            'title'       => $this->title,
            'value'       => $this->value,
            'deleted_at'  => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),
        ];
    }
}
