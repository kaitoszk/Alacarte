<?php

namespace App\Http\Requests\Concerns;

/**
 * 下書き保存のとき、フォームの初期状態で残る全項目が空の材料行・手順行を取り除く。
 * 途中まで入力した行はそのまま残す（空欄は Action で '' として保存する）。
 * アップロードファイルは添字で行と対応しているため、詰め直さずに元の添字のまま落とす。
 */
trait NormalizesRecipeDraft
{
    abstract public function publishes(): bool;

    protected function prepareForValidation(): void
    {
        if ($this->publishes()) {
            return;
        }

        $isBlank = fn (mixed $value): bool => $value === null || $value === '' || $value === [];

        $this->merge([
            'ingredients' => collect((array) $this->input('ingredients', []))
                ->reject(fn (mixed $row) => is_array($row) && $isBlank($row['name'] ?? null) && $isBlank($row['quantity'] ?? null))
                ->all(),
            'steps' => collect((array) $this->input('steps', []))
                ->reject(fn (mixed $row, int|string $index) => is_array($row)
                    && $isBlank($row['description'] ?? null)
                    && $isBlank($row['kept_image_ids'] ?? null)
                    && $isBlank($this->file("steps.{$index}.images")))
                ->all(),
        ]);
    }
}
