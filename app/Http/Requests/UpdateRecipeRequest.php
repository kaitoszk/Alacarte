<?php

namespace App\Http\Requests;

use App\Models\Recipe;
use App\Models\StepImage;
use App\Rules\MaxTotalStepImages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRecipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'recipe_image' => ['nullable', 'image', 'max:5120'],
            'cooking_time' => ['required', 'integer', 'min:1', 'max:1440'],
            'servings' => ['required', 'integer', 'min:1', 'max:100'],
            'ingredients' => ['required', 'array', 'min:1', 'max:50'],
            'ingredients.*.name' => ['required', 'string', 'max:255'],
            'ingredients.*.quantity' => ['required', 'string', 'max:255'],
            'steps' => ['required', 'array', 'min:1', 'max:50', new MaxTotalStepImages],
            'steps.*.description' => ['required', 'string', 'max:2000'],
            'steps.*.images' => ['nullable', 'array', 'max:3'],
            'steps.*.images.*' => ['image', 'max:5120'],
            // 残す既存の手順画像。このレシピの画像だけを受け付け、同じ画像を2か所に置くことはできない
            'steps.*.kept_image_ids' => ['nullable', 'array', 'max:3'],
            'steps.*.kept_image_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(StepImage::class, 'id')->whereIn('step_id', $this->recipe()->steps()->pluck('id')->all()),
            ],
        ];
    }

    /**
     * 1手順あたりの画像の上限（3枚）を、残す既存画像と新しい画像の合計で判定する。
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ((array) ($validator->getData()['steps'] ?? []) as $index => $step) {
                    if (! is_array($step)) {
                        continue;
                    }

                    $count = count((array) ($step['images'] ?? [])) + count((array) ($step['kept_image_ids'] ?? []));

                    if ($count > 3) {
                        $validator->errors()->add("steps.{$index}.images", '手順の画像は1手順3枚までにしてください。');
                    }
                }
            },
        ];
    }

    private function recipe(): Recipe
    {
        /** @var Recipe */
        return $this->route('recipe');
    }
}
