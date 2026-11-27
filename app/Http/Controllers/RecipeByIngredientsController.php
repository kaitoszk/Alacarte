<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchByIngredientsRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Inertia\Inertia;
use Inertia\Response;

class RecipeByIngredientsController extends Controller
{
    /**
     * 冷蔵庫の材料からレシピを探す。一致した材料の多い順、同数なら新しい順に並べる。
     */
    public function __invoke(SearchByIngredientsRequest $request): Response
    {
        $this->authorize('viewAny', Recipe::class);

        $names = $request->ingredientNames();

        // 材料が未入力なら探さない（全件を一致0で並べても意味がないため）
        $recipes = $names === [] ? null : Recipe::query()
            ->published()
            ->matchingIngredients($names)
            ->with('user')
            ->withCount(['favorites', 'comments'])
            ->withFavoritedBy($request->user())
            ->orderByDesc('matched_ingredients_count')
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('recipes/ByIngredients', [
            'recipes' => $recipes === null ? null : RecipeResource::collection($recipes),
            'ingredients' => $names,
        ]);
    }
}
