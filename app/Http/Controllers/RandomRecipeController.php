<?php

namespace App\Http\Controllers;

use App\Http\Requests\RandomRecipeRequest;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class RandomRecipeController extends Controller
{
    /**
     * 今日のおまかせ。条件に合う公開済みレシピからランダムに1件選び、詳細へ遷移する。
     */
    public function __invoke(RandomRecipeRequest $request): RedirectResponse
    {
        $user = $request->user();

        $recipe = Recipe::query()
            ->published()
            ->cookableWithin($request->maxMinutes())
            ->when($request->fromFavorites(), fn (Builder $query) => $query
                ->whereHas('favorites', fn (Builder $favorite) => $favorite->where('user_id', $user?->id)))
            ->inRandomOrder()
            ->first();

        if ($recipe === null) {
            return redirect()->route('home')->with('info', '条件に合うレシピが見つかりませんでした。条件を変えてみてください。');
        }

        return redirect()->route('recipes.show', $recipe);
    }
}
