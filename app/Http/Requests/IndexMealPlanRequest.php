<?php

namespace App\Http\Requests;

use App\Models\MealPlan;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexMealPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 不正な week はエラーにせず今週として扱うため、ここでは弾かない（weekStart() で解釈する）。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * 表示する週の月曜日。week（Y-m-d）が無い・不正なら今週。月曜以外が来てもその週の月曜に丸める。
     */
    public function weekStart(): CarbonImmutable
    {
        $week = $this->query('week');

        try {
            $date = is_string($week) ? CarbonImmutable::createFromFormat('!Y-m-d', $week, MealPlan::TIMEZONE) : null;
        } catch (InvalidFormatException) {
            $date = null;
        }

        // createFromFormat は 2026-02-31 のような日付を繰り上げて受け入れるため、往復して一致するか確かめる
        if (! $date instanceof CarbonImmutable || $date->toDateString() !== $week) {
            $date = MealPlan::today();
        }

        return $date->startOfWeek(CarbonImmutable::MONDAY);
    }
}
