<?php

use App\Models\Cart;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;

/**
 * 材料を指定した順で持つレシピを作る。
 *
 * @param  array<int, array{0: string, 1: string}>  $ingredients  [name, quantity] の配列
 */
function recipeWithIngredients(array $ingredients): Recipe
{
    $recipe = Recipe::factory()->create();

    foreach ($ingredients as $index => [$name, $quantity]) {
        Ingredient::factory()->for($recipe)->create([
            'order_number' => $index + 1,
            'name' => $name,
            'quantity' => $quantity,
        ]);
    }

    return $recipe;
}

test('レシピの材料をまとめて買い物リストに追加できる', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredients([['玉ねぎ', '1個'], ['豚肉', '200g']]);

    $this->actingAs($user)
        ->post(route('carts.store', $recipe))
        ->assertRedirect();

    expect(Cart::where('user_id', $user->id)->orderBy('order_number')->get(['recipe_id', 'name', 'quantity', 'order_number'])->toArray())
        ->toBe([
            ['recipe_id' => $recipe->id, 'name' => '玉ねぎ', 'quantity' => '1個', 'order_number' => 1],
            ['recipe_id' => $recipe->id, 'name' => '豚肉', 'quantity' => '200g', 'order_number' => 2],
        ]);
});

test('同じレシピを二重に追加しても材料は増えない', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredients([['玉ねぎ', '1個'], ['豚肉', '200g']]);

    $this->actingAs($user)->post(route('carts.store', $recipe));
    $this->actingAs($user)->post(route('carts.store', $recipe));

    expect(Cart::where('user_id', $user->id)->count())->toBe(2);
});

test('未ログインは買い物リストへの追加も閲覧もできない', function () {
    $recipe = recipeWithIngredients([['玉ねぎ', '1個']]);

    $this->post(route('carts.store', $recipe))->assertRedirect(route('login'));
    $this->get(route('carts.index'))->assertRedirect(route('login'));

    expect(Cart::count())->toBe(0);
});

test('買い物リストは自分の分だけをレシピごとに、後から追加したレシピを上にして表示する', function () {
    $user = User::factory()->create();
    $first = recipeWithIngredients([['玉ねぎ', '1個'], ['豚肉', '200g']]);
    $second = recipeWithIngredients([['卵', '2個']]);
    $othersRecipe = recipeWithIngredients([['牛乳', '200ml']]);

    $this->actingAs($user)->post(route('carts.store', $first));
    $this->actingAs($user)->post(route('carts.store', $second));
    $this->actingAs(User::factory()->create())->post(route('carts.store', $othersRecipe));

    $this->actingAs($user)
        ->get(route('carts.index'))
        ->assertInertia(fn ($page) => $page
            ->component('carts/Index')
            ->has('groups', 2)
            ->where('groups.0.recipe.id', $second->id)
            ->where('groups.0.recipe.is_deleted', false)
            ->where('groups.0.items.0.name', '卵')
            ->where('groups.1.recipe.id', $first->id)
            ->where('groups.1.items.0.name', '玉ねぎ')
            ->where('groups.1.items.1.name', '豚肉')
            ->where('groups.1.items.1.is_checked', false)
        );
});

test('元レシピを編集しても買い物リストの材料は変わらない', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredients([['玉ねぎ', '1個']]);

    $this->actingAs($user)->post(route('carts.store', $recipe));

    $this->actingAs($recipe->user)->put(route('recipes.update', $recipe), [
        'title' => '更新後のタイトル',
        'cooking_time' => 45,
        'servings' => 4,
        'ingredients' => [['name' => 'にんじん', 'quantity' => '2本']],
        'steps' => [['description' => '炒める']],
    ])->assertRedirect();

    expect(Cart::where('user_id', $user->id)->pluck('name')->all())->toBe(['玉ねぎ']);
});

test('元レシピが削除されても買い物リストに残り、削除済みとして表示される', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredients([['玉ねぎ', '1個']]);

    $this->actingAs($user)->post(route('carts.store', $recipe));
    $recipe->delete();

    $this->actingAs($user)
        ->get(route('carts.index'))
        ->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            ->where('groups.0.recipe.title', $recipe->title)
            ->where('groups.0.recipe.is_deleted', true)
            ->where('groups.0.items.0.name', '玉ねぎ')
        );
});

test('レシピ詳細の isInCart は追加前は false、追加後は true になる', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredients([['玉ねぎ', '1個']]);

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertInertia(fn ($page) => $page->where('isInCart', false));

    $this->actingAs($user)->post(route('carts.store', $recipe));

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertInertia(fn ($page) => $page->where('isInCart', true));
});
