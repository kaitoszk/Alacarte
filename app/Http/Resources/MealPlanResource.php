<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MealPlanResource extends JsonResource
{
    /**
     * 献立ページの1行に必要な分だけ渡す。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'recipe' => [
                'id' => $this->recipe->id,
                'title' => $this->recipe->title,
                'recipe_image_url' => $this->recipe->recipe_image_path !== null ? Storage::url($this->recipe->recipe_image_path) : null,
            ],
        ];
    }
}
