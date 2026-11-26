<?php

use App\Models\Cart;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // UTC 2026-11-25 20:00 = 日本時間 2026-11-26（木）05:00。今週は 11/23（月）〜11/29（日）
    $this->travelTo(Carbon::parse('2026-11-25 20:00:00', 'UTC'));
});

/** 材料を1つ持つレシピ */
function recipeWithIngredient(string $name = '玉ねぎ'): Recipe
{
    $recipe = Recipe::factory()->create();
    Ingredient::factory()->for($recipe)->create(['name' => $name, 'quantity' => '1個', 'order_number' => 1]);

    return $recipe;
}

test('表示中の週の今日以降の献立のレシピの材料を買い物リストに入れ、フラッシュ付きで献立ページに戻る', function () {
    $user = User::factory()->create();
    $today = recipeWithIngredient('豚肉');
    $sunday = recipeWithIngredient('人参');
    $past = recipeWithIngredient('キャベツ');
    $nextWeek = recipeWithIngredient('卵');
    MealPlan::factory()->for($user)->for($today)->create(['date' => '2026-11-26']);
    MealPlan::factory()->for($user)->for($sunday)->create(['date' => '2026-11-29']);
    MealPlan::factory()->for($user)->for($past)->create(['date' => '2026-11-25']); // 昨日
    MealPlan::factory()->for($user)->for($nextWeek)->create(['date' => '2026-11-30']); // 翌週

    $this->actingAs($user)
        ->post(route('meal-plans.cart.store'), ['week' => '2026-11-23'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('meal-plans.index', ['week' => '2026-11-23']))
        ->assertSessionHas('success', '買い物リストに追加しました');

    expect($user->carts()->orderBy('id')->pluck('recipe_id')->all())
        // 遅い日から追加し、直近のレシピが買い物リストの一番上（最後に追加）に来る
        ->toBe([$sunday->id, $today->id]);
    expect($user->carts()->where('recipe_id', $today->id)->first())
        ->name->toBe('豚肉')
        ->quantity->toBe('1個');
});

test('フラッシュは共有 props で献立ページに渡る', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->followingRedirects()
        ->post(route('meal-plans.cart.store'), ['week' => '2026-11-23'])
        ->assertInertia(fn ($page) => $page
            ->component('meal-plans/Index')
            ->where('flash.success', '買い物リストに追加しました')
        );
});

test('週内に同じレシピが複数あっても材料は1回分', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredient();
    MealPlan::factory()->for($user)->for($recipe)->create(['date' => '2026-11-26']);
    MealPlan::factory()->for($user)->for($recipe)->create(['date' => '2026-11-28']);

    $this->actingAs($user)->post(route('meal-plans.cart.store'), ['week' => '2026-11-23']);

    expect($user->carts()->count())->toBe(1);
});

test('すでに買い物リストにあるレシピは重複しない', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredient();
    MealPlan::factory()->for($user)->for($recipe)->create(['date' => '2026-11-26']);

    $this->actingAs($user)->post(route('meal-plans.cart.store'), ['week' => '2026-11-23']);
    $this->actingAs($user)->post(route('meal-plans.cart.store'), ['week' => '2026-11-23']);

    expect($user->carts()->count())->toBe(1);
});

test('別レシピの同じ材料は合算せずレシピごとの行のまま', function () {
    $user = User::factory()->create();
    $first = recipeWithIngredient('玉ねぎ');
    $second = recipeWithIngredient('玉ねぎ');
    MealPlan::factory()->for($user)->for($first)->create(['date' => '2026-11-26']);
    MealPlan::factory()->for($user)->for($second)->create(['date' => '2026-11-27']);

    $this->actingAs($user)->post(route('meal-plans.cart.store'), ['week' => '2026-11-23']);

    expect($user->carts()->where('name', '玉ねぎ')->pluck('recipe_id')->sort()->values()->all())
        ->toBe([$first->id, $second->id]);
});

test('削除されたレシピ・下書きに戻ったレシピの献立はスキップする', function () {
    $user = User::factory()->create();
    $deleted = recipeWithIngredient();
    $draft = recipeWithIngredient();
    $published = recipeWithIngredient();
    MealPlan::factory()->for($user)->for($deleted)->create(['date' => '2026-11-26']);
    MealPlan::factory()->for($user)->for($draft)->create(['date' => '2026-11-26']);
    MealPlan::factory()->for($user)->for($published)->create(['date' => '2026-11-26']);
    $deleted->delete();
    $draft->update(['published_at' => null]);

    $this->actingAs($user)
        ->post(route('meal-plans.cart.store'), ['week' => '2026-11-23'])
        ->assertSessionHasNoErrors();

    expect($user->carts()->pluck('recipe_id')->all())->toBe([$published->id]);
});

test('月曜以外の week はその週の月曜に丸める', function () {
    $user = User::factory()->create();
    $recipe = recipeWithIngredient();
    MealPlan::factory()->for($user)->for($recipe)->create(['date' => '2026-12-07']);

    $this->actingAs($user)
        ->post(route('meal-plans.cart.store'), ['week' => '2026-12-10'])
        ->assertRedirect(route('meal-plans.index', ['week' => '2026-12-07']));

    expect($user->carts()->count())->toBe(1);
});

test('過去の週は何も追加せずフラッシュ付きで戻る', function () {
    $user = User::factory()->create();
    MealPlan::factory()->for($user)->for(recipeWithIngredient())->create(['date' => '2026-11-18']);

    $this->actingAs($user)
        ->post(route('meal-plans.cart.store'), ['week' => '2026-11-16'])
        ->assertRedirect(route('meal-plans.index', ['week' => '2026-11-16']))
        ->assertSessionHas('success');

    expect($user->carts()->count())->toBe(0);
});

test('不正な week はバリデーションエラーにして何も追加しない', function (?string $week) {
    $user = User::factory()->create();
    MealPlan::factory()->for($user)->for(recipeWithIngredient())->create(['date' => '2026-11-26']);

    $this->actingAs($user)
        ->post(route('meal-plans.cart.store'), ['week' => $week])
        ->assertSessionHasErrors('week');

    expect(Cart::count())->toBe(0);
})->with(['未指定' => null, '文字列' => 'abc', '存在しない日付' => '2026-02-31', '区切りが違う' => '2026/11/23']);

test('他人の献立は入らない', function () {
    $user = User::factory()->create();
    MealPlan::factory()->for(recipeWithIngredient())->create(['date' => '2026-11-26']); // 他人

    $this->actingAs($user)->post(route('meal-plans.cart.store'), ['week' => '2026-11-23']);

    expect(Cart::count())->toBe(0);
});

test('未ログインはログイン画面にリダイレクトする', function () {
    $this->post(route('meal-plans.cart.store'), ['week' => '2026-11-23'])
        ->assertRedirect(route('login'));

    expect(Cart::count())->toBe(0);
});
