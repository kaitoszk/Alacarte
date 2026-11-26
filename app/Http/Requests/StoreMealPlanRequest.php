<?php

namespace App\Http\Requests;

use App\Models\MealPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMealPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 日付は画面の選択肢と同じ「今日〜13日後（日本時間）」に限る。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                Rule::in(MealPlan::addableDates()),
                Rule::unique('meal_plans')
                    ->where('user_id', $this->user()->id)
                    ->where('recipe_id', $this->route('recipe')->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.in' => '日付は今日から2週間以内で選んでください。',
            'date.unique' => 'その日にはすでに追加されています。',
        ];
    }
}
