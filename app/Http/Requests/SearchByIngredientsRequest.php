<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchByIngredientsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 材料は最大5つ。1つあたりの長さはレシピ検索のキーワードと揃える。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ingredients' => ['nullable', 'array', 'max:5'],
            'ingredients.*' => ['required', 'string', 'max:50', 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ingredients' => '材料',
            'ingredients.*' => '材料',
        ];
    }

    /**
     * @return list<string>
     */
    public function ingredientNames(): array
    {
        return array_values($this->validated('ingredients') ?? []);
    }
}
