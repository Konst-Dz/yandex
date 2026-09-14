<?php

namespace App\Modules\Organizations\Http\Resources;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function __construct(?Organization $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'status' => $this->status,
            'rating' => $this->rating !== null ? (float) $this->rating : null,
            'ratingsCount' => $this->ratings_count ?? 0,
            'reviewsCount' => $this->reviews_count ?? 0,
        ];
    }
}
