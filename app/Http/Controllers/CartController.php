<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddRecipeToCartAction;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    /**
     * 買い物リスト。レシピごとにまとめ、最後に追加したレシピを上にする。
     * 元レシピが削除（ソフトデリート）されていてもタイトルは表示するため withTrashed で読む。
     */
    public function index(Request $request): Response
    {
        $groups = $request->user()->carts()
            ->with(['recipe' => fn ($recipe) => $recipe->withTrashed()])
            ->orderBy('order_number')
            ->get()
            ->groupBy('recipe_id')
            ->sortByDesc(fn (Collection $items) => $items->max('id'))
            ->map(fn (Collection $items) => [
                'recipe' => [
                    'id' => $items->first()->recipe->id,
                    'title' => $items->first()->recipe->title,
                    'is_deleted' => $items->first()->recipe->trashed(),
                ],
                'items' => CartResource::collection($items)->resolve($request),
            ])
            ->values();

        return Inertia::render('carts/Index', [
            'groups' => $groups,
        ]);
    }

    /**
     * レシピの材料を買い物リストに追加する。
     */
    public function store(Request $request, Recipe $recipe, AddRecipeToCartAction $action): RedirectResponse
    {
        $this->authorize('create', Cart::class);

        $action->handle($request->user(), $recipe);

        return back();
    }
}
