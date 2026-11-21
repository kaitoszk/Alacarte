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

test('チェックの付け外しができ保存される', function () {
    $cart = Cart::factory()->create(['is_checked' => false]);

    $this->actingAs($cart->user)
        ->patch(route('carts.update', $cart), ['is_checked' => true])
        ->assertRedirect();
    expect($cart->fresh()->is_checked)->toBeTrue();

    $this->actingAs($cart->user)
        ->patch(route('carts.update', $cart), ['is_checked' => false])
        ->assertRedirect();
    expect($cart->fresh()->is_checked)->toBeFalse();
});

test('同じ値のチェックが二重に届いても結果は変わらない', function () {
    $cart = Cart::factory()->create(['is_checked' => false]);

    $this->actingAs($cart->user)->patch(route('carts.update', $cart), ['is_checked' => true]);
    $this->actingAs($cart->user)->patch(route('carts.update', $cart), ['is_checked' => true]);

    expect($cart->fresh()->is_checked)->toBeTrue();
});

test('is_checked がない・boolean でない場合はバリデーションエラーになる', function (array $payload) {
    $cart = Cart::factory()->create(['is_checked' => false]);

    $this->actingAs($cart->user)
        ->patch(route('carts.update', $cart), $payload)
        ->assertSessionHasErrors('is_checked');
    expect($cart->fresh()->is_checked)->toBeFalse();
})->with([
    'なし' => [[]],
    '文字列' => [['is_checked' => 'yes']],
]);

test('材料を個別に削除できる', function () {
    $cart = Cart::factory()->create();

    $this->actingAs($cart->user)
        ->delete(route('carts.destroy', $cart))
        ->assertRedirect();

    $this->assertModelMissing($cart);
});

test('チェック済みの一括削除は自分のチェック済みだけを消す', function () {
    $user = User::factory()->create();
    $checked = Cart::factory()->for($user)->create(['is_checked' => true]);
    $unchecked = Cart::factory()->for($user)->create(['is_checked' => false]);
    $othersChecked = Cart::factory()->create(['is_checked' => true]);

    $this->actingAs($user)
        ->delete(route('carts.destroyChecked'))
        ->assertRedirect();

    $this->assertModelMissing($checked);
    $this->assertModelExists($unchecked);
    $this->assertModelExists($othersChecked);
});

test('他人の材料は更新も削除もできない', function () {
    $cart = Cart::factory()->create(['is_checked' => false]);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->patch(route('carts.update', $cart), ['is_checked' => true])
        ->assertForbidden();
    $this->actingAs($other)
        ->delete(route('carts.destroy', $cart))
        ->assertForbidden();

    expect($cart->fresh()->is_checked)->toBeFalse();
});

test('未ログインはチェック・削除・一括削除ができない', function () {
    $cart = Cart::factory()->create(['is_checked' => true]);

    $this->patch(route('carts.update', $cart), ['is_checked' => false])->assertRedirect(route('login'));
    $this->delete(route('carts.destroy', $cart))->assertRedirect(route('login'));
    $this->delete(route('carts.destroyChecked'))->assertRedirect(route('login'));

    $this->assertModelExists($cart);
});

test('レシピの材料を全部消すと再び追加できる状態に戻る', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredients([['玉ねぎ', '1個']]);

    $this->actingAs($user)->post(route('carts.store', $recipe));
    $cart = Cart::where('user_id', $user->id)->firstOrFail();
    $this->actingAs($user)->delete(route('carts.destroy', $cart));

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertInertia(fn ($page) => $page->where('isInCart', false));
});
