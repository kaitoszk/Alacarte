<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesRecipeDraft;
use App\Rules\MaxTotalStepImages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
{
    use NormalizesRecipeDraft;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 公開として保存するか。false なら下書き。
     */
    public function publishes(): bool
    {
        return $this->boolean('publish');
    }

    /**
     * Get the validation rules that apply to the request.
     * 下書きはタイトル以外を必須にしない。型・上限のチェックは下書きでも同じにする。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->publishes() ? 'required' : 'nullable';

        return [
            'publish' => ['boolean'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'recipe_image' => [$required, 'image', 'max:5120'],
            'cooking_time' => [$required, 'integer', 'min:1', 'max:1440'],
            'servings' => [$required, 'integer', 'min:1', 'max:100'],
            'ingredients' => [$required, 'array', $this->publishes() ? 'min:1' : 'min:0', 'max:50'],
            'ingredients.*.name' => [$required, 'string', 'max:255'],
            'ingredients.*.quantity' => [$required, 'string', 'max:255'],
            'steps' => [$required, 'array', $this->publishes() ? 'min:1' : 'min:0', 'max:50', new MaxTotalStepImages],
            'steps.*.description' => [$required, 'string', 'max:2000'],
            'steps.*.images' => ['nullable', 'array', 'max:3'],
            'steps.*.images.*' => ['image', 'max:5120'],
        ];
    }
}
