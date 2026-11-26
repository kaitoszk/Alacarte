<?php

namespace App\Actions\MealPlan;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;

class AddRecipeToMealPlanAction
{
    /**
     * 指定した日の献立にレシピを入れる。同じ日に同じレシピがあれば何もしない（連打で2回届いても1件）。
     * 下書きを弾くのは RecipePolicy::interact、日付の範囲は StoreMealPlanRequest で検証済みの前提。
     */
    public function handle(User $user, Recipe $recipe, string $date): MealPlan
    {
        return $user->mealPlans()->firstOrCreate([
            'recipe_id' => $recipe->id,
            'date' => $date,
        ]);
    }
}
