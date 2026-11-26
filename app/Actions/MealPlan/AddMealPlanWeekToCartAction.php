<?php

namespace App\Actions\MealPlan;

use App\Actions\Cart\AddRecipeToCartAction;
use App\Models\MealPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AddMealPlanWeekToCartAction
{
    public function __construct(private AddRecipeToCartAction $addRecipeToCart) {}

    /**
     * 週（月曜〜日曜）のうち今日以降の献立のレシピの材料を、レシピごとに買い物リストへ追加する。
     * 削除・下書きに戻ったレシピは黙って除く。同じレシピは1回分だけ、すでにリストにあるものは
     * AddRecipeToCartAction 側で何もしない。
     */
    public function handle(User $user, CarbonImmutable $weekStart): void
    {
        $from = $weekStart->max(MealPlan::today());
        $to = $weekStart->addDays(6);

        if ($from->gt($to)) {
            return;
        }

        $recipes = $user->mealPlans()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereHas('recipe', fn ($recipe) => $recipe->published())
            ->with('recipe')
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->pluck('recipe')
            // 同じレシピは直近の日の分を残す
            ->unique('id')
            // 買い物リストは最後に追加したレシピが上に来るため、遅い日から追加して直近のレシピを一番上にする
            ->reverse();

        DB::transaction(function () use ($user, $recipes) {
            foreach ($recipes as $recipe) {
                $this->addRecipeToCart->handle($user, $recipe);
            }
        });
    }
}
