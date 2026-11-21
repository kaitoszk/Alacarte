<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddRecipeToCartAction;
use App\Models\Cart;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
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
