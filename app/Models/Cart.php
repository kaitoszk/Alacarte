<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 買い物リストの1材料。追加時点のレシピの材料をコピーして持つ。
 */
class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipe_id',
        'name',
        'quantity',
        'order_number',
        'is_checked',
    ];

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
