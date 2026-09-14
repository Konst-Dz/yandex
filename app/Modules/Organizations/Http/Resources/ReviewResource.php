<?php

namespace App\Modules\Organizations\Http\Resources;

use App\Modules\Organizations\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function __construct(?Review $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->external_id,
            'author' => $this->author,
            'text' => $this->text,
            'rating' => $this->rating,
            'reviewedAt' => $this->reviewed_at?->toIso8601String(),
        ];
    }
}
