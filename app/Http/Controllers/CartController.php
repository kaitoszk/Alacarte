<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddRecipeToCartAction;
use App\Http\Requests\UpdateCartRequest;
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

    /**
     * 購入済みチェックを付け外しする。
     */
    public function update(UpdateCartRequest $request, Cart $cart): RedirectResponse
    {
        $this->authorize('update', $cart);

        $cart->update($request->validated());

        return back();
    }

    /**
     * 材料を1件削除する。
     */
    public function destroy(Cart $cart): RedirectResponse
    {
        $this->authorize('delete', $cart);

        $cart->delete();

        return back();
    }

    /**
     * 自分のチェック済みの材料をまとめて削除する。対象をクエリで自分の行に絞るため、行ごとの認可は不要。
     */
    public function destroyChecked(Request $request): RedirectResponse
    {
        $request->user()->carts()->where('is_checked', true)->delete();

        return back();
    }
}
