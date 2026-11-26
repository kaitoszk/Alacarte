<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 全手順の画像の合計枚数を制限する。
 * php.ini の max_file_uploads を超えたファイルは黙って捨てられるため、その手前で弾く。
 * フロント（RecipeForm.vue の MAX_TOTAL_STEP_IMAGES）と値を揃えること。
 */
class MaxTotalStepImages implements ValidationRule
{
    public const MAX = 20;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $total = collect($value)->sum(
            fn ($step) => is_array($step) && is_array($step['images'] ?? null) ? count($step['images']) : 0
        );

        if ($total > self::MAX) {
            $fail('手順の画像は合計'.self::MAX.'枚までにしてください。');
        }
    }
}
