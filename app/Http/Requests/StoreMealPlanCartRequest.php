<?php

namespace App\Http\Requests;

use App\Models\MealPlan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMealPlanCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 一覧と違い、不正な week を今週扱いにすると意図しない週の材料が入るため弾く。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'week' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * 対象の週の月曜日。月曜以外が来てもその週の月曜に丸める。
     */
    public function weekStart(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->validated('week'), MealPlan::TIMEZONE)
            ->startOfWeek(CarbonImmutable::MONDAY);
    }
}
