<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class BulkUpdateCartRequest extends BulkCartRequest
{
    /**
     * 反転ではなく値を明示して受け取る（連打で2回届いても結果が変わらないように）。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'is_checked' => ['required', 'boolean'],
        ];
    }
}
