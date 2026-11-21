<?php

namespace App\Actions\Cart;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddRecipeToCartAction
{
    /**
     * レシピの材料を買い物リストにまとめてコピーする。
     * 同じレシピの行がすでにあれば何もしない（二重追加防止）。
     */
    public function handle(User $user, Recipe $recipe): void
    {
        DB::transaction(function () use ($user, $recipe) {
            // ボタン連打で同時に届いた追加が両方 exists=false を見ないよう、ユーザー行をロックして直列化する
            User::whereKey($user->id)->lockForUpdate()->first();

            if ($user->carts()->where('recipe_id', $recipe->id)->exists()) {
                return;
            }

            $user->carts()->createMany(
                $recipe->ingredients()->orderBy('order_number')->get()->map(fn ($ingredient) => [
                    'recipe_id' => $recipe->id,
                    'name' => $ingredient->name,
                    'quantity' => $ingredient->quantity,
                    'order_number' => $ingredient->order_number,
                ])->all()
            );
        });
    }
}
