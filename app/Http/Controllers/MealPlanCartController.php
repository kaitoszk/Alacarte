<?php

namespace App\Http\Controllers;

use App\Actions\MealPlan\AddMealPlanWeekToCartAction;
use App\Http\Requests\StoreMealPlanCartRequest;
use App\Models\Cart;
use Illuminate\Http\RedirectResponse;

class MealPlanCartController extends Controller
{
    /**
     * 表示中の週の献立の材料をまとめて買い物リストに追加する。
     */
    public function store(StoreMealPlanCartRequest $request, AddMealPlanWeekToCartAction $action): RedirectResponse
    {
        $this->authorize('create', Cart::class);

        $weekStart = $request->weekStart();

        $action->handle($request->user(), $weekStart);

        return redirect()
            ->route('meal-plans.index', ['week' => $weekStart->toDateString()])
            ->with('success', '買い物リストに追加しました');
    }
}
