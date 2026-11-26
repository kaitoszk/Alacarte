<?php

namespace App\Http\Controllers;

use App\Actions\MealPlan\AddRecipeToMealPlanAction;
use App\Http\Requests\IndexMealPlanRequest;
use App\Http\Requests\StoreMealPlanRequest;
use App\Http\Resources\MealPlanResource;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MealPlanController extends Controller
{
    /**
     * 自分の1週間分（月曜〜日曜）の献立。献立のない日も含めて7日分を返す。
     * 元レシピが削除（ソフトデリート）されたものは whereHas で除外する。
     */
    public function index(IndexMealPlanRequest $request): Response
    {
        $weekStart = $request->weekStart();
        $weekEnd = $weekStart->addDays(6);

        $mealPlans = $request->user()->mealPlans()
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereHas('recipe')
            ->with('recipe')
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (MealPlan $mealPlan) => $mealPlan->date->toDateString());

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $mealPlans, $request) {
            $date = $weekStart->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'meal_plans' => MealPlanResource::collection($mealPlans->get($date, collect()))->resolve($request),
            ];
        });

        return Inertia::render('meal-plans/Index', [
            'days' => $days,
            'today' => MealPlan::today()->toDateString(),
            'prevWeek' => $weekStart->subWeek()->toDateString(),
            'nextWeek' => $weekStart->addWeek()->toDateString(),
        ]);
    }

    /**
     * レシピを献立に追加する。下書きには追加できない（RecipePolicy::interact で 404）。
     */
    public function store(StoreMealPlanRequest $request, Recipe $recipe, AddRecipeToMealPlanAction $action): RedirectResponse
    {
        $this->authorize('interact', $recipe);

        $action->handle($request->user(), $recipe, $request->validated('date'));

        return back();
    }

    /**
     * 献立から外す。
     */
    public function destroy(MealPlan $mealPlan): RedirectResponse
    {
        $this->authorize('delete', $mealPlan);

        $mealPlan->delete();

        return back();
    }
}
