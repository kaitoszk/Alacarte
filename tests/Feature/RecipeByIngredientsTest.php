<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * 指定した材料名を持つ公開済みレシピを作る。
 *
 * @param  list<string>  $names
 */
function publishedRecipeWithIngredientNames(array $names, array $attributes = []): Recipe
{
    $recipe = Recipe::factory()->create($attributes);

    foreach ($names as $index => $name) {
        Ingredient::factory()->for($recipe)->create(['name' => $name, 'order_number' => $index + 1]);
    }

    return $recipe;
}

test('ゲストでも材料未入力のページを表示でき、レシピは探さない', function () {
    publishedRecipeWithIngredientNames(['豚肉']);

    $this->get(route('recipes.by-ingredients'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('recipes/ByIngredients')
            ->where('recipes', null)
            ->where('ingredients', []));
});

test('一致した材料の多い順に並び、同数なら新しい順になる', function () {
    $one = publishedRecipeWithIngredientNames(['豚肉', '塩'], ['created_at' => now()->subDays(1)]);
    $three = publishedRecipeWithIngredientNames(['豚肉', '玉ねぎ', 'にんじん'], ['created_at' => now()->subDays(3)]);
    $twoOld = publishedRecipeWithIngredientNames(['玉ねぎ', 'にんじん'], ['created_at' => now()->subDays(5)]);
    $twoNew = publishedRecipeWithIngredientNames(['豚肉', '玉ねぎ'], ['created_at' => now()->subDays(2)]);

    $this->get(route('recipes.by-ingredients', ['ingredients' => ['豚肉', '玉ねぎ', 'にんじん']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('recipes/ByIngredients')
            ->where('ingredients', ['豚肉', '玉ねぎ', 'にんじん'])
            ->has('recipes.data', 4)
            ->where('recipes.data.0.id', $three->id)
            ->where('recipes.data.0.matched_ingredients_count', 3)
            ->where('recipes.data.1.id', $twoNew->id)
            ->where('recipes.data.2.id', $twoOld->id)
            ->where('recipes.data.3.id', $one->id)
            ->where('recipes.data.3.matched_ingredients_count', 1));
});

test('材料名は部分一致で探す', function () {
    $recipe = publishedRecipeWithIngredientNames(['豚バラ肉']);

    $this->get(route('recipes.by-ingredients', ['ingredients' => ['豚']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.id', $recipe->id));
});

test('どの材料にも一致しないレシピと下書きは含めない', function () {
    publishedRecipeWithIngredientNames(['鶏肉']);
    $draft = Recipe::factory()->draft()->create();
    Ingredient::factory()->for($draft)->create(['name' => '豚肉']);
    $published = publishedRecipeWithIngredientNames(['豚肉']);

    $this->get(route('recipes.by-ingredients', ['ingredients' => ['豚肉']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.id', $published->id));
});

test('LIKE のワイルドカードは文字として扱う', function () {
    publishedRecipeWithIngredientNames(['豚肉']);

    $this->get(route('recipes.by-ingredients', ['ingredients' => ['%']]))
        ->assertInertia(fn (Assert $page) => $page->has('recipes.data', 0));
});

test('材料は5つまで入力できる', function () {
    publishedRecipeWithIngredientNames(['a']);

    $this->get(route('recipes.by-ingredients', ['ingredients' => ['a', 'b', 'c', 'd', 'e']]))
        ->assertOk()
        ->assertSessionHasNoErrors();
});

test('材料を6つ以上入力するとバリデーションエラーになる', function () {
    $this->from(route('recipes.by-ingredients'))
        ->get(route('recipes.by-ingredients', ['ingredients' => ['a', 'b', 'c', 'd', 'e', 'f']]))
        ->assertRedirect(route('recipes.by-ingredients'))
        ->assertSessionHasErrors('ingredients');
});

test('同じ材料の重複や長すぎる材料名はバリデーションエラーになる', function () {
    $this->get(route('recipes.by-ingredients', ['ingredients' => ['豚肉', '豚肉']]))
        ->assertSessionHasErrors('ingredients.0');

    $this->get(route('recipes.by-ingredients', ['ingredients' => [str_repeat('あ', 51)]]))
        ->assertSessionHasErrors('ingredients.0');
});

test('件数が増えてもクエリ数が変わらない（N+1 にならない）', function () {
    $countQueries = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('recipes.by-ingredients', ['ingredients' => ['豚肉']]))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    publishedRecipeWithIngredientNames(['豚肉']);
    $single = $countQueries();

    foreach (range(1, 5) as $_) {
        publishedRecipeWithIngredientNames(['豚肉']);
    }

    expect($countQueries())->toBe($single);
});
