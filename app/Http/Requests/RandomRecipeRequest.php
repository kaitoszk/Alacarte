<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RandomRecipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 調理時間の上限はレシピ一覧の絞り込みと同じ選択肢に揃える。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'time' => ['nullable', 'integer', 'in:15,30,60'],
            'favorites' => ['nullable', 'boolean'],
        ];
    }

    public function maxMinutes(): ?int
    {
        $time = $this->validated('time');

        return $time === null ? null : (int) $time;
    }

    /**
     * お気に入りから選ぶか。ゲストは対象のお気に入りが無いので、条件ごと無視する。
     */
    public function fromFavorites(): bool
    {
        return $this->user() !== null && $this->boolean('favorites');
    }
}
