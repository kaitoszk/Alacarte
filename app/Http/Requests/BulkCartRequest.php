<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 買い物リストの「材料ごと」表示でまとめた複数行を、まとめて操作する。
 * ids が全部自分の行であることをここで保証するため、行ごとの Policy チェックは不要。
 */
class BulkCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('carts', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
